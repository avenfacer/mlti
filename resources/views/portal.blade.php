<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pusat layanan digital internal BPS Provinsi Jambi.">
    <title>Pusat Layanan Digital | BPS Provinsi Jambi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <style>
        :root { color-scheme: light; --ink: #20204d; --blue: #5368df; --pink: #e875b2; --muted: #777f9d; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f8f6fc; color: var(--ink); font-family: 'Plus Jakarta Sans', sans-serif; }
        a { color: inherit; text-decoration: none; }
        .hero { min-height: 535px; padding: 30px max(5vw, calc((100vw - 1380px) / 2)); color: #fff; position: relative; overflow: hidden; background: linear-gradient(112deg, #b94f98 0%, #7655bc 42%, #466ed4 100%); clip-path: polygon(0 0,100% 0,100% 86%,0 100%); }
        .hero::before, .hero::after { content: ''; position: absolute; border-radius: 50%; pointer-events: none; }
        .hero::before { width: 390px; height: 390px; right: -115px; top: -150px; background: rgba(255,190,224,.25); filter: blur(3px); }
        .hero::after { width: 300px; height: 300px; left: -150px; bottom: -170px; background: rgba(104,174,255,.28); }
        .topbar { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; max-width: 1380px; margin: 0 auto; }
        .brand { display: inline-flex; align-items: center; gap: 12px; font-size: 25px; font-weight: 800; letter-spacing: -.7px; }
        .brand-mark { display: grid; grid-template-columns: repeat(2, 12px); gap: 4px; transform: skewY(-8deg); }
        .brand-mark i { display: block; height: 15px; border-radius: 3px; background: #f17cb9; }
        .brand-mark i:nth-child(2), .brand-mark i:nth-child(3) { background: #77a4ff; }
        .top-actions { display: flex; gap: 12px; align-items: center; }
        .login-link, .logout-button { border: 0; border-radius: 999px; padding: 13px 21px; font: inherit; font-size: 13px; font-weight: 700; cursor: pointer; color: #22234e; background: #fff; }
        .user-pill { color: #e8eaff; font-size: 13px; font-weight: 700; }
        .hero-copy { position: relative; z-index: 1; max-width: 760px; margin: 35px auto 0; text-align: center; }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; border: 1px solid rgba(255,255,255,.22); background: rgba(255,255,255,.08); border-radius: 999px; padding: 8px 13px; color: #d6d9ff; font-size: 11px; font-weight: 700; letter-spacing: 1.3px; text-transform: uppercase; }
        h1 { margin: 20px 0 15px; font-size: clamp(36px, 5vw, 58px); line-height: 1.15; letter-spacing: -2.5px; font-weight: 800; }
        .hero-copy p { max-width: 690px; margin: 0 auto; color: #c6c9e7; font-size: 16px; line-height: 1.85; }
        .main { position: relative; z-index: 2; max-width: 1190px; margin: 0 auto; padding: 27px 24px 75px; }
        .section-head { display: flex; justify-content: space-between; align-items: end; gap: 20px; margin: 0 4px 20px; }
        .section-head h2 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -.7px; }
        .section-head p { margin: 7px 0 0; color: var(--muted); font-size: 13px; }
        .service-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .service-card { min-height: 285px; position: relative; overflow: hidden; display: flex; flex-direction: column; border: 1px solid #e5e8f4; border-radius: 22px; background: #fff; padding: 27px; box-shadow: 0 17px 45px rgba(40,48,105,.08); transition: transform .2s ease, box-shadow .2s ease; }
        .service-card:hover { transform: translateY(-4px); box-shadow: 0 22px 50px rgba(40,48,105,.14); }
        .service-card.featured { color: #fff; border: 0; background: linear-gradient(128deg, #dc70ae 0%, #8062d0 48%, #4656c7 100%); }
        .service-card.featured::after { content: ''; position: absolute; width: 220px; height: 220px; right: -70px; top: -95px; border: 1px solid rgba(255,255,255,.15); border-radius: 50%; box-shadow: 0 0 0 28px rgba(255,255,255,.035), 0 0 0 58px rgba(255,255,255,.025); }
        .card-top { display: flex; justify-content: space-between; align-items: start; position: relative; z-index: 1; }
        .service-icon { width: 55px; height: 55px; display: grid; place-items: center; border-radius: 16px; color: #4f57d9; background: #eff0ff; font-size: 24px; }
        .featured .service-icon { color: #fff; background: rgba(255,255,255,.16); }
        .access { border-radius: 999px; padding: 7px 10px; color: #ad457a; background: #fff0f7; font-size: 10px; font-weight: 800; }
        .featured .access { color: #fff; background: rgba(255,255,255,.16); }
        .service-content { position: relative; z-index: 1; margin-top: auto; padding-top: 30px; }
        .service-category { margin: 0 0 9px; color: #888fe0; font-size: 11px; font-weight: 800; }
        .service-card h3 { margin: 0 0 9px; font-size: 21px; font-weight: 800; letter-spacing: -.5px; }
        .service-card p { max-width: 520px; margin: 0; color: #737b96; font-size: 13px; line-height: 1.75; }
        .featured p { color: #d1d3f5; }
        .card-link { display: flex; justify-content: space-between; align-items: center; margin-top: 21px; color: #4b52cf; font-size: 12px; font-weight: 800; }
        .featured .card-link { color: #fff; }
        .coming { background: #fafbfe; border-style: dashed; box-shadow: none; }
        .coming:hover { transform: none; box-shadow: none; }
        .coming .service-icon { color: #8f96af; background: #f0f2f8; }
        .coming .card-link { color: #979db2; }
        footer { padding: 20px 24px 32px; text-align: center; color: #949ab0; font-size: 11px; }
        @media (max-width: 700px) { .hero { min-height: 500px; padding: 22px 22px 100px; clip-path: polygon(0 0,100% 0,100% 94%,0 100%); } .brand { font-size: 20px; } .login-link, .logout-button { padding: 11px 14px; font-size: 11px; } .user-pill { display: none; } .hero-copy { margin-top: 75px; } h1 { letter-spacing: -1.5px; } .hero-copy p { font-size: 14px; } .main { margin-top: 0; padding-inline: 16px; } .section-head { display: block; } .section-head h2 { font-size: 21px; } .service-grid { grid-template-columns: 1fr; gap: 14px; } .service-card { min-height: 260px; padding: 22px; } }
    </style>
</head>
<body>
    <header class="hero">
        <nav class="topbar" aria-label="Navigasi utama">
            <a class="brand" href="{{ route('portal') }}"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i><i></i></span>Portal Internal</a>
            <div class="top-actions">
                @auth
                    <span class="user-pill"><i class="bi bi-person-check-fill me-1"></i>{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit"><i class="bi bi-box-arrow-right me-1"></i> Keluar</button></form>
                @else
                    <a class="login-link" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk Pegawai</a>
                @endauth
            </div>
        </nav>
        <div class="hero-copy">
            <span class="eyebrow"><i class="bi bi-stars"></i> BPS Provinsi Jambi</span>
            <h1>Pusat Layanan Digital<br>BPS Provinsi Jambi</h1>
            <p>Gateway integrasi seluruh sistem informasi internal dan publik untuk mendukung percepatan birokrasi dan kualitas data.</p>
        </div>
    </header>

    <main class="main">
        <div class="section-head">
            <div><h2>Layanan Digital</h2><p>Pilih layanan yang ingin Anda gunakan.</p></div>
        </div>
        <section class="service-grid" aria-label="Daftar layanan">
            <a class="service-card featured" href="{{ route('mlti.home') }}">
                <div class="card-top"><span class="service-icon"><i class="bi bi-pc-display-horizontal"></i></span><span class="access"><i class="bi bi-lock-fill me-1"></i>Akses Internal</span></div>
                <div class="service-content"><div class="service-category">LAYANAN UNGGULAN</div><h3>MLTI</h3><p>Manajemen layanan teknologi informasi, pelaporan perangkat, peminjaman ruangan, dan pengajuan Zoom.</p><span class="card-link">Buka layanan MLTI <i class="bi bi-arrow-right"></i></span></div>
            </a>
            <a class="service-card" href="{{ route('services.surat-tugas') }}">
                <div class="card-top"><span class="service-icon"><i class="bi bi-file-earmark-text"></i></span><span class="access"><i class="bi bi-lock-fill me-1"></i>Akses Internal</span></div>
                <div class="service-content"><div class="service-category">ADMINISTRASI PEGAWAI</div><h3>Surat Tugas</h3><p>Layanan pengajuan dan pengelolaan surat tugas pegawai dalam satu alur digital.</p><span class="card-link">Buka layanan Surat Tugas <i class="bi bi-arrow-right"></i></span></div>
            </a>
            <div class="service-card coming">
                <div class="card-top"><span class="service-icon"><i class="bi bi-grid"></i></span><span class="access">SEGERA HADIR</span></div>
                <div class="service-content"><div class="service-category">LAYANAN TERINTEGRASI</div><h3>Layanan lainnya</h3><p>Ruang untuk sistem dan layanan internal BPS Provinsi Jambi berikutnya.</p><span class="card-link">Layanan baru akan ditambahkan <i class="bi bi-plus-circle"></i></span></div>
            </div>
        </section>
    </main>
    <footer>© {{ date('Y') }} BPS Provinsi Jambi · Pusat Layanan Digital</footer>
</body>
</html>
