<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Lib\Database;
use App\Models\Comments;
use App\Lib\FlashMessageService;

$redirect_url = '/index.php?page=news';
$article_id_from_post = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT); // Get article ID earlier

if ($article_id_from_post) {
    $redirect_url = '/index.php?page=news&id=' . $article_id_from_post;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $flashMessageService = new FlashMessageService();
    $flashMessageService->addError('Invalid request method.');
    header('Location: ' . $redirect_url);
    exit;
}

// --- BEGIN CSRF Token Check ---
// Use $article_id_from_post to form the correct token name
$csrf_session_key = 'csrf_token_add_comment_article_' . $article_id_from_post;

if (!$article_id_from_post || !isset($_POST['csrf_token']) || !isset($_SESSION[$csrf_session_key]) || !hash_equals($_SESSION[$csrf_session_key], $_POST['csrf_token'])) {
    $flashMessageService = new FlashMessageService();
    $flashMessageService->addError('CSRF token validation failed. Please try again.');
    if ($article_id_from_post) { // Regenerate token with correct name if article ID is known
        $_SESSION[$csrf_session_key] = bin2hex(random_bytes(32));
    }
    header('Location: ' . $redirect_url);
    exit;
}
// If check passed, can remove used token from session to prevent reuse (optional, but recommended)
// unset($_SESSION[$csrf_session_key]);
// --- END CSRF Token Check ---


if (!isset($_SESSION['user_id'])) {
    $flashMessageService = new FlashMessageService();
    $flashMessageService->addError('You must be logged in to post a comment.');
    header('Location: /index.php?page=login&return_to=' . urlencode($redirect_url));
    exit;
}

$user_id_form = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$author_name = filter_input(INPUT_POST, 'author_name', FILTER_SANITIZE_SPECIAL_CHARS);
$content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS));

$flashMessageService = new FlashMessageService();

if (!$article_id_from_post || !$user_id_form || empty($author_name) || empty($content)) { // Используем $article_id_from_post
    $flashMessageService->addError('All required fields to post a comment are not filled.');
    header('Location: ' . $redirect_url);
    exit;
}

if ($user_id_form !== (int)$_SESSION['user_id']) {
    $flashMessageService->addError('User authentication mismatch. Cannot post comment.');
    error_log("Comment submission: User ID mismatch. Form: {$user_id_form}, Session: {$_SESSION['user_id']}");
    header('Location: ' . $redirect_url);
    exit;
}

$database_handler = new Database();
if (!$database_handler->getConnection()) {
    $flashMessageService->addError('Database connection error. Could not post comment.');
    error_log("add_comment_process.php: Database connection failed.");
    header('Location: ' . $redirect_url);
    exit;
}

$commentModel = new Comments($database_handler);

$status = Comments::STATUS_PENDING;

if ($commentModel->addComment($article_id_from_post, (int)$_SESSION['user_id'], $content, $author_name, $status)) {
    $flashMessageService->addSuccess('Comment added successfully!');
    // $redirect_url .= '#comment-' . $comment_id; // $comment_id is not defined here, need to get inserted comment ID if necessary
} else {
    $flashMessageService->addError('Failed to add comment. Please try again.');
    error_log("add_comment_process.php: Failed to add comment for article ID {$article_id_from_post} by user ID {$_SESSION['user_id']}");
}

header('Location: ' . $redirect_url);
exit;
?>