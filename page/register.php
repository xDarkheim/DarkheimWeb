<?php
if (!isset($flashMessageService)) {
     error_log("Critical: FlashMessageService not available in register.php");
}

if (isset($_SESSION['user_id'])) {
    header("Location: /index.php?page=account_dashboard");
    exit();
}

$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : ['username' => '', 'email' => ''];

unset($_SESSION['form_data']);

$csrf_token = $_SESSION['csrf_token_register'] ?? '';
if (empty($csrf_token)) {
    $_SESSION['csrf_token_register'] = bin2hex(random_bytes(32));
    $csrf_token = $_SESSION['csrf_token_register'];
}
?>

<div class="auth-page-container auth-layout-split">
    <div class="auth-layout-column auth-layout-column-info">
        <h1 class="page-title auth-page-main-title">Create Your Account</h1>
        <div class="auth-info-content">
            <p>Join our community to share your experiences, participate in discussions, and stay updated with the latest news.</p>
            <ul>
                <li>Engage with other users.</li>
                <li>Customize your profile.</li>
                <li>Contribute your own articles.</li>
            </ul>
        </div>
    </div>
    <div class="auth-layout-column auth-layout-column-form">
        <div class="auth-form-card">
            <?php
            if (!empty($page_messages)) {
                foreach ($page_messages as $message) {
                    $messageTypeClass = 'info'; 
                    if (isset($message['type'])) {
                        switch (strtolower($message['type'])) {
                            case 'success':
                                $messageTypeClass = 'success';
                                break;
                            case 'error':
                            case 'errors':
                                $messageTypeClass = 'errors';
                                break;
                            case 'warning':
                                $messageTypeClass = 'warning';
                                break;
                        }
                    }
                    echo '<div class="messages ' . htmlspecialchars($messageTypeClass) . '">';
                    echo '<p>' . htmlspecialchars($message['text']) . '</p>';
                    echo '</div>';
                }
            }
            ?>
            <form action="/modules/register_process.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">Username:</label>
                    <div class="input-group">
                        <span class="input-group-icon">👤</span>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Choose a unique username" required value="<?php echo htmlspecialchars($form_data['username']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address:</label>
                    <div class="input-group">
                        <span class="input-group-icon">✉️</span>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email address" required value="<?php echo htmlspecialchars($form_data['email']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password:</label>
                    <div class="input-group">
                        <span class="input-group-icon">🔒</span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Create a strong password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm" class="form-label">Confirm Password:</label>
                    <div class="input-group">
                        <span class="input-group-icon">🔒</span>
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control" placeholder="Confirm your password" required>
                    </div>
                </div>

                <?php if (!empty($validation_errors)): ?>
                    <div class="form-errors">
                        <?php foreach ($validation_errors as $error_message_string): ?> 
                            <p><?php echo htmlspecialchars($error_message_string); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary button-block">Create Account</button>
                </div>
            </form>

            <div class="auth-form-footer">
                <p>Already have an account? <a href="/index.php?page=login">Sign In</a></p>
            </div>
        </div> 

        <div class="auth-warning-message" style="margin-top: 20px;">
            <p><strong>Important Security Notice:</strong></p>
            <p>Choose a strong, unique password and never share it with anyone. Our administrators will <strong>never</strong> ask for your password. Keep your account secure.</p>
        </div>

    </div>
</div>
