<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\Area;
use App\Models\Peminjaman;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    private function authorizeActor(Request $request): User
    {
        $actor = $request->user();
        $actor?->loadMissing('role');

        abort_unless(
            $actor && in_array($actor->role?->key, [
                Role::KEY_SP_TOOL,
                Role::KEY_MGR_TOOL,
                Role::KEY_ADMIN,
                Role::KEY_SUPER_ADMIN,
            ], true),
            403
        );

        return $actor;
    }

    public function index(Request $request): array
    {
        $actor = $this->authorizeActor($request);
        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;

        $logs = $this->buildFilteredQuery($request, $actor)->paginate($perPage);

        return [
            'data' => $logs->getCollection()
                ->map(fn (ActivityLog $log) => $this->mapLog($log))
                ->values(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ];
    }

    public function export(Request $request): StreamedResponse
    {
        $actor = $this->authorizeActor($request);
        $filename = 'activity-logs-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($request, $actor) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fwrite($handle, "sep=;\n");

            fputcsv($handle, [
                'Waktu',
                'User',
                'Role',
                'Area',
                'Aksi',
                'Tipe Data',
                'Label Data',
                'Deskripsi',
                'Field',
                'Sebelum',
                'Sesudah',
            ], ';');

            $this->buildFilteredQuery($request, $actor)
                ->chunk(500, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        $row = $this->mapLog($log);

                        $beforeChanges = $row['before_changes'];
                        $afterChanges = $row['after_changes'];
                        $changeCount = max(count($beforeChanges), count($afterChanges));

                        if ($changeCount === 0) {
                            fputcsv($handle, [
                                $row['created_at'],
                                $row['actor_name'],
                                $row['actor_role_label'],
                                $row['area_name'],
                                $row['action_label'],
                                $row['subject_type'],
                                $row['subject_label'],
                                $row['description'],
                                '',
                                '',
                                '',
                            ], ';');
                            continue;
                        }

                        for ($index = 0; $index < $changeCount; $index += 1) {
                            $before = $beforeChanges[$index] ?? null;
                            $after = $afterChanges[$index] ?? null;
                            $fieldLabel = $before['label'] ?? $after['label'] ?? '';

                            fputcsv($handle, [
                                $row['created_at'],
                                $row['actor_name'],
                                $row['actor_role_label'],
                                $row['area_name'],
                                $row['action_label'],
                                $row['subject_type'],
                                $row['subject_label'],
                                $row['description'],
                                $fieldLabel,
                                $this->stringifyValue($before['value'] ?? null),
                                $this->stringifyValue($after['value'] ?? null),
                            ], ';');
                        }
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildFilteredQuery(Request $request, User $actor): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $action = trim((string) $request->query('action', ''));
        $areaId = (int) $request->query('area_id', 0);

        $query = ActivityLog::query()
            ->with('area')
            ->whereNotIn('action', ['login', 'logout'])
            ->orderByDesc('created_at');

        if (in_array($actor->role?->key, [
            Role::KEY_SP_TOOL,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
        ], true)) {
            $query->where('area_id', $actor->area_id);
        } elseif ($areaId > 0) {
            $query->where('area_id', $areaId);
        }

        if ($action !== '' && strtolower($action) !== 'semua') {
            $query->where('action', $action);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('actor_name', 'like', '%' . $search . '%')
                    ->orWhere('subject_label', 'like', '%' . $search . '%')
                    ->orWhere('subject_type', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('route', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    private function mapLog(ActivityLog $log): array
    {
        $changes = $this->mapChanges($log);

        return [
            'id' => $log->id,
            'created_at' => $log->created_at?->format('d M Y H:i:s'),
            'action' => $log->action,
            'action_label' => Str::headline($log->action),
            'actor_name' => $log->actor_name ?? '-',
            'actor_role_label' => $log->actor_role_key
                ? Str::of($log->actor_role_key)->replace('_', ' ')->title()->toString()
                : '-',
            'area_name' => $log->area?->name ?? '-',
            'subject_type' => $log->subject_type ?? '-',
            'subject_label' => $log->subject_label ?? '-',
            'description' => $log->description ?? '-',
            'before_changes' => array_map(
                fn (array $change) => [
                    'field' => $change['field'],
                    'label' => $change['label'],
                    'value' => $change['before'],
                ],
                $changes
            ),
            'after_changes' => array_map(
                fn (array $change) => [
                    'field' => $change['field'],
                    'label' => $change['label'],
                    'value' => $change['after'],
                ],
                $changes
            ),
        ];
    }

    private function mapChanges(ActivityLog $log): array
    {
        $oldValues = is_array($log->old_values) ? $log->old_values : [];
        $newValues = is_array($log->new_values) ? $log->new_values : [];

        return collect(array_keys($oldValues))
            ->merge(array_keys($newValues))
            ->unique()
            ->filter(function (string $field) use ($oldValues, $newValues, $log) {
                if ($log->action === 'create' || $log->action === 'delete') {
                    return true;
                }

                return ($oldValues[$field] ?? null) !== ($newValues[$field] ?? null);
            })
            ->map(function (string $field) use ($oldValues, $newValues) {
                return [
                    'field' => $field,
                    'label' => $this->fieldLabel($field),
                    'before' => $this->displayFieldValue($field, $oldValues[$field] ?? null),
                    'after' => $this->displayFieldValue($field, $newValues[$field] ?? null),
                ];
            })
            ->values()
            ->all();
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'status' => 'Status',
            'review_note' => 'Review Note',
            'reviewed_by' => 'Di Review Oleh',
            'reviewed_at' => 'Di Review Pada',
            'tanggal_pinjam' => 'Tanggal Pinjam',
            'tanggal_kembali' => 'Tanggal Kembali',
            'keperluan' => 'Keperluan',
            'approved_qty' => 'Qty Disetujui',
            'review_status' => 'Status Review',
            'rejection_reason' => 'Alasan Penolakan',
            'pengirim_nama' => 'Nama Pengirim',
            'user_id' => 'User',
            'area_id' => 'Area',
            'alat_id' => 'Alat',
            'role_id' => 'Role',
            'peminjaman_id' => 'Peminjaman',
            default => Str::of($field)->replace('_', ' ')->title()->toString(),
        };
    }

    private function displayFieldValue(string $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            'reviewed_by', 'user_id' => User::query()->find($value)?->name ?? $value,
            'area_id' => Area::query()->find($value)?->name ?? $value,
            'role_id' => Role::query()->find($value)?->name ?? $value,
            'alat_id' => Alat::query()->find($value)?->nama ?? $value,
            'peminjaman_id' => Peminjaman::query()->find($value)?->keperluan ?? "Peminjaman #{$value}",
            default => $value,
        };
    }

    private function stringifyValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}
