<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo User — actor base del sistema sisgenot.
 *
 * Cada usuario tiene un rol (admin | teacher | student).
 * Los roles teacher y student extienden su perfil en las
 * tablas correspondientes mediante relaciones hasOne.
 *
 * @property int    $id
 * @property string $email
 * @property string $role
 * @property string $full_name
 * @property bool   $is_active
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /** @var list<string> Campos asignables en masa */
    protected $fillable = [
        'email',
        'password',
        'role',
        'full_name',
        'identification',
        'phone',
        'profile_photo_path',
        'auth_token',
        'auth_token_sent_at',
        'password_reset_token',
        'password_reset_sent_at',
        'must_change_password',
        'is_active',
    ];

    /** @var list<string> Campos ocultos en serialización */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var array<string, string> Casteos de atributos */
    protected $casts = [
        'is_active'         => 'boolean',
        'auth_token_sent_at' => 'datetime',
        'password_reset_sent_at' => 'datetime',
        'must_change_password' => 'boolean',
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // ─────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────

    /** Perfil de docente (si role = 'teacher') */
    public function teacher(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /** Perfil de estudiante (si role = 'student') */
    public function student(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Student::class);
    }

    // ─────────────────────────────────────────────
    // Helpers de rol
    // ─────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }
}
