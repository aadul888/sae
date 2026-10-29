{{--
    Form generik modul Teknisi Lapangan.
    Kolom & tipe diambil dari TeknisiModuleRegistry::get($modul)['columns'].
--}}
@php
    $sources = $sources ?? [];
    $grid = fn($conf) => !empty($conf['wide']) ? 'grid-column: 1 / -1;' : '';
@endphp

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
    @foreach ($spec['columns'] as $col => $conf)
        @php $type = $conf['type'] ?? 'string'; @endphp
        @continue($type === 'hidden')
        {{-- Kolom turunan (mis. Petugas pada area master) diisi sistem dari
             master Sarpras, jadi tidak ditampilkan sebagai isian. --}}
        @continue(!empty($conf['derived']))

        <div style="{{ $grid($conf) }}">
            <label class="form-label" style="font-weight:600;">
                {{ $conf['label'] }}
                @if (!empty($conf['required']))
                    <span style="color: var(--danger, #ef4444);">*</span>
                @endif
            </label>

            @if ($type === 'select')
                @if (!empty($conf['source']))
                    <select name="{{ $col }}" class="form-control" {{ !empty($conf['required']) ? 'required' : '' }}>
                        <option value="">— Pilih {{ $conf['label'] }} —</option>
                        @foreach ($sources[$conf['source']] ?? [] as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="{{ $col }}" class="form-control" {{ !empty($conf['required']) ? 'required' : '' }}>
                        @foreach ($conf['options'] ?? [] as $k => $v)
                            <option value="{{ $k }}" {{ ($conf['default'] ?? null) === $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                @endif
            @elseif ($type === 'text')
                <textarea name="{{ $col }}" class="form-control" rows="2" {{ !empty($conf['required']) ? 'required' : '' }}></textarea>
            @elseif ($type === 'date')
                <input type="date" name="{{ $col }}" class="form-control" {{ !empty($conf['required']) ? 'required' : '' }}
                    value="{{ ($conf['default'] ?? null) === 'today' ? now()->toDateString() : '' }}">
            @elseif ($type === 'time')
                <input type="time" name="{{ $col }}" class="form-control">
            @elseif (in_array($type, ['int', 'decimal'], true))
                <input type="number" step="{{ $type === 'decimal' ? '0.01' : '1' }}" name="{{ $col }}" class="form-control"
                    value="{{ $conf['default'] ?? '' }}">
            @elseif (!empty($conf['people']))
                <input type="search" name="{{ $col }}" class="form-control" autocomplete="off"
                    placeholder="Cari GTK atau ketik nama..."
                    data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="gtk"
                    data-people-target="#ptkField{{ $col }}">
                <input type="hidden" name="petugas_ptk_id" id="ptkField{{ $col }}">
            @else
                <input type="text" name="{{ $col }}" class="form-control" {{ !empty($conf['required']) ? 'required' : '' }}
                    @if (!empty($conf['list'])) list="dlModul{{ $col }}" @endif>
                @if (!empty($conf['list']) && !empty($sources[$conf['list']]))
                    <datalist id="dlModul{{ $col }}">
                        @foreach ($sources[$conf['list']] as $opt)
                            <option value="{{ $opt['label'] }}"></option>
                        @endforeach
                    </datalist>
                @endif
            @endif
        </div>
    @endforeach
</div>
