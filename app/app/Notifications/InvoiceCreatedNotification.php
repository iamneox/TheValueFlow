<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuevo cierre disponible — '.$this->invoice->number)
            ->line('Se ha generado un nuevo cierre para el periodo '.$this->invoice->period_start->format('d/m/Y').' - '.$this->invoice->period_end->format('d/m/Y'))
            ->line('Importe total: '.number_format($this->invoice->total_amount, 2).' €')
            ->action('Ver en la plataforma', url('/invoices/'.$this->invoice->id));
    }
}
