<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Lib\Database;
use App\Lib\Router;
use App\Lib\FlashMessageService;
use App\Lib\Auth;
use App\Components\NavigationComponent;
use App\Components\UserPanelComponent;
use App\Components\QuickLinksComponent;
use App\Lib\SettingsManager;
use App\Lib\MailerService;

$database_handler = new Database();
$db = $database_handler->getConnection();

if ($db === null) {
    error_log("Critical Error: Database connection failed. Check Database class and config.");
}

$settingsManager = new SettingsManager($database_handler);
$site_settings_from_db = $settingsManager->getAllSettings();

$flashMessageService = new FlashMessageService();

$mailerService = new MailerService($site_settings_from_db);

$auth = new Auth($database_handler, $flashMessageService, $mailerService);

$routes_config = require_once ROOT_PATH . DS . 'includes' . DS . 'config' . DS . 'routes_config.php';

$router = new Router(ROOT_PATH . DS . 'page', $routes_config);

$page_key = isset($_GET['page']) ? trim(strtolower($_GET['page'])) : 'home';

$router->dispatch($page_key);

$all_messages = $flashMessageService->getMessages(); // Get all messages ONCE and clear them from the session

$page_messages_for_display = []; // Messages for main display
$sidebar_success_text = null;    // Text for the message in the sidebar

$sidebar_success_identifier = $_SESSION['success_message_sidebar'] ?? null;
if ($sidebar_success_identifier) {
    unset($_SESSION['success_message_sidebar']); // Clear the identifier from the session
}

// Process 'success' type messages for possible separation
if (isset($all_messages['success']) && is_array($all_messages['success'])) {
    $main_success_messages = [];
    if ($sidebar_success_identifier) {
        foreach ($all_messages['success'] as $msgData) {
            if (isset($msgData['text']) && $msgData['text'] === $sidebar_success_identifier) {
                $sidebar_success_text = $msgData['text']; // Or $msgData, if is_html flag is needed
            } else {
                $main_success_messages[] = $msgData;
            }
        }
    } else {
        $main_success_messages = $all_messages['success'];
    }
    if (!empty($main_success_messages)) {
        $page_messages_for_display['success'] = $main_success_messages;
    }
    unset($all_messages['success']);
}


foreach ($all_messages as $type => $messagesOfType) {
    if (!empty($messagesOfType)) {
        $page_messages_for_display[$type] = $messagesOfType;
    }
}

global $template_data;
if (!isset($template_data) || !is_array($template_data)) {
    $template_data = [];
}
$template_data['page_messages'] = $page_messages_for_display;
if ($sidebar_success_text) {
    $template_data['sidebar_success_message_text'] = $sidebar_success_text; // For use in the sidebar component
}

$current_user_role = $_SESSION['user_role'] ?? null;

$site_name_from_db = $site_settings_from_db['site_name'] ?? 'WebEngine Darkheim';

$current_page_specific_title = $page_title ?? 'Default Title';

if ($current_page_specific_title && $current_page_specific_title !== $site_name_from_db) {
    $title_for_html_tag = htmlspecialchars($current_page_specific_title) . " | " . htmlspecialchars($site_name_from_db);
    $main_heading_for_page = htmlspecialchars($current_page_specific_title);
} else {
    $title_for_html_tag = htmlspecialchars($site_name_from_db);
    $main_heading_for_page = htmlspecialchars($site_name_from_db);
}

$template_data['html_page_title'] = $title_for_html_tag;
$template_data['page_main_heading'] = $main_heading_for_page;
$template_data['site_name_logo'] = htmlspecialchars($site_name_from_db);

$template_data['site_config'] = $site_settings_from_db;

$template_data['database_handler'] = $database_handler;
$template_data['db'] = $db;

$navigationComponent = new NavigationComponent($page_key);
$template_data['main_navigation_html'] = $navigationComponent->render();

$auth_pages_no_sidebar = ['login', 'register', 'edit_user', 'forgot_password' , 'resend_verification', 'error_404', 'reset_password'];
$show_sidebar = !in_array($page_key, $auth_pages_no_sidebar);

if ($show_sidebar) {
    $userPanelComponent = new UserPanelComponent(
        $current_user_role,
        [],
        $sidebar_success_text ?? null
    );
    $sidebar_user_panel_html = $userPanelComponent->render();
    $template_data['sidebar_user_panel_html'] = $sidebar_user_panel_html;

    $quick_links_config_array = require_once ROOT_PATH . DS . 'includes' . DS . 'config' . DS . 'quick_links_config.php';
    $quickLinksComponent = new QuickLinksComponent($quick_links_config_array, $current_user_role);
    $template_data['recent_news_sidebar_html'] = $quickLinksComponent->render();
    $template_data['show_sidebar'] = true;
} else {
    $template_data['sidebar_user_panel_html'] = '';
    $template_data['recent_news_sidebar_html'] = '';
    $template_data['show_sidebar'] = false;
}

extract($template_data);

require_once ROOT_PATH . DS . 'themes' . DS . SITE_THEME . DS . 'header.php';
    // Display global flash messages right after body tag
    if (!empty($page_messages) && is_array($page_messages)) {
        echo '<div class="flash-messages-container global-flash-messages">';
        foreach ($page_messages as $type => $messagesOfType) {
            if (is_array($messagesOfType)) {
                foreach ($messagesOfType as $messageData) {
                    if (is_array($messageData) && isset($messageData['text'])) {
                        $text = $messageData['is_html'] ?? false ? $messageData['text'] : htmlspecialchars($messageData['text']);
                        $baseMessageClass = 'message';
                        $typeMessageClass = '';
                        switch (htmlspecialchars($type)) {
                            case 'success': $typeMessageClass = 'message--success'; break;
                            case 'error': $typeMessageClass = 'message--error'; break;
                            case 'warning': $typeMessageClass = 'message--warning'; break;
                            case 'info': $typeMessageClass = 'message--info'; break;
                            default: $typeMessageClass = 'message--info';
                        }
                        $alertClass = trim("$baseMessageClass $typeMessageClass");
                        echo "<div class=\"{$alertClass}\" role=\"alert\"><p>{$text}</p><button type=\"button\" class=\"close-flash-message\" onclick=\"this.parentElement.style.display='none';\">&times;</button></div>";
                    }
                }
            }
        }
        echo '</div>';
    }

if (!empty($content_file) && file_exists($content_file)) {
    require_once $content_file;
} else {
    error_log("Error: Content file not found or invalid for page key '{$page_key}'. Expected at: {$content_file}");
    $page_title = "Page Not Found";
    if ($page_title && $page_title !== ($site_settings_from_db['site_name'] ?? 'WebEngine Darkheim')) {
        $template_data['html_page_title'] = htmlspecialchars($page_title) . " | " . htmlspecialchars($site_settings_from_db['site_name'] ?? 'WebEngine Darkheim');
        $template_data['page_main_heading'] = htmlspecialchars($page_title);
    } else {
        $template_data['html_page_title'] = htmlspecialchars($site_settings_from_db['site_name'] ?? 'WebEngine Darkheim');
        $template_data['page_main_heading'] = htmlspecialchars($site_settings_from_db['site_name'] ?? 'WebEngine Darkheim');
    }
    $html_page_title = $template_data['html_page_title'];
    $page_main_heading = $template_data['page_main_heading'];

    require_once ROOT_PATH . DS . 'page' . DS . '404.php';
}

require_once ROOT_PATH . DS . 'themes' . DS . SITE_THEME . DS . 'footer.php';
?>