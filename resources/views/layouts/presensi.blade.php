<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi - {{ $kegiatan->nama }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
        }

        .page {
            page-break-after: always;
            position: relative;
            min-height: 257mm;
        }

        .page:last-child {
            page-break-after: avoid;
        }

        /* Header */
        .header {
            width: 100%;
            margin-bottom: 20px;
        }

        .header img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Title */
        .title {
            text-align: center;
            margin: 16px 0 32px;
        }

        .title h2 {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .content {
            margin-left: 120px;
            margin-right: 120px;
        }

        /* Info Section */
        .info {
            margin: 20px 0 40px 0;
        }

        .info-row {
            display: flex;
            flex-direction: row;
            align-items: start;
            margin-bottom: 12px;
        }

        .info-label {
            display: inline-block;
            width: 180px;
            font-weight: bold;
            vertical-align: top;
        }

        .info-separator {
            display: inline-block;
            width: 10px;
            font-weight: bold;
            vertical-align: top;
        }

        .info-value {
            display: inline-block;
            width: calc(100% - 190px - 120px);
            font-weight: bold;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            vertical-align: top;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }

        table th {
            font-weight: bold;
            font-size: 12pt;
            height: 40px;
        }

        table td {
            font-size: 12pt;
            height: 100px;
        }

        table td.no-col {
            width: 30px;
        }

        table td.nama-col {
            width: 300px;
            text-align: left;
            padding-left: 10px;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        table td.nim-col {
            width: 160px;
        }

        table td.ttd-col {
            width: 100px;
        }

        .signature-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
        }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
        }

        .footer img {
            width: 100%;
            height: auto;
            display: block;
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .page {
                page-break-after: always;
            }

            .page:last-child {
                page-break-after: avoid;
            }
        }
    </style>
</head>

<body>
    @php
        $firstPageItems = 6; // Jumlah mahasiswa untuk halaman pertama
        $otherPageItems = 10; // Jumlah mahasiswa untuk halaman lainnya

        // Hitung total halaman
        $remainingItems = $mahasiswaList->count() - $firstPageItems;
        $totalPages = 1 + ($remainingItems > 0 ? ceil($remainingItems / $otherPageItems) : 0);
    @endphp

    @for ($page = 1; $page <= $totalPages; $page++)
        <div class="page">
            <!-- Header Image -->
            <div class="header">
                <img src="{{ $headerImagePath }}" alt="Header">
            </div>

            <div class="content">
                <!-- Info Section (hanya di halaman pertama) -->
                @if ($page === 1)
                    <!-- Title -->
                    <div class="title">
                        <h2>PRESENSI MAHASISWA FMIPA</h2>
                        <h2>PEMILIHAN CAKA DAN CAWAKA BEM SERTA CAKA HIMA</h2>
                        <h2>PEMILU RAYA (PEMIRA) FMIPA {{ now()->year }}</h2>
                    </div>

                    <div class="info">
                        <div class="info-row">
                            <span class="info-label">Hari, tanggal</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">
                                {{ \Carbon\Carbon::parse($kegiatan->waktu_mulai)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Waktu</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">
                                {{ \Carbon\Carbon::parse($kegiatan->waktu_mulai)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($kegiatan->waktu_selesai)->format('H:i') }} WITA
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tempat</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">
                                Online via pemirafmipa.dpmfmipaunud.com
                            </span>
                        </div>
                    </div>
                @endif

                <!-- Table -->
                <table>
                    @php
                        // Hitung start index dan jumlah items untuk halaman ini
                        if ($page === 1) {
                            $startIndex = 0;
                            $itemsThisPage = min($firstPageItems, $mahasiswaList->count());
                        } else {
                            $startIndex = $firstPageItems + ($page - 2) * $otherPageItems;
                            $itemsThisPage = min($otherPageItems, $mahasiswaList->count() - $startIndex);
                        }

                        $currentPageItems = $mahasiswaList->slice($startIndex, $itemsThisPage);
                    @endphp

                    <!-- Table Header (hanya untuk halaman pertama) -->
                    @if ($page === 1)
                        <thead>
                            <tr>
                                <th class="no-col">NO.</th>
                                <th class="nama-col">NAMA</th>
                                <th class="nim-col">NIM</th>
                                <th class="ttd-col">TTD</th>
                            </tr>
                        </thead>
                    @endif

                    <tbody>
                        @foreach ($currentPageItems as $index => $mahasiswa)
                            <tr>
                                <td class="no-col">{{ $index + 1 }}.</td>
                                <td class="nama-col">{{ $mahasiswa->nama }}</td>
                                <td class="nim-col">{{ $mahasiswa->nim }}</td>
                                <td class="ttd-col">
                                    @if ($mahasiswa->pivot->ttd)
                                        @php
                                            $ttdPath = storage_path('app/public/' . $mahasiswa->pivot->ttd);
                                            if (file_exists($ttdPath)) {
                                                $ttdData = base64_encode(file_get_contents($ttdPath));
                                                $ttdSrc =
                                                    'data:image/' .
                                                    pathinfo($ttdPath, PATHINFO_EXTENSION) .
                                                    ';base64,' .
                                                    $ttdData;
                                            } else {
                                                $ttdSrc = null;
                                            }
                                        @endphp
                                        @if ($ttdSrc)
                                            <img src="{{ $ttdSrc }}" alt="TTD" class="signature-image">
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Footer Image -->
            <div class="footer">
                <img src="{{ $footerImagePath }}" alt="Footer">
            </div>
        </div>
    @endfor
</body>

</html>
