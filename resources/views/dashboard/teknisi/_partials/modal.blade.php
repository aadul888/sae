{{--
    Modal generik CRUD modul Teknisi & IT (create + edit dalam satu form).

    Konvensi: form diisi via tombol `data-teknisi-form` pada baris tabel.
    Atribut `data-field-<nama>` dipetakan ke input `[name="<nama>"]`.

    Variabel:
      $modalId (default 'modalCreate')
      $modalTitle, $modalAction (route store), $updateMethod (default POST)
      $modalBody — HTML isi form (grid/field) mentah
      $editFlagId — id input hidden _method (opsional)
--}}
<div id="{{ $modalId ?? 'modalCreate' }}" class="modal-backdrop"
    style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
    <div class="modal-card"
        style="background: var(--card-bg, #fff); border-radius: var(--radius-lg, 12px); max-width: 720px; width: 100%; max-height: 92vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);">
        <div
            style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: var(--card-bg, #fff); z-index: 2;">
            <h3 style="margin: 0; font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-heading);">
                <i class="fas fa-pen-to-square" style="color: var(--primary, #3b82f6); margin-right: 8px;"></i>
                <span data-modal-title>{{ $modalTitle ?? 'Tambah Data' }}</span>
            </h3>
            <button type="button" data-modal-close="{{ $modalId ?? 'modalCreate' }}"
                style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form method="POST" action="{{ $modalAction ?? '#' }}" data-modal-form>
            @csrf
            <input type="hidden" name="_method" value="POST" data-modal-method>
            <div style="padding: 20px;">
                @if (!empty($modalBodyView))
                    @include($modalBodyView)
                @else
                    {!! $modalBody ?? '' !!}
                @endif
            </div>
            <div
                style="display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px; border-top: 1px solid var(--border-color); position: sticky; bottom: 0; background: var(--card-bg, #fff);">
                <button type="button" class="btn btn-secondary" data-modal-close="{{ $modalId ?? 'modalCreate' }}">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
