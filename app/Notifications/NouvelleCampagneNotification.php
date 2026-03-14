<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // <--- AJOUTÉ : Pour l'arrière-plan
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class NouvelleCampagneNotification extends Notification implements ShouldQueue // <--- AJOUTÉ : implements ShouldQueue
{
    use Queueable;

    protected $campagne;

    /**
     * Crée une nouvelle instance de notification.
     */
    public function __construct($campagne)
    {
        $this->campagne = $campagne;
    }

    /**
     * Détermine les canaux de notification.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Représentation mail de la notification.
     */
    public function toMail($notifiable)
    {
        // On prépare l'affichage des groupes cibles pour éviter les erreurs sur implode
        $groupes = is_array($this->campagne->groupes_cibles)
                    ? implode(', ', $this->campagne->groupes_cibles)
                    : 'Tous les groupes';

        return (new MailMessage)
            ->subject('🩸 Urgence : Nouvelle campagne de don de sang !')
            ->greeting('Bonjour ' . ($notifiable->name ?? 'Cher donneur') . ' !')
            ->line('L\'Hôpital Saint-Raphaël organise une nouvelle collecte de sang.')
            ->line('**Détails de la campagne :**')
            ->line('📍 **Lieu :** ' . $this->campagne->lieu)
            ->line('📅 **Date :** Du ' . Carbon::parse($this->campagne->date_debut)->format('d/m/Y'))
            ->line('🎯 **Cible :** ' . $groupes)
            // L'URL doit correspondre à la route de ton application Vue.js
            ->action('Je souhaite participer', url('/Reservation'))
            ->line('Votre don peut sauver jusqu\'à trois vies. Merci pour votre générosité !')
            ->salutation('L\'équipe médicale de Saint-Raphaël');
    }
}
