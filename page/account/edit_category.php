<?php

use App\Models\User;
use App\Lib\FlashMessageService;
use App\Lib\Database;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== User::ROLE_ADMIN) {
    if (isset($flashMessageService)) $flashMessageService->addError("Access Denied.");
    echo "<div class='message message--error'><p>Access Denied.</p></div>";
    return;
}

// Initialize services
if (!isset($database_handler) || !$database_handler instanceof Database) {
    try {
        $database_handler = new Database();
    } catch (Exception $e) {
        error_log("Edit Category: Failed to initialize Database handler: " . $e->getMessage());
        echo "<div class='message message--error'><p>Critical error: Database service unavailable.</p></div>";
        return;
    }
}
if (!isset($flashMessageService) || !$flashMessageService instanceof FlashMessageService) {
    $flashMessageService = new FlashMessageService();
}

$db = $database_handler->getConnection();
$page_title = "Edit Category";
$errors = [];
$category_data = null;
$category_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$category_id) {
    $flashMessageService->addError("Invalid category ID specified.");
    header('Location: /index.php?page=manage_categories');
    exit();
}

// --- Helper function to generate slugs (can be moved to a utility class) ---
function generateSlugEdit(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a-' . substr(md5(time()), 0, 6);
    }
    return $text;
}

// --- Handle POST request (Update Category) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_edit_category_' . $category_id] ?? '', $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token. Action aborted.";
    } else {
        $posted_category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        if ($posted_category_id !== $category_id) {
            $errors[] = "Category ID mismatch. Action aborted.";
        } else {
            $updated_name = trim(filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
            $updated_slug = trim(filter_input(INPUT_POST, 'category_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

            if (empty($updated_name)) {
                $errors[] = "Category name cannot be empty.";
            } else {
                if (empty($updated_slug)) {
                    $updated_slug = generateSlugEdit($updated_name);
                } else {
                    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $updated_slug)) {
                        $errors[] = "Slug can only contain lowercase letters, numbers, and hyphens, and cannot start or end with a hyphen.";
                    }
                }

                if (empty($errors)) {
                    // Check if new name or slug conflicts with another category
                    $stmt_check = $db->prepare("SELECT id FROM categories WHERE (name = :name OR slug = :slug) AND id != :id");
                    $stmt_check->bindParam(':name', $updated_name);
                    $stmt_check->bindParam(':slug', $updated_slug);
                    $stmt_check->bindParam(':id', $category_id, PDO::PARAM_INT);
                    $stmt_check->execute();
                    if ($stmt_check->fetch()) {
                        $errors[] = "Another category with this name or slug already exists.";
                    } else {
                        try {
                            $stmt_update = $db->prepare("UPDATE categories SET name = :name, slug = :slug, updated_at = NOW() WHERE id = :id");
                            $stmt_update->bindParam(':name', $updated_name);
                            $stmt_update->bindParam(':slug', $updated_slug);
                            $stmt_update->bindParam(':id', $category_id, PDO::PARAM_INT);

                            if ($stmt_update->execute()) {
                                $flashMessageService->addSuccess("Category '{$updated_name}' updated successfully.");
                                unset($_SESSION['csrf_token_edit_category_' . $category_id]);
                                header('Location: /index.php?page=manage_categories');
                                exit();
                            } else {
                                $errors[] = "Failed to update category. Database error.";
                                error_log("Edit Category - Update: PDO Error: " . print_r($stmt_update->errorInfo(), true));
                            }
                        } catch (PDOException $e) {
                            $errors[] = "Database error updating category: " . $e->getMessage();
                            error_log("Edit Category - Update PDOException: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
     // To repopulate form with submitted (but erroneous) data
    $category_data = [
        'id' => $category_id,
        'name' => $_POST['category_name'] ?? '',
        'slug' => $_POST['category_slug'] ?? ''
    ];
}


// --- Fetch category data for editing if not already set by POST error ---
if (!$category_data) {
    try {
        $stmt_fetch = $db->prepare("SELECT id, name, slug FROM categories WHERE id = :id");
        $stmt_fetch->bindParam(':id', $category_id, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $category_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if (!$category_data) {
            $flashMessageService->addError("Category not found.");
            header('Location: /index.php?page=manage_categories');
            exit();
        }
    } catch (PDOException $e) {
        $flashMessageService->addError("Database error fetching category: " . $e->getMessage());
        error_log("Edit Category - Fetch PDOException: " . $e->getMessage());
        header('Location: /index.php?page=manage_categories');
        exit();
    }
}

// --- Generate CSRF token for the form ---
$csrf_token_key = 'csrf_token_edit_category_' . $category_id;
if (empty($_SESSION[$csrf_token_key])) {
    $_SESSION[$csrf_token_key] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION[$csrf_token_key];

?>

<div class="page-container edit-category-page">
    <header class="page-header">
        <a href="/index.php?page=manage_categories" class="button button-secondary form-page-back-link">&laquo; Back to Manage Categories</a>
        <h1><?php echo htmlspecialchars($page_title); ?>: <?php echo htmlspecialchars($category_data['name'] ?? 'ID ' . $category_id); ?></h1>
    </header>

    <?php $flashMessageService->displayMessages(); ?>

    <?php if (!empty($errors)): ?>
        <div class="message message--error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($category_data): ?>
    <div class="admin-content-container card">
        <div class="card-body">
            <form action="/index.php?page=edit_category&id=<?php echo htmlspecialchars($category_id); ?>" method="POST" class="styled-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_data['id']); ?>">
                
                <div class="form-group">
                    <label for="category_name" class="form-label">Category Name <span class="required-asterisk">*</span></label>
                    <input type="text" id="category_name" name="category_name" class="form-control" value="<?php echo htmlspecialchars($category_data['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category_slug" class="form-label">Category Slug</label>
                    <input type="text" id="category_slug" name="category_slug" class="form-control" value="<?php echo htmlspecialchars($category_data['slug'] ?? ''); ?>" placeholder="e.g., php-frameworks">
                    <small class="form-text">If left blank, a slug will be generated from the name. Use lowercase letters, numbers, and hyphens.</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary">Update Category</button>
                    <a href="/index.php?page=manage_categories" class="button button-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php elseif (empty($errors)): // Only show if no data and no initial errors (e.g. ID error already handled) ?>
        <p class="message message--info">Category data could not be loaded.</p>
    <?php endif; ?>
</div>