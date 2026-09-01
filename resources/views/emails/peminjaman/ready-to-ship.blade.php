@php
    $peminjamAreaName = $peminjaman->area?->name ?? '-';
    $peminjamName = $peminjaman->user?->name ?? '-';
    $tanggalPinjam = $peminjaman->tanggal_pinjam?->format('d M Y') ?? '-';
    $tanggalKembali = $peminjaman->tanggal_kembali?->format('d M Y') ?? '-';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Peminjaman Alat Siap Disiapkan &amp; Dikirim</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 0;">
<tr>
<td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; max-width:600px; width:100%;">
    <tr>
        <td style="background-color:#15803d; padding:20px 32px;">
            <span style="color:#ffffff; font-size:18px; font-weight:bold;">{{ config('app.name', 'IPTOOLS LITE LITE') }}</span>
        </td>
    </tr>
    <tr>
        <td style="padding:32px;">
            <p style="margin:0 0 16px; font-size:16px; color:#111827;">Halo,</p>
            <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#374151;">
                Peminjaman alat di area <strong>{{ $peminjamAreaName }}</strong> telah disetujui dan siap untuk Anda siapkan &amp; kirim sebagai <strong>PIC Tool</strong>.
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
                    <td style="padding:4px 0; color:#6b7280;">Keperluan</td>
                    <td style="padding:4px 0;">{{ $peminjaman->pekerjaan }}</td>
                </tr>
                <tr>
                    <td style="padding:4px 0; color:#6b7280;">Tanggal Pinjam</td>
                    <td style="padding:4px 0;">{{ $tanggalPinjam }}</td>
                </tr>
                <tr>
                    <td style="padding:4px 0; color:#6b7280;">Tanggal Kembali</td>
                    <td style="padding:4px 0;">{{ $tanggalKembali }}</td>
                </tr>
            </table>

            @if (count($items) > 0)
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:24px;">
                <tr>
                    <td style="padding:8px 12px; background-color:#f9fafb; border:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:bold;">Alat</td>
                    <td style="padding:8px 12px; background-color:#f9fafb; border:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:bold;">Kode</td>
                    <td style="padding:8px 12px; background-color:#f9fafb; border:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:bold; text-align:right;">Qty Disetujui</td>
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
                    <td style="border-radius:6px; background-color:#15803d;">
                        <a href="{{ $shippingUrl }}" target="_blank" style="display:inline-block; padding:12px 24px; font-size:14px; color:#ffffff; text-decoration:none; font-weight:bold;">
                            Siapkan &amp; Kirim Sekarang
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
