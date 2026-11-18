<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;

class VerifyEmailQueued extends VerifyEmailBase implements ShouldQueue
{
   use Queueable;

    // Tùy chỉnh số lần thử lại nếu server mail bị lỗi mạng
    public $tries = 3;

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Xác thực tài khoản Nhà tuyển dụng - JobNest')
            ->view('emails.auth.verify-employer', [
                'verifyUrl'    => $verificationUrl,
                'employerName' => $notifiable->employer->ho_ten ?? 'Nhà tuyển dụng'
            ]);
    }
}