@extends('layouts.cetak')

@section('title', 'Cetak Peta & Denah Sekolah — ' . ($peta->nama ?? ''))

@section('content')
    @php
        $vb = explode(' ', $peta->view_box ?? '0 0 1000 700');
        $vbW = (int) ($vb[2] ?? 1000);
        $vbH = (int) ($vb[3] ?? 700);
        $kopW = 240;
    @endphp

    <div class="sheet" style="padding: 8mm;">
        <div style="display:flex; flex-direction:column; gap:8px;">

            {{-- Kanvas denah --}}
            <div style="border:2px solid #000;">
                <svg viewBox="0 0 {{ $vbW + $kopW }} {{ $vbH }}" width="100%" style="display:block; background:#fff;"
                    xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="cetakHatch" width="10" height="10" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                            <line x1="0" y1="0" x2="0" y2="10" stroke="#3b82f6" stroke-width="3" opacity="0.5" />
                        </pattern>
                    </defs>

                    {{-- Latar citra (bila ada) --}}
                    @if ($peta->latar_url)
                        <image href="{{ $peta->latar_url }}" x="0" y="0" width="{{ $vbW }}" height="{{ $vbH }}"
                            opacity="{{ ($peta->latar_opacity ?? 70) / 100 }}" preserveAspectRatio="none" />
                    @endif

                    {{-- Blok area --}}
                    @foreach ($areas as $a)
                        @php
                            $meta = $statuses[$a->status_visual] ?? ['warna' => '#10b981'];
                            $isHatch = $a->status_visual === 'siap_bangun';
                            $fill = $isHatch ? 'url(#cetakHatch)' : $meta['warna'];
                        @endphp
                        <rect x="{{ $a->x ?? 0 }}" y="{{ $a->y ?? 0 }}" width="{{ $a->w ?? 0 }}" height="{{ $a->h ?? 0 }}"
                            fill="{{ $fill }}" fill-opacity="{{ $isHatch ? 0.35 : 0.55 }}"
                            stroke="{{ $a->status_visual === 'rusak_2026' ? '#7f1d1d' : '#334155' }}"
                            stroke-width="1" @if ($a->status_visual === 'siap_bangun') stroke-dasharray="6 4" @endif />
                        <text x="{{ ($a->x ?? 0) + ($a->w ?? 0) / 2 }}" y="{{ ($a->y ?? 0) + ($a->h ?? 0) / 2 }}"
                            font-size="9" font-weight="700" text-anchor="middle" dominant-baseline="middle"
                            fill="#0f172a">{{ $a->kode_area }}</text>
                    @endforeach

                    {{-- Bingkai + garis kop --}}
                    <rect x="0.5" y="0.5" width="{{ $vbW + $kopW - 1 }}" height="{{ $vbH - 1 }}" fill="none" stroke="#000" stroke-width="2" />
                    <line x1="{{ $vbW }}" y1="0" x2="{{ $vbW }}" y2="{{ $vbH }}" stroke="#000" stroke-width="2" />

                    {{-- Kop dokumen --}}
                    <g transform="translate({{ $vbW }},0)">
                        <text x="12" y="26" font-size="14" font-weight="800" fill="#0f172a">PEMETAAN SARANA &amp;</text>
                        <text x="12" y="44" font-size="14" font-weight="800" fill="#0f172a">PRASARANA (SARPRAS)</text>
                        <text x="12" y="64" font-size="11" fill="#334155">{{ $peta->nama ?? 'SMK Negeri 1 Pagelaran' }}</text>
                        <text x="12" y="80" font-size="11" fill="#334155">{{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}</text>
                        <text x="12" y="96" font-size="11" fill="#334155">Skala {{ $peta->skala ?? '1:125' }}</text>
                        <line x1="12" y1="106" x2="{{ $kopW - 12 }}" y2="106" stroke="#cbd5e1" stroke-width="1" />
                        <text x="12" y="126" font-size="12" font-weight="700" fill="#0f172a">LEGENDA WARNA</text>

                        @php $ly = 144; @endphp
                        @foreach ($statuses as $key => $meta)
                            <rect x="12" y="{{ $ly }}" width="18" height="13" fill="{{ $meta['warna'] }}"
                                fill-opacity="0.6" stroke="#0f172a" stroke-width="0.8"
                                @if ($key === 'siap_bangun') stroke-dasharray="4 3" @endif />
                            <text x="38" y="{{ $ly + 11 }}" font-size="11" fill="#0f172a">{{ $meta['label'] }}</text>
                            @php $ly += 19; @endphp
                        @endforeach

                        {{-- Kompas U-T-S-B --}}
                        <g transform="translate({{ $kopW / 2 }},{{ $vbH - 90 }})">
                            <circle r="30" fill="none" stroke="#0f172a" stroke-width="1.2" />
                            <line x1="0" y1="24" x2="0" y2="-24" stroke="#dc2626" stroke-width="2" />
                            <line x1="-24" y1="0" x2="24" y2="0" stroke="#0f172a" stroke-width="1" />
                            <text x="-4" y="-34" font-size="12" font-weight="700" fill="#dc2626">U</text>
                            <text x="-4" y="42" font-size="11" fill="#0f172a">S</text>
                            <text x="34" y="4" font-size="11" fill="#0f172a">T</text>
                            <text x="-42" y="4" font-size="11" fill="#0f172a">B</text>
                        </g>

                        <text x="12" y="{{ $vbH - 16 }}" font-size="10" fill="#64748b">Revisi {{ $peta->revisi_ke ?? 0 }} — dibuat {{ now()->translatedFormat('d M Y') }}</text>
                    </g>
                </svg>
            </div>

            {{-- Rekap jumlah --}}
            <table style="width:100%; border-collapse:collapse; font-size:9pt;" border="1">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th style="padding:4px;">Total Area</th>
                        @foreach ($statuses as $meta)
                            <th style="padding:4px;">{{ $meta['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr style="text-align:center;">
                        <td style="padding:4px; font-weight:700;">{{ $stat['total'] ?? 0 }}</td>
                        @foreach ($statuses as $key => $meta)
                            <td style="padding:4px;">{{ $stat[$key] ?? 0 }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('toolbar_middle')
    <span style="font-size:9pt; color:#475569;">Gunakan "Cetak Dokumen" lalu pilih "Save as PDF" pada dialog cetak.</span>
@endsection
