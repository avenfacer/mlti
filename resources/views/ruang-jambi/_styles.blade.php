{{-- Gaya khusus Ruang Jambi. Warna mengikuti tema aplikasi (public/assets/css/custom.css):
     pink #FF84BA → biru #99C2FF. Tombol memakai .btn-primary / .btn-secondary bawaan tema. --}}
<style>
    :root { --rj-soft: #fff1f7; --rj-soft-2: #f5f9ff; --rj-line: #ffd4e5; }
    .rj-gradient { background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); }

    /* Nomor langkah formulir */
    .rj-step { display: inline-flex; width: 28px; height: 28px; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 50%; background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); color: #fff; font-weight: 600; font-size: .85rem; }
    .rj-step-head { display: flex; gap: 12px; align-items: flex-start; }
    .rj-step-head h6 { margin: 0; font-weight: 600; }
    .rj-step-head small { color: #b59fa8; }

    /* Kode referensi */
    .rj-code { border: 2px dashed var(--rj-line); background: linear-gradient(90deg, #fff8fc, var(--rj-soft-2)); border-radius: 10px; padding: 10px 14px; text-align: center; }
    .rj-code small { display: block; font-size: .7rem; letter-spacing: .06em; text-transform: uppercase; color: var(--color-primary); font-weight: 600; }
    .rj-code strong { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 1.25rem; letter-spacing: .08em; color: var(--text-primary); }
    .rj-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 600; color: var(--color-primary); white-space: nowrap; }

    .rj-detail th { width: 36%; }

    /* Riwayat */
    .rj-timeline { list-style: none; padding: 0; position: relative; }
    .rj-timeline li { display: flex; gap: 12px; padding-bottom: 16px; position: relative; }
    .rj-timeline li:not(:last-child)::before { content: ''; position: absolute; left: 14px; top: 30px; bottom: 0; width: 2px; background: var(--border-color); }
    .rj-dot { width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; font-size: .85rem; }
    .rj-note { margin-top: 6px; padding: 6px 10px; background: #fff9fc; border-left: 3px solid var(--rj-line); border-radius: 4px; font-size: .85rem; white-space: pre-line; }

    /* Warna status/aksi di luar palet Bootstrap: selaras tema */
    .bg-rj-primary { background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)) !important; color: #fff !important; }
    .bg-rj-revisi { background-color: var(--color-secondary) !important; color: #fff !important; }

    /* Kartu pilihan ruang */
    .rj-room { display: flex; gap: 12px; padding: 14px; border: 1px solid var(--border-color); border-radius: 10px; cursor: pointer; transition: border-color .15s, background .15s; margin-bottom: 10px; }
    .rj-room:hover { border-color: var(--rj-line); }
    .rj-room.selected { border-color: var(--color-primary); background: var(--rj-soft); box-shadow: 0 0 0 1px var(--color-primary); }
    .rj-room.disabled { cursor: not-allowed; background: #fbf8fa; opacity: .75; }
    .rj-room input { margin-top: 4px; width: 17px; height: 17px; accent-color: var(--color-primary); }
    .rj-room .rj-room-reason { font-size: .78rem; margin-top: 6px; }
    .rj-chip { display: inline-flex; align-items: center; gap: 4px; font-size: .72rem; padding: 2px 8px; border-radius: 999px; background: var(--rj-soft-2); color: #5b7396; margin: 2px 4px 2px 0; }
    .rj-facility { display: flex; gap: 10px; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; height: 100%; }
    .rj-facility:has(input:checked) { border-color: var(--color-primary); background: var(--rj-soft); }
    .rj-facility input { margin-top: 3px; accent-color: var(--color-primary); }

    /* Ringkasan pemesanan: pita gradien selebar kartu (kartu tema ber-padding 24px) */
    .rj-summary-head { margin: -24px -24px 16px; padding: 16px 24px; border-radius: 12px 12px 0 0; background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); }
    .rj-summary-head h6, .rj-summary-head small { color: #fff !important; }
    .rj-summary dt { font-size: .72rem; color: #b59fa8; font-weight: 500; }
    .rj-summary dd { font-weight: 600; margin-bottom: 10px; color: var(--text-primary); }
    .rj-summary dd.empty { font-weight: 400; font-style: italic; color: #c9b8c0; }

    /* Tab status / kelola */
    .rj-tabs { border-bottom: 1px solid var(--border-color); flex-wrap: nowrap; overflow-x: auto; }
    .rj-tabs .nav-link { color: var(--text-secondary); font-weight: 500; font-size: .875rem; white-space: nowrap; border-bottom: 2px solid transparent; }
    .rj-tabs .nav-link:hover { color: var(--color-primary); }
    .rj-tabs .nav-link.active { color: var(--color-primary); border-bottom-color: var(--color-primary); background: transparent; }
    @media (min-width: 1200px) { .rj-sticky { position: sticky; top: 80px; } }
</style>
