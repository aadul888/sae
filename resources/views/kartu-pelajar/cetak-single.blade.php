<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Pelajar — {{ $card['nama'] }}</title>
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
            padding: 24px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a;
        }

        .action-bar {
            max-width: 820px;
            margin: 0 auto 24px;
            background: #ffffff;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
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
            transition: background 0.15s;
        }

        .btn-print:hover {
            background: #0369a1;
        }

        .btn-back {
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .filter-select {
            padding: 7px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 0.82rem;
            font-weight: 600;
            color: #1e293b;
            background-color: #ffffff;
            cursor: pointer;
        }

        .print-canvas {
            display: flex;
            justify-content: center;
            align-items: center;
        }

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
            .action-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="action-bar no-print">
        <a href="javascript:window.close();" class="btn-back"><i class="fas fa-arrow-left"></i> Tutup Jendela</a>
        
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <label for="sideFilter" style="font-size: 0.8rem; font-weight: 700; color: #475569;">Sisi Kartu:</label>
            <select id="sideFilter" class="filter-select" onchange="changeSideFilter(this.value)">
                <option value="both">Depan &amp; Belakang</option>
                <option value="front-only">Depan Saja</option>
                <option value="back-only">Belakang Saja</option>
            </select>

            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Cetak Kartu Sekarang
            </button>
        </div>
    </div>

    <div class="print-canvas">
        @include('kartu-pelajar.template', ['card' => $card])
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
