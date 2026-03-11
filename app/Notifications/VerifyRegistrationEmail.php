<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyRegistrationEmail extends Notification
{
    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('register.verify', ['token' => $this->token]);

        return (new MailMessage)
            ->subject('Xác thực địa chỉ email - ' . config('app.name'))
            ->greeting('Xin chào ' . $notifiable->name . '!')
            ->line('Cảm ơn bạn đã đăng ký tài khoản. Vui lòng nhấn vào nút bên dưới để xác thực email và hoàn tất đăng ký.')
            ->action('Xác thực Email', $url)
            ->line('Link xác thực sẽ hết hạn sau 24 giờ.')
            ->line('Nếu bạn không thực hiện đăng ký, hãy bỏ qua email này.');
    }
}
