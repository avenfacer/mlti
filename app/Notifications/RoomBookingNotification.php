<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi alur Ruang Jambi. Data `database` memakai bentuk generik
 * (kind/title/message/url) agar dapat dirender di lonceng header.
 */
class RoomBookingNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Booking $booking,
        protected string $title,
        protected string $message,
        protected string $url,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (extension_loaded('mbstring') && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title.' ('.$this->booking->booking_code.')')
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->message)
            ->action('Lihat Pengajuan', $this->url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'room_booking',
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'booking_code' => $this->booking->booking_code,
            'status' => $this->booking->status,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
