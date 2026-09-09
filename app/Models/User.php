<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'password', 'role', 'status', 'reset_token', 'token_expires_at'];

    protected $hidden = ['password', 'remember_token', 'reset_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'token_expires_at' => 'datetime'];
    }

    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
