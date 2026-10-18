<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun PEMIRA LM FMIPA {{ $tahun }}</title>
    <style>
        body {
            margin: 0; padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background-color: #0a0d22;
            color: #94a3b8;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background-color: #141836;
            border: 1px solid #1e244b;
            border-radius: 12px;
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }
        .header {
            background: linear-gradient(135deg, #0f122d 0%, #1a1f4e 100%);
            border-bottom: 2px solid rgba(201,162,39,0.4);
            padding: 32px 40px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 4px;
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 0;
            font-size: 12px;
            color: rgba(201,162,39,0.8);
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .body {
            padding: 36px 40px;
            box-sizing: border-box;
        }
        .greeting {
            font-size: 15px;
            color: #cbd5e1;
            margin: 0 0 20px;
            line-height: 1.6;
        }
        .info-card {
            background-color: #1a1f4a;
            border: 1px dashed #2d3748;
            border-radius: 10px;
            padding: 24px 28px;
            margin: 24px 0;
            box-sizing: border-box;
            width: 100%;
        }
        .info-card h3 {
            margin: 0 0 16px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #c9a227;
        }
        .credential-table {
            width: 100%;
        }
        .credential-table td {
            padding: 8px 0;
        }
        .credential-label {
            font-size: 13px;
            color: #94a3b8;
            width: 100px;
        }
        .credential-value {
            font-family: 'Courier New', Courier, monospace;
            font-size: 16px;
            font-weight: 700;
            color: #e2e8f0;
            background-color: #0a0d22;
            padding: 6px 12px;
            border-radius: 6px;
            letter-spacing: 2px;
            border: 1px solid #1e244b;
            display: inline-block;
            word-break: break-all;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 28px;
            background-color: #c9a227;
            color: #0a0d22;
            font-weight: 600;
            font-size: 14px;
            border-radius: 6px;
            text-decoration: none;
        }
        .warning {
            background-color: #2d1822;
            border: 1px solid #4a2530;
            border-radius: 8px;
            padding: 14px 18px;
            font-size: 13px;
            color: #e2b3bc;
            margin: 20px 0;
            line-height: 1.5;
            box-sizing: border-box;
            width: 100%;
        }
        .steps {
            margin: 30px 0 10px;
        }
        .steps p {
            font-size: 13px;
            color: #64748b;
            margin: 0 0 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .step-table {
            width: 100%;
            margin-bottom: 12px;
            border-spacing: 0;
        }
        .step-num-td {
            width: 36px;
            vertical-align: top;
            padding-top: 2px;
        }
        .step-text-td {
            vertical-align: top;
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.6;
        }
        .step-num {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            background-color: #1a202c;
            border: 1px solid #c9a227;
            border-radius: 50%;
            font-size: 12px;
            font-weight: 700;
            color: #c9a227;
        }
        .footer {
            border-top: 1px solid #1e244b;
            padding: 24px 40px;
            text-align: center;
            font-size: 12px;
            color: #475569;
            background-color: #0a0d22;
            box-sizing: border-box;
        }
        
        /* Mobile Responsiveness */
        @media only screen and (max-width: 600px) {
            .wrapper {
                margin: 20px auto;
                border-radius: 8px;
                border-left: none;
                border-right: none;
            }
            .header, .body, .footer {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }
            .info-card {
                padding: 20px 16px !important;
            }
            .header h1 {
                font-size: 20px !important;
            }
            .credential-value {
                font-size: 15px !important;
                letter-spacing: 1px !important;
            }
            .credential-label {
                width: 80px !important;
                font-size: 12px !important;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1 style="padding-right: 24px;">🗳️ PEMIRA LM FMIPA {{ $tahun }}</h1>
        <p>Informasi Akun Pemilihan</p>
    </div>

    <div class="body">
        <p class="greeting">
            Halo <strong style="color: #f8fafc;">{{ $mahasiswa->nama }}</strong>,<br>
            Akun Anda untuk sistem pemilihan <strong style="color: #c9a227;">PEMIRA LM FMIPA {{ $tahun }}</strong> telah berhasil dibuat.
            Gunakan informasi di bawah ini untuk masuk ke bilik suara.
        </p>

        <div class="info-card">
            <h3>Informasi Login Anda</h3>
            <table class="credential-table">
                <tr>
                    <td class="credential-label">NIM</td>
                    <td><span class="credential-value">{{ $mahasiswa->nim }}</span></td>
                </tr>
                <tr>
                    <td class="credential-label">Password</td>
                    <td><span class="credential-value">{{ $plainPassword }}</span></td>
                </tr>
            </table>
        </div>

        <div class="btn-container">
            <a href="{{ $siteUrl ?? $loginUrl }}" class="btn">Buka Website PEMIRA</a>
        </div>

        <div class="warning">
            <strong style="color: #f67280;">Penting:</strong> Jangan bagikan informasi login ini kepada siapapun. Setiap suara yang diberikan menggunakan akun ini menjadi tanggung jawab Anda. Laporkan kepada panitia jika ada pihak lain yang mengetahui password ini.
        </div>

        <div class="steps">
            <p>Petunjuk Pemilihan:</p>
            <table class="step-table">
                <tr>
                    <td class="step-num-td"><div class="step-num">1</div></td>
                    <td class="step-text-td">Kunjungi website utama PEMIRA melalui link <a href="{{ $siteUrl ?? $loginUrl }}" style="color: #c9a227; text-decoration: none;">{{ $siteUrl ?? $loginUrl }}</a></td>
                </tr>
            </table>
            <table class="step-table">
                <tr>
                    <td class="step-num-td"><div class="step-num">2</div></td>
                    <td class="step-text-td">Klik tombol <strong style="color: #cbd5e1;">"Masuk"</strong> pada halaman utama.</td>
                </tr>
            </table>
            <table class="step-table">
                <tr>
                    <td class="step-num-td"><div class="step-num">3</div></td>
                    <td class="step-text-td">Masukkan <strong style="color: #cbd5e1;">NIM</strong> dan <strong style="color: #cbd5e1;">password</strong> seperti yang tertera di atas.</td>
                </tr>
            </table>
            <table class="step-table">
                <tr>
                    <td class="step-num-td"><div class="step-num">4</div></td>
                    <td class="step-text-td">Masuk ke bilik suara dan tentukan pilihan terbaik Anda.</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        Email ini dikirim secara otomatis oleh sistem PEMIRA LM FMIPA {{ $tahun }}.<br>
        Jika Anda mengalami kendala, hubungi panitia PEMIRA melalui kanal resmi.<br><br>
        <em>Satu Suara untuk Masa Depan</em>
    </div>
</div>
</body>
</html>
