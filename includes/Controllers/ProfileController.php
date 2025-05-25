<?php
namespace App\Controllers;

use App\Lib\Database;
use App\Models\User;
use App\Lib\FlashMessageService; // Assuming FlashService is in App\Lib

class ProfileController {
    private Database $db_handler;
    private int $userId;
    private FlashMessageService $flashService;
    private ?User $user = null;

    // private MailerService $mailerService;

    public function __construct(Database $db_handler, int $userId, FlashMessageService $flashService /*, MailerService $mailerService*/) {
        $this->db_handler = $db_handler;
        $this->userId = $userId;
        $this->flashService = $flashService;
        // $this->mailerService = $mailerService; // Добавьте это
    }

    private function loadUser(): ?User {
        if ($this->user === null) {
            $userInstance = new User($this->db_handler);
            $this->user = $userInstance->findById($this->userId);
        }
        return $this->user;
    }

    public function getCurrentUserData(): ?array {
        $user = $this->loadUser();
        if (!$user) {
            return null;
        }
        $userData = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'created_at' => $user->getCreatedAt(),
            'location' => $user->getLocation(),
            'user_status' => $user->getUserStatus(),
            'bio' => $user->getBio(),
            'website_url' => $user->getWebsiteUrl(),
        ];
        return $userData;
    }

    public function handleChangePasswordRequest(string $currentPassword, string $newPassword, string $confirmPassword): void {
        $user = $this->loadUser();
        if (!$user) {
            $this->flashService->addError("User data could not be loaded.");
            return;
        }

        // 1. Валидация (пустые поля, совпадение нового пароля и подтверждения, сложность пароля)
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->flashService->addError("All password fields are required.");
            return;
        }
        if ($newPassword !== $confirmPassword) {
            $this->flashService->addError("New password and confirmation password do not match.");
            return;
        }
        if (strlen($newPassword) < 8) { // Пример минимальной длины
            $this->flashService->addError("New password must be at least 8 characters long.");
            return;
        }
        // Добавьте другие проверки сложности пароля, если нужно

        // 2. Проверка текущего пароля
        if (!password_verify($currentPassword, $user->getPasswordHash())) {
            $this->flashService->addError("Incorrect current password.");
            return;
        }

        // 3. Хеширование нового пароля и сохранение
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($user->updatePassword($newPasswordHash)) { // Предполагается, что есть такой метод в User.php
            $this->flashService->addSuccess("Your password has been changed successfully.");

            // 4. Отправка уведомления на email
            $siteName = defined('SITE_NAME') ? \SITE_NAME : (defined('MAIL_FROM_NAME') ? \MAIL_FROM_NAME : 'Our Website');
            $changeDateTime = date('Y-m-d H:i:s');
            // $userIpAddress = $_SERVER['REMOTE_ADDR'] ?? null; // Опционально, если хотите логировать/отправлять IP

            global $mailerService; // Или лучше внедрить через конструктор
            if (!$mailerService instanceof \App\Lib\MailerService) {
                error_log("ProfileController: MailerService not available for sending password change notification.");
                // Не прерываем пользователя из-за этого, но логируем
            } else {
                $email_content_array = $mailerService->renderTemplate('password_changed_notification', [
                    'username' => $user->getUsername() ?? 'User',
                    'siteName' => $siteName,
                    'changeDateTime' => $changeDateTime,
                    // 'changeIpAddress' => $userIpAddress, // Опционально
                ]);
                $emailSubject = "Password Changed Notification - " . $siteName;

                if ($email_content_array && isset($email_content_array['html']) && isset($email_content_array['text'])) {
                    if (!$mailerService->send(
                        $user->getEmail(), // Отправляем на email пользователя
                        $user->getUsername() ?? 'User',
                        $emailSubject,
                        $email_content_array['html'],
                        $email_content_array['text']
                    )) {
                        error_log("ProfileController: Failed to send password change notification to {$user->getEmail()}. MailerService error: " . ($mailerService->ErrorInfo ?? 'Unknown error'));
                        // Можно добавить flash-сообщение пользователю, что уведомление не было отправлено, но пароль изменен.
                        // $this->flashService->addWarning("Your password was changed, but we couldn't send a notification email. Please check your email settings or contact support if this persists.");
                    }
                } else {
                    error_log("ProfileController: Failed to render email template 'password_changed_notification' for user {$user->getUsername()}.");
                }
            }

        } else {
            $this->flashService->addError("Failed to change your password. Please try again.");
            error_log("ProfileController: Failed to update password for user ID: " . $this->userId);
        }
    }

    public function handleUpdateDetailsRequest(array $postData): void {
        $user = $this->loadUser();
        if (!$user) {
            error_log("ProfileController: User not found for ID: " . $this->userId . " during details update.");
            $this->flashService->addError("User data could not be loaded. Please try again.");
            return;
        }

        $detailsToUpdate = [];
        $updateAttemptedFields = array_keys($postData);
        $emailChangeRequested = false;

        if (in_array('email', $updateAttemptedFields)) {
            $newEmail = trim($postData['email'] ?? '');
            if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $this->flashService->addError("Please enter a valid email address.");
                return; // Stop processing if new email is invalid
            }
            if (strtolower($newEmail) !== strtolower($user->getEmail())) {
                // Check if new email is already in use by another account
                $existingUserByNewEmail = User::findByUsernameOrEmail($this->db_handler, '', $newEmail);
                if ($existingUserByNewEmail && $existingUserByNewEmail['id'] != $this->userId) {
                    $this->flashService->addError("This email address (" . htmlspecialchars($newEmail) . ") is already in use by another account.");
                    return; // Stop processing
                }

                // Initiate email change confirmation process
                $emailChangeToken = bin2hex(random_bytes(32));
                $user->setPendingEmailAddress($newEmail);
                $user->setPendingEmailChangeToken($emailChangeToken, 1); // 1 hour validity

                if ($user->savePendingEmailChange()) {
                    $siteName = defined('SITE_NAME') ? \SITE_NAME : (defined('MAIL_FROM_NAME') ? \MAIL_FROM_NAME : 'Our Website');
                    $confirmationLink = rtrim(SITE_URL, '/') . "/index.php?page=verify_email_change&token=" . urlencode($emailChangeToken);

                    // Ensure mailerService is available (you'll need to inject it into ProfileController)
                    global $mailerService; // Or better, inject via constructor
                    if (!$mailerService instanceof \App\Lib\MailerService) {
                         error_log("ProfileController: MailerService not available for sending email change confirmation.");
                         $this->flashService->addError("System error: Could not prepare email confirmation. Please contact support.");
                         return;
                    }

                    $email_content_array = $mailerService->renderTemplate('confirm_email_change', [
                        'username' => $user->getUsername() ?? 'User',
                        'newEmailAddress' => $newEmail,
                        'confirmationLink' => $confirmationLink,
                        'siteName' => $siteName
                    ]);
                    $emailSubject = "Confirm Your New Email Address - " . $siteName;

                    if ($email_content_array && isset($email_content_array['html']) && isset($email_content_array['text'])) {
                        if ($mailerService->send(
                            $newEmail, // Send to the NEW email address
                            $user->getUsername() ?? 'User',
                            $emailSubject,
                            $email_content_array['html'],
                            $email_content_array['text']
                        )) {
                            $this->flashService->addInfo("A confirmation link has been sent to " . htmlspecialchars($newEmail) . ". Please check your inbox to complete the email change. Your current email remains " . htmlspecialchars($user->getEmail()) . " until confirmed.");
                        } else {
                            $this->flashService->addError("Could not send email change confirmation to " . htmlspecialchars($newEmail) . ". Please try again or contact support.");
                            error_log("ProfileController: Failed to send email change confirmation to {$newEmail}. MailerService error: " . ($mailerService->ErrorInfo ?? 'Unknown error'));
                        }
                    } else {
                        $this->flashService->addError("Could not prepare the email change confirmation. Please contact support.");
                        error_log("ProfileController: Failed to render email template 'confirm_email_change' for user {$user->getUsername()} to new email {$newEmail}.");
                    }
                } else {
                    $this->flashService->addError("Failed to save pending email change. Please try again.");
                    error_log("ProfileController: Failed to save pending email change for user ID: " . $this->userId);
                }
                $emailChangeRequested = true; // Mark that an email change was processed
            } else {
                 // Email is the same, no change needed for this field
            }
        }

        // Process other profile details (location, user_status, bio, website_url)
        // Ensure these are processed only if no email change error occurred or if they are independent
        if (!$emailChangeRequested || ($emailChangeRequested && $this->flashService->hasMessages('info'))) { // Proceed if only info message from email change
            if (in_array('location', $updateAttemptedFields)) {
                $newLocation = isset($postData['location']) ? trim($postData['location']) : null;
                if ($newLocation !== $user->getLocation()) {
                    $detailsToUpdate['location'] = $newLocation;
                }
            }
    
            if (in_array('user_status', $updateAttemptedFields)) {
                $newUserStatus = isset($postData['user_status']) ? trim($postData['user_status']) : null;
                if ($newUserStatus !== $user->getUserStatus()) {
                    $detailsToUpdate['user_status'] = $newUserStatus;
                }
            }
    
            if (in_array('bio', $updateAttemptedFields)) {
                $newBio = isset($postData['bio']) ? trim($postData['bio']) : null;
                if ($newBio !== $user->getBio()) {
                    $detailsToUpdate['bio'] = $newBio;
                }
            }
    
            if (in_array('website_url', $updateAttemptedFields)) {
                $newWebsiteUrl = isset($postData['website_url']) ? trim($postData['website_url']) : null;
                if ($newWebsiteUrl && !filter_var($newWebsiteUrl, FILTER_VALIDATE_URL)) {
                     // Allow URLs without scheme, prepend http:// for validation
                    if (!preg_match('/^(?:[a-z][a-z0-9+.-]*:|\/\/)/i', $newWebsiteUrl) && !filter_var("http://" . $newWebsiteUrl, FILTER_VALIDATE_URL)) {
                        $this->flashService->addError("Please enter a valid website URL.");
                        // Do not return here if other fields might be valid
                    } else if (!preg_match('/^(?:[a-z][a-z0-9+.-]*:|\/\/)/i', $newWebsiteUrl)){
                        $newWebsiteUrl = "http://" . $newWebsiteUrl; // Prepend for storage if scheme missing
                    }
                }
                 if ($newWebsiteUrl !== $user->getWebsiteUrl()) {
                    $detailsToUpdate['website_url'] = $newWebsiteUrl;
                }
            }
    
            if (empty($detailsToUpdate) && !$emailChangeRequested) { // If no other details changed AND no email change was initiated
                $this->flashService->addInfo("No changes detected for other profile details.");
                return;
            }
            
            if (!empty($detailsToUpdate)) {
                if ($user->updateDetails($detailsToUpdate)) { // updateDetails should only update non-email fields now
                    $this->flashService->addSuccess("Profile details (excluding email) updated successfully.");
                } else {
                    $this->flashService->addError("Failed to update some profile details. Please try again.");
                    error_log("ProfileController: Failed to update non-email details for user ID: " . $this->userId . " Details: " . print_r($detailsToUpdate, true));
                }
            }
        }
    }
}
?>