<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitation extends Notification
{
    // Removed Queueable and ShouldQueue for real-time sending

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invitation $invitation
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('register', ['token' => $this->invitation->token]);
        $expiresAt = $this->invitation->expires_at->format('F j, Y \a\t g:i A');
        $expiresIn = $this->invitation->expires_at->diffForHumans();
        $appName = config('app.name');
        
        // Get role display name
        $role = \App\UserRole::tryFrom($this->invitation->role);
        $roleDisplay = $role ? $role->displayName() : 'Staff';
        
        // Get inviter name
        $inviterName = $this->invitation->invitedBy->name ?? 'Your administrator';

        $data = [
            'url' => $url,
            'email' => $this->invitation->email,
            'roleDisplay' => $roleDisplay,
            'expiresAt' => $expiresAt,
            'expiresIn' => $expiresIn,
            'appName' => $appName,
            'inviterName' => $inviterName,
        ];

        return (new MailMessage)
            ->subject('🎉 Welcome to ' . $appName . ' - Account Invitation')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->replyTo(config('mail.from.address'), config('mail.from.name'))
            ->priority(1)
            ->metadata('type', 'invitation')
            ->metadata('expires', $this->invitation->expires_at->toDateTimeString())
            ->metadata('role', $this->invitation->role)
            ->view(['emails.invitation', 'emails.invitation-text'], $data);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'email' => $this->invitation->email,
        ];
    }
}
