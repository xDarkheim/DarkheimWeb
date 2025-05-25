<!-- 
Copyright (c) 2025 Dmytro Hovenko
All rights reserved.
-->

# WebEngine Darkheim

WebEngine Darkheim is a web application focused on providing a resource hub for web development topics, including articles, news, and user account management.

## Features

*   **User Authentication**: Secure user registration ([page/register.php](page/register.php)), login ([page/login.php](page/login.php)), logout ([modules/logout_process.php](modules/logout_process.php)), password reset, and email verification.
    *   Styled HTML email templates for registration verification ([includes/view/emails/registration_verification.php](includes/view/emails/registration_verification.php)) and password reset requests ([includes/view/emails/password_reset_request.php](includes/view/emails/password_reset_request.php)).
*   **Account Management**:
    *   User dashboard ([page/account/dashboard.php](page/account/dashboard.php)).
    *   Profile updates ([page/account/edit_profile.php](page/account/edit_profile.php)).
    *   Account settings ([page/account/settings.php](page/account/settings.php)).
*   **Content System**:
    *   Article creation ([page/account/create_article.php](page/account/create_article.php)), editing (e.g., `page/account/edit_article.php`), and management ([page/account/manage_articles.php](page/account/manage_articles.php)).
    *   News/Article display ([page/news.php](page/news.php)).
    *   Individual article view (e.g., `index.php?page=news&id=X`).
    *   Categorization of articles.
    *   Commenting system on articles ([modules/add_comment_process.php](modules/add_comment_process.php)).
*   **Administrative Features** (Role-based):
    *   Site settings management ([page/site_settings.php](page/site_settings.php)) for administrators.
    *   User management: listing, editing, and deleting users ([page/account/manage_users.php](page/account/manage_users.php), [page/account/edit_user.php](page/account/edit_user.php)) for administrators.
*   **Modular Design**:
    *   Core functionalities separated into [includes/](includes/).
    *   Form processing and actions in [modules/](modules/).
    *   Reusable UI elements in [includes/components/](includes/components/).
*   **Theming**: Support for themes, with a default theme located in [themes/default/](themes/default/).
*   **Basic Pages**: Includes standard pages like About ([page/about.php](page/about.php)), Contact ([page/contact.php](page/contact.php)), and a 404 error page ([page/404.php](page/404.php)).
*   **Flash Messages**: System for displaying temporary user notifications.

## Project Structure

```
.
├── includes/           # Core files, libraries, and components
│   ├── bootstrap.php   # Main application bootstrap
│   ├── components/     # Reusable UI components (NavigationComponent, QuickLinksComponent, UserPanelComponent)
│   ├── config/         # Configuration files (app_config.php.example, routes.php, router_config.php, etc.)
│   ├── controllers/    # Business logic handlers (ProfileController, etc.)
│   ├── lib/            # Utility libraries (Auth, Database, Router, FlashMessageService, MailerService, etc.)
│   ├── models/         # Database interaction models (Article, User, Category, Comment)
│   └── view/           # View partials and email templates
│       └── emails/     # HTML and text email templates
├── modules/            # Action processing scripts (login_process.php, add_comment_process.php, etc.)
├── page/               # User-facing pages
│   ├── account/        # User account-specific pages (dashboard, profiles, articles, settings, admin user management)
│   ├── 404.php
│   ├── about.php
│   ├── contact.php
│   ├── home.php
│   ├── login.php
│   ├── news.php
│   ├── site_settings.php # Admin page for site settings
│   └── register.php
├── public/             # Publicly accessible files (DOCUMENT_ROOT)
│   ├── index.php       # Main entry point of the application (via webengine.php)
│   └── webengine.php   # Core request handler
├── themes/             # Site themes
│   └── default/        # Default theme (CSS, potentially JS, images)
│       └── css/
│           └── style.css
├── vendor/             # Composer dependencies (e.g., PHPMailer) - Should be installed via Composer
├── .htaccess           # Apache server configuration
├── .gitignore          # Specifies intentionally untracked files that Git should ignore
├── composer.json       # Defines project dependencies for Composer
├── composer.lock       # Records exact versions of dependencies
└── README.md           # This file
```
*(Note: `public/assets/` directory can be used for globally shared assets like images or JavaScript libraries not specific to a theme.)*

## Prerequisites

*   PHP 7.4 or higher (PHP 8.x recommended)
*   Web Server (Apache with `mod_rewrite` enabled, or Nginx with equivalent configuration)
*   MySQL or MariaDB
*   Composer for managing PHP dependencies
*   Git for cloning the repository

## Setup and Deployment

1.  **Clone the repository.**
    ```bash
    git clone https://github.com/xDarkheim/lab # Replace with your actual repository URL
    cd lab
    ```
2.  **Install Dependencies**: Use Composer to install required PHP libraries (like PHPMailer).
    ```bash
    composer install --no-dev --optimize-autoloader
    ```
    *   `--no-dev`: Skips development-specific dependencies.
    *   `--optimize-autoloader`: Optimizes the autoloader for production.

3.  **Web Server Configuration**:
    *   Configure your web server (e.g., Apache, Nginx) to point the **document root** to the `public/` directory of the project. This is crucial for security.
    *   Ensure `mod_rewrite` is enabled for Apache if using the provided `.htaccess`.

4.  **Configuration File**:
    *   Copy the example configuration file:
        ```bash
        cp includes/config/app_config.php.example includes/config/app_config.php
        ```
    *   Edit `includes/config/app_config.php` and update the following:
        *   Database connection details (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
        *   `SITE_URL` to match your domain.
        *   Email sending configuration (e.g., `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, etc.) if you are using SMTP for sending emails.
    *   **Important**: The actual `includes/config/app_config.php` file (with your credentials) is ignored by Git (see `.gitignore`) and should **never** be committed to the repository.

5.  **Database Setup**:
    *   Create a database (e.g., `simple` or your chosen name in `app_config.php`).
    *   **Database Schema**: Execute the following SQL queries to create the necessary tables. You can also find this schema in the `README.md` or create a `database_schema.sql` file.

    ```sql
    CREATE TABLE `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(255) NOT NULL,
      `email` varchar(255) NOT NULL,
      `password_hash` varchar(255) NOT NULL,
      `role` varchar(50) DEFAULT 'user', -- e.g., 'user', 'editor', 'admin'
      `is_active` tinyint(1) NOT NULL DEFAULT 0,
      `email_verification_token_hash` VARCHAR(255) NULL DEFAULT NULL,
      `email_verification_expires_at` DATETIME NULL DEFAULT NULL,
      `reset_token_hash` VARCHAR(255) NULL DEFAULT NULL,
      `reset_token_expires_at` DATETIME NULL DEFAULT NULL,
      `location` varchar(255) DEFAULT NULL,
      `user_status` varchar(255) DEFAULT NULL,
      `bio` text DEFAULT NULL,
      `website_url` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `username` (`username`),
      UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE `articles` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `title` varchar(255) NOT NULL,
      `short_description` text DEFAULT NULL,
      `full_text` longtext NOT NULL,
      `date` datetime NOT NULL, 
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE `categories` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `slug` varchar(255) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE `article_categories` (
      `article_id` int(11) NOT NULL,
      `category_id` int(11) NOT NULL,
      PRIMARY KEY (`article_id`,`category_id`),
      KEY `category_id` (`category_id`),
      CONSTRAINT `article_categories_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
      CONSTRAINT `article_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE `comments` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `article_id` int(11) NOT NULL,
      `user_id` int(11) DEFAULT NULL, 
      `author_name` VARCHAR(255) DEFAULT NULL, 
      `content` text NOT NULL,
      `status` VARCHAR(50) NOT NULL DEFAULT 'pending', 
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `article_id` (`article_id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
      CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL 
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_name` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Default site settings
    INSERT IGNORE INTO `site_settings` (`setting_name`, `setting_value`) VALUES
    ('site_name', 'Darkheim WebEngine'),
    ('site_tagline', 'Your Hub for Web Development Insights'),
    ('admin_email', 'admin@example.com');
    ```

6.  **Permissions**: Ensure the web server has appropriate write permissions for any directories that require it (e.g., if you plan to have file uploads, a server-side cache, or log directories).

7.  **Access**: Open the application in your browser, pointing to your `public/` directory (e.g., `http://localhost/lab/public/` or your configured virtual host like `http://darkheim.lab/`).

## Usage

*   **Browse Content**: Navigate to pages like Home (`/`), News (`/index.php?page=news`), About (`/index.php?page=about`), and Contact (`/index.php?page=contact`).
*   **User Accounts**:
    *   Register for a new account via `/index.php?page=register`. You will receive a verification email.
    *   Login to an existing account via `/index.php?page=login`.
    *   If you forget your password, use the "Forgot Password" link on the login page.
    *   Access your dashboard at `/index.php?page=account_dashboard` after logging in.
    *   Manage your profile, articles (if applicable by role), and settings through the account pages.
*   **Admin Functions**: Users with the 'admin' role can access:
    *   Site Settings: `/index.php?page=site_settings`
    *   User Management: `/index.php?page=manage_users`

## Customization

*   **Theming**:
    *   Modify the existing theme in [themes/default/css/style.css](themes/default/css/style.css).
    *   Create a new theme by duplicating the `default` theme directory and updating `SITE_THEME` in `includes/config/app_config.php`.
*   **Email Templates**: Customize HTML email templates in [includes/view/emails/](includes/view/emails/).
*   **Modules**: Add new functionality by creating new PHP scripts in the [modules/](modules/) directory for processing data or actions.
*   **Pages**: Create new content pages within the [page/](page/) directory. Define their routes and access rules in `includes/config/routes.php` and `includes/config/router_config.php`.
*   **Components**: Develop new reusable UI parts in [includes/components/](includes/components/) and integrate them into your pages.

## Contributing (Optional)

If you'd like to contribute (even if this is a personal project, it's good practice to think about):
1. Fork the repository.
2. Create a new branch (`git checkout -b feature/YourFeature` or `bugfix/YourBugfix`).
3. Make your changes.
4. Commit your changes (`git commit -m 'Add some feature'`).
5. Push to the branch (`git push origin feature/YourFeature`).
6. Open a Pull Request.

## License

All rights reserved.