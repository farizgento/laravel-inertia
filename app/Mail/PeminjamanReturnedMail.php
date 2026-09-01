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

class PeminjamanReturnedMail extends Mailable implements ShouldQueue
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

    public bool $isFullyReturned;

    /**
     * @var array<int, array{name: string, code: string, returned_qty: int, approved_qty: int}>
     */
    public array $items;

    public string $shippingUrl;

    public function __construct(Peminjaman $peminjaman)
    {
        $this->peminjaman = $peminjaman;
        $this->isFullyReturned = $peminjaman->status === Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA;

        $this->items = $peminjaman->items
            ->filter(fn ($item) => (int) $item->returned_qty > 0)
            ->map(fn ($item) => [
                'name' => $item->alat?->nama ?? '-',
                'code' => $item->alat?->kode ?? '-',
                'returned_qty' => (int) $item->returned_qty,
                'approved_qty' => (int) $item->approved_qty,
            ])
            ->values()
            ->all();

        $route = $peminjaman->is_inter_area ? 'pengiriman-antar-area' : 'pengiriman-alat';
        $this->shippingUrl = rtrim((string) config('app.url'), '/') . '/' . $route;
    }

    public function envelope(): Envelope
    {
        $suffix = $this->isFullyReturned ? 'Seluruhnya' : 'Sebagian';

        return new Envelope(
            subject: "Peminjaman Alat #{$this->peminjaman->id} Telah Dikembalikan {$suffix}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.peminjaman.returned',
            with: [
                'peminjaman' => $this->peminjaman,
                'items' => $this->items,
                'isFullyReturned' => $this->isFullyReturned,
                'shippingUrl' => $this->shippingUrl,
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
        Log::error('Gagal mengirim email pengembalian peminjaman setelah beberapa percobaan.', [
            'peminjaman_id' => $this->peminjaman->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
