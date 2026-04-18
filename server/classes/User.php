<?php

declare(strict_types=1);

class User
{
    public int $id;
    public string $email;
    public ?string $firstName;
    public ?string $lastName;
    public ?string $phone;
    public int $roleId;
    public bool $emailVerified;
    public string $passwordHash;
    public string $createdAt;
    
    public function __construct(array $data)
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->email = $data['email'] ?? '';
        $this->passwordHash = $data['password_hash'] ?? '';
        $this->firstName = $data['first_name'] ?? null;
        $this->lastName = $data['last_name'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->roleId = (int)($data['role_id'] ?? 0);
        $this->emailVerified = (bool)($data['email_verified'] ?? false);
        $this->createdAt = $data['created_at'] ?? '';
    }
    
    public function getFullName(): string
    {
        if (!$this->firstName && !$this->lastName) {
            return $this->email;
        }
        return trim($this->firstName . ' ' . $this->lastName);
    }
    
    public function getInitials(): string
    {
        $f = !empty($this->firstName) ? mb_substr($this->firstName, 0, 1) : '';
        $l = !empty($this->lastName) ? mb_substr($this->lastName, 0, 1) : '';
        
        $initials = strtoupper($f . $l);
        return !empty($initials) ? $initials : strtoupper(mb_substr($this->email, 0, 1));
    }
    
    public function isAdmin(): bool
    {
        return $this->roleId === 3;
    }
    
    public function isStaff(): bool
    {
        return $this->roleId === 2;
    }
}