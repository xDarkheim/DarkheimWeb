<?php
/**
 * This file is for configuring page routes.
 */

return [
    'home' => ['file' => 'home.php', 'title' => 'Homepage'],
    'news' => ['file' => 'news.php', 'title' => 'News'],
    'about' => ['file' => 'about.php', 'title' => 'About Us'],
    'contact' => ['file' => 'contact.php', 'title' => 'Contact'],
    'login' => ['file' => 'login.php', 'title' => 'Login'],
    'register' => ['file' => 'register.php', 'title' => 'Register'],
    'forgot_password' => ['file' => 'forgot_password.php', 'title' => 'Forgot Password'],
    'verify_email' => ['file' => 'verify_email.php', 'title' => 'Verify Email'],
    'resend_verification' => ['file' => 'resend_verification.php', 'title' => 'Resend Verification'],
    'reset_password' => ['file' => 'reset_password.php', 'title' => 'Reset Password'],

    // Account routes
    'account_dashboard' => ['file' => 'account/dashboard.php', 'title' => 'My Dashboard', 'auth' => true],
    'manage_articles' => ['file' => 'account/manage_articles.php', 'title' => 'Manage Articles', 'auth' => true],
    'create_article' => ['file' => 'account/create_article.php', 'title' => 'Create Article', 'auth' => true],
    'delete_article' => ['file' => 'account/delete_article.php', 'title' => 'Delete Article', 'auth' => true],
    'edit_article' => ['file' => 'account/edit_article.php', 'title' => 'Edit Article', 'auth' => true],
    'account_edit_profile' => ['file' => 'account/edit_profile.php', 'title' => 'Edit Profile', 'auth' => true],
    'account_settings' => ['file' => 'account/settings.php', 'title' => 'Account Settings', 'auth' => true],
    'site_settings' => ['file' => 'site_settings.php', 'title' => 'Site Settings', 'auth' => true, 'admin' => true],
    'manage_users' => ['file' => 'account/manage_users.php', 'title' => 'Manage Users', 'auth' => true, 'admin' => true],
    'edit_user' => ['file' => 'account/edit_user.php', 'title' => 'Edit User', 'auth' => true, 'admin' => true],

    // Default/fallback
    '404' => ['file' => '404.php', 'title' => 'Page Not Found'],
];