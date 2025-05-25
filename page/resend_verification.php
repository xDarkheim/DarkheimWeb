<?php

global $database_handler, $flashMessageService, $auth;

if (!isset($auth) || !$auth instanceof \App\Lib\Auth) {
    die("A critical error occurred (Auth service). Please contact support.");
}

$email_to_resend = null;
$csrf_token_name = "csrf_token_resend_verification";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST[$csrf_token_name]) || !hash_equals($_SESSION[$csrf_token_name] ?? '', $_POST[$csrf_token_name] ?? '')) {
        $flashMessageService->addError('Security error: Invalid CSRF token.');
    } else {
        $email_to_resend = trim($_POST['email'] ?? '');
        if (empty($email_to_resend) || !filter_var($email_to_resend, FILTER_VALIDATE_EMAIL)) {
            $flashMessageService->addError("Please provide a valid email address.");
        } else {
            $result = $auth->resendVerificationEmail($email_to_resend);
            if ($result['success']) {
                $flashMessageService->addSuccess($result['message']);
                header("Location: " . rtrim(SITE_URL, '/') . "/index.php?page=login");
                exit();
            } else {
                $flashMessageService->addError($result['message'] ?? "An error occurred. Please try again.");
            }
        }
    }

    $_SESSION[$csrf_token_name] = bin2hex(random_bytes(32));
} else {
    $email_to_resend = trim($_GET['email'] ?? ''); 
    $_SESSION[$csrf_token_name] = bin2hex(random_bytes(32));
}

?>
<div class="page-container auth-form-container">
    <div class="auth-form-card">
        <h1 class="page-title page-title--auth-card"><?php echo htmlspecialchars($page_title); ?></h1>

        <form action="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=resend_verification" method="POST" class="form-styled">
            <input type="hidden" name="<?php echo $csrf_token_name; ?>" value="<?php echo htmlspecialchars($_SESSION[$csrf_token_name]); ?>">
            
            <div class="form-group">
                <label for="email" class="form-label">Your Email Address:</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($email_to_resend ?? ''); ?>" 
                       placeholder="Enter your registered email" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="button button-primary button-block">Resend Verification Email</button>
            </div>
        </form>
        <div class="auth-form-footer">
            <p class="form-links">
                Remembered your password or already verified your email? <a href="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=login">Login</a>
            </p>
        </div>
    </div>

    <div class="auth-warning-message" style="margin-top: 20px;">
        <p><strong>Important Note:</strong></p>
        <p>Please ensure you are using the email address you registered with. Verification emails can sometimes be delayed or end up in your 'Spam' or 'Junk' folder. Please check these folders before attempting to resend the email multiple times.</p>
    </div>
</div>