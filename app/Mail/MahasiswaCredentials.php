<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MahasiswaCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $mahasiswa,
        public string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            to: [new Address($this->mahasiswa->email, $this->mahasiswa->nama)],
            subject: 'Akun PEMIRA LM FMIPA ' . now()->year . ' - Informasi Login Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.mahasiswa-credentials',
            with: [
                'mahasiswa'     => $this->mahasiswa,
                'plainPassword' => $this->plainPassword,
                'loginUrl'      => config('app.url') . '/login',
                'tahun'         => now()->year,
            ],
        );
    }
}
