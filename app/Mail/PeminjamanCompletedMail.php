<?php

namespace App\Mail;

use App\Models\Peminjaman;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PeminjamanCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Number of times the queued job may be attempted before it is
     * moved to the failed_jobs table.
     */
    public int $tries = 3;

    /**
     * Wait this many seconds between retry attempts (SMTP hiccups,
     * transient DNS issues on the internal relay, etc.).
     */
    public int $backoff = 30;

    public Peminjaman $peminjaman;

    /**
     * @var array<int, array{name: string, code: string, qty: int}>
     */
    public array $items;

    public string $historyUrl;

    public function __construct(Peminjaman $peminjaman)
    {
        $this->peminjaman = $peminjaman;

        $this->items = $peminjaman->items
            ->filter(fn ($item) => (int) $item->approved_qty > 0)
            ->map(fn ($item) => [
                'name' => $item->alat?->nama ?? '-',
                'code' => $item->alat?->kode ?? '-',
                'qty' => (int) $item->approved_qty,
            ])
            ->values()
            ->all();

        $this->historyUrl = rtrim((string) config('app.url'), '/') . '/riwayat-peminjaman';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Peminjaman Alat #{$this->peminjaman->id} Telah Selesai",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.peminjaman.completed',
            with: [
                'peminjaman' => $this->peminjaman,
                'items' => $this->items,
                'historyUrl' => $this->historyUrl,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Called by the queue worker when the job has exhausted all of its
     * retry attempts. Logged here so a persistently-down SMTP server
     * doesn't fail silently into the failed_jobs table.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Gagal mengirim email penyelesaian peminjaman setelah beberapa percobaan.', [
            'peminjaman_id' => $this->peminjaman->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
