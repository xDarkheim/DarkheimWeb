<?php

namespace App\Models;

use App\Lib\Database;
use PDO;
use PDOException;

class Comments
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    private ?PDO $db;

    public function __construct(Database $database_handler)
    {
        $this->db = $database_handler->getConnection();
    }

    public static function findByArticleId(Database $database_handler, int $article_id, string $status = self::STATUS_APPROVED): array
    {
        $db = $database_handler->getConnection();
        if (!$db) {
            return [];
        }
        try {
            $sql = "SELECT c.*, u.username AS author_username 
                    FROM comments c 
                    LEFT JOIN users u ON c.user_id = u.id 
                    WHERE c.article_id = :article_id AND c.status = :status
                    ORDER BY c.created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR); 
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching comments by article ID {$article_id}: " . $e->getMessage());
            return [];
        }
    }

    public static function getAllByArticleIdForAdmin(Database $db_handler, int $article_id): array
    {
        $db = $db_handler->getConnection();
        if (!$db) {
            error_log("Comments::getAllByArticleIdForAdmin - Database connection not available.");
            return [];
        }
        try {
            // Select all comments for the article, so admin sees all statuses
            $sql = "SELECT c.*, u.username AS author_username
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE c.article_id = :article_id
                    ORDER BY c.created_at DESC"; // or ORDER BY c.status, c.created_at DESC
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all comments for admin for article ID {$article_id}: " . $e->getMessage());
            return [];
        }
    }

    public function findById(int $comment_id): ?array
    {
        if (!$this->db) {
            return null;
        }
        try {
            // Fetching details including user_id for permission checks
            $sql = "SELECT c.*, u.username AS author_username 
                    FROM comments c 
                    LEFT JOIN users u ON c.user_id = u.id 
                    WHERE c.id = :comment_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':comment_id', $comment_id, PDO::PARAM_INT);
            $stmt->execute();
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
            return $comment ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching comment by ID {$comment_id}: " . $e->getMessage());
            return null;
        }
    }

    public function addComment(int $article_id, ?int $user_id, string $content, ?string $author_name = null, string $status = self::STATUS_PENDING): bool
    {
        if (!$this->db) {
            return false;
        }
        try {
            $sql = "INSERT INTO comments (article_id, user_id, author_name, content, status, created_at, updated_at) 
                    VALUES (:article_id, :user_id, :author_name, :content, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
            if ($user_id === null) {
                $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            }
            if ($author_name === null) {
                $stmt->bindValue(':author_name', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':author_name', $author_name, PDO::PARAM_STR);
            }
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding comment: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateContent(int $comment_id, string $new_content): bool
    {
        if (!$this->db) {
            return false;
        }
        try {
            $stmt = $this->db->prepare("UPDATE comments SET content = :content, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->bindParam(':content', $new_content, PDO::PARAM_STR);
            $stmt->bindParam(':id', $comment_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating comment content for ID {$comment_id}: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus(int $comment_id, string $new_status): bool
    {
        if (!$this->db || !in_array($new_status, [self::STATUS_APPROVED, self::STATUS_PENDING, self::STATUS_REJECTED])) {
            return false;
        }
        try {
            $stmt = $this->db->prepare("UPDATE comments SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
            $stmt->bindParam(':id', $comment_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating comment status: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $comment_id): bool
    {
        if (!$this->db) {
            return false;
        }
        try {
            $stmt = $this->db->prepare("DELETE FROM comments WHERE id = :id");
            $stmt->bindParam(':id', $comment_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting comment ID {$comment_id}: " . $e->getMessage());
            return false;
        }
    }

}