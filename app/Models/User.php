<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $primaryKey = 'utilisateur_id';public $incrementing = true;
    protected $fillable = [
    'nom_complet',
    'email',
    'password',
    'role_utilisateur',
    'photo',
    // Ajoute ceci
];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    // Un utilisateur peut avoir plusieurs dons
public function dons()
{
    return $this->hasMany(Don::class);
}
public function donneur()
{
    // 'utilisateur_id' est la clé étrangère dans la table 'donneurs'
    // 'utilisateur_id' est aussi la clé locale dans la table 'users'
    return $this->hasOne(Donneur::class, 'utilisateur_id', 'utilisateur_id');
}
}
