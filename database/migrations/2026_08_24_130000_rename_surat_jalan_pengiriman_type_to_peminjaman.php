<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CHECK_CONSTRAINT = 'sj_type_domain_check';

    private const INSERT_TRIGGER = 'sj_type_insert_check';

    private const UPDATE_TRIGGER = 'sj_type_update_check';

    public function up(): void
    {
        $this->relaxDomainIfNeeded();
        DB::table('surat_jalan')
            ->where('jenis', 'pengiriman')
            ->update(['jenis' => 'peminjaman']);
        $this->enforceDomain();
    }

    public function down(): void
    {
        $this->relaxDomainIfNeeded();
        DB::table('surat_jalan')
            ->where('jenis', 'peminjaman')
            ->update(['jenis' => 'pengiriman']);
        $this->enforceLegacyDomain();
    }

    private function relaxDomainIfNeeded(): void
    {
        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'oracle' => $this->statementIgnoringErrors('ALTER TABLE surat_jalan DROP CONSTRAINT '.self::CHECK_CONSTRAINT),
            'sqlite' => $this->dropSqliteTriggers(),
            'mysql' => $this->relaxMysqlDomain(),
            'pgsql', 'sqlsrv' => $this->statementIgnoringErrors('ALTER TABLE surat_jalan DROP CONSTRAINT '.self::CHECK_CONSTRAINT),
            default => throw new \RuntimeException(
                "Driver database {$driver} belum didukung untuk perubahan jenis surat jalan."
            ),
        };
    }

    private function enforceDomain(): void
    {
        $this->enforceDomainFor(['peminjaman', 'pengembalian']);
    }

    private function enforceLegacyDomain(): void
    {
        $this->enforceDomainFor(['pengiriman', 'pengembalian']);
    }

    /**
     * @param  array{0: string, 1: string}  $types
     */
    private function enforceDomainFor(array $types): void
    {
        $driver = DB::connection()->getDriverName();
        [$shipmentType, $returnType] = $types;
        $list = "'{$shipmentType}', '{$returnType}'";

        match ($driver) {
            'oracle' => DB::statement(
                'ALTER TABLE surat_jalan ADD CONSTRAINT '.self::CHECK_CONSTRAINT." CHECK (jenis IN ({$list}))"
            ),
            'sqlite' => $this->createSqliteTriggers($shipmentType, $returnType),
            'mysql', 'pgsql', 'sqlsrv' => DB::statement(
                'ALTER TABLE surat_jalan ADD CONSTRAINT '.self::CHECK_CONSTRAINT." CHECK (jenis IN ({$list}))"
            ),
            default => throw new \RuntimeException(
                "Driver database {$driver} belum didukung untuk pembatasan jenis surat jalan."
            ),
        };
    }

    private function dropSqliteTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS '.self::INSERT_TRIGGER);
        DB::unprepared('DROP TRIGGER IF EXISTS '.self::UPDATE_TRIGGER);
    }

    private function createSqliteTriggers(string $shipmentType, string $returnType): void
    {
        $message = "Jenis surat jalan harus {$shipmentType} atau {$returnType}.";
        DB::unprepared(
            'CREATE TRIGGER '.self::INSERT_TRIGGER.' BEFORE INSERT ON surat_jalan '.
            "FOR EACH ROW WHEN NEW.jenis IS NULL OR NEW.jenis NOT IN ('{$shipmentType}', '{$returnType}') ".
            "BEGIN SELECT RAISE(ABORT, '{$message}'); END"
        );
        DB::unprepared(
            'CREATE TRIGGER '.self::UPDATE_TRIGGER.' BEFORE UPDATE OF jenis ON surat_jalan '.
            "FOR EACH ROW WHEN NEW.jenis IS NULL OR NEW.jenis NOT IN ('{$shipmentType}', '{$returnType}') ".
            "BEGIN SELECT RAISE(ABORT, '{$message}'); END"
        );
    }

    private function relaxMysqlDomain(): void
    {
        $serverVersion = strtolower((string) DB::connection()
            ->getPdo()
            ->getAttribute(\PDO::ATTR_SERVER_VERSION));
        $dropClause = str_contains($serverVersion, 'mariadb')
            ? 'DROP CONSTRAINT '
            : 'DROP CHECK ';

        $this->statementIgnoringErrors('ALTER TABLE surat_jalan '.$dropClause.self::CHECK_CONSTRAINT);
    }

    private function statementIgnoringErrors(string $sql): void
    {
        try {
            DB::statement($sql);
        } catch (\Throwable) {
            //
        }
    }
};
