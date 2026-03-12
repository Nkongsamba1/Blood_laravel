<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donneur extends Model
{
    protected $primaryKey = 'donneur_id'; // Indique la clé primaire
    // On définit la table si elle n'est pas au pluriel automatique
    protected $table = 'donneurs';

    // Liste des champs que Laravel a le droit d'écrire en base
    protected $fillable = [
        'utilisateur_id',
        'groupe_sanguin',
        'genre',
        'date_naissance',
        'telephone'
    ];

    // Relation : Un donneur appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
  public function dons() {
        return $this->hasMany(Don::class, 'donneur_id', 'donneur_id');
    }
}
