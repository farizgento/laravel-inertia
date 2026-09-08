@php
    use App\Mail\PeminjamanReturnReminderMail;

    $peminjamAreaName = $peminjaman->area?->name ?? '-';
    $peminjamName = $peminjaman->user?->name ?? '-';
    $tanggalPinjam = $peminjaman->tanggal_pinjam?->format('d M Y') ?? '-';
    $tanggalKembali = $peminjaman->tanggal_kembali?->format('d M Y') ?? '-';

    $isTerlambat = $jenis === PeminjamanReturnReminderMail::JENIS_TERLAMBAT;
    $isHariTenggat = $jenis === PeminjamanReturnReminderMail::JENIS_HARI_TENGGAT;

    // Merah untuk yang sudah lewat tenggat, oranye pada hari-H, biru untuk H-1.
    $accent = $isTerlambat ? '#b91c1c' : ($isHariTenggat ? '#c2410c' : '#0369a1');

    $headline = $isTerlambat
        ? 'Peminjaman Alat Melewati Batas Pengembalian'
        : ($isHariTenggat ? 'Pengembalian Alat Jatuh Tempo Hari Ini' : 'Pengembalian Alat Jatuh Tempo Besok');

    $intro = $isTerlambat
        ? 'Alat berikut sudah melewati tanggal pengembalian dan tercatat belum dikembalikan. Mohon segera dikembalikan atau hubungi PIC Tool area terkait bila ada kendala.'
        : ($isHariTenggat
            ? 'Hari ini adalah batas pengembalian untuk peminjaman berikut. Mohon kembalikan alat sesuai jadwal.'
            : 'Batas pengembalian untuk peminjaman berikut adalah besok. Mohon siapkan alat untuk dikembalikan tepat waktu.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $headline }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 0;">
<tr>
<td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; max-width:600px; width:100%;">
    <tr>
        <td style="background-color:{{ $accent }}; padding:20px 32px;">
            <span style="color:#ffffff; font-size:18px; font-weight:bold;">{{ config('app.name', 'IPTOOLS LITE') }}</span>
        </td>
    </tr>
    <tr>
        <td style="padding:32px;">
            <p style="margin:0 0 8px; font-size:18px; font-weight:bold; color:#111827;">{{ $headline }}</p>

            @if ($isTerlambat)
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
                <tr>
                    <td style="background-color:#fef2f2; border-left:4px solid #b91c1c; padding:12px 16px; font-size:14px; color:#991b1b;">
                        Terlambat <strong>{{ $hariTerlambat }} hari</strong> dari tanggal kembali {{ $tanggalKembali }}.
                    </td>
                </tr>
            </table>
            @endif

            <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#374151;">
                {{ $intro }}
            </p>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0 24px; font-size:14px; color:#374151;">
                <tr>
                    <td style="padding:4px 0; width:160px; color:#6b7280;">No. Peminjaman</td>
                    <td style="padding:4px 0;">#{{ $peminjaman->id }}</td>
                </tr>
                <tr>
                    <td style="padding:4px 0; color:#6b7280;">Diajukan oleh</td>
                    <td style="padding:4px 0;">{{ $peminjamName }}</td>
                </tr>
                <tr>
                    <td style="padding:4px 0; color:#6b7280;">Area</td>
                    <td style="padding:4px 0;">{{ $peminjamAreaName }}</td>
                </tr>
                <tr>
                    <td style="padding:4px 0; color:#6b7280;">Keperluan</td>
                    <td style="padding:4px 0;">{{ $peminjaman->pekerjaan }}</td>
                </tr>
                <tr>
                    <td style="padding:4px 0; color:#6b7280;">Tanggal Pinjam</td>
                    <td style="padding:4px 0;">{{ $tanggalPinjam }}</td>
                </tr>
                <tr>
                    <td style="padding:4px 0; color:#6b7280;">Tanggal Kembali</td>
                    <td style="padding:4px 0; font-weight:bold; color:{{ $accent }};">{{ $tanggalKembali }}</td>
                </tr>
            </table>

            @if (count($items) > 0)
            <p style="margin:0 0 8px; font-size:13px; font-weight:bold; color:#6b7280;">Alat yang belum dikembalikan</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:24px;">
                <tr>
                    <td style="padding:8px 12px; background-color:#f9fafb; border:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:bold;">Alat</td>
                    <td style="padding:8px 12px; background-color:#f9fafb; border:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:bold;">Kode</td>
                    <td style="padding:8px 12px; background-color:#f9fafb; border:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:bold; text-align:right;">Sisa</td>
                </tr>
                @foreach ($items as $item)
                <tr>
                    <td style="padding:8px 12px; border:1px solid #e5e7eb; font-size:14px; color:#111827;">{{ $item['name'] }}</td>
                    <td style="padding:8px 12px; border:1px solid #e5e7eb; font-size:14px; color:#111827;">{{ $item['code'] }}</td>
                    <td style="padding:8px 12px; border:1px solid #e5e7eb; font-size:14px; color:#111827; text-align:right;">{{ $item['qty'] }}</td>
                </tr>
                @endforeach
            </table>
            @endif

            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="border-radius:6px; background-color:{{ $accent }};">
                        <a href="{{ $returnUrl }}" target="_blank" style="display:inline-block; padding:12px 24px; font-size:14px; color:#ffffff; text-decoration:none; font-weight:bold;">
                            Proses Pengembalian
                        </a>
                    </td>
                </tr>
            </table>

            <p style="margin:24px 0 0; font-size:12px; line-height:1.6; color:#9ca3af;">
                Email ini dikirim otomatis oleh {{ config('app.name', 'IPTOOLS LITE') }}. Mohon tidak membalas email ini.
            </p>
        </td>
    </tr>
</table>
</td>
</tr>
</table>
</body>
</html>
