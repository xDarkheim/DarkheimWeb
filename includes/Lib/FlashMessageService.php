<?php
namespace App\Lib;

class FlashMessageService {

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
        }
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
    }


    public function addMessage(string $type, string $message, bool $isHtml = false): void {
        $_SESSION['flash_messages'][$type][] = [
            'text' => $message,
            'is_html' => $isHtml
        ];
    }

    public function addSuccess(string $message, bool $isHtml = false): void {
        $this->addMessage('success', $message, $isHtml);
    }

    public function addError(string $message, bool $isHtml = false): void {
        $this->addMessage('error', $message, $isHtml);
    }

    public function addInfo(string $message, bool $isHtml = false): void {
        $this->addMessage('info', $message, $isHtml);
    }

    public function addWarning(string $message, bool $isHtml = false): void {
        $this->addMessage('warning', $message, $isHtml);
    }

    public function getMessages(): array {
        $messages = $_SESSION['flash_messages'] ?? [];
        $_SESSION['flash_messages'] = [];
        return $messages;
    }

    public function hasMessages(?string $type = null): bool {
        if ($type === null) {
            return !empty($_SESSION['flash_messages']);
        }
        return !empty($_SESSION['flash_messages'][$type]);
    }

    public function displayMessages(): void {
        $allMessages = $this->getMessages();
        if (!empty($allMessages)) {
            echo '<div class="flash-messages-container">';
            foreach ($allMessages as $type => $messages) {
                foreach ($messages as $messageData) {
                    $text = $messageData['text'];
                    $isHtml = $messageData['is_html'];
                    $alertClass = 'flash-message alert alert-' . htmlspecialchars($type);
                    
                    echo '<div class="' . $alertClass . '">';
                    if ($isHtml) {
                        echo $text;
                    } else {
                        echo htmlspecialchars($text);
                    }
                    echo '</div>';
                }
            }
            echo '</div>';
        }
    }
}