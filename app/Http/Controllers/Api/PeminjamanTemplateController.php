<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\PeminjamanTemplate;
use App\Models\PeminjamanTemplateItem;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PeminjamanTemplateController extends Controller
{
    private function roleKey(Request $request): string
    {
        return strtolower((string) ($request->user()?->role?->key ?? ''));
    }

    private function isSuperAdmin(Request $request): bool
    {
        return $this->roleKey($request) === Role::KEY_SUPER_ADMIN;
    }

    private function canManage(Request $request): bool
    {
        return in_array($this->roleKey($request), [
            Role::KEY_PIC_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ], true);
    }

    private function accessibleAreaIds(Request $request): ?array
    {
        if ($this->isSuperAdmin($request)) {
            return null;
        }

        $areaId = $request->user()?->area_id;

        return $areaId ? [(int) $areaId] : [];
    }

    private function ensureTemplateAccessible(Request $request, PeminjamanTemplate $template, bool $forManage = false): void
    {
        if ($forManage && ! $this->canManage($request)) {
            abort(403, 'Anda tidak memiliki akses mengelola template.');
        }

        $areaIds = $this->accessibleAreaIds($request);
        if ($areaIds === null) {
            return;
        }

        abort_unless(in_array((int) $template->area_id, $areaIds, true), 403, 'Template tidak tersedia untuk area anda.');
    }

    private function validatePayload(Request $request, ?PeminjamanTemplate $template = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['required', Rule::in([
                Peminjaman::KATEGORI_INTRA_AREA,
                Peminjaman::KATEGORI_ANTAR_AREA,
            ])],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'source_area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:alats,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $isSuperAdmin = $this->isSuperAdmin($request);
        $areaId = $isSuperAdmin
            ? (int) ($data['area_id'] ?? $template?->area_id ?? 0)
            : (int) ($request->user()?->area_id ?? 0);

        if (! $areaId) {
            throw ValidationException::withMessages([
                'area_id' => ['Area template wajib dipilih.'],
            ]);
        }

        $sourceAreaId = $data['kategori'] === Peminjaman::KATEGORI_ANTAR_AREA
            ? (int) ($data['source_area_id'] ?? 0)
            : null;

        if ($data['kategori'] === Peminjaman::KATEGORI_ANTAR_AREA) {
            if (! $sourceAreaId) {
                throw ValidationException::withMessages([
                    'source_area_id' => ['Area sumber wajib dipilih untuk template antar area.'],
                ]);
            }

            if ($sourceAreaId === $areaId) {
                throw ValidationException::withMessages([
                    'source_area_id' => ['Area sumber harus berbeda dari area peminjam.'],
                ]);
            }
        }

        if (! $isSuperAdmin && (int) ($data['area_id'] ?? $areaId) !== $areaId) {
            throw ValidationException::withMessages([
                'area_id' => ['Anda hanya dapat membuat template untuk area sendiri.'],
            ]);
        }

        $items = collect($data['items'])
            ->map(fn (array $item) => [
                'id' => (int) $item['id'],
                'qty' => (int) $item['qty'],
            ])
            ->keyBy('id');

        $toolAreaId = $sourceAreaId ?: $areaId;
        $alats = Alat::query()
            ->whereIn('id', $items->keys()->all())
            ->where('area_id', $toolAreaId)
            ->get()
            ->keyBy('id');

        if ($alats->count() !== $items->count()) {
            throw ValidationException::withMessages([
                'items' => ['Ada alat yang tidak ditemukan pada area template.'],
            ]);
        }

        foreach ($alats as $alat) {
            $qty = (int) $items[$alat->id]['qty'];
            $totalAset = (int) $alat->total_aset;
            if ($qty > $totalAset) {
                throw ValidationException::withMessages([
                    'items' => ["Jumlah {$alat->nama} melebihi total aset ({$totalAset})."],
                ]);
            }
        }

        return [
            ...$data,
            'area_id' => $areaId,
            'source_area_id' => $sourceAreaId,
            'items' => $items,
        ];
    }

    public function index(Request $request)
    {
        $areaIds = $this->accessibleAreaIds($request);
        $kategori = trim((string) $request->query('kategori', ''));
        $areaId = $request->filled('area_id') ? (int) $request->query('area_id') : null;
        $sourceAreaId = $request->filled('source_area_id') ? (int) $request->query('source_area_id') : null;

        $query = PeminjamanTemplate::query()
            ->with(['area:id,name,kode', 'sourceArea:id,name,kode', 'creator:id,name', 'items.alat.area:id,name,kode'])
            ->withCount('items')
            ->orderBy('nama');

        if ($areaIds !== null) {
            if (! $areaIds) {
                return response()->json([]);
            }

            $query->whereIn('area_id', $areaIds);
        } elseif ($areaId) {
            $query->where('area_id', $areaId);
        }

        if ($kategori !== '') {
            $query->where('kategori', $kategori);
        }

        if ($sourceAreaId) {
            $query->where('source_area_id', $sourceAreaId);
        }

        return response()->json(
            $query->get()->map(fn (PeminjamanTemplate $template) => $this->formatTemplate($template))->values()
        );
    }

    public function store(Request $request)
    {
        abort_unless($this->canManage($request), 403, 'Anda tidak memiliki akses membuat template.');

        $data = $this->validatePayload($request);
        $template = DB::transaction(function () use ($request, $data) {
            $template = PeminjamanTemplate::create([
                'area_id' => $data['area_id'],
                'source_area_id' => $data['source_area_id'],
                'created_by' => $request->user()?->id,
                'nama' => $data['nama'],
                'kategori' => $data['kategori'],
            ]);

            $this->replaceItems($template, $data['items']);

            return $template;
        });

        return response()->json($this->formatTemplate($template->fresh(['area', 'sourceArea', 'creator', 'items.alat.area'])), 201);
    }

    public function show(Request $request, PeminjamanTemplate $template)
    {
        $this->ensureTemplateAccessible($request, $template);

        return response()->json($this->formatTemplate($template->load(['area', 'sourceArea', 'creator', 'items.alat.area'])));
    }

    public function update(Request $request, PeminjamanTemplate $template)
    {
        $this->ensureTemplateAccessible($request, $template, true);

        $data = $this->validatePayload($request, $template);
        DB::transaction(function () use ($template, $data) {
            $template->update([
                'area_id' => $data['area_id'],
                'source_area_id' => $data['source_area_id'],
                'nama' => $data['nama'],
                'kategori' => $data['kategori'],
            ]);

            $this->replaceItems($template, $data['items']);
        });

        return response()->json($this->formatTemplate($template->fresh(['area', 'sourceArea', 'creator', 'items.alat.area'])));
    }

    public function destroy(Request $request, PeminjamanTemplate $template)
    {
        $this->ensureTemplateAccessible($request, $template, true);

        $template->delete();

        return response()->json(['message' => 'Template peminjaman berhasil dihapus.']);
    }

    private function replaceItems(PeminjamanTemplate $template, $items): void
    {
        $template->items()->delete();
        $now = now();
        $rows = $items
            ->map(fn (array $item) => [
                'peminjaman_template_id' => $template->id,
                'alat_id' => $item['id'],
                'qty' => $item['qty'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($rows) {
            PeminjamanTemplateItem::insert($rows);
        }
    }

    private function formatTemplate(PeminjamanTemplate $template): array
    {
        return [
            'id' => $template->id,
            'nama' => $template->nama,
            'kategori' => $template->kategori,
            'area_id' => $template->area_id,
            'area_name' => $template->area?->name ?? '-',
            'source_area_id' => $template->source_area_id,
            'source_area_name' => $template->sourceArea?->name ?? null,
            'created_by_name' => $template->creator?->name ?? '-',
            'items_count' => $template->items_count ?? $template->items->count(),
            'items' => $template->items
                ->map(fn (PeminjamanTemplateItem $item) => [
                    'id' => $item->alat_id,
                    'alat_id' => $item->alat_id,
                    'qty' => (int) $item->qty,
                    'kode' => $item->alat?->kode ?? '-',
                    'nama' => $item->alat?->nama ?? '-',
                    'name' => $item->alat?->nama ?? '-',
                    'jenis_alat' => $item->alat?->jenis_alat ?? '-',
                    'klasifikasi_alat' => $item->alat?->klasifikasi_alat ?? '-',
                    'stok' => (int) ($item->alat?->total_aset ?? $item->qty),
                    'total_aset' => (int) ($item->alat?->total_aset ?? $item->qty),
                    'area_id' => $item->alat?->area_id,
                    'area_name' => $item->alat?->area?->name ?? '-',
                ])
                ->values(),
            'created_at' => $template->created_at?->format('d M Y H:i'),
            'updated_at' => $template->updated_at?->format('d M Y H:i'),
        ];
    }
}
