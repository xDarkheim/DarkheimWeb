<?php

use App\Models\User;
use App\Lib\FlashMessageService;

require_once dirname(__DIR__) . '/includes/bootstrap.php';


global $database_handler, $flashMessageService, $site_settings_from_db;


if (!isset($database_handler) || !$database_handler instanceof \App\Lib\Database) {
    error_log("Critical: Database handler not available in reset_password.php");
    die("A critical system error occurred (DB). Please try again later or contact support.");
}
if (!isset($flashMessageService) || !$flashMessageService instanceof FlashMessageService) {
    error_log("Critical: FlashMessageService not available in reset_password.php");
    die("A critical system error occurred (Flash). Please try again later or contact support.");
}

$token_valid = false;
$user_id_for_reset = null;
$form_errors = [];

$token_from_url = trim($_GET['token'] ?? '');
$email_from_url = trim($_GET['email'] ?? '');

if (empty($token_from_url) || empty($email_from_url) || !filter_var($email_from_url, FILTER_VALIDATE_EMAIL)) {
    $flashMessageService->addError("Invalid or missing password reset link parameters.");
} else {
    $userModel = new User($database_handler);
    $user = $userModel->findByEmail($email_from_url);

    if ($user) {
        $user_id_for_reset = $user->getId();
        $stored_token_hash = $user->getResetTokenHash();
        $token_expires_at = $user->getResetTokenExpiresAt();

        if ($stored_token_hash && password_verify($token_from_url, $stored_token_hash)) {
            if ($token_expires_at && strtotime($token_expires_at) > time()) {
                $token_valid = true;
            } else {
                $flashMessageService->addError("Your password reset token has expired. Please request a new one.");

                if (!$user->setPasswordResetToken(null)) { 
                    error_log("Reset Password: Failed to clear expired reset token in DB for user ID " . $user->getId());

                }
            }
        } else {
            $flashMessageService->addError("Invalid password reset token. Please check the link or request a new one.");
        }
    } else {
        $flashMessageService->addError("No account found for the provided email address.");
    }
}

$csrf_token_name = 'csrf_token_reset_password';
if (empty($_SESSION[$csrf_token_name])) {
    $_SESSION[$csrf_token_name] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION[$csrf_token_name];


if ($token_valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION[$csrf_token_name], $_POST['csrf_token'])) {
        $form_errors[] = 'Invalid security token. Please try again.';
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password)) {
            $form_errors[] = "New password cannot be empty.";
        } elseif (strlen($new_password) < 8) { 
            $form_errors[] = "Password must be at least 8 characters long.";
        }
        if ($new_password !== $confirm_password) {
            $form_errors[] = "Passwords do not match.";
        }

        if (empty($form_errors) && $user_id_for_reset) {
            $userToUpdate = (new User($database_handler))->findById($user_id_for_reset);
            if ($userToUpdate) {
                $userToUpdate->setPassword($new_password);
                $newPasswordHash = $userToUpdate->getPasswordHash(); 

                $passwordUpdated = $userToUpdate->updatePassword($newPasswordHash);

                $tokenCleared = $userToUpdate->setPasswordResetToken(null);

                if ($passwordUpdated && $tokenCleared) {
                    $flashMessageService->addSuccess("Your password has been successfully reset. You can now log in with your new password.");
                    $_SESSION[$csrf_token_name] = bin2hex(random_bytes(32));
                    header("Location: " . rtrim(SITE_URL, '/') . "/index.php?page=login");
                    exit();
                } else {
                    $form_errors[] = "Failed to update your password or clear reset token. Please try again.";
                    if (!$passwordUpdated) error_log("Reset Password: Failed to update password in DB for user ID " . $userToUpdate->getId());
                    if (!$tokenCleared) error_log("Reset Password: Failed to clear reset token in DB for user ID " . $userToUpdate->getId());
                }
            } else {
                 $form_errors[] = "Could not find user account to update. Please contact support.";
            }
        }
    }
    $_SESSION[$csrf_token_name] = bin2hex(random_bytes(32));
    $csrf_token = $_SESSION[$csrf_token_name];
}
?>

<div class="auth-page-container auth-layout-split">
    <div class="auth-layout-column auth-layout-column-info">
        <h1 class="page-title auth-page-main-title"><?php echo htmlspecialchars($page_title); ?></h1>
        <div class="auth-info-content">
            <?php if ($token_valid): ?>
                <p>Please enter your new password below. Make sure it's strong and memorable.</p>
            <?php else: ?>
                <p>There was an issue with your password reset request. Please see the messages below or <a href="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=forgot_password">request a new reset link</a>.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="auth-layout-column auth-layout-column-form">
        <div class="auth-form-card">
            <h2 class="auth-form-title">Set New Password</h2>

            <?php
            if (!empty($form_errors)): ?>
                <div class="messages errors form-errors">
                    <?php foreach ($form_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($token_valid): ?>
                <form action="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=reset_password&token=<?php echo htmlspecialchars($token_from_url); ?>&email=<?php echo htmlspecialchars($email_from_url); ?>" method="POST" id="resetPasswordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password:</label>
                        <div class="input-group">
                            <span class="input-group-icon">🔒</span>
                            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter your new password" required minlength="8">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password:</label>
                        <div class="input-group">
                            <span class="input-group-icon">🔒</span>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm your new password" required minlength="8">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button button-primary button-block">Reset Password</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="auth-form-footer" style="text-align: center;">
                    <p><a href="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=forgot_password" class="button button-secondary">Request New Reset Link</a></p>
                    <p><a href="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=login">Back to Login</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>