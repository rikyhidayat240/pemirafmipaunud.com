<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun PEMIRA LM FMIPA {{ $tahun }}</title>
    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #0f122d;
            color: #e2e8f0;
        }
        .wrapper {
            max-width: 600px;
            margin: 0 auto;
            background: #14183c;
            border: 1px solid rgba(201,162,39,0.25);
            border-radius: 12px;
            overflow: hidden;
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
        }
        .greeting {
            font-size: 16px;
            color: #cbd5e1;
            margin: 0 0 20px;
            line-height: 1.6;
        }
        .info-card {
            background: rgba(201,162,39,0.06);
            border: 1px solid rgba(201,162,39,0.3);
            border-radius: 10px;
            padding: 24px 28px;
            margin: 24px 0;
        }
        .info-card h3 {
            margin: 0 0 16px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(201,162,39,0.7);
        }
        .credential-row {
            display: flex;
            align-items: center;
            margin: 10px 0;
            gap: 12px;
        }
        .credential-label {
            font-size: 13px;
            color: #94a3b8;
            min-width: 100px;
        }
        .credential-value {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            background: rgba(255,255,255,0.07);
            padding: 6px 14px;
            border-radius: 6px;
            letter-spacing: 2px;
        }
        .btn {
            display: inline-block;
            margin: 24px 0 8px;
            padding: 14px 36px;
            background: linear-gradient(135deg, #c9a227, #e8c547);
            color: #0f122d;
            font-weight: 700;
            font-size: 15px;
            border-radius: 8px;
            text-decoration: none;
            letter-spacing: 0.5px;
        }
        .warning {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 8px;
            padding: 14px 18px;
            font-size: 13px;
            color: #fca5a5;
            margin: 20px 0;
            line-height: 1.5;
        }
        .warning strong { color: #f87171; }
        .steps {
            margin: 20px 0;
        }
        .step {
            display: flex;
            gap: 14px;
            margin: 12px 0;
            align-items: flex-start;
        }
        .step-num {
            width: 26px; height: 26px;
            background: rgba(201,162,39,0.15);
            border: 1px solid rgba(201,162,39,0.4);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
            color: #c9a227;
            flex-shrink: 0;
        }
        .step-text {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.6;
            padding-top: 3px;
        }
        .footer {
            border-top: 1px solid rgba(201,162,39,0.15);
            padding: 24px 40px;
            text-align: center;
            font-size: 12px;
            color: #475569;
            background: rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>🗳️ PEMIRA LM FMIPA {{ $tahun }}</h1>
        <p>Informasi Akun Pemilihan</p>
    </div>

    <div class="body">
        <p class="greeting">
            Halo <strong style="color: #ffffff;">{{ $mahasiswa->nama }}</strong>,<br>
            Akun Anda untuk <strong style="color: #c9a227;">PEMIRA LM FMIPA {{ $tahun }}</strong> telah berhasil dibuat oleh panitia.
            Gunakan informasi di bawah ini untuk masuk ke sistem pemilihan.
        </p>

        <div class="info-card">
            <h3>🔑 Informasi Login Anda</h3>
            <div class="credential-row">
                <span class="credential-label">NIM (Username)</span>
                <span class="credential-value">{{ $mahasiswa->nim }}</span>
            </div>
            <div class="credential-row">
                <span class="credential-label">Password</span>
                <span class="credential-value">{{ $plainPassword }}</span>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="{{ $loginUrl }}" class="btn">Masuk ke Bilik Suara →</a>
        </div>

        <div class="warning">
            <strong>⚠️ Penting:</strong> Jangan bagikan informasi login ini kepada siapapun.
            Setiap suara yang diberikan menggunakan akun ini menjadi tanggung jawab Anda.
            Laporkan kepada panitia jika ada pihak lain yang mengetahui password ini.
        </div>

        <div class="steps">
            <p style="font-size: 13px; color: #64748b; margin: 0 0 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Cara Login:</p>
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-text">Buka link <a href="{{ $loginUrl }}" style="color: #c9a227;">{{ $loginUrl }}</a></div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-text">Masukkan <strong style="color: #e2e8f0;">NIM</strong> sebagai username dan <strong style="color: #e2e8f0;">password</strong> seperti yang tertera di atas</div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-text">Klik "Masuk ke Bilik Suara" dan berikan suara Anda</div>
            </div>
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
