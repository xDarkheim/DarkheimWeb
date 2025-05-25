<?php

use App\Models\Category;
use App\Models\User;
use App\Lib\FlashMessageService;
use App\Lib\Database; // Assuming you have this for DB connection

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== User::ROLE_ADMIN) {
    if (isset($flashMessageService)) $flashMessageService->addError("Access Denied. You do not have permission to view this page.");
    echo "<div class='message message--error'><p>Access Denied. You do not have permission to view this page.</p></div>";
    // Consider redirecting to home or login page
    // header('Location: /index.php?page=home');
    // exit();
    return;
}

// Initialize services if not already available (this might be handled by your bootstrap.php)
if (!isset($database_handler) || !$database_handler instanceof Database) {
    // This is a fallback, ideally $database_handler is globally available or injected
    try {
        $database_handler = new Database(); 
    } catch (Exception $e) {
        error_log("Manage Categories: Failed to initialize Database handler: " . $e->getMessage());
        echo "<div class='message message--error'><p>Critical error: Database service unavailable.</p></div>";
        return;
    }
}
if (!isset($flashMessageService) || !$flashMessageService instanceof FlashMessageService) {
    $flashMessageService = new FlashMessageService(); // Fallback
}

$db = $database_handler->getConnection(); // Get PDO connection

$page_title = "Manage Categories";
$errors = [];
$success_message = '';

// --- Helper function to generate slugs ---
function generateSlug(string $text): string {
    // Remove unwanted characters
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a-' . substr(md5(time()), 0, 6); // Fallback for empty slugs
    }
    return $text;
}


// --- Handle POST requests (Add/Delete Category) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_manage_categories'] ?? '', $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token. Action aborted.";
    } else {
        // --- Add New Category ---
        if (isset($_POST['action']) && $_POST['action'] === 'add_category') {
            $category_name = trim(filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
            $category_slug = trim(filter_input(INPUT_POST, 'category_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

            if (empty($category_name)) {
                $errors[] = "Category name cannot be empty.";
            } else {
                if (empty($category_slug)) {
                    $category_slug = generateSlug($category_name);
                } else {
                    // Validate slug format (optional, but good practice)
                    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $category_slug)) {
                        $errors[] = "Slug can only contain lowercase letters, numbers, and hyphens, and cannot start or end with a hyphen.";
                    }
                }

                if (empty($errors)) {
                    // Check if category name or slug already exists
                    $stmt_check = $db->prepare("SELECT id FROM categories WHERE name = :name OR slug = :slug");
                    $stmt_check->bindParam(':name', $category_name);
                    $stmt_check->bindParam(':slug', $category_slug);
                    $stmt_check->execute();
                    if ($stmt_check->fetch()) {
                        $errors[] = "A category with this name or slug already exists.";
                    } else {
                        // Create category (Assuming Category model has a create method or you handle it here)
                        try {
                            $stmt_insert = $db->prepare("INSERT INTO categories (name, slug, created_at, updated_at) VALUES (:name, :slug, NOW(), NOW())");
                            $stmt_insert->bindParam(':name', $category_name);
                            $stmt_insert->bindParam(':slug', $category_slug);
                            if ($stmt_insert->execute()) {
                                $flashMessageService->addSuccess("Category '{$category_name}' added successfully.");
                                header('Location: /index.php?page=manage_categories'); // Refresh to show new category
                                exit();
                            } else {
                                $errors[] = "Failed to add category. Database error.";
                                error_log("Manage Categories - Add: PDO Error: " . print_r($stmt_insert->errorInfo(), true));
                            }
                        } catch (PDOException $e) {
                            $errors[] = "Database error adding category: " . $e->getMessage();
                            error_log("Manage Categories - Add PDOException: " . $e->getMessage());
                        }
                    }
                }
            }
        }
        // --- Delete Category ---
        elseif (isset($_POST['action']) && $_POST['action'] === 'delete_category' && isset($_POST['category_id_to_delete'])) {
            $category_id_to_delete = filter_var($_POST['category_id_to_delete'], FILTER_VALIDATE_INT);
            if ($category_id_to_delete) {
                // Before deleting, check if any articles are using this category.
                // For simplicity, we'll just delete. In a real app, you might want to reassign articles or prevent deletion.
                try {
                    // First, remove associations from article_categories
                    $stmt_delete_assoc = $db->prepare("DELETE FROM article_categories WHERE category_id = :category_id");
                    $stmt_delete_assoc->bindParam(':category_id', $category_id_to_delete, PDO::PARAM_INT);
                    $stmt_delete_assoc->execute(); // We don't strictly need to check success here if the next step is the main one

                    // Then, delete the category itself
                    $stmt_delete = $db->prepare("DELETE FROM categories WHERE id = :id");
                    $stmt_delete->bindParam(':id', $category_id_to_delete, PDO::PARAM_INT);
                    if ($stmt_delete->execute() && $stmt_delete->rowCount() > 0) {
                        $flashMessageService->addSuccess("Category (ID: {$category_id_to_delete}) deleted successfully.");
                        header('Location: /index.php?page=manage_categories'); // Refresh
                        exit();
                    } else {
                        $errors[] = "Failed to delete category or category not found.";
                        error_log("Manage Categories - Delete: PDO Error or 0 rows affected for ID {$category_id_to_delete}. Info: " . print_r($stmt_delete->errorInfo(), true));
                    }
                } catch (PDOException $e) {
                    $errors[] = "Database error deleting category: " . $e->getMessage();
                     error_log("Manage Categories - Delete PDOException: " . $e->getMessage());
                }
            } else {
                $errors[] = "Invalid category ID for deletion.";
            }
        }
    }
}

// --- Generate CSRF token for forms ---
if (empty($_SESSION['csrf_token_manage_categories'])) {
    $_SESSION['csrf_token_manage_categories'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_manage_categories'];

// --- Fetch all categories for display ---
$categories = [];
try {
    $stmt_categories = $db->query("SELECT id, name, slug, created_at, updated_at FROM categories ORDER BY name ASC");
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error fetching categories: " . $e->getMessage();
    error_log("Manage Categories - Fetch PDOException: " . $e->getMessage());
}

?>

<div class="page-container manage-categories-page">
    <header class="page-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </header>

    <?php $flashMessageService->displayMessages(); ?>

    <?php if (!empty($errors)): ?>
        <div class="message message--error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="admin-content-container">
        <section class="category-add-form-section card">
            <h2 class="card-header">Add New Category</h2>
            <div class="card-body">
                <form action="/index.php?page=manage_categories" method="POST" class="styled-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="form-group">
                        <label for="category_name" class="form-label">Category Name <span class="required-asterisk">*</span></label>
                        <input type="text" id="category_name" name="category_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_slug" class="form-label">Category Slug (Optional)</label>
                        <input type="text" id="category_slug" name="category_slug" class="form-control" placeholder="e.g., php-frameworks">
                        <small class="form-text">If left blank, a slug will be generated from the name. Use lowercase letters, numbers, and hyphens.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button button-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="category-list-section card" style="margin-top: var(--spacing-5);">
            <h2 class="card-header">Existing Categories</h2>
            <div class="card-body">
                <?php if (empty($categories) && empty($errors)): ?>
                    <p class="message message--info">No categories found.</p>
                <?php elseif (!empty($categories)): ?>
                    <div class="table-responsive">
                        <table class="styled-table categories-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Created At</th>
                                    <th class="actions-column">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['id']); ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                        <td><?php echo htmlspecialchars(date('M j, Y, g:i a', strtotime($category['created_at']))); ?></td>
                                        <td class="actions-cell">
                                            <a href="/index.php?page=edit_category&id=<?php echo $category['id']; ?>" class="button button-secondary button-small">Edit</a>
                                            <form action="/index.php?page=manage_categories" method="POST" style="display: inline-block; margin-left: var(--spacing-1);" onsubmit="return confirm('Are you sure you want to delete this category? This will also remove it from all associated articles.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <input type="hidden" name="category_id_to_delete" value="<?php echo $category['id']; ?>">
                                                <input type="hidden" name="action" value="delete_category">
                                                <button type="submit" class="button button-danger button-small">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>