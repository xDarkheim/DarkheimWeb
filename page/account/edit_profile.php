<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php?page=home&login_required=true");
    exit;
}
use App\Controllers\ProfileController;
// Предполагается, что $mailerService уже доступен (например, из bootstrap.php)
// global $mailerService; 

$userId = (int)$_SESSION['user_id'];
// Передаем $mailerService в конструктор
$profileController = new ProfileController($database_handler, $userId, $flashMessageService, $mailerService); 

$page_message = ['text' => '', 'type' => ''];
$userData = $profileController->getCurrentUserData();

if (!isset($_SESSION['csrf_token_edit_profile_info'])) {
    $_SESSION['csrf_token_edit_profile_info'] = bin2hex(random_bytes(32));
}
// Добавляем CSRF токен для формы смены пароля
if (!isset($_SESSION['csrf_token_change_password'])) {
    $_SESSION['csrf_token_change_password'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Обработка обновления информации профиля
    if (isset($_POST['update_profile_info'])) {
        if (!isset($_POST['csrf_token_edit_profile_info']) || !hash_equals($_SESSION['csrf_token_edit_profile_info'] ?? '', $_POST['csrf_token_edit_profile_info'] ?? '')) {

            if (isset($flashMessageService)) { 
                $flashMessageService->addError('Security error: Invalid CSRF token for profile info. Please refresh and try again.');
            } else {
                $page_message['text'] = 'Security error: Invalid CSRF token for profile info. Please refresh and try again.';
                $page_message['type'] = 'error';
            }
            header('Location: /index.php?page=account_edit_profile');
            exit;
        }
        $_SESSION['csrf_token_edit_profile_info'] = bin2hex(random_bytes(32)); // Regenerate after use

        $profileInfoData = [
            'email' => $_POST['email'] ?? null,
            'location' => $_POST['location'] ?? null,
            'user_status' => $_POST['user_status'] ?? null,
            'bio' => $_POST['bio'] ?? null,
            'website_url' => $_POST['website_url'] ?? null,
        ];
        $profileController->handleUpdateDetailsRequest($profileInfoData);
        // Не делаем редирект сразу, если есть другие формы на странице,
        // или делаем редирект, но тогда сообщения об успехе/ошибке должны быть во flash
        // header('Location: /index.php?page=account_edit_profile'); 
        // exit;
    }
    // Обработка смены пароля
    elseif (isset($_POST['change_password_submit'])) {
        if (!isset($_POST['csrf_token_change_password']) || !hash_equals($_SESSION['csrf_token_change_password'] ?? '', $_POST['csrf_token_change_password'] ?? '')) {

            if (isset($flashMessageService)) { 
                $flashMessageService->addError('Security error: Invalid CSRF token for password change. Please refresh and try again.');
            } else {
                $page_message['text'] = 'Security error: Invalid CSRF token for password change. Please refresh and try again.';
                $page_message['type'] = 'error';
            }
            header('Location: /index.php?page=account_edit_profile');
            exit;
        }
        $_SESSION['csrf_token_change_password'] = bin2hex(random_bytes(32)); // Regenerate after use

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $profileController->handleChangePasswordRequest($currentPassword, $newPassword, $confirmPassword);
        // header('Location: /index.php?page=account_edit_profile'); // Редирект для обновления flash сообщений
        // exit;
    }
    // После обработки POST, чтобы flash сообщения отобразились корректно:
    if (isset($_POST['update_profile_info']) || isset($_POST['change_password_submit'])) {
        header('Location: /index.php?page=account_edit_profile');
        exit;
    }
}

if (!$userData) {
    $userData = [
        'username' => 'N/A', 'email' => 'N/A',
        'location' => '', 'user_status' => '', 'bio' => '', 'website_url' => ''
    ];
    if (empty($page_message['text'])) {
      $page_message = ['text' => 'Failed to load user data.', 'type' => 'error'];
    }
    error_log("Edit Profile Page: Could not load user data for user ID: " . $userId);
}
?>

<div class="form-page-container account-settings-container">
    <h1>Edit Profile Information</h1>

    <?php if (!empty($page_message['text'])): ?>
        <div class="messages <?php echo htmlspecialchars($page_message['type'] === 'success' ? 'success' : ($page_message['type'] === 'info' ? 'info' : 'errors')); ?>">
            <p><?php echo htmlspecialchars($page_message['text']); ?></p>
        </div>
    <?php endif; ?>

    <div class="settings-section profile-details-section">
        <h2>Profile Details</h2>
        <form action="/index.php?page=account_edit_profile" method="post" class="settings-form">
            <input type="hidden" name="csrf_token_edit_profile_info" value="<?php echo htmlspecialchars($_SESSION['csrf_token_edit_profile_info']); ?>">

            <div class="setting-item">
                <div class="setting-label">
                    <label for="username-display">Username:</label>
                    <small class="setting-description">Your public display name. Cannot be changed here.</small>
                </div>
                <div class="setting-control">
                    <input type="text" id="username-display" name="username_display" class="form-control" value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" disabled>
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="email">Email:</label>
                    <small class="setting-description">Your account email address. Changing it will require confirmation via the new email.</small>
                </div>
                <div class="setting-control">
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" > 
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="location">Location:</label>
                    <small class="setting-description">Where are you based? (e.g., City, Country)</small>
                </div>
                <div class="setting-control">
                    <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($userData['location'] ?? ''); ?>" placeholder="e.g., City, Country" maxlength="100">
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="user_status">Status / Mood:</label>
                    <small class="setting-description">A short status or what you're up to.</small>
                </div>
                <div class="setting-control">
                    <input type="text" id="user_status" name="user_status" class="form-control" value="<?php echo htmlspecialchars($userData['user_status'] ?? ''); ?>" placeholder="e.g., Coding a new feature!" maxlength="150">
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="bio">Bio / About Me:</label>
                    <small class="setting-description">Tell us a little about yourself.</small>
                </div>
                <div class="setting-control">
                    <textarea id="bio" name="bio" class="form-control" rows="4" placeholder="A brief introduction..." maxlength="1000"><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="website_url">Website URL:</label>
                    <small class="setting-description">Your personal or professional website (include http:// or https://).</small>
                </div>
                <div class="setting-control">
                    <input type="url" id="website_url" name="website_url" class="form-control" value="<?php echo htmlspecialchars($userData['website_url'] ?? ''); ?>" placeholder="https://example.com">
                </div>
            </div>

            <div class="form-actions setting-actions">
                <button type="submit" name="update_profile_info" class="button button-primary">Save Profile Information</button>
            </div>
        </form>
    </div>

    <div class="settings-section password-change-section">
        <h2>Change Password</h2>
        <form action="/index.php?page=account_edit_profile" method="post" class="settings-form">
            <input type="hidden" name="csrf_token_change_password" value="<?php echo htmlspecialchars($_SESSION['csrf_token_change_password']); ?>">

            <div class="setting-item">
                <div class="setting-label">
                    <label for="current_password">Current Password:</label>
                    <small class="setting-description">Enter your current password.</small>
                </div>
                <div class="setting-control">
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="new_password">New Password:</label>
                    <small class="setting-description">Choose a strong new password (min. 8 characters).</small>
                </div>
                <div class="setting-control">
                    <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="confirm_password">Confirm New Password:</label>
                    <small class="setting-description">Enter your new password again.</small>
                </div>
                <div class="setting-control">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                </div>
            </div>

            <div class="form-actions setting-actions">
                <button type="submit" name="change_password_submit" class="button button-danger">Change Password</button>
            </div>
        </form>
    </div>

    <div class="page-actions">
        <p><a href="/index.php?page=account_dashboard" class="button button-secondary">Back to Dashboard</a></p>
    </div>
</div>