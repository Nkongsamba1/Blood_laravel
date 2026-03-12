<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Campagne extends Model
{
    use HasFactory;

    // Mise à jour des champs pour correspondre au nouveau formulaire
    protected $fillable = [
        'titre',
        'lieu',
        'date_debut',    // Début de la période de campagne
        'date_fin',      // Fin de la période
        'planning',      // Stocke les jours ouvrables et heures (JSON)
        'capacite_max',
        'description',
        'groupes_cibles'
    ];

    /**
     * Le "Casting" : crucial pour transformer le JSON de la BDD
     * en un tableau PHP utilisable par ton front-end Vue.js.
     */
    protected $casts = [
        'planning' => 'array',
        'groupes_cibles' => 'array',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    /**
     * Relation avec les dons
     */
    public function dons()
    {
        return $this->hasMany(Don::class);
    }
}
