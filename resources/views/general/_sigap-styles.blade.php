{{-- Gaya khusus SIGAP. Selaras dengan tema aplikasi (public/assets/css/custom.css): pink #FF84BA → biru #99C2FF. --}}
<style>
    :root { --sg-soft: #fff1f7; --sg-soft-2: #f5f9ff; --sg-line: #ffd4e5; }

    /* Nomor langkah formulir */
    .sg-step { display: inline-flex; width: 28px; height: 28px; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 50%; background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); color: #fff; font-weight: 600; font-size: .85rem; }
    .sg-step-head { display: flex; gap: 12px; align-items: flex-start; }
    .sg-step-head h6 { margin: 0; font-weight: 600; }
    .sg-step-head small { color: #b59fa8; }

    /* Pilihan jenis kerusakan */
    .sg-cat { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; height: 100%; min-height: 92px; padding: 12px 8px; border: 1px solid var(--border-color); border-radius: 10px; cursor: pointer; text-align: center; font-size: .85rem; font-weight: 500; transition: border-color .15s, background .15s; }
    .sg-cat i { font-size: 1.5rem; color: var(--color-primary); }
    .sg-cat:hover { border-color: var(--sg-line); }
    .sg-cat:has(input:checked) { border-color: var(--color-primary); background: var(--sg-soft); box-shadow: 0 0 0 1px var(--color-primary); }
    .sg-cat input { position: absolute; opacity: 0; pointer-events: none; }
    .sg-cat-group.is-invalid .sg-cat { border-color: var(--bs-danger, #dc3545); }

    /* Kode tiket */
    .sg-code { border: 2px dashed var(--sg-line); background: linear-gradient(90deg, #fff8fc, var(--sg-soft-2)); border-radius: 10px; padding: 10px 14px; text-align: center; }
    .sg-code small { display: block; font-size: .7rem; letter-spacing: .06em; text-transform: uppercase; color: var(--color-primary); font-weight: 600; }
    .sg-code strong { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 1.25rem; letter-spacing: .08em; color: var(--text-primary); }
    .sg-icon { color: var(--color-primary); }
    .sg-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 600; color: var(--color-primary); white-space: nowrap; }

    .sg-detail th { width: 36%; color: var(--text-secondary); font-weight: 500; }
    .sg-detail td, .sg-detail th { padding-top: .55rem; padding-bottom: .55rem; }

    /* Progres penanganan */
    .sg-timeline { list-style: none; padding: 0; margin: 0; position: relative; }
    .sg-timeline li { display: flex; gap: 12px; padding-bottom: 18px; position: relative; }
    .sg-timeline li:not(:last-child)::before { content: ''; position: absolute; left: 14px; top: 30px; bottom: 0; width: 2px; background: var(--border-color); }
    .sg-timeline li.done:not(:last-child)::before { background: var(--sg-line); }
    .sg-dot { width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; font-size: .85rem; background: #e9dfe4; }
    .sg-timeline li.pending strong { color: #b59fa8; font-weight: 500; }
    .sg-note { margin-top: 6px; padding: 8px 12px; background: #fff9fc; border-left: 3px solid var(--sg-line); border-radius: 4px; font-size: .875rem; white-space: pre-line; }

    .bg-sg-primary { background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)) !important; color: #fff !important; }

    /* Tab status */
    .sg-tabs { border-bottom: 1px solid var(--border-color); flex-wrap: nowrap; overflow-x: auto; }
    .sg-tabs .nav-link { color: var(--text-secondary); font-weight: 500; font-size: .875rem; white-space: nowrap; border-bottom: 2px solid transparent; }
    .sg-tabs .nav-link:hover { color: var(--color-primary); }
    .sg-tabs .nav-link.active { color: var(--color-primary); border-bottom-color: var(--color-primary); background: transparent; }

    /* Pilihan status (form tindak lanjut) */
    .sg-status-opt { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; font-weight: 500; font-size: .9rem; height: 100%; }
    .sg-status-opt:has(input:checked) { border-color: var(--color-primary); background: var(--sg-soft); }
    .sg-status-opt input { accent-color: var(--color-primary); }

    .sg-side-list { list-style: none; padding: 0; margin: 0; }
    .sg-side-list li { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 10px 0; border-bottom: 1px dashed var(--border-color); }
    .sg-side-list li:last-child { border-bottom: 0; }
    .sg-side-list .min-w-0 { min-width: 0; }
    .sg-flow li { padding-bottom: 12px; }
    @media (min-width: 1200px) { .sg-sticky { position: sticky; top: 80px; } }
</style>
