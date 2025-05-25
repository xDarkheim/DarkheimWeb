<?php

return [
    'title' => 'Site Management', // Title for this block of links
    'links' => [
        [
            'url' => '/index.php?page=create_article',
            'text' => 'Create Post',
            'icon' => 'fas fa-plus-square',
            'description' => 'Write and publish a new article.',
            'roles' => ['admin', 'editor'] // Roles that can see this link
        ],
        [
            'url' => '/index.php?page=manage_articles',
            'text' => 'Content Management',
            'icon' => 'fas fa-edit',
            'description' => 'View, edit, or delete existing articles.',
            'roles' => ['admin', 'editor']
        ],
        [
            'url' => '/index.php?page=manage_users',
            'text' => 'User Management',
            'icon' => 'fas fa-users-cog',
            'description' => 'View and manage user accounts.',
            'roles' => ['admin']
        ],
        [
            'url' => '/index.php?page=site_settings',
            'text' => 'Site Settings',
            'icon' => 'fas fa-cogs',
            'description' => 'Manage global website settings.',
            'roles' => ['admin']
        ],
        [
            'url' => '/index.php?page=manage_categories',
            'text' => 'Manage Categories',
            'icon' => 'fas fa-tags',
            'description' => 'Add, edit, or delete site categories.',
            'roles' => ['admin']
        ]
        // If necessary, you can add other links for administrators
        // For example, comment management, log viewing, etc.
    ]
];
?>