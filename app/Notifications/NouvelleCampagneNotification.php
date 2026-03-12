<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NouvelleCampagneNotification extends Notification
{
    use Queueable;

    protected $campagne;

    public function __construct($campagne)
    {
        $this->campagne = $campagne;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('🩸 Urgence : Nouvelle campagne de don de sang !')
            ->greeting('Bonjour ' . $notifiable->nom_complet . ' !')
            ->line('L\'Hôpital Saint-Raphaël organise une nouvelle collecte de sang.')
            ->line('**Détails de la campagne :**')
            ->line('📍 Lieu : ' . $this->campagne->lieu)
            ->line('📅 Date : Du ' . \Carbon\Carbon::parse($this->campagne->date_debut)->format('d/m/Y'))
            ->line('Cible : Groupes sanguins ' . implode(', ', $this->campagne->groupes_cibles ?? ['Tous']))
            ->action('Je souhaite participer', url('/campagnes' . $this->campagne->id))
            ->line('Votre don peut sauver jusqu\'à trois vies. Merci pour votre générosité !')
            ->salutation('L\'équipe médicale de Saint-Raphaël');
    }
}
