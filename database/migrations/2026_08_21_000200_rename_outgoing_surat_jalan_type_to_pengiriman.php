<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LEGACY_TYPE = 'peminjaman_keluar';

    private const SHIPMENT_TYPE = 'pengiriman';

    public function up(): void
    {
        $this->renameType(self::LEGACY_TYPE, self::SHIPMENT_TYPE);
    }

    public function down(): void
    {
        $this->renameType(self::SHIPMENT_TYPE, self::LEGACY_TYPE);
    }

    private function renameType(string $from, string $to): void
    {
        DB::transaction(function () use ($from, $to) {
            $conflict = DB::table('surat_jalan')
                ->select(['peminjaman_id', 'urutan'])
                ->whereIn('jenis', [$from, $to])
                ->groupBy('peminjaman_id', 'urutan')
                ->havingRaw('COUNT(*) > 1')
                ->first();

            if ($conflict) {
                throw new \RuntimeException(
                    "Jenis surat jalan {$from} tidak dapat diubah menjadi {$to} karena ada urutan dokumen yang bertabrakan."
                );
            }

            DB::table('surat_jalan')
                ->where('jenis', $from)
                ->update(['jenis' => $to]);
        });
    }
};
