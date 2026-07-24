<?php
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$encodedEmail = urlencode($email);

// Skema Deep Link kustom ke aplikasi Ambativasi
$deepLink = "ambativasi://auth/reset-password?email=" . $encodedEmail;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membuka Aplikasi Ambativasi</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            background-color: #FFFFFF;
            width: 100%;
            max-width: 400px;
            padding: 36px 28px;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid #E2E8F0;
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            background-color: #EFF6FF;
            color: #2563EB;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 20px;
        }
        h1 {
            font-size: 20px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }
        p {
            font-size: 14px;
            color: #64748B;
            line-height: 1.5;
            margin-bottom: 28px;
        }
        .btn {
            display: block;
            width: 100%;
            background-color: #2563EB;
            color: #FFFFFF;
            font-size: 15px;
            font-weight: 600;
            padding: 16px;
            border-radius: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .btn:active {
            transform: scale(0.98);
            background-color: #1D4ED8;
        }
        .hint {
            margin-top: 16px;
            font-size: 12px;
            color: #94A3B8;
        }
    </style>
</head>
<body>

    <div class="card">
        <div class="icon-circle">🔑</div>
        <h1>Membuka Ambativasi...</h1>
        <p>Anda sedang dialihkan ke aplikasi untuk mengatur ulang kata sandi.</p>

        <a id="openAppBtn" href="<?php echo $deepLink; ?>" class="btn">
            Buka Aplikasi Ambativasi
        </a>

        <p class="hint">Jika aplikasi tidak terbuka otomatis, ketuk tombol biru di atas.</p>
    </div>

    <script>
        const deepLink = "<?php echo $deepLink; ?>";

        // 💡 OTOMATIS EKSEKUSI PENGALIHAN SAAT HALAMAN SELESAI DIMUAT
        window.addEventListener("DOMContentLoaded", () => {
            // Percobaan 1: Redirect langsung via location.href
            window.location.href = deepLink;

            // Percobaan 2: Fallback dengan jeda singkat jika browser menunda request
            setTimeout(() => {
                window.location.replace(deepLink);
            }, 300);
        });
    </script>
</body>
</html>