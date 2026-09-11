<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Pelajar Masal — {{ $rombel->nama }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="{{ asset('css/kartu-pelajar.css') }}">

    <style>
        body {
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a;
        }

        .action-toolbar {
            max-width: 1040px;
            margin: 0 auto 20px;
            background: #ffffff;
            padding: 14px 20px;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .toolbar-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .toolbar-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
        }

        .toolbar-desc {
            font-size: 0.78rem;
            color: #64748b;
        }

        .toolbar-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 0.82rem;
            font-weight: 600;
            color: #1e293b;
            background-color: #ffffff;
            cursor: pointer;
        }

        .btn-print {
            background: #0284c7;
            color: #ffffff;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3);
            transition: background 0.15s;
        }

        .btn-print:hover {
            background: #0369a1;
        }

        /* Print Sheet Container (Portrait) */
        .print-grid {
            max-width: 190mm;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 5mm;
        }

        /* Filter visibility states */
        body.filter-front-only .kp-card-back {
            display: none !important;
        }

        body.filter-back-only .kp-card-front {
            display: none !important;
        }

        @media print {
            body {
                background: none !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .print-grid {
                max-width: 100% !important;
                margin: 0 !important;
                gap: 4mm !important;
                justify-content: flex-start !important;
            }

            .kp-card-pair {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

    <!-- Sticky Toolbar (Non-Printable) -->
    <div class="action-toolbar no-print">
        <div class="toolbar-info">
            <div class="toolbar-title">
                <i class="fas fa-print" style="color: #0284c7;"></i> Cetak Masal Kartu Pelajar: {{ $rombel->nama }}
            </div>
            <div class="toolbar-desc">
                Total {{ count($cards) }} Peserta Didik &bull; Standar CR-80 Portrait (54mm &times; 85.6mm)
            </div>
        </div>

        <div class="toolbar-controls">
            <label for="sideFilter" style="font-size: 0.8rem; font-weight: 700; color: #475569;">Tampilan Sisi:</label>
            <select id="sideFilter" class="filter-select" onchange="changeSideFilter(this.value)">
                <option value="both">Depan &amp; Belakang</option>
                <option value="front-only">Halaman Depan Saja</option>
                <option value="back-only">Halaman Belakang Saja</option>
            </select>

            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Cetak Dokumen
            </button>
        </div>
    </div>

    <!-- Container Cetak Kartu -->
    <div class="print-grid">
        @forelse($cards as $card)
            @include('kartu-pelajar.template', ['card' => $card, 'wrapperClass' => ''])
        @empty
            <div class="no-print" style="text-align: center; padding: 48px; background: #fff; border-radius: 12px; width: 100%;">
                <i class="fas fa-users-slash" style="font-size: 40px; color: #94a3b8; margin-bottom: 12px;"></i>
                <h3 style="font-size: 1.1rem; color: #1e293b;">Tidak Ada Peserta Didik di Rombel Ini</h3>
                <p style="font-size: 0.85rem; color: #64748b;">Belum ada data peserta didik yang terhubung ke rombongan belajar {{ $rombel->nama }}.</p>
            </div>
        @endforelse
    </div>

    <script>
        function changeSideFilter(val) {
            document.body.classList.remove('filter-front-only', 'filter-back-only');
            if (val === 'front-only') {
                document.body.classList.add('filter-front-only');
            } else if (val === 'back-only') {
                document.body.classList.add('filter-back-only');
            }
        }
    </script>
</body>
</html>
