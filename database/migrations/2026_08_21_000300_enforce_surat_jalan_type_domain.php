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
        $invalid = DB::table('surat_jalan')
            ->select(['id', 'jenis'])
            ->where(function ($query) {
                $query->whereNull('jenis')
                    ->orWhereNotIn('jenis', ['pengiriman', 'pengembalian']);
            })
            ->first();

        if ($invalid) {
            $type = $invalid->jenis ?? 'NULL';

            throw new \RuntimeException(
                "Jenis surat jalan pada dokumen ID {$invalid->id} tidak valid: {$type}."
            );
        }

        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'oracle' => $this->enforceOracleDomain(),
            'sqlite' => $this->enforceSqliteDomain(),
            'mysql' => $this->enforceMysqlDomain(),
            'pgsql' => $this->enforcePostgresDomain(),
            'sqlsrv' => $this->enforceSqlServerDomain(),
            default => throw new \RuntimeException(
                "Driver database {$driver} belum didukung untuk pembatasan jenis surat jalan."
            ),
        };
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'oracle' => $this->relaxOracleDomain(),
            'sqlite' => $this->relaxSqliteDomain(),
            'mysql' => $this->relaxMysqlDomain(),
            'pgsql' => $this->relaxPostgresDomain(),
            'sqlsrv' => $this->relaxSqlServerDomain(),
            default => throw new \RuntimeException(
                "Driver database {$driver} belum didukung untuk pembatasan jenis surat jalan."
            ),
        };
    }

    private function enforceOracleDomain(): void
    {
        DB::statement('ALTER TABLE surat_jalan MODIFY (jenis NOT NULL)');
        DB::statement(
            'ALTER TABLE surat_jalan ADD CONSTRAINT '.self::CHECK_CONSTRAINT.
            " CHECK (jenis IN ('pengiriman', 'pengembalian'))"
        );
    }

    private function relaxOracleDomain(): void
    {
        DB::statement('ALTER TABLE surat_jalan DROP CONSTRAINT '.self::CHECK_CONSTRAINT);
        DB::statement('ALTER TABLE surat_jalan MODIFY (jenis NULL)');
    }

    private function enforceSqliteDomain(): void
    {
        DB::unprepared(
            'CREATE TRIGGER '.self::INSERT_TRIGGER.' BEFORE INSERT ON surat_jalan '.
            "FOR EACH ROW WHEN NEW.jenis IS NULL OR NEW.jenis NOT IN ('pengiriman', 'pengembalian') ".
            "BEGIN SELECT RAISE(ABORT, 'Jenis surat jalan harus pengiriman atau pengembalian.'); END"
        );
        DB::unprepared(
            'CREATE TRIGGER '.self::UPDATE_TRIGGER.' BEFORE UPDATE OF jenis ON surat_jalan '.
            "FOR EACH ROW WHEN NEW.jenis IS NULL OR NEW.jenis NOT IN ('pengiriman', 'pengembalian') ".
            "BEGIN SELECT RAISE(ABORT, 'Jenis surat jalan harus pengiriman atau pengembalian.'); END"
        );
    }

    private function relaxSqliteDomain(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS '.self::INSERT_TRIGGER);
        DB::unprepared('DROP TRIGGER IF EXISTS '.self::UPDATE_TRIGGER);
    }

    private function enforceMysqlDomain(): void
    {
        DB::statement('ALTER TABLE surat_jalan MODIFY jenis VARCHAR(32) NOT NULL');
        DB::statement(
            'ALTER TABLE surat_jalan ADD CONSTRAINT '.self::CHECK_CONSTRAINT.
            " CHECK (jenis IN ('pengiriman', 'pengembalian'))"
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

        DB::statement('ALTER TABLE surat_jalan '.$dropClause.self::CHECK_CONSTRAINT);
        DB::statement('ALTER TABLE surat_jalan MODIFY jenis VARCHAR(32) NULL');
    }

    private function enforcePostgresDomain(): void
    {
        DB::statement('ALTER TABLE surat_jalan ALTER COLUMN jenis SET NOT NULL');
        DB::statement(
            'ALTER TABLE surat_jalan ADD CONSTRAINT '.self::CHECK_CONSTRAINT.
            " CHECK (jenis IN ('pengiriman', 'pengembalian'))"
        );
    }

    private function relaxPostgresDomain(): void
    {
        DB::statement('ALTER TABLE surat_jalan DROP CONSTRAINT '.self::CHECK_CONSTRAINT);
        DB::statement('ALTER TABLE surat_jalan ALTER COLUMN jenis DROP NOT NULL');
    }

    private function enforceSqlServerDomain(): void
    {
        DB::statement('ALTER TABLE surat_jalan ALTER COLUMN jenis NVARCHAR(32) NOT NULL');
        DB::statement(
            'ALTER TABLE surat_jalan ADD CONSTRAINT '.self::CHECK_CONSTRAINT.
            " CHECK (jenis IN ('pengiriman', 'pengembalian'))"
        );
    }

    private function relaxSqlServerDomain(): void
    {
        DB::statement('ALTER TABLE surat_jalan DROP CONSTRAINT '.self::CHECK_CONSTRAINT);
        DB::statement('ALTER TABLE surat_jalan ALTER COLUMN jenis NVARCHAR(32) NULL');
    }
};
