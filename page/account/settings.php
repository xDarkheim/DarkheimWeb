<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php?page=home&login_required=true");
    exit;
}

use App\Controllers\ProfileController; // For possible future use of $userData
use App\Lib\FlashMessageService;    // For displaying messages

$userId = (int)$_SESSION['user_id'];

// $flashMessageService should be available globally or via $template_data
// If it's not initialized globally, it needs to be created:
// if (!isset($flashMessageService) || !$flashMessageService instanceof FlashMessageService) {
//     $flashMessageService = new FlashMessageService();
// }

// $profileController = new ProfileController($database_handler, $userId, $flashMessageService);
// $userData = $profileController->getCurrentUserData(); // Can be uncommented if $userData is needed

$page_title = "Account & Site Settings";

// Generate CSRF token for future forms on this page
if (!isset($_SESSION['csrf_token_account_settings'])) {
    $_SESSION['csrf_token_account_settings'] = bin2hex(random_bytes(32));
}

// POST request handling will be added here as functionality is implemented
// if ($_SERVER["REQUEST_METHOD"] == "POST") {
//     // ...
// }

?>

<div class="form-page-container account-settings-container">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php
    // Display flash messages if any (e.g., after a redirect)
    // Ensure $flashMessageService is initialized and available
    if (isset($flashMessageService) && $flashMessageService->hasMessages()) {
        // Container for messages, styled via .form-page-container .messages
        foreach ($flashMessageService->getMessages() as $type => $messagesOfType) {
            foreach ($messagesOfType as $messageData) {
                $text = $messageData['is_html'] ?? false ? $messageData['text'] : htmlspecialchars($messageData['text']);
                $message_class = 'messages '; // Base class
                switch (htmlspecialchars($type)) {
                    case 'success': $message_class .= 'success'; break;
                    case 'error': $message_class .= 'errors'; break; // CSS expects .errors
                    case 'warning': $message_class .= 'warning'; break;
                    case 'info': $message_class .= 'info'; break;
                    default: $message_class .= 'info';
                }
                echo "<div class=\"{$message_class}\"><p>{$text}</p></div>";
            }
        }
        $flashMessageService->clearMessages(); // Clear messages after display
    }
    ?>

    <div class="settings-section">
        <h2>Account Preferences</h2>
        <p>This section will allow you to manage your account preferences, such as notification settings, language, and more. <em>(Functionality coming soon)</em></p>
        <form class="settings-form placeholder-form">
            <div class="form-group">
                <label for="placeholder_account_setting">Example Account Setting:</label>
                <select id="placeholder_account_setting" name="placeholder_account_setting" class="form-control" disabled>
                    <option>Option 1</option>
                    <option>Option 2</option>
                </select>
                <small class="setting-description">This is a placeholder for a future account setting.</small>
            </div>
            <div class="form-actions">
                <button type="button" class="button button-primary" disabled>Save Account Preferences</button>
            </div>
        </form>
    </div>

    <hr class="section-divider">

    <div class="settings-section">
        <h2>Site Design Customization</h2>
        <p>Here, you will be able to customize certain aspects of the site's appearance, if applicable. <em>(Functionality coming soon)</em></p>
        <form class="settings-form placeholder-form">
            <div class="form-group">
                <label for="placeholder_design_setting">Example Design Setting (e.g., Theme):</label>
                <select id="placeholder_design_setting" name="placeholder_design_setting" class="form-control" disabled>
                    <option>Default Theme</option>
                    <option>Dark Theme (Current)</option>
                    <option>Light Theme</option>
                </select>
                <small class="setting-description">This is a placeholder for a future design customization option.</small>
            </div>
            <div class="form-actions">
                <button type="button" class="button button-primary" disabled>Save Design Settings</button>
            </div>
        </form>
    </div>
    
    <hr class="section-divider">

    <div class="settings-section">
        <h2>Profile Management</h2>
        <p>To manage your public profile details, change your password, or update your email address, please visit the Edit Profile page.</p>
        <ul class="settings-links-list" style="list-style: none; padding-left: 0;">
            <li>
                <a href="/index.php?page=account_edit_profile" class="button button-secondary">
                    <i class="fas fa-user-edit"></i> Go to Edit Profile
                </a>
            </li>
        </ul>
    </div>

    <div class="page-actions">
        <p><a href="/index.php?page=account_dashboard" class="button button-secondary">Back to Dashboard</a></p>
    </div>
</div>