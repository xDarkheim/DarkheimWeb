<?php
namespace App\Models;

use App\Lib\Database;
use PDO;
use PDOException;

class User {
    public const ROLE_ADMIN = 'admin';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_USER = 'user';

    private ?int $id = null;
    private ?string $username = null;
    private ?string $email = null;
    private ?string $password_hash = null;
    private ?string $email_verification_token_hash = null;
    private ?string $email_verification_expires_at = null;
    private ?string $email_verified_at = null;
    private int $is_active = 0;

    private ?string $role = self::ROLE_USER;
    private ?string $reset_token_hash = null;
    private ?string $reset_token_expires_at = null;
    private ?string $location = null;
    private ?string $user_status = null;
    private ?string $bio = null;
    private ?string $website_url = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;
    private bool $is_password_changed = false;

    private ?string $pending_email_address = null;
    private ?string $pending_email_token_hash = null;
    private ?string $pending_email_token_expires_at = null;

    private Database $db_handler;

    public function __construct(Database $db_handler) {
        $this->db_handler = $db_handler;
    }

    protected function loadUserData(array $data): void {
        $this->id = (int)$data['id'];
        $this->username = $data['username'];
        $this->email = $data['email'];
        $this->password_hash = $data['password_hash'];
        
        $this->email_verification_token_hash = $data['email_verification_token_hash'] ?? null;
        $this->email_verification_expires_at = $data['email_verification_expires_at'] ?? null;
        $this->email_verified_at = $data['email_verified_at'] ?? null;
        $this->is_active = isset($data['is_active']) ? (int)$data['is_active'] : 0;

        $this->role = $data['role'] ?? self::ROLE_USER;
        $this->reset_token_hash = $data['reset_token_hash'] ?? null;
        $this->reset_token_expires_at = $data['reset_token_expires_at'] ?? null;
        $this->location = $data['location'] ?? null;
        $this->user_status = $data['user_status'] ?? null;
        $this->bio = $data['bio'] ?? null;
        $this->website_url = $data['website_url'] ?? null;
        $this->created_at = $data['created_at'];
        $this->updated_at = $data['updated_at'] ?? null;

        // Добавьте эти строки:
        $this->pending_email_address = $data['pending_email_address'] ?? null;
        $this->pending_email_token_hash = $data['pending_email_token_hash'] ?? null;
        $this->pending_email_token_expires_at = $data['pending_email_token_expires_at'] ?? null;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function getPasswordHash(): ?string {
        return $this->password_hash;
    }

    public function getRole(): ?string {
        return $this->role;
    }

    public function getCreatedAt(): ?string {
        return $this->created_at;
    }

    public function getLocation(): ?string {
        return $this->location;
    }

    public function getUserStatus(): ?string {
        return $this->user_status;
    }

    public function getBio(): ?string {
        return $this->bio;
    }

    public function getWebsiteUrl(): ?string {
        return $this->website_url;
    }

    public function getUpdatedAt(): ?string {
        return $this->updated_at;
    }
    public function getResetTokenHash(): ?string
    {
        return $this->reset_token_hash;
    }

    public function getResetTokenExpiresAt(): ?string
    {
        return $this->reset_token_expires_at;
    }

    public function getEmailVerificationTokenHash(): ?string { return $this->email_verification_token_hash; }
    public function getEmailVerificationExpiresAt(): ?string { return $this->email_verification_expires_at; }
    public function getEmailVerifiedAt(): ?string { return $this->email_verified_at; }
    public function isActive(): bool { return (bool)$this->is_active; }


    public function setUsername(string $username): void {
        $this->username = trim($username);
    }

    public function setEmail(string $email): void {
        $this->email = trim($email);
    }

    public function setPassword(string $plainPassword): void {
        if (empty($plainPassword)) {
            error_log("User model (setPassword): Attempted to set an empty password for user ID: " . ($this->id ?? 'Unknown'));
            return;
        }
        error_log("User model (setPassword): Plain password received for user ID " . ($this->id ?? 'Unknown') . ": " . substr($plainPassword, 0, 3) . "..."); 
        $this->password_hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        error_log("User model (setPassword): New hash generated for user ID " . ($this->id ?? 'Unknown') . ": " . substr($this->password_hash, 0, 10) . "...");
        $this->is_password_changed = true; 
    }

    public function setRole(string $role): void {
        $this->role = $role;
    }

    public function setLocation(?string $location): void {
        $this->location = $location ? trim($location) : null;
    }

    public function setUserStatus(?string $user_status): void {
        $this->user_status = $user_status ? trim($user_status) : null;
    }

    public function setBio(?string $bio): void {
        $this->bio = $bio ? trim($bio) : null;
    }

    public function setWebsiteUrl(?string $website_url): void {
        $url = $website_url ? trim($website_url) : null;
        if ($url && !preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
        $this->website_url = $url;
    }

    public function setEmailVerificationToken(string $token): void {
        $this->email_verification_token_hash = hash('sha256', $token);
        $this->email_verification_expires_at = date('Y-m-d H:i:s', time() + (24 * 60 * 60));
        $this->is_active = 0; 
        $this->email_verified_at = null; 
    }
    
    public function save(): bool {
        $conn = $this->db_handler->getConnection();
        if (!$conn) return false;

        if ($this->id === null) {
            $this->created_at = date('Y-m-d H:i:s');
            if ($this->role === null) $this->role = self::ROLE_USER;

            $sql = "INSERT INTO users (username, email, password_hash, role, created_at, 
                                    email_verification_token_hash, email_verification_expires_at, is_active,
                                    location, user_status, bio, website_url) 
                    VALUES (:username, :email, :password_hash, :role, :created_at, 
                            :email_verification_token_hash, :email_verification_expires_at, :is_active,
                            :location, :user_status, :bio, :website_url)";
            $stmt = $conn->prepare($sql);
            $params = [
                ':username' => $this->username,
                ':email' => $this->email,
                ':password_hash' => $this->password_hash,
                ':role' => $this->role,
                ':created_at' => $this->created_at,
                ':email_verification_token_hash' => $this->email_verification_token_hash,
                ':email_verification_expires_at' => $this->email_verification_expires_at,
                ':is_active' => $this->is_active,
                ':location' => $this->location,
                ':user_status' => $this->user_status,
                ':bio' => $this->bio,
                ':website_url' => $this->website_url,
            ];
        } else { 
            error_log("User::save() called for existing user ID {$this->id}. Use specific update methods instead.");
            $sql = "UPDATE users SET username = :username, email = :email, role = :role, 
                                   location = :location, user_status = :user_status, bio = :bio, website_url = :website_url,
                                   updated_at = NOW()
                    WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $params = [
                ':username' => $this->username,
                ':email' => $this->email,
                ':role' => $this->role,
                ':location' => $this->location,
                ':user_status' => $this->user_status,
                ':bio' => $this->bio,
                ':website_url' => $this->website_url,
                ':id' => $this->id, 
            ];

            if (!empty($this->password_hash) && $this->is_password_changed) {
                error_log("User model (save/update): is_password_changed is true. Current SQL does NOT update password. Hash: " . substr($this->password_hash, 0, 10) . "... for user ID: " . $this->id);
            }
            error_log("User model (save/update): Executing SQL for general details: " . $sql);
        }

        if ($stmt === false) {
            error_log("User model save: Failed to prepare statement. Error: " . implode(":", $conn->errorInfo()));
            return false;
        }
        $result = $stmt->execute($params);
        if ($result) {
            if ($this->id === null) {
                $this->id = (int)$conn->lastInsertId();
                $this->is_password_changed = false; 
            }
            return true;
        } else { 
            error_log("User model save: Failed to execute statement. Error: " . implode(":", $stmt->errorInfo()));
            return false;
        }
    }

    public function markEmailAsVerified(): bool {
        if ($this->id === null) return false;

        $this->email_verified_at = date('Y-m-d H:i:s');
        $this->is_active = 1;

        $conn = $this->db_handler->getConnection();
        if (!$conn) {
            error_log("User model (markEmailAsVerified): No database connection.");
            return false;
        }

        $sql = "UPDATE users SET
                    email_verified_at = :email_verified_at,
                    is_active = :is_active,
                    email_verification_token_hash = NULL,
                    email_verification_expires_at = NULL,
                    updated_at = NOW()
                WHERE id = :id";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            error_log("User model (markEmailAsVerified): Failed to prepare statement. Error: " . implode(":", $conn->errorInfo()));
            return false;
        }

        $executeResult = $stmt->execute([
            ':email_verified_at' => $this->email_verified_at,
            ':is_active' => $this->is_active,
            ':id' => $this->id
        ]);

        if (!$executeResult) {
            error_log("User model (markEmailAsVerified): Failed to execute statement. Error: " . implode(":", $stmt->errorInfo()) . " For User ID: " . $this->id);
            return false;
        }
        
        return true;
    }

    public static function findByEmailVerificationToken(Database $db_handler, string $token): ?User {
        $token_hash = hash('sha256', $token); 
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("User model (findByEmailVerificationToken): No database connection."); 
            return null;
        }

        $sql = "SELECT * FROM users WHERE email_verification_token_hash = ? AND email_verification_expires_at > NOW()";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("User model (findByEmailVerificationToken): Failed to prepare statement. Error: " . implode(":", $conn->errorInfo()));
            return null;
        }
        $stmt->execute([$token_hash]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $user = new self($db_handler);
            $user->loadUserData($userData);
            return $user;
        } else {
            error_log("User model (findByEmailVerificationToken): No user found for token_hash: " . $token_hash . ". Original token: " . $token);
            $checkExpiredSql = "SELECT email_verification_expires_at FROM users WHERE email_verification_token_hash = ? LIMIT 1"; 
            $checkStmt = $conn->prepare($checkExpiredSql);
            if ($checkStmt) {
                $checkStmt->execute([$token_hash]);
                $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($result) { 
                    error_log("User model (findByEmailVerificationToken): Token hash " . $token_hash . " exists, but may have expired. Expiration was at " . $result['email_verification_expires_at']);
                } else {
                    error_log("User model (findByEmailVerificationToken): Token hash " . $token_hash . " does not exist in DB.");
                }
            }
            return null;
        }
    }
    
    public static function findByUsernameOrEmail(Database $db_handler, string $username = '', string $email = ''): ?array {
        $conn = $db_handler->getConnection();
        if (!$conn) return null;

        $stmt_check = $conn->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ?");
        if ($stmt_check === false) {
            return null;
        }
        $stmt_check->execute([$username, $email]);
        $user_data = $stmt_check->fetch(PDO::FETCH_ASSOC);
        return $user_data ?: null;
    }

    public static function findByIdentifier(Database $db_handler, string $identifier): ?self {
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("User::findByIdentifier - Database connection failed.");
            return null;
        }

        $sql = "SELECT id, username, email, password_hash, role, created_at, updated_at, 
                       location, user_status, bio, website_url, 
                       reset_token_hash, reset_token_expires_at,
                       email_verification_token_hash, email_verification_expires_at, email_verified_at, is_active
                FROM users 
                WHERE username = :username_identifier OR email = :email_identifier";
        
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            error_log("User::findByIdentifier - Failed to prepare statement. Error: " . implode(":", $conn->errorInfo()));
            return null;
        }

        $stmt->bindParam(':username_identifier', $identifier, PDO::PARAM_STR);
        $stmt->bindParam(':email_identifier', $identifier, PDO::PARAM_STR);
        
        try {
            $stmt->execute();
        } catch (\PDOException $e) {
            error_log("User::findByIdentifier - PDOException on execute: " . $e->getMessage());
            return null;
        }
        
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $user = new self($db_handler);
            $user->loadUserData($userData); 
            return $user;
        }
        error_log("User::findByIdentifier - No user found for identifier: " . $identifier);
        return null;
    }

    public function findById(int $id): ?self 
    {
        $conn = $this->db_handler->getConnection();
        if (!$conn) {
            error_log("User::findById - Database connection failed.");
            return null;
        }
        try {
            $stmt = $conn->prepare("SELECT id, username, email, password_hash, role, created_at, updated_at, location, user_status, bio, website_url, reset_token_hash, reset_token_expires_at FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                $this->id = (int)$userData['id'];
                $this->username = $userData['username'];
                $this->email = $userData['email'];
                $this->password_hash = $userData['password_hash'];
                $this->role = $userData['role'];
                $this->created_at = $userData['created_at'];
                $this->updated_at = $userData['updated_at'] ?? null;
                $this->location = $userData['location'] ?? null;
                $this->user_status = $userData['user_status'] ?? null;
                $this->bio = $userData['bio'] ?? null;
                $this->website_url = $userData['website_url'] ?? null;
                $this->reset_token_hash = $userData['reset_token_hash'] ?? null;
                $this->reset_token_expires_at = $userData['reset_token_expires_at'] ?? null;
                return $this; 
            }
        } catch (\PDOException $e) {
            error_log("User::findById - PDOException for ID {$id}: " . $e->getMessage());
        }
        return null;
    }

    public function verifyPassword(string $plainPassword): bool {
        return password_verify($plainPassword, $this->password_hash);
    }

    public function updatePassword(string $newPasswordHash): bool {
        if ($this->id === null) {
            return false;
        }
        $conn = $this->db_handler->getConnection();
        if (!$conn) return false;

        $sql = "UPDATE users SET password_hash = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("User model: Failed to prepare statement for updating password: " . implode(":", $conn->errorInfo()));
            return false;
        } 
        $result = $stmt->execute([$newPasswordHash, $this->id]);
        if ($result) {
            $this->password_hash = $newPasswordHash;
            $this->is_password_changed = false; 
        } else {
            error_log("User model: Failed to execute statement for updating password: " . implode(":", $stmt->errorInfo()));
        }
        return $result;
    }

    public function updateEmail(string $newEmail): bool {
        if ($this->id === null) {
            return false;
        }
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("User model: Invalid email format for update: " . $newEmail);
            return false;
        }

        $conn = $this->db_handler->getConnection();
        if (!$conn) return false;

        $sql = "UPDATE users SET email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("User model: Failed to prepare statement for updating email: " . implode(":", $conn->errorInfo()));
            return false;
        }
        $result = $stmt->execute([$newEmail, $this->id]);
        if ($result) {
            $this->email = $newEmail;
        } else {
            error_log("User model: Failed to execute statement for updating email: " . implode(":", $stmt->errorInfo()));
        }
        return $result;
    }

    public function updateDetails(array $details): bool {
        if ($this->id === null) return false;

        $allowedFields = ['email', 'location', 'user_status', 'bio', 'website_url'];
        $fieldsToUpdate = [];
        $valuesToUpdate = [];

        foreach ($details as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $setterMethod = 'set' . ucfirst(str_replace('_', '', ucwords($key, '_')));
                if (method_exists($this, $setterMethod)) {
                    $this->$setterMethod($value);
                    $fieldsToUpdate[] = "{$key} = ?";
                    $valuesToUpdate[] = $this->$key;
                } elseif (property_exists($this, $key)) {
                    $this->$key = $value;
                    $fieldsToUpdate[] = "{$key} = ?";
                    $valuesToUpdate[] = $this->$key;
                }
            }
        }

        if (empty($fieldsToUpdate)) {
            return true;
        }

        $conn = $this->db_handler->getConnection();
        if (!$conn) return false;

        $sql = "UPDATE users SET " . implode(', ', $fieldsToUpdate) . " WHERE id = ?";
        $valuesToUpdate[] = $this->id;

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("User model (updateDetails): Failed to prepare statement: " . implode(":", $conn->errorInfo()));
            return false;
        }
        $result = $stmt->execute($valuesToUpdate);
        if (!$result) {
            error_log("User model (updateDetails): Failed to execute statement: " . implode(":", $stmt->errorInfo()));
        }
        return $result;
    }

    public function findByEmail(string $email): ?self
    {
        $conn = $this->db_handler->getConnection();
        if (!$conn) {
            error_log("User::findByEmail - Database connection failed.");
            return null;
        }
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                $foundUser = new self($this->db_handler);
                $foundUser->id = (int)$userData['id'];
                $foundUser->username = $userData['username'];
                $foundUser->email = $userData['email'];
                $foundUser->password_hash = $userData['password_hash'];
                $foundUser->role = $userData['role'];
                $foundUser->reset_token_hash = $userData['reset_token_hash'];
                $foundUser->reset_token_expires_at = $userData['reset_token_expires_at'];
                $foundUser->created_at = $userData['created_at'];
                $foundUser->updated_at = $userData['updated_at'];
                return $foundUser;
            }
        } catch (\PDOException $e) {
            error_log("User::findByEmail - PDOException for email {$email}: " . $e->getMessage());
        }
        return null;
    }

    public function setPasswordResetToken(?string $token, int $validityDurationSeconds = 3600): bool
    {
        if (!$this->id) {
            error_log("User::setPasswordResetToken - User ID not set.");
            return false;
        }

        $conn = $this->db_handler->getConnection();
        if (!$conn) {
            error_log("User::setPasswordResetToken - Database connection failed for user ID {$this->id}.");
            return false;
        }

        $tokenHashToSave = null;
        $expiresAtToSave = null;

        if ($token !== null) {
            $tokenHashToSave = password_hash($token, PASSWORD_DEFAULT);
            $expiresAtToSave = date('Y-m-d H:i:s', time() + $validityDurationSeconds);
            error_log("User::setPasswordResetToken - Setting new reset token for user ID {$this->id}. Expires at: " . $expiresAtToSave);
        } else {
            error_log("User::setPasswordResetToken - Clearing reset token for user ID {$this->id}.");
        }
        
        $this->reset_token_hash = $tokenHashToSave;
        $this->reset_token_expires_at = $expiresAtToSave;

        try {
            $stmt = $conn->prepare("UPDATE users SET reset_token_hash = :token_hash, reset_token_expires_at = :expires_at, updated_at = NOW() WHERE id = :id");
            $stmt->bindParam(':token_hash', $tokenHashToSave, $tokenHashToSave === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':expires_at', $expiresAtToSave, $expiresAtToSave === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $executed = $stmt->execute();
            if ($executed) {
                error_log("User::setPasswordResetToken - DB update successful for user ID {$this->id}.");
            } else {
                error_log("User::setPasswordResetToken - DB update FAILED for user ID {$this->id}. Error: " . implode(":", $stmt->errorInfo()));
            }
            return $executed;
        } catch (\PDOException $e) {
            error_log("User::setPasswordResetToken - PDOException for user ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    public function getPendingEmailAddress(): ?string
    {
        return $this->pending_email_address;
    }

    public function setPendingEmailAddress(?string $pending_email_address): void
    {
        $this->pending_email_address = $pending_email_address;
    }

    public function getPendingEmailTokenHash(): ?string
    {
        return $this->pending_email_token_hash;
    }

    // No direct setter for hash, it should be set via setPendingEmailChangeToken

    public function getPendingEmailTokenExpiresAt(): ?string
    {
        return $this->pending_email_token_expires_at;
    }

    public function setPendingEmailChangeToken(string $token, int $validityHours = 1): void
    {
        if (empty($token)) {
            $this->pending_email_token_hash = null;
            $this->pending_email_token_expires_at = null;
        } else {
            $this->pending_email_token_hash = password_hash($token, PASSWORD_DEFAULT);
            $this->pending_email_token_expires_at = date('Y-m-d H:i:s', time() + ($validityHours * 3600));
        }
    }

    public function clearPendingEmailChange(): void
    {
        $this->pending_email_address = null;
        $this->pending_email_token_hash = null;
        $this->pending_email_token_expires_at = null;
    }

    public static function findByPendingEmailChangeToken(Database $db_handler, string $token): ?self
    {
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("User::findByPendingEmailChangeToken - Database connection failed.");
            return null;
        }
        // We need to fetch all users and check the token, as we only store the hash.
        // This is not ideal for performance on large tables.
        // A more performant way would be to store the token selector and verifier separately.
        // For now, this approach will work for smaller user bases.
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE pending_email_token_hash IS NOT NULL AND pending_email_token_expires_at > NOW()");
            $stmt->execute();
            while ($userData = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($token, $userData['pending_email_token_hash'])) {
                    $user = new self($db_handler);
                    $user->loadUserData($userData);
                    return $user;
                }
            }
            return null;
        } catch (PDOException $e) {
            error_log("User::findByPendingEmailChangeToken - PDOException: " . $e->getMessage());
            return null;
        }
    }

    public function savePendingEmailChange(): bool
    {
        if ($this->id === null) return false;

        $conn = $this->db_handler->getConnection();
        if (!$conn) return false;

        $sql = "UPDATE users SET 
                    pending_email_address = :pending_email_address,
                    pending_email_token_hash = :pending_email_token_hash,
                    pending_email_token_expires_at = :pending_email_token_expires_at,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("User model (savePendingEmailChange): Failed to prepare statement: " . implode(":", $conn->errorInfo()));
            return false;
        }
        return $stmt->execute([
            ':pending_email_address' => $this->pending_email_address,
            ':pending_email_token_hash' => $this->pending_email_token_hash,
            ':pending_email_token_expires_at' => $this->pending_email_token_expires_at,
            ':id' => $this->id
        ]);
    }

    public function confirmEmailChange(): bool
    {
        if ($this->id === null || $this->pending_email_address === null) {
            return false;
        }

        $this->email = $this->pending_email_address;
        // Optionally, if email verification status should be reset:
        // $this->is_active = 0; // Or some other status indicating re-verification needed for the new email
        // $this->email_verified_at = null;
        // $this->setEmailVerificationToken(bin2hex(random_bytes(32))); // Generate a new token for the new email

        $this->clearPendingEmailChange(); // Clear pending fields

        $conn = $this->db_handler->getConnection();
        if (!$conn) return false;

        // Update the main email and clear pending fields
        $sql = "UPDATE users SET 
                    email = :email,
                    pending_email_address = NULL,
                    pending_email_token_hash = NULL,
                    pending_email_token_expires_at = NULL,
                    -- email_verified_at = NULL, -- if re-verification is needed
                    -- is_active = 0, -- if re-verification is needed
                    -- email_verification_token_hash = :new_email_verification_token_hash, -- if re-verification is needed
                    -- email_verification_expires_at = :new_email_verification_expires_at, -- if re-verification is needed
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("User model (confirmEmailChange): Failed to prepare statement: " . implode(":", $conn->errorInfo()));
            return false;
        }
        
        $params = [
            ':email' => $this->email,
            ':id' => $this->id
        ];
        // If re-verification is needed for the new email:
        // $params[':new_email_verification_token_hash'] = $this->email_verification_token_hash;
        // $params[':new_email_verification_expires_at'] = $this->email_verification_expires_at;

        return $stmt->execute($params);
    }

    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_USER,
            self::ROLE_EDITOR,
            self::ROLE_ADMIN,
        ];
    }
}
?>
