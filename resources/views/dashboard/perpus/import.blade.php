@extends('layouts.dashboard')

@section('title', 'Import & Scan Katalog')

@section('content')
    <div class="dash-content-inner">
        <div class="dash-banner" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:48px;height:48px;border-radius:var(--radius-lg,12px);background:rgba(6,182,212,0.12);color:#06b6d4;display:flex;align-items:center;justify-content:center;font-size:var(--text-2xl,1.5rem);flex-shrink:0;">
                    <i class="fas fa-file-import"></i>
                </div>
                <div>
                    <h2 style="font-size:var(--text-xl,1.25rem);font-weight:800;color:var(--text-color);margin:0;">Import Katalog &amp; Input Cepat Barcode</h2>
                    <div style="font-size:var(--text-sm,0.8125rem);color:var(--text-muted);margin-top:2px;">
                        Impor massal dari Excel/CSV, atau catat buku baru satuan via scan barcode/QR kamera.
                    </div>
                </div>
            </div>
        </div>

        @include('dashboard.perpus.partials.tab-nav')

        @if (session('error'))
            <div class="alert alert-danger" style="margin-bottom:16px;"><i class="fas fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success" style="margin-bottom:16px;"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
        @endif

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:20px;">
            {{-- A. Import Massal --}}
            <div class="card" style="padding:20px;">
                <h3 style="margin:0 0 6px;font-size:1.05rem;font-weight:800;"><i class="fas fa-file-excel" style="color:#10b981;"></i> Import Massal (Excel / CSV)</h3>
                <p style="color:var(--text-muted);font-size:0.84rem;margin:0 0 14px;">
                    Kolom yang dikenali: kode_buku, barcode, isbn, judul, penulis, penerbit, tahun_terbit, klasifikasi_ddc,
                    kategori, jumlah_eksemplar, lokasi_rak, tgl_pembelian, harga_perolehan, sumber_dana.
                </p>
                <a href="{{ route('dashboard.perpustakaan.import.template') }}" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;height:38px;padding:0 14px;margin-bottom:14px;">
                    <i class="fas fa-download"></i> Unduh Template Excel
                </a>
                <form method="POST" action="{{ route('dashboard.perpustakaan.import.process') }}" enctype="multipart/form-data">
                    @csrf
                    <div style="border:2px dashed var(--border-color);border-radius:12px;padding:22px;text-align:center;margin-bottom:14px;">
                        <i class="fas fa-cloud-arrow-up" style="font-size:2rem;color:var(--text-muted);"></i>
                        <div style="margin-top:8px;">
                            <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required
                                style="font-size:0.85rem;">
                        </div>
                        <small style="color:var(--text-muted);">Format .xlsx, .xls, atau .csv (maks 8 MB)</small>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;height:40px;"><i class="fas fa-upload"></i> Mulai Import</button>
                </form>
            </div>

            {{-- B. Scan Barcode/QR --}}
            <div class="card" style="padding:20px;">
                <h3 style="margin:0 0 6px;font-size:1.05rem;font-weight:800;"><i class="fas fa-barcode" style="color:#06b6d4;"></i> Input Buku Baru via Scan</h3>
                <p style="color:var(--text-muted);font-size:0.84rem;margin:0 0 12px;">
                    Pindai barcode/QR dengan kamera atau scanner. Isi data pembelian &amp; harga aset sekolah.
                </p>

                <div id="scanPreviewWrap" style="display:none;margin-bottom:12px;">
                    <video id="scanVideo" style="width:100%;border-radius:12px;background:#000;" muted playsinline></video>
                    <canvas id="scanCanvas" style="display:none;"></canvas>
                </div>
                <button type="button" id="btnStartScan" class="btn btn-outline" style="width:100%;height:38px;margin-bottom:14px;">
                    <i class="fas fa-camera"></i> Nyalakan Kamera Scanner
                </button>

                <form id="scanBukuForm">
                    @csrf
                    <div style="display:grid;gap:10px;">
                        <div>
                            <label style="font-size:0.78rem;font-weight:700;">Barcode / QR <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="barcode" id="f_barcode" class="form-control" required placeholder="Scan atau ketik barcode...">
                        </div>
                        <div>
                            <label style="font-size:0.78rem;font-weight:700;">Judul Buku <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="judul" class="form-control" required>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <div>
                                <label style="font-size:0.78rem;font-weight:700;">Kode Buku</label>
                                <input type="text" name="kode_buku" class="form-control" placeholder="otomatis bila kosong">
                            </div>
                            <div>
                                <label style="font-size:0.78rem;font-weight:700;">ISBN</label>
                                <input type="text" name="isbn" class="form-control">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <div>
                                <label style="font-size:0.78rem;font-weight:700;">Penulis</label>
                                <input type="text" name="penulis" class="form-control">
                            </div>
                            <div>
                                <label style="font-size:0.78rem;font-weight:700;">Penerbit</label>
                                <input type="text" name="penerbit" class="form-control">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                            <div>
                                <label style="font-size:0.78rem;font-weight:700;">Kategori</label>
                                <input type="text" name="kategori" class="form-control" placeholder="Umum">
                            </div>
                            <div>
                                <label style="font-size:0.78rem;font-weight:700;">Eksemplar</label>
                                <input type="number" name="jumlah_eksemplar" class="form-control" value="1" min="1">
                            </div>
                            <div>
                                <label style="font-size:0.78rem;font-weight:700;">Rak</label>
                                <input type="text" name="lokasi_rak" class="form-control">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <div>
                                <label style="font-size:0.78rem;font-weight:700;">Tanggal Pembelian</label>
                                <input type="date" name="tgl_pembelian" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                            <div>
                                <label style="font-size:0.78rem;font-weight:700;">Harga Aset (Rp)</label>
                                <input type="number" name="harga_perolehan" class="form-control" min="0" value="0">
                            </div>
                        </div>
                        <div>
                            <label style="font-size:0.78rem;font-weight:700;">Sumber Dana</label>
                            <input type="text" name="sumber_dana" class="form-control" placeholder="BOS / Komite / Hibah">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;height:40px;margin-top:14px;">
                        <i class="fas fa-save"></i> Simpan Buku Baru
                    </button>
                </form>
                <div id="scanMsg" style="margin-top:10px;font-size:0.85rem;"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('vendor/jsqr/jsqr.min.js') }}"></script>
        <script>
            (function () {
                const form = document.getElementById('scanBukuForm');
                const msg = document.getElementById('scanMsg');
                const video = document.getElementById('scanVideo');
                const canvas = document.getElementById('scanCanvas');
                const wrap = document.getElementById('scanPreviewWrap');
                const btn = document.getElementById('btnStartScan');
                let stream = null, raf = null, scanning = false;

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    msg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                    try {
                        const res = await fetch('{{ route('dashboard.perpustakaan.koleksi.scan') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                            body: new FormData(form)
                        });
                        const data = await res.json();
                        if (data.success) {
                            msg.innerHTML = '<span style="color:#10b981;"><i class="fas fa-circle-check"></i> ' + data.message + ' (Barcode: ' + data.barcode + ')</span>';
                            form.reset();
                            document.querySelector('[name=jumlah_eksemplar]').value = 1;
                            document.querySelector('[name=tgl_pembelian]').value = '{{ date('Y-m-d') }}';
                            document.getElementById('f_barcode').focus();
                        } else {
                            msg.innerHTML = '<span style="color:#ef4444;"><i class="fas fa-circle-exclamation"></i> ' + (data.message || 'Gagal menyimpan.') + '</span>';
                        }
                    } catch (err) {
                        msg.innerHTML = '<span style="color:#ef4444;">Gagal menyimpan: ' + err.message + '</span>';
                    }
                });

                btn.addEventListener('click', async () => {
                    if (scanning) { stopScan(); return; }
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                        video.srcObject = stream;
                        wrap.style.display = 'block';
                        btn.innerHTML = '<i class="fas fa-stop"></i> Hentikan Kamera';
                        scanning = true;
                        video.play();
                        requestAnimationFrame(tick);
                    } catch (err) {
                        msg.innerHTML = '<span style="color:#ef4444;">Tidak bisa mengakses kamera: ' + err.message + '</span>';
                    }
                });

                function tick() {
                    if (!scanning) return;
                    if (video.readyState === video.HAVE_ENOUGH_DATA) {
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const code = jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
                        if (code && code.data) {
                            document.getElementById('f_barcode').value = code.data;
                            document.querySelector('[name=judul]').focus();
                            msg.innerHTML = '<span style="color:#10b981;">Barcode terbaca: ' + code.data + '</span>';
                            stopScan();
                            return;
                        }
                    }
                    raf = requestAnimationFrame(tick);
                }

                function stopScan() {
                    scanning = false;
                    cancelAnimationFrame(raf);
                    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
                    wrap.style.display = 'none';
                    btn.innerHTML = '<i class="fas fa-camera"></i> Nyalakan Kamera Scanner';
                }
            })();
        </script>
    @endpush
@endsection
