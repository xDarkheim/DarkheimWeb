<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use App\Models\User;
use App\Lib\Database;
use App\Lib\FlashMessageService;

require_once dirname(__DIR__) . '/includes/bootstrap.php'; // Ensure bootstrap is loaded

global $database_handler, $flashMessageService; // Assuming these are global or use DI

if (!isset($database_handler) || !$database_handler instanceof Database) {
    error_log("verify_email_change.php: Database handler not available.");
    die("A critical error occurred (DB handler). Please contact support.");
}

if (!isset($flashMessageService) || !$flashMessageService instanceof FlashMessageService) {
    error_log("verify_email_change.php: FlashMessageService not available.");
    die("A critical error occurred (Flash service). Please contact support.");
}

$token = $_GET['token'] ?? null;

if (empty($token)) {
    $flashMessageService->addError("Email change confirmation token not provided.");
    header("Location: " . rtrim(SITE_URL, '/') . "/index.php?page=home");
    exit();
}

$user = User::findByPendingEmailChangeToken($database_handler, $token);

// --- DEBUG ---
if ($user) {
    error_log("User Object in verify_email_change.php: " . print_r($user, true));
    error_log("Value from getPendingEmailAddress(): " . $user->getPendingEmailAddress());
} else {
    error_log("User not found by token in verify_email_change.php");
}
// --- END DEBUG ---

if ($user) { // User found by token
    // Check if token has expired (already handled in findByPendingEmailChangeToken, but double check)
    if ($user->getPendingEmailTokenExpiresAt() && strtotime($user->getPendingEmailTokenExpiresAt()) < time()) {
        $flashMessageService->addError("This email change confirmation link has expired. Please request the change again.");
        // Optionally, clear the expired token from DB
        $user->clearPendingEmailChange();
        $user->savePendingEmailChange(); // Save the cleared state
    } elseif ($user->getPendingEmailAddress()) { // Pending email exists
        $oldEmail = $user->getEmail(); // For logging or notification
        $newEmail = $user->getPendingEmailAddress();

        if ($user->confirmEmailChange()) {
            $flashMessageService->addSuccess("Your email address has been successfully changed to " . htmlspecialchars($newEmail) . ".");
            // If user was logged in, their session might need to be updated if email is part of session identity
            // Or, force re-login for security
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user->getId()) {
                $_SESSION['user_email'] = $newEmail; // Update session if email is stored there
            }
            // Optionally send notification to old email about successful change
        } else {
            $flashMessageService->addError("Failed to update your email address. Please try again or contact support.");
            error_log("Failed to confirm email change for token: " . htmlspecialchars($token) . " User ID: " . $user->getId());
        }
    } else { // User found, token not expired, BUT NO PENDING EMAIL
        $flashMessageService->addError("Invalid email change request. No pending email found.");
    }
} else { // User NOT found by token (or token invalid/expired by findBy logic)
    $flashMessageService->addError("Invalid or expired email change confirmation token.");
    error_log("Invalid or expired email change token used: " . htmlspecialchars($token));
}

// Redirect to account settings or login page
if (isset($_SESSION['user_id'])) {
    header("Location: " . rtrim(SITE_URL, '/') . "/index.php?page=account_settings");
} else {
    header("Location: " . rtrim(SITE_URL, '/') . "/index.php?page=login");
}
exit();
?>