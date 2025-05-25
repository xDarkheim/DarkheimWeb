<?php
error_log("TESTING PHP ERROR LOG FROM verify_email.php - " . date('Y-m-d H:i:s'));

if (!isset($database_handler) || !$database_handler instanceof \App\Lib\Database) {
    error_log("verify_email.php: Database handler not available.");
    die("A critical error occurred (DB handler). Please contact support.");
}

if (!isset($flashMessageService) || !$flashMessageService instanceof \App\Lib\FlashMessageService) {
    error_log("verify_email.php: FlashMessageService not available.");
    die("A critical error occurred (Flash service). Please contact support.");
}


$token = $_GET['token'] ?? null;

if (empty($token)) {
    $flashMessageService->addError("Verification token not provided.");
    header("Location: " . rtrim(SITE_URL, '/') . "/index.php?page=login");
    exit();
}

$user = \App\Models\User::findByEmailVerificationToken($database_handler, $token);

if ($user) {
    if ($user->isActive()) {
        $flashMessageService->addInfo("Your email address is already verified. You can log in.");
        header("Location: " . rtrim(SITE_URL, '/') . "/index.php?page=login");
        exit();
    }

    if ($user->markEmailAsVerified()) {
        $flashMessageService->addSuccess("Email verified successfully! Your account is now active. You can log in.");
        header("Location: " . rtrim(SITE_URL, '/') . "/index.php?page=login");
        exit();
    } else {
        $flashMessageService->addError("Failed to activate your account. The token might be correct, but an error occurred. Please try again or contact support.");
        error_log("Failed to mark email as verified for token: " . htmlspecialchars($token) . " User ID: " . $user->getId());
        header("Location: " . rtrim(SITE_URL, '/') . "/index.php?page=register"); 
        exit();
    }
} else {
    $flashMessageService->addError("Invalid or expired verification token. Please try registering again, or if you already registered, try resending the verification email or logging in.");
    error_log("Invalid or expired email verification token used: " . htmlspecialchars($token));
    header("Location: " . rtrim(SITE_URL, '/') . "/index.php?page=register");
    exit();
}

$flashMessageService->addError("An unexpected error occurred during email verification.");
header("Location: " . rtrim(SITE_URL, '/') . "/index.php?page=home");
exit();
?>