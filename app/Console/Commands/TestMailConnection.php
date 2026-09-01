<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailConnection extends Command
{
    protected $signature = 'mail:test {email? : Alamat email tujuan uji}';

    protected $description = 'Kirim email uji untuk memastikan konfigurasi SMTP berjalan';

    public function handle(): int
    {
        $to = $this->argument('email') ?: config('mail.from.address');

        if (! $to) {
            $this->error('Email tujuan belum diisi dan MAIL_FROM_ADDRESS juga kosong.');

            return self::FAILURE;
        }

        $subject = 'Test Email - '.config('app.name');
        $message = implode(PHP_EOL, [
            'Ini adalah email uji dari aplikasi '.config('app.name').'.',
            'Waktu kirim: '.now()->format('Y-m-d H:i:s'),
            'Mailer: '.config('mail.default'),
            'Host: '.config('mail.mailers.smtp.host'),
            'Port: '.config('mail.mailers.smtp.port'),
        ]);

        try {
            Mail::raw($message, function ($mail) use ($to, $subject) {
                $mail->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            $this->error('Gagal mengirim email uji: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Email uji berhasil dikirim ke {$to}.");

        return self::SUCCESS;
    }
}
