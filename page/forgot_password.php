<?php

use App\Models\User;

if (!defined('SITE_URL')) {
    $scheme = $_SERVER['REQUEST_SCHEME'] ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITE_URL', $scheme . '://' . $host);
}


global $flashMessageService, $database_handler, $site_settings_from_db, $mailerService;

if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in forgot_password.php");
}
if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in forgot_password.php");
    if (isset($flashMessageService)) $flashMessageService->addError("A system error occurred. Please try again later.");
}
if (!isset($mailerService) || !$mailerService instanceof \App\Lib\MailerService) {
    error_log("Critical: MailerService not available in forgot_password.php");

    if (isset($flashMessageService)) {
        $flashMessageService->addError("A system error occurred (Mail). Please try again later or contact support.");
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: /index.php?page=account_dashboard");
    exit();
}

$csrf_token_name = 'csrf_token_forgot_password';
if (empty($_SESSION[$csrf_token_name])) {
    $_SESSION[$csrf_token_name] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION[$csrf_token_name];

$email_sent_successfully = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION[$csrf_token_name], $_POST['csrf_token'])) {
        if (isset($flashMessageService)) $flashMessageService->addError('Security error: Invalid CSRF token. Please try again.');
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);

        if (!$email) {
            if (isset($flashMessageService)) $flashMessageService->addError('Please enter a valid email address.');
        } else {
            if (!isset($database_handler)) { 
                if (isset($flashMessageService)) $flashMessageService->addError("A system error occurred (DB). Please try again later.");
            } elseif (!isset($mailerService)) { 
                 if (isset($flashMessageService)) $flashMessageService->addError("A system error occurred (Mail). Please try again later.");
            } else {
                $userModel = new \App\Models\User($database_handler);
                $user = $userModel->findByEmail($email);

                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    if ($user->setPasswordResetToken($token)) { 
                        $reset_link = rtrim(SITE_URL, '/') . "/index.php?page=reset_password&token=" . urlencode($token) . "&email=" . urlencode($user->getEmail());
                        
                        $siteName = $site_settings_from_db['site_name'] ?? (defined('SITE_NAME') ? SITE_NAME : 'Your Site');
                        $subject = "Password Reset Request - " . $siteName;
                        
                        $username_for_email = $user->getUsername() ? $user->getUsername() : 'User';

                        $template_data = [
                            'username' => $username_for_email,
                            'siteName' => $siteName,
                            'reset_link' => $reset_link
                        ];
                        
                        $email_bodies = null;

                        if (isset($mailerService) && method_exists($mailerService, 'renderTemplate')) {
                            $rendered_content = $mailerService->renderTemplate('password_reset_request', $template_data);
                            
                            if (is_array($rendered_content) && isset($rendered_content['html']) && isset($rendered_content['text'])) {
                                $email_bodies = $rendered_content;
                            } else {
                                error_log("Forgot Password: MailerService::renderTemplate did not return expected array for 'password_reset_request'.");
                            }
                        } else {
                            error_log("Forgot Password: MailerService::renderTemplate method not found or MailerService not available. Falling back to local render.");
                            $renderEmailTemplate = function(string $templatePath, array $data): ?array {
                                if (!file_exists($templatePath)) {
                                    error_log("Email template not found: " . $templatePath);
                                    return null;
                                }
                                extract($data);
                                ob_start();
                                $email_content = require $templatePath; 
                                ob_end_clean();
                                return is_array($email_content) ? $email_content : null;
                            };
                            // Убедитесь, что ROOT_PATH и DS определены корректно
                            $email_template_path = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'emails' . DIRECTORY_SEPARATOR . 'password_reset_request.php';
                            $email_bodies = $renderEmailTemplate($email_template_path, $template_data);
                        }

                        // Теперь $email_bodies должен содержать ['html' => ..., 'text' => ...] или быть null
                        if ($email_bodies && isset($email_bodies['text']) && isset($email_bodies['html'])) {
                            $recipientName = $user->getUsername() ?: $user->getEmail();
                            if ($mailerService->send( // Убедитесь, что $mailerService здесь доступен и настроен
                                $user->getEmail(),
                                $recipientName,
                                $subject,
                                $email_bodies['html'],
                                $email_bodies['text']
                            )) {
                                $email_sent_successfully = true;
                                if (isset($flashMessageService)) $flashMessageService->addSuccess('If an account with that email address exists, a password reset link has been sent. Please check your inbox (and spam folder).');
                            } else {
                                if (isset($flashMessageService)) $flashMessageService->addError('Failed to send password reset email. Please try again later or contact support.');
                                $errorToLog = 'MailerService->send() returned false. Check MailerService logs for details.';
                                error_log("Forgot Password: Failed to send email to " . $user->getEmail() . " using MailerService. Error: " . $errorToLog);
                            }
                        } else {
                            if (isset($flashMessageService)) $flashMessageService->addError('Error loading or processing email template. Please contact support.');
                            error_log("Forgot Password: Failed to load or process email template. Path: " . ($email_template_path ?? 'N/A'));
                        }
                    } else {
                        if (isset($flashMessageService)) $flashMessageService->addError('Failed to generate or save password reset token. Please try again.');
                        error_log("Forgot Password: User::setPasswordResetToken() method returned false for email: " . htmlspecialchars($email));
                    }
                } else { 
                    error_log("Forgot Password: Account not found for email: " . htmlspecialchars($email) . ". Generic success message displayed.");
                    $email_sent_successfully = true; 
                    if (isset($flashMessageService)) $flashMessageService->addSuccess('If an account with that email address exists, a password reset link has been sent. Please check your inbox (and spam folder).');
                }
            }
        }
    }
    
    $_SESSION[$csrf_token_name] = bin2hex(random_bytes(32));
    $csrf_token = $_SESSION[$csrf_token_name]; 
}

?>

<div class="auth-page-container auth-layout-split">
    <div class="auth-layout-column auth-layout-column-info">
    <h1 class="page-title auth-page-main-title">Forgot Your Password?</h1>
    <div class="auth-info-content">
        <p>No problem! Enter your email address below, and we'll send you a link to reset your password.</p>
        <p>If you remember your password, you can <a href="/index.php?page=login">log in here</a>.</p>
    </div>
    </div>

    <div class="auth-layout-column auth-layout-column-form">
    <div class="auth-form-card">
        <h2 class="auth-form-title">Reset Password</h2>

        <?php
        
        
        ?>

        <?php if ($email_sent_successfully): ?>
            <div class="messages message-success">
                <p>If an account with that email address exists, a password reset link has been sent. Please check your inbox (and spam folder).</p>
            </div>
            <p>Please check your email for the password reset link. If you don't see it, check your spam folder.</p>
        <?php else: ?>
            <form action="/index.php?page=forgot_password" method="POST" id="forgotPasswordForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label">Your Email Address:</label>
                    <div class="input-group">
                        <span class="input-group-icon">✉️</span>
                        <input type="email" name="email" id="email" class="form-control"
                               placeholder="e.g., yourname@example.com" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary button-block">Send Reset Link</button>
                </div>
            </form>
        <?php endif; ?>

        <div class="auth-form-footer">
            <p>Remembered your password? <a href="/index.php?page=login">Sign In</a></p>
            <p>Don't have an account? <a href="/index.php?page=register">Create one</a></p>
        </div>
    </div>
</div>
</div>