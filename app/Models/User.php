<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'profile_photo_path',
        'notif_dismissed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'notif_dismissed' => 'array',
    ];

    /**
     * Check if user has admin role
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user has cliente role
     *
     * @return bool
     */
    public function isCliente(): bool
    {
        return $this->hasRole('cliente');
    }

    /**
     * Get the cliente profile associated with the user
     */
    public function cliente()
    {
        return $this->hasOne(Cliente::class);
    }

    public function suscripciones()
    {
        return $this->hasMany(Suscripcion::class);
    }

    public function integracionConfig()
    {
        return $this->hasOne(IntegracionConfig::class);
    }

    public function facturasServicio()
    {
        return $this->hasMany(FacturaServicio::class);
    }

    /**
     * Send the password reset notification in Spanish.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
