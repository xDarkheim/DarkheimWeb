<?php
namespace App\Lib;

use App\Models\User;

class Auth {
    private Database $db_handler;
    private FlashMessageService $flashService;
    private MailerService $mailerService;

    public function __construct(Database $db_handler, FlashMessageService $flashService) {
        $this->db_handler = $db_handler;
        $this->flashService = $flashService;
        $this->mailerService = new MailerService(); 
    }

    public function register(array $data): array {
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $password_confirm = $data['password_confirm'] ?? '';
        $errors = [];

        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = "Username must be between 3 and 50 characters.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, and underscores.";
        }

        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        } elseif ($password !== $password_confirm) {
            $errors[] = "Passwords do not match.";
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'data' => ['username' => $username, 'email' => $email]];
        }

        $existingUser = User::findByUsernameOrEmail($this->db_handler, $username, $email);
        if ($existingUser) {
            if (isset($existingUser['username']) && strtolower($existingUser['username']) === strtolower($username)) {
                $errors[] = "A user with this username already exists.";
            }
            if (isset($existingUser['email']) && strtolower($existingUser['email']) === strtolower($email)) {
                $errors[] = "A user with this email address already exists.";
            }
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors, 'data' => ['username' => $username, 'email' => $email]];
            }
        }

        $user = new User($this->db_handler);
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($password); 

        $verificationToken = bin2hex(random_bytes(32));
        $user->setEmailVerificationToken($verificationToken);

        if ($user->save()) {
            $siteName = defined('SITE_NAME') ? \SITE_NAME : (defined('MAIL_FROM_NAME') ? \MAIL_FROM_NAME : 'Our Website');
            $verificationLink = rtrim(SITE_URL, '/') . "/index.php?page=verify_email&token=" . urlencode($verificationToken);
            
            $emailHtmlBody = $this->mailerService->renderTemplate('registration_verification', [
                'username' => $username,
                'verificationLink' => $verificationLink,
                'siteName' => $siteName
            ]);
            $emailSubject = "Verify Your Email Address - " . $siteName;

            if ($this->mailerService->send($email, $username, $emailSubject, $emailHtmlBody)) {
                return ['success' => true, 'message' => "Registration successful! Please check your email (" . htmlspecialchars($email) . ") to verify your account and activate it."];
            } else {
                error_log("Auth::register - User {$username} registered, but verification email failed to send to {$email}.");
                $this->flashService->addWarning("Registration was successful, but we couldn't send the verification email. Please contact support if you don't receive it shortly.");
                return ['success' => true, 'message' => "Registration successful! We tried to send a verification email to " . htmlspecialchars($email) . ". If you don't receive it, please contact support."];
            }
        } else {
            $errors[] = "Failed to save user. Please try again.";
            return ['success' => false, 'errors' => $errors, 'data' => ['username' => $username, 'email' => $email]];
        }
    }

    public function login(string $identifier, string $password): array {
        $errors = [];

        if (empty($identifier)) {
            $errors[] = "Username or email is required.";
        }
        if (empty($password)) {
            $errors[] = "Password is required.";
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $user = User::findByIdentifier($this->db_handler, $identifier);

        if ($user && password_verify($password, $user->getPasswordHash() ?? '')) {
            if (!$user->isActive()) {
                $resendLink = rtrim(SITE_URL, '/') . "/index.php?page=resend_verification&email=" . urlencode($user->getEmail() ?? '');
                $errorMessage = "Your account is not active. Please verify your email address. Check your inbox (and spam folder). <a href=\"{$resendLink}\">Resend verification email?</a>";
                $errors[] = $errorMessage; 
                return ['success' => false, 'errors' => [$errorMessage]]; 
            }
            return [
                'success' => true,
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'role' => $user->getRole(),
            ];
        } else {
            $errors[] = "Invalid username/email or password.";
            return ['success' => false, 'errors' => $errors];
        }
    }

    public function resendVerificationEmail(string $email): array {
        $user = User::findByIdentifier($this->db_handler, $email);

        if (!$user) {
            return ['success' => false, 'message' => "No account found with that email address."];
        }

        if ($user->isActive()) {
            return ['success' => false, 'message' => "This account is already active."];
        }

        $verificationToken = bin2hex(random_bytes(32));
        $user->setEmailVerificationToken($verificationToken);

        $conn = $this->db_handler->getConnection();
        $sql = "UPDATE users SET email_verification_token_hash = ?, email_verification_expires_at = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt && $stmt->execute([$user->getEmailVerificationTokenHash(), $user->getEmailVerificationExpiresAt(), $user->getId()])) {
            $siteName = defined('SITE_NAME') ? \SITE_NAME : (defined('MAIL_FROM_NAME') ? \MAIL_FROM_NAME : 'Our Website');
            $verificationLink = rtrim(SITE_URL, '/') . "/index.php?page=verify_email&token=" . urlencode($verificationToken);
            
            $emailHtmlBody = $this->mailerService->renderTemplate('registration_verification', [
                'username' => $user->getUsername() ?? 'User',
                'verificationLink' => $verificationLink,
                'siteName' => $siteName
            ]);
            $emailSubject = "Verify Your Email Address - " . $siteName;

            if ($this->mailerService->send($user->getEmail() ?? '', $user->getUsername() ?? 'User', $emailSubject, $emailHtmlBody)) {
                return ['success' => true, 'message' => "A new verification email has been sent to " . htmlspecialchars($user->getEmail() ?? '') . ". Please check your inbox."];
            } else {
                error_log("Auth::resendVerificationEmail - Failed to send new verification email to {$user->getEmail()}.");
                return ['success' => false, 'message' => "Could not send verification email. Please try again later or contact support."];
            }
        } else {
            error_log("Auth::resendVerificationEmail - Failed to update verification token for user {$user->getEmail()}. DB error: " . ($stmt ? implode(":", $stmt->errorInfo()) : $conn->errorInfo()[2]));
            return ['success' => false, 'message' => "Failed to update your account for email verification. Please contact support."];
        }
    }

    public function logout(): void {
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['user_role']);

        $flash_messages_backup = $_SESSION['flash_messages'] ?? [];
        
        $_SESSION = []; 

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        
        $this->flashService->addSuccess('You have successfully logged out.');
    }
}