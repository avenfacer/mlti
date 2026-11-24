<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Tugas | Portal Internal BPS Provinsi Jambi</title>
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; background: #f5f7fc; color: #25264f; font-family: Arial, sans-serif; }
        main { width: min(100%, 620px); padding: 48px 32px; border: 1px solid #e7e9f3; border-radius: 24px; background: #fff; text-align: center; box-shadow: 0 20px 55px rgba(39,45,105,.09); }
        .icon { width: 72px; height: 72px; margin: 0 auto 20px; display: grid; place-items: center; border-radius: 22px; color: #fff; background: linear-gradient(135deg,#ef75b5,#4b59dc); font-size: 30px; }
        h1 { margin: 0 0 12px; font-size: 28px; }
        p { margin: 0 auto 25px; color: #747b95; line-height: 1.7; }
        a { display: inline-flex; gap: 9px; align-items: center; padding: 12px 18px; border-radius: 999px; color: #fff; background: #454fd0; font-size: 13px; font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>
    <main>
        <div class="icon"><i class="bi bi-file-earmark-text"></i></div>
        <h1>Layanan Surat Tugas</h1>
        <p>Layanan Surat Tugas sudah terdaftar di portal. Modul pengajuan dan pengelolaannya akan tersedia di sini.</p>
        <a href="{{ route('portal') }}"><i class="bi bi-arrow-left"></i> Kembali ke pusat layanan</a>
    </main>
</body>
</html>
