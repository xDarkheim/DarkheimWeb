<?php

use App\Controllers\ProfileController;
use App\Lib\FlashMessageService;

$flashMessageService = new FlashMessageService();

if (!isset($_SESSION['user_id'])) {
    $flashMessageService->addError('Please log in to access your dashboard.');
    header("Location: /index.php?page=login");
    exit();
}
$userId = (int)$_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'] ?? 'user'; // Get the role of the current user

$profileController = new ProfileController($database_handler, $userId, $flashMessageService);
$userData = $profileController->getCurrentUserData();

$user_article_count = 0;
$user_comment_count = 0;
$user_notification_count = 0;

if ($database_handler && $pdo = $database_handler->getConnection()) {
    try {
        $stmt_articles = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE user_id = ?");
        $stmt_articles->execute([$userId]);
        $user_article_count = (int)$stmt_articles->fetchColumn();

        $stmt_comments = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
        $stmt_comments->execute([$userId]);
        $user_comment_count = (int)$stmt_comments->fetchColumn();

        // Assume the notifications table exists
        $table_exists_stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
        if ($table_exists_stmt && $table_exists_stmt->rowCount() > 0) {
            $stmt_notifications = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt_notifications->execute([$userId]);
            $user_notification_count = (int)$stmt_notifications->fetchColumn();
        } else {
            $user_notification_count = 0; // Table does not exist, no notifications
            error_log("Dashboard Stats: 'notifications' table not found for user ID $userId.");
        }

    } catch (PDOException $e) {
        error_log("Dashboard Stats Error for user ID $userId: " . $e->getMessage());
        // Set default values in case of an error so the page doesn't break
        $user_article_count = $user_article_count ?? 0;
        $user_comment_count = $user_comment_count ?? 0;
        $user_notification_count = $user_notification_count ?? 0;
    }
} else {
    error_log("Dashboard: Database handler or connection not available for user ID $userId. Stats will be 0.");
    $user_article_count = 0;
    $user_comment_count = 0;
    $user_notification_count = 0;
}


if (!$userData) {
    $userData = [
        'username' => $_SESSION['username'] ?? 'User',
        'email' => 'N/A',
        'location' => 'Not set',
        'user_status' => 'Not set',
        'bio' => '',
        'website_url' => ''
    ];
    error_log("Dashboard: Failed to load full user data for user ID: " . $userId . ". Using defaults.");
} else {
    // Logging loaded user data
    error_log("Dashboard: User data loaded: " . print_r($userData, true));
}

// Define personal quick actions
$personal_actions = [
    [
        'url' => '/index.php?page=create_article',
        'text' => 'Create New Article',
        'description' => 'Write and publish a new blog post',
        'icon' => 'fas fa-pencil-alt', // Icon for create
        'roles' => ['user', 'editor', 'admin'] // Available to everyone who can write
    ],
    [
        'url' => '/index.php?page=manage_articles',
        'text' => 'Manage My Articles',
        'description' => 'View, edit, delete your blog posts',
        'icon' => 'fas fa-list-alt', // Icon for manage
        'roles' => ['user', 'editor', 'admin'] // Available to everyone who can write
    ],
    [
        'url' => '/index.php?page=account_edit_profile',
        'text' => 'Edit My Profile',
        'description' => 'Update username, email, location, bio, and website URL',
        'icon' => 'fas fa-user-edit',
        'roles' => ['user', 'editor', 'admin'] // Available to all
    ],
    [
        'url' => '/index.php?page=account_settings',
        'text' => 'Account Settings',
        'description' => 'Manage account settings, security parameters, etc.',
        'icon' => 'fas fa-user-cog', // Icon for account settings
        'roles' => ['user', 'editor', 'admin'] // Available to all
    ]
];

$site_management_config = [];
$quick_links_file_path = ROOT_PATH . '/includes/config/quick_links_config.php';
if (file_exists($quick_links_file_path)) {
    $site_management_config = include $quick_links_file_path;
} else {
    error_log("Dashboard: Admin quick links config file not found at: " . $quick_links_file_path);
}


function can_user_access_action(array $action_roles, string $current_user_role): bool {
    // If the roles array is empty, by default assume access is granted (or change the logic)
    if (empty($action_roles)) return true;
    return in_array($current_user_role, $action_roles);
}

?>

<div class="page-container dashboard-container">
    <header class="page-header">
        <h1 class="page-title dashboard-header">My Dashboard</h1>
    </header>

    <p class="dashboard-welcome-message">
        Welcome back, <strong><?php echo htmlspecialchars($userData['username'] ?? 'User'); ?></strong>!
        <?php if (!empty($userData['user_status'])): ?>
            <br><span class="user-current-status">Current status: <?php echo htmlspecialchars($userData['user_status']); ?></span>
        <?php endif; ?>
    </p>
    <p class="dashboard-intro">Here you can manage your articles, profile, and account settings.</p>

    <!-- Overview Cards Section (Stats) -->
    <div class="dashboard-overview">
        <div class="overview-card">
            <?php /* <span class="overview-card-icon"><i class="fas fa-file-alt"></i></span> */ ?>
            <span class="overview-card-value"><?php echo $user_article_count; ?></span>
            <span class="overview-card-label">Your Articles</span>
        </div>
        <div class="overview-card">
            <?php /* <span class="overview-card-icon"><i class="fas fa-comments"></i></span> */ ?>
            <span class="overview-card-value"><?php echo $user_comment_count; ?></span>
            <span class="overview-card-label">Your Comments</span>
        </div>
        <div class="overview-card">
            <?php /* <span class="overview-card-icon"><i class="fas fa-bell"></i></span> */ ?>
            <span class="overview-card-value"><?php echo $user_notification_count; ?></span>
            <span class="overview-card-label">Notifications</span>
        </div>
    </div>

    <!-- Profile Snapshot Section -->
    <div class="dashboard-profile-snapshot">
        <h3 class="dashboard-section-title">Profile Snapshot</h3>
        <ul class="profile-details-list">
            <li><strong>Email:</strong> <?php echo htmlspecialchars($userData['email'] ?? 'N/A'); ?></li>
            <?php if (!empty($userData['user_status'])): ?>
            <li><strong>Status/Mood:</strong> <?php echo htmlspecialchars($userData['user_status']); ?></li>
            <?php endif; ?>
            <li><strong>Location:</strong> <?php echo htmlspecialchars($userData['location'] ?? 'Not specified'); ?></li>
            <?php if (!empty($userData['website_url'])): ?>
            <li><strong>Website:</strong> <a href="<?php echo htmlspecialchars($userData['website_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($userData['website_url']); ?></a></li>
            <?php endif; ?>
            <?php if (!empty($userData['bio'])): ?>
            <li class="profile-bio"><strong>About me:</strong> <p><?php echo nl2br(htmlspecialchars($userData['bio'])); ?></p></li>
            <?php endif; ?>
        </ul>
        <p class="snapshot-edit-link">
            <a href="/index.php?page=account_edit_profile" class="button button-outline button-small">Edit Full Profile</a>
        </p>
    </div>

    <!-- Personal Quick Actions Section -->
    <h3 class="dashboard-section-title">Quick Actions</h3>
    <ul class="dashboard-actions">
        <?php foreach ($personal_actions as $action): ?>
            <?php if (can_user_access_action($action['roles'], $current_user_role)): ?>
            <li>
                <a href="<?php echo htmlspecialchars($action['url']); ?>">
                    <div class="action-main-content">
                        <?php /* if (!empty($action['icon'])): ?><i class="<?php echo htmlspecialchars($action['icon']); ?> fa-fw"></i><?php endif; */ ?>
                        <span class="action-text"><?php echo htmlspecialchars($action['text']); ?></span>
                    </div>
                    <?php if (!empty($action['description'])): ?>
                        <span class="action-status"><?php echo htmlspecialchars(trim(str_replace(['(', ')'], '', $action['description']))); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <?php 
    // Display the "Site Management" section
    if (!empty($site_management_config['links']) && isset($site_management_config['title'])):
        $has_accessible_admin_links = false;
        foreach ($site_management_config['links'] as $link) {
            if (can_user_access_action($link['roles'], $current_user_role)) {
                $has_accessible_admin_links = true;
                break;
            }
        }

        if ($has_accessible_admin_links):
    ?>
    <h3 class="dashboard-section-title" style="margin-top: var(--spacing-6);"><?php echo htmlspecialchars($site_management_config['title']); ?></h3>
    <ul class="dashboard-actions"> <?php // Reuse .dashboard-actions class ?>
        <?php foreach ($site_management_config['links'] as $action): ?>
            <?php if (can_user_access_action($action['roles'], $current_user_role)): ?>
            <li>
                <a href="<?php echo htmlspecialchars($action['url']); ?>">
                    <div class="action-main-content">
                        <?php /* if (!empty($action['icon'])): ?><i class="<?php echo htmlspecialchars($action['icon']); ?> fa-fw"></i><?php endif; */ ?>
                        <span class="action-text"><?php echo htmlspecialchars($action['text']); ?></span>
                    </div>
                    <?php if (!empty($action['description'])): ?>
                        <span class="action-status"><?php echo htmlspecialchars(trim(str_replace(['(', ')'], '', $action['description']))); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
    <?php endif; endif; ?>
</div>