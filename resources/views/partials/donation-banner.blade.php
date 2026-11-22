@php
    // Banner iklan donasi dikendalikan pusat (sae-core). Tampil hanya bila
    // sekolah belum donatur dan admin pusat mengaktifkan iklan.
    $saeShowAds = \App\Services\CoreControlService::showAds();
@endphp
@if ($saeShowAds)
    <div class="sae-donasi-banner"
        style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:12px 20px 0;padding:10px 16px;border-radius:var(--radius-lg,12px);background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.4);color:var(--text-color);font-size:var(--text-sm,0.8125rem);">
        <span style="display:flex;align-items:center;gap:8px;font-weight:600;">
            <i class="fas fa-heart" style="color:var(--primary,#3b82f6);"></i>
            Dukung kelangsungan SAE dengan donasi. Iklan ini hilang otomatis setelah donasi tercatat.
        </span>
        <a href="https://wa.me/6285860605060" target="_blank" rel="noopener"
            class="btn btn-primary btn-sm"
            style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;text-decoration:none;">
            <i class="fab fa-whatsapp"></i> Donasi Sekarang
        </a>
    </div>
@endif
