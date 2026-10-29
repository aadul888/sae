<?php

namespace App\Http\Controllers\Teknisi;

use App\Support\Table;
use App\Support\TeknisiModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Hapus massal (bulk) untuk seluruh datatable modul Teknisi.
 *
 * Satu endpoint melayani 9 modul lapangan (registry), 5 modul TUPOKSI,
 * Work Order, dan Pemeliharaan. Baris yang bukan milik pengguna (bagi
 * teknisi non-koordinator) dilewati, bukan dihapus.
 */
class TeknisiBulkDeleteController extends TeknisiBaseController
{
    /** Tabel di luar registry: [nama tabel, kolom penanda ptk (null bila tak ada)]. */
    private const EXTRA = [
        'area-tugas'       => ['teknisi_area_tugas', 'petugas_ptk_id'],
        'kebersihan-ruang' => ['teknisi_kebersihan_ruang', 'petugas_ptk_id'],
        'kebersihan-rapat' => ['teknisi_kebersihan_rapat', 'petugas_ptk_id'],
        'sarana-rapat'     => ['teknisi_sarana_rapat', 'petugas_ptk_id'],
        'jurnal-perbaikan' => ['teknisi_jurnal_perbaikan', 'petugas_ptk_id'],
        'work-order'       => ['teknisi_work_order', 'teknisi_ptk_id'],
        'pemeliharaan'     => ['teknisi_pemeliharaan', null],
    ];

    public function destroy(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $request->validate([
            'modul' => 'required|string',
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required',
        ]);

        [$table, $ptkColumn] = $this->resolve($data['modul']);
        if (!$table || !Table::exists($table)) {
            return response()->json(['status' => 'error', 'message' => 'Modul tidak dikenal.'], 404);
        }

        $rows = DB::table($table)->whereIn('id', array_values(array_unique($data['ids'])))->get();

        $deleted = 0;
        foreach ($rows as $row) {
            if (!$this->ownsRow($row, $context, $ptkColumn)) {
                continue;
            }
            DB::table($table)->where('id', $row->id)->delete();
            $deleted++;
        }

        $skipped = $rows->count() - $deleted;

        return response()->json([
            'status'  => 'success',
            'message' => $deleted . ' data dihapus'
                . ($skipped > 0 ? ", {$skipped} dilewati (bukan milik Anda)." : '.'),
            'count'   => $deleted,
            'skipped' => $skipped,
        ]);
    }

    /** @return array{0:?string,1:?string} */
    private function resolve(string $modul): array
    {
        if (isset(self::EXTRA[$modul])) {
            return self::EXTRA[$modul];
        }

        if (in_array($modul, TeknisiModuleRegistry::keys(), true)) {
            return [TeknisiModuleRegistry::get($modul)['table'], 'petugas_ptk_id'];
        }

        return [null, null];
    }
}
