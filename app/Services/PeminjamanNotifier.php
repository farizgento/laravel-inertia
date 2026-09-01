<?php

namespace App\Services;

use App\Mail\PeminjamanCompletedMail;
use App\Mail\PeminjamanReadyToShipMail;
use App\Mail\PeminjamanReturnedMail;
use App\Mail\PeminjamanReviewNeededMail;
use App\Mail\PeminjamanShippedMail;
use App\Models\Peminjaman;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PeminjamanNotifier
{
    /**
     * Notify SP Tool users in the reviewing area that a peminjaman needs their review.
     * Failures are logged but never thrown, so a broken/unreachable SMTP server
     * never blocks the peminjaman workflow itself.
     */
    public static function notifyReviewNeeded(Peminjaman $peminjaman): void
    {
        $areaId = self::resolveReviewingAreaId($peminjaman);

        if (! $areaId) {
            return;
        }

        $peminjaman->loadMissing(['items.alat', 'user', 'area']);

        $recipients = User::query()
            ->whereHas('role', fn ($query) => $query->where('key', Role::KEY_SP_TOOL))
            ->where('area_id', $areaId)
            ->whereNotNull('email')
            ->get();

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new PeminjamanReviewNeededMail($peminjaman));
            } catch (Throwable $e) {
                Log::warning('Gagal mengirim email review peminjaman ke SP Tool.', [
                    'peminjaman_id' => $peminjaman->id,
                    'recipient_id' => $recipient->id,
                    'recipient_email' => $recipient->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notify PIC Tool users in the shipping area that an approved peminjaman
     * is ready to be prepared and shipped. Failures are logged but never
     * thrown, so a broken/unreachable SMTP server never blocks the
     * peminjaman workflow itself.
     */
    public static function notifyReadyForShipment(Peminjaman $peminjaman): void
    {
        if ($peminjaman->status !== Peminjaman::STATUS_DISETUJUI) {
            return;
        }

        $areaId = $peminjaman->area_id ? (int) $peminjaman->area_id : null;

        if (! $areaId) {
            return;
        }

        $peminjaman->loadMissing(['items.alat', 'user', 'area']);

        $recipients = User::query()
            ->whereHas('role', fn ($query) => $query->where('key', Role::KEY_PIC_TOOL))
            ->where('area_id', $areaId)
            ->whereNotNull('email')
            ->get();

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new PeminjamanReadyToShipMail($peminjaman));
            } catch (Throwable $e) {
                Log::warning('Gagal mengirim email persiapan pengiriman peminjaman ke PIC Tool.', [
                    'peminjaman_id' => $peminjaman->id,
                    'recipient_id' => $recipient->id,
                    'recipient_email' => $recipient->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notify whoever must confirm receipt that a peminjaman has been shipped:
     * the borrowing user for intra-area loans, or PIC Tool of the requesting
     * area for inter-area loans. Failures are logged but never thrown.
     */
    public static function notifyShipped(Peminjaman $peminjaman): void
    {
        if ($peminjaman->status !== Peminjaman::STATUS_DIKIRIM) {
            return;
        }

        $peminjaman->loadMissing(['items.alat', 'user', 'area']);

        if ($peminjaman->is_inter_area) {
            $areaId = $peminjaman->requester_area_id ? (int) $peminjaman->requester_area_id : null;
            if (! $areaId) {
                return;
            }

            $recipients = User::query()
                ->whereHas('role', fn ($query) => $query->where('key', Role::KEY_PIC_TOOL))
                ->where('area_id', $areaId)
                ->whereNotNull('email')
                ->get();
        } else {
            $recipients = $peminjaman->user && $peminjaman->user->email
                ? collect([$peminjaman->user])
                : collect();
        }

        self::sendToRecipients(
            $recipients,
            fn () => new PeminjamanShippedMail($peminjaman),
            'Gagal mengirim email konfirmasi pengiriman peminjaman.',
            $peminjaman
        );
    }

    /**
     * Notify PIC Tool of the shipping (source) area that alat has been
     * returned, fully or partially, so they can verify and process it.
     * Failures are logged but never thrown.
     */
    public static function notifyReturned(Peminjaman $peminjaman): void
    {
        if (! in_array($peminjaman->status, [
            Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS,
            Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA,
        ], true)) {
            return;
        }

        $areaId = $peminjaman->area_id ? (int) $peminjaman->area_id : null;

        if (! $areaId) {
            return;
        }

        $peminjaman->loadMissing(['items.alat', 'user', 'area']);

        $recipients = User::query()
            ->whereHas('role', fn ($query) => $query->where('key', Role::KEY_PIC_TOOL))
            ->where('area_id', $areaId)
            ->whereNotNull('email')
            ->get();

        self::sendToRecipients(
            $recipients,
            fn () => new PeminjamanReturnedMail($peminjaman),
            'Gagal mengirim email pengembalian peminjaman ke PIC Tool.',
            $peminjaman
        );
    }

    /**
     * Notify the original borrower that their peminjaman has been fully
     * closed out. Failures are logged but never thrown.
     */
    public static function notifyCompleted(Peminjaman $peminjaman): void
    {
        if ($peminjaman->status !== Peminjaman::STATUS_SELESAI) {
            return;
        }

        $peminjaman->loadMissing(['items.alat', 'user', 'area']);

        $recipients = $peminjaman->user && $peminjaman->user->email
            ? collect([$peminjaman->user])
            : collect();

        self::sendToRecipients(
            $recipients,
            fn () => new PeminjamanCompletedMail($peminjaman),
            'Gagal mengirim email penyelesaian peminjaman.',
            $peminjaman
        );
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private static function sendToRecipients(
        Collection $recipients,
        \Closure $mailFactory,
        string $failureLogMessage,
        Peminjaman $peminjaman
    ): void {
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send($mailFactory());
            } catch (Throwable $e) {
                Log::warning($failureLogMessage, [
                    'peminjaman_id' => $peminjaman->id,
                    'recipient_id' => $recipient->id,
                    'recipient_email' => $recipient->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private static function resolveReviewingAreaId(Peminjaman $peminjaman): ?int
    {
        if ($peminjaman->status === Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM) {
            return $peminjaman->requester_area_id ? (int) $peminjaman->requester_area_id : null;
        }

        if ($peminjaman->status === Peminjaman::STATUS_MENUNGGU_REVIEW) {
            return $peminjaman->area_id ? (int) $peminjaman->area_id : null;
        }

        return null;
    }
}
