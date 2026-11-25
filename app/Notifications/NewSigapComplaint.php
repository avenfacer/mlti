<?php

namespace App\Notifications;

use App\Models\GeneralComplaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSigapComplaint extends Notification
{
    use Queueable;

    public function __construct(protected GeneralComplaint $complaint) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (extension_loaded('mbstring')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reporter = $this->complaint->reporter;

        return (new MailMessage)
            ->subject('Pengaduan SIGAP Baru: '.$this->complaint->category.' ('.$this->complaint->ticket_id.')')
            ->greeting('Halo Tim Umum,')
            ->line('Ada pengaduan kerusakan fasilitas umum baru yang memerlukan tindak lanjut.')
            ->line('**Detail Pengaduan:**')
            ->line('- **Nomor Tiket:** '.$this->complaint->ticket_id)
            ->line('- **Lokasi:** '.$this->complaint->locationLabel())
            ->line('- **Jenis Kerusakan:** '.$this->complaint->category)
            ->line('- **Deskripsi:** '.$this->complaint->description)
            ->line('- **Pelapor:** '.($reporter ? $reporter->name : 'N/A'))
            ->action('Tindak Lanjuti Pengaduan', route('umum.complaints.show', $this->complaint))
            ->line('Terima kasih atas responsnya!');
    }

    public function toArray(object $notifiable): array
    {
        $reporter = $this->complaint->reporter;

        return [
            'kind' => 'sigap',
            'title' => 'Pengaduan SIGAP: '.$this->complaint->category,
            'url' => route('umum.complaints.show', $this->complaint),
            'report_id' => $this->complaint->ticket_id,
            'issue_type' => $this->complaint->category,
            'location' => $this->complaint->locationLabel(),
            'description' => $this->complaint->description,
            'reporter_name' => $reporter ? $reporter->name : 'N/A',
            'reported_at' => $this->complaint->created_at->toDateTimeString(),
        ];
    }
}
