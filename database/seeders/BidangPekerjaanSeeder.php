<?php

namespace Database\Seeders;

use App\Models\BidangPekerjaan;
use Illuminate\Database\Seeder;

class BidangPekerjaanSeeder extends Seeder
{
    public function run(): void
    {
        $bidangs = [
            'Listrik',
            'Mekanik',
            'Instrument',
            'Lain Lain',
        ];

        // updateOrCreate supaya seeder aman dijalankan ulang tanpa menggandakan
        // data atau menimpa bidang yang sudah ditambahkan admin.
        foreach ($bidangs as $nama) {
            BidangPekerjaan::updateOrCreate(['nama' => $nama]);
        }
    }
}
