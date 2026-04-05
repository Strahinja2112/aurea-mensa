<?php

declare(strict_types=1);

class User
{
    public int $id;
    public string $email;
    public ?string $first_name;
    public ?string $last_name;
    public ?string $phone;
    public int $role_id;
    public bool $email_verified;
    public string $password_hash;
    public string $created_at;
    
    public function __construct(array $data)
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->email = $data['email'] ?? '';
        $this->password_hash = $data['password_hash'] ?? '';
        $this->first_name = $data['first_name'] ?? null;
        $this->last_name = $data['last_name'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->role_id = (int)($data['role_id'] ?? 0);
        $this->email_verified = (bool)($data['email_verified'] ?? false);
        $this->created_at = $data['created_at'] ?? '';
    }
    
    public function getFullName(): string
    {
        if (!$this->first_name && !$this->last_name) {
            return $this->email;
        }
        return trim($this->first_name . ' ' . $this->last_name);
    }
    
    public function getInitials(): string
    {
        $f = !empty($this->first_name) ? mb_substr($this->first_name, 0, 1) : '';
        $l = !empty($this->last_name) ? mb_substr($this->last_name, 0, 1) : '';
        
        $initials = strtoupper($f . $l);
        return !empty($initials) ? $initials : strtoupper(mb_substr($this->email, 0, 1));
    }
    
    public function isAdmin(): bool
    {
        return $this->role_id === 3;
    }
    
    public function isStaff(): bool
    {
        return $this->role_id === 2;
    }
}