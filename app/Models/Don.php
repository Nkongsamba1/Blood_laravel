<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Don extends Model
{
    use HasFactory;

    // Nom de la table si différent de 'dons'
    protected $table = 'dons';

    protected $fillable = [
        'donneur_id',
        'personnel_id',
        'campagne_id',
        'tension_arterielle',
        'poids_donneur',
        'est_apte',
        'num_poche',
        'quantite',
        'type_don',
        'date_don',
        'heure_debut',      // Utilisé pour l'heure de début du prélèvement
        'heure_fin',
        'lieu',
        'heure_rdv',
        'statut'            // 'En attente', 'Apte', 'Effectué', 'Annulé'
    ];

    /**
     * Relation avec le donneur
     */
    public function donneur()
    {
        // On s'assure que la clé étrangère est bien 'donneur_id'
        return $this->belongsTo(Donneur::class, 'donneur_id', 'donneur_id');
    }

    /**
     * Relation avec le personnel
     */
    public function personnel()
    {
        return $this->belongsTo(User::class, 'personnel_id', 'utilisateur_id');
    }

    /**
     * Relation avec la campagne (facultatif)
     */
    public function campagne()
    {
        return $this->belongsTo(Campagne::class, 'campagne_id');
    }
}
