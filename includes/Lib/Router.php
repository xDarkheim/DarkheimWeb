<?php
namespace App\Lib;

class Router {
    protected array $routes = [];
    protected string $page_path = ''; 
    private ?string $controller_file = null; 
    private ?string $currentPageTitle = null;

    public function __construct(string $page_path, array $routes) {
        $this->page_path = rtrim($page_path, DS) . DS; 
        $this->routes = $routes;
    }

    public function dispatch(string $page_key): void { 
        global $content_file; 
        global $page_title;   

        $pageKeyToUse = strtolower($page_key);

        if (array_key_exists($pageKeyToUse, $this->routes)) {
            $route_config = $this->routes[$pageKeyToUse];
            
            $this->controller_file = $this->page_path . $route_config['file']; 
            
            $this->currentPageTitle = $route_config['title'] ?? ucfirst(str_replace('_', ' ', $pageKeyToUse));

            if (!empty($route_config['guest_only']) && isset($_SESSION['user_id'])) {
                header("Location: /index.php?page=account_dashboard");
                exit();
            }

            if (!empty($route_config['auth_required']) && !isset($_SESSION['user_id'])) {
                $_SESSION['login_errors'] = ['Please log in to access this page.'];
                header("Location: /index.php?page=home");
                exit();
            }

            if (file_exists($this->controller_file)) {
                $content_file = $this->controller_file;
                $page_title = $this->currentPageTitle;
            } else {
                error_log("Router error: Page file '{$route_config['file']}' not found at '{$this->controller_file}' for page key '{$page_key}'.");
                $this->currentPageTitle = 'Page Not Found';
                $this->controller_file = $this->page_path . '404.php';
                
                $page_title = $this->currentPageTitle;
                $content_file = $this->controller_file;

                if (!headers_sent()) {
                    http_response_code(404);
                }
            }
        } else { 
            $this->currentPageTitle = 'Page Not Found';
            
            $this->controller_file = $this->page_path . '404.php'; 
            
            $page_title = $this->currentPageTitle;
            $content_file = $this->controller_file;

            if (!headers_sent()) {
                http_response_code(404);
            }
        }
    }

    
    public function getPageTitle(): ?string {
        return $this->currentPageTitle;
    }
}
