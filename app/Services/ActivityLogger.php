<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\Area;
use App\Models\LaporanAlat;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\PeminjamanItemPhoto;
use App\Models\SuratJalan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ActivityLogger
{
    private const IGNORED_KEYS = [
        'created_at',
        'updated_at',
        'remember_token',
        'password',
    ];

    public static function log(string $action, ?Model $subject = null, array $context = []): void
    {
        $request = app()->bound('request') ? app(Request::class) : null;
        $actor = $context['actor'] ?? $request?->user();

        $oldValues = self::sanitizeValues((array) ($context['old_values'] ?? []));
        $newValues = self::sanitizeValues((array) ($context['new_values'] ?? []));
        $properties = self::sanitizeValues((array) ($context['properties'] ?? []));

        ActivityLog::create([
            'user_id' => $actor?->id,
            'area_id' => $context['area_id'] ?? self::resolveAreaId($subject, $actor),
            'subject_id' => $context['subject_id'] ?? $subject?->getKey(),
            'actor_name' => $context['actor_name'] ?? $actor?->name ?? 'System',
            'actor_role_key' => $context['actor_role_key'] ?? $actor?->role?->key,
            'actor_area_id' => $context['actor_area_id'] ?? $actor?->area_id,
            'action' => $action,
            'subject_type' => $context['subject_type'] ?? ($subject ? class_basename($subject) : null),
            'subject_label' => $context['subject_label'] ?? self::resolveSubjectLabel($subject),
            'description' => $context['description'] ?? self::buildDescription($action, $subject, $actor),
            'method' => $context['method'] ?? $request?->method(),
            'route' => $context['route'] ?? $request?->route()?->uri(),
            'url' => $context['url'] ?? $request?->fullUrl(),
            'ip_address' => $context['ip_address'] ?? $request?->ip(),
            'user_agent' => $context['user_agent'] ?? $request?->userAgent(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'properties' => $properties ?: null,
        ]);
    }

    public static function logModelEvent(string $action, Model $model, array $oldValues = []): void
    {
        $request = app()->bound('request') ? app(Request::class) : null;
        $actor = $request?->user();

        if (! $actor) {
            return;
        }

        $oldSnapshot = self::sanitizeValues(
            Arr::except($oldValues, self::IGNORED_KEYS)
        );
        $newSnapshot = self::sanitizeValues(
            $action === 'delete' ? [] : self::modelValues($model)
        );

        if ($action === 'create') {
            $oldChanges = [];
            $newChanges = $newSnapshot;
        } elseif ($action === 'delete') {
            $oldChanges = $oldSnapshot;
            $newChanges = [];
        } else {
            $changedKeys = collect(array_keys($oldSnapshot))
                ->merge(array_keys($newSnapshot))
                ->unique()
                ->filter(fn (string $key) => self::valuesDiffer(
                    $oldSnapshot[$key] ?? null,
                    $newSnapshot[$key] ?? null
                ))
                ->values()
                ->all();

            if (! $changedKeys) {
                return;
            }

            $oldChanges = Arr::only($oldSnapshot, $changedKeys);
            $newChanges = Arr::only($newSnapshot, $changedKeys);
        }

        self::log($action, $model, [
            'actor' => $actor,
            'old_values' => $oldChanges,
            'new_values' => $newChanges,
            'description' => self::buildDescription($action, $model, $actor),
        ]);
    }

    private static function modelValues(Model $model): array
    {
        return self::sanitizeValues(
            Arr::except($model->getAttributes(), self::IGNORED_KEYS)
        );
    }

    private static function sanitizeValues(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            if (in_array((string) $key, ['password', 'remember_token'], true)) {
                $sanitized[$key] = '[hidden]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeValues($value);
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $sanitized[$key] = $value->format('Y-m-d H:i:s');
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value) || is_null($value)) {
                $sanitized[$key] = $value;
                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] = Str::limit($value, 1000);
                continue;
            }

            $sanitized[$key] = (string) $value;
        }

        return $sanitized;
    }

    private static function valuesDiffer(mixed $before, mixed $after): bool
    {
        return $before !== $after;
    }

    private static function resolveAreaId(?Model $subject, ?User $actor): ?int
    {
        if ($subject instanceof User) {
            return $subject->area_id ? (int) $subject->area_id : null;
        }

        if ($subject instanceof Area) {
            return $subject->id ? (int) $subject->id : null;
        }

        if ($subject instanceof Alat || $subject instanceof Peminjaman) {
            return $subject->area_id ? (int) $subject->area_id : null;
        }

        if ($subject instanceof LaporanAlat) {
            return $subject->alat?->area_id
                ? (int) $subject->alat->area_id
                : ($subject->user?->area_id ? (int) $subject->user->area_id : null);
        }

        if ($subject instanceof SuratJalan) {
            return $subject->peminjaman?->area_id ? (int) $subject->peminjaman->area_id : null;
        }

        if ($subject instanceof PeminjamanItem) {
            return $subject->peminjaman?->area_id ? (int) $subject->peminjaman->area_id : null;
        }

        if ($subject instanceof PeminjamanItemPhoto) {
            return $subject->item?->peminjaman?->area_id
                ? (int) $subject->item->peminjaman->area_id
                : null;
        }

        return $actor?->area_id ? (int) $actor->area_id : null;
    }

    private static function resolveSubjectLabel(?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        return match (true) {
            $subject instanceof User => trim("{$subject->name} ({$subject->email})"),
            $subject instanceof Area => $subject->name,
            $subject instanceof Alat => $subject->nama,
            $subject instanceof Peminjaman => trim("Peminjaman #{$subject->id} {$subject->keperluan}"),
            $subject instanceof LaporanAlat => trim(ucfirst($subject->kategori) . " #{$subject->id}"),
            $subject instanceof SuratJalan => "Surat Jalan #{$subject->peminjaman_id}",
            $subject instanceof PeminjamanItem => trim(($subject->alat?->nama ?? 'Item Peminjaman') . " #{$subject->id}"),
            $subject instanceof PeminjamanItemPhoto => trim(($subject->original_name ?: 'Foto Bukti') . " #{$subject->id}"),
            default => class_basename($subject) . ' #' . $subject->getKey(),
        };
    }

    private static function buildDescription(string $action, ?Model $subject, ?User $actor): string
    {
        $actorName = $actor?->name ?? 'System';
        $subjectType = $subject ? Str::headline(class_basename($subject)) : 'Aplikasi';
        $subjectLabel = self::resolveSubjectLabel($subject);

        $actionLabel = match ($action) {
            'create' => 'menambahkan',
            'update' => 'memperbarui',
            'delete' => 'menghapus',
            'register' => 'mendaftarkan akun',
            default => $action,
        };

        if ($action === 'register') {
            return trim("{$actorName} {$actionLabel}");
        }

        return trim("{$actorName} {$actionLabel} {$subjectType}" . ($subjectLabel ? " {$subjectLabel}" : ''));
    }
}
