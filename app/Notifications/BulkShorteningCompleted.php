<?php

namespace App\Notifications;

use App\Models\BulkJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class BulkShorteningCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BulkJob $bulkJob)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your Bulk URL Shortening Job is Complete!')
            ->line('Good news! Your bulk shortening job for file "'.$this->bulkJob->original_filename.'" has finished processing.')
            ->line('Successfully processed '.$this->bulkJob->processed_rows.' rows.')
            ->action('View Results', route('bulk.index'));

        if ($this->bulkJob->result_file_path && Storage::disk('local')->exists($this->bulkJob->result_file_path)) {
            $mail->attach(Storage::disk('local')->path($this->bulkJob->result_file_path), [
                'as' => 'results.csv',
                'mime' => 'text/csv',
            ]);
        }

        return $mail;
    }
}
