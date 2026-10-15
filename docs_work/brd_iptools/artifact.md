# Kontrak Pembuatan BRD IPTOOLS LITE

## Referensi

- Dokumen acuan: `D:\WEB\laravel-inertia\docs_work\brd_iptools\BRD InfraSight reference.docx`
- SHA-256: `3C570F66340C12338F35601F244B0DE69009ABDF51BBEF9DE10A3B24D1C0101F`
- Ukuran: 4.082.030 byte
- Hasil render acuan: `D:\WEB\laravel-inertia\docs_work\brd_iptools\reference_render`
- Jumlah halaman hasil render Microsoft Word: 25
- Jumlah section: 1
- Bukti audit: `reference_analysis.json`, `template-style-evidence.json`, keluaran section, heading, image, field, dan footnote audit dalam log tugas
- Inventaris lengkap path, ukuran, dan SHA-256 setiap package part tersimpan pada `reference_analysis.json` bagian `package_parts`

## Sistem Halaman

- Ukuran kertas 21,59 x 27,94 cm, portrait
- Margin kiri/kanan 1,778 cm, atas 1,651 cm, bawah 1,397 cm
- Jarak header 0,559 cm dan footer 0,635 cm
- Satu section dengan `differentFirstPage` aktif sehingga sampul tidak memakai header dan footer isi
- Halaman isi memakai header grafis biru dan logo PLN Indonesia Power dari `word/header1.xml`
- Footer memakai format `Halaman PAGE dari NUMPAGES`, rata tengah, 8 pt, abu-abu
- Bab utama dimulai pada halaman baru. Tabel panjang boleh berlanjut dengan header berulang

## Tipografi

- Body memakai Arial 9 pt, line spacing sekitar 1,03, space after 3 pt
- Heading 1 memakai 14 pt bold, warna hitam, space before/after 4 pt, keep with next
- Heading 2 memakai 11 pt bold, warna hitam, space before 3 pt dan after 2 pt, keep with next
- Judul sampul memakai 24-26 pt bold; subjudul 10-11 pt; semua berada di tengah
- Judul dan heading final memakai warna hitam sesuai standar dokumen; biru tua dipertahankan untuk pita, header, dan tabel
- Teks penjelasan atau catatan memakai Arial 8,5-9 pt; italic abu-abu hanya untuk catatan pengendalian

## Tabel dan Daftar

- Semua tabel memakai grid tipis `#D9D9D9` atau garis abu-abu sumber
- Header tabel memakai fill biru muda sekitar `#D9EAF7`, teks biru tua atau hitam, bold
- Cell padding sekitar 0,12-0,16 cm; isi vertikal tengah; kolom naratif rata kiri dan kolom status/ID dapat dipusatkan
- Baris tidak memakai tinggi tetap dan header diulang jika tabel melintas halaman
- Lebar kolom harus disesuaikan dengan jenis konten; kolom ID/status lebih kecil daripada deskripsi
- Daftar bernomor dan bullet memakai pola daftar sumber dan indentation konsisten

## Komponen

- Sampul berisi judul BRD, pita biru berisi nama produk, deskripsi fungsi, organisasi, versi, dan bulan dokumen
- Kontrol dokumen terdiri dari riwayat, pemeriksaan/persetujuan, distribusi, dan catatan pengendalian
- Daftar isi memakai field TOC Word; field diperbarui saat dokumen dibuka atau dirender di Word
- Isi mempertahankan 14 bab sumber: detail proyek; kelayakan operasi; kelayakan finansial/risiko; proses saat ini; proses sasaran; alur bisnis; status/menu/hak akses; kebutuhan; nonfungsional/data/integrasi/pelaporan; antarmuka; stakeholder/RACI/asumsi; UAT/ketertelusuran; implementasi; persetujuan/perubahan
- Diagram sumber khusus InfraSight dihapus. BRD baru menggunakan diagram alur IPTOOLS LITE yang dibuat untuk dokumen ini
- Header sumber dipertahankan tetapi label produk diubah dari InfraSight menjadi IPTOOLS LITE

## Alur Konten dan Slot

- Seluruh isi body adalah slot tulis ulang karena subjek berubah dari InfraSight ke IPTOOLS LITE
- Styles, theme, numbering, header/footer, logo, relasi header, dan page settings adalah preserve-only kecuali teks produk pada header
- Gambar body InfraSight wajib dihapus dan tidak boleh muncul pada dokumen final
- Isi harus bersumber dari route, controller, model, service, migration, test, UI, konfigurasi, dan manual IPTOOLS LITE di workspace
- Fakta yang tidak tersedia, seperti sponsor, anggaran, SLA, RPO, RTO, tanggal go-live resmi, dan nama approver, ditulis TBD atau perlu validasi
- Halaman persetujuan menyediakan slot nama, keputusan, tanggal, dan tanda tangan tanpa mengarang identitas

## Package Preservation

- Preserve-only: `[Content_Types].xml` kecuali relasi gambar body yang dibuang oleh python-docx, `_rels/.rels`, `word/styles.xml`, `word/theme/theme1.xml`, `word/fontTable.xml`, `word/settings.xml` kecuali `updateFields`, `word/header1.xml` kecuali teks produk, `word/footer1.xml`, relasi header/footer, dan logo perusahaan
- Editable: `word/document.xml`, relasi gambar body, metadata inti, serta teks produk pada header
- `word/media/image5.png` (logo header) harus dipertahankan. Gambar body referensi dapat dihapus karena tidak relevan dengan IPTOOLS LITE
- Tidak ada footnote atau endnote. Field sumber terdiri dari TOC/PAGEREF dan PAGE/NUMPAGES. Dokumen baru menggunakan TOC, PAGE, dan NUMPAGES

## Gerbang Fidelitas

- Hash dokumen acuan harus tetap sama setelah pembuatan
- Final harus memakai geometri halaman, header, footer, warna tabel, dan hierarki judul yang dikenali dari acuan
- Tidak boleh ada kata InfraSight atau materi fitur InfraSight pada final
- Semua tabel, diagram, header, footer, dan halaman harus diperiksa pada PNG hasil render terbaru
- Tidak boleh ada clipping, overlap, tabel rusak, font hilang, halaman kosong tak disengaja, atau isi placeholder selain TBD dan slot persetujuan yang memang belum diketahui
