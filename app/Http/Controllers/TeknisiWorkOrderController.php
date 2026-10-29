<?php

namespace App\Http\Controllers;

use App\Models\TeknisiWorkOrder;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TeknisiWorkOrderController extends Teknisi\TeknisiBaseController
{
    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $q = $request->input('q');
        $kategori = $request->input('kategori');
        $status = $request->input('status');
        $urgensi = $request->input('urgensi');
        $perPage = (int) $request->input('per_page', 15);

        $query = TeknisiWorkOrder::with('teknisi')->latest('tanggal')->latest('id');
        $this->scopeOwned($query->getQuery(), $context);

        if (!empty($q)) {
            $query->where(function ($b) use ($q) {
                $b->where('nomor_wo', 'like', "%{$q}%")
                  ->orWhere('lokasi_unit', 'like', "%{$q}%")
                  ->orWhere('deskripsi_kerusakan', 'like', "%{$q}%")
                  ->orWhere('pelapor_nama', 'like', "%{$q}%");
            });
        }

        if (!empty($kategori)) {
            $query->where('kategori_perbaikan', $kategori);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($urgensi)) {
            $query->where('tingkat_urgensi', $urgensi);
        }

        $workOrders = $query->paginate($perPage)->withQueryString();

        $stats = [
            'total_antrean' => $this->scopeOwned(TeknisiWorkOrder::where('status', 'antrean')->getQuery(), $context)->count(),
            'total_proses'  => $this->scopeOwned(TeknisiWorkOrder::where('status', 'proses')->getQuery(), $context)->count(),
            'total_selesai' => $this->scopeOwned(TeknisiWorkOrder::where('status', 'selesai')->getQuery(), $context)->count(),
            'total_darurat' => $this->scopeOwned(TeknisiWorkOrder::where('tingkat_urgensi', 'darurat')->whereIn('status', ['antrean', 'proses'])->getQuery(), $context)->count(),
        ];

        $daftarRuang = \Illuminate\Support\Facades\DB::table('sarpras_ruang')->orderBy('gedung', 'asc')->orderBy('nama_ruang', 'asc')->get();
        $daftarAset = \Illuminate\Support\Facades\DB::table('sarpras_aset')->select('id', 'nama_barang', 'kode_aset', 'ruang_id')->orderBy('nama_barang', 'asc')->get();

        return view('dashboard.teknisi.work-order', compact(
            'workOrders',
            'stats',
            'daftarRuang',
            'daftarAset',
            'q',
            'kategori',
            'status',
            'urgensi',
            'perPage'
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $request->validate([
            'tanggal'             => 'required|date',
            'lokasi_unit'         => 'required|string|max:100',
            'kategori_perbaikan'  => 'required|string|max:50',
            'deskripsi_kerusakan' => 'required|string',
            'tingkat_urgensi'     => 'required|in:rendah,normal,darurat',
            'pelapor_nama'        => 'nullable|string|max:100',
            'aset_id'             => 'nullable|integer|exists:sarpras_aset,id',
            'ruang_id'            => 'nullable|integer|exists:sarpras_ruang,id',
        ]);

        $prefix = 'WO-' . date('Ym') . '-';
        $lastOrder = TeknisiWorkOrder::where('nomor_wo', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();
        $nextNum = $lastOrder ? ((int) substr($lastOrder->nomor_wo, -4)) + 1 : 1;
        $nomorWo = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        TeknisiWorkOrder::create([
            'nomor_wo'            => $nomorWo,
            'tanggal'             => $request->tanggal,
            'lokasi_unit'         => $request->lokasi_unit,
            'kategori_perbaikan'  => $request->kategori_perbaikan,
            'deskripsi_kerusakan' => $request->deskripsi_kerusakan,
            'tingkat_urgensi'     => $request->tingkat_urgensi,
            'pelapor_nama'        => $request->pelapor_nama,
            'aset_id'             => $request->aset_id,
            'ruang_id'            => $request->ruang_id,
            'user_id'             => $context['user_id'],
            'status'              => 'antrean',
        ]);

        return redirect()->route('dashboard.teknisi.work-order')
            ->with('success', "Work Order {$nomorWo} berhasil dibuat dan masuk ke antrean.");
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'              => 'required|in:antrean,proses,selesai,menunggu_sparepart',
            'teknisi_ptk_id'      => 'nullable|string',
            'tindakan_perbaikan'  => 'nullable|string',
            'estimasi_biaya_part' => 'nullable|numeric|min:0',
            'tgl_selesai'         => 'nullable|date',
        ]);

        $order = TeknisiWorkOrder::findOrFail($id);
        $order->status = $request->status;
        $order->teknisi_ptk_id = $request->teknisi_ptk_id ?: $order->teknisi_ptk_id;
        $order->tindakan_perbaikan = $request->tindakan_perbaikan;
        $order->estimasi_biaya_part = $request->estimasi_biaya_part ?? 0;

        if ($request->status === 'selesai') {
            $order->tgl_selesai = $request->tgl_selesai ?: Carbon::today()->toDateString();
        }

        $order->save();

        return redirect()->route('dashboard.teknisi.work-order')
            ->with('success', "Status Work Order {$order->nomor_wo} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $order = TeknisiWorkOrder::findOrFail($id);
        $order->delete();

        return redirect()->route('dashboard.teknisi.work-order')
            ->with('success', "Work Order {$order->nomor_wo} berhasil dihapus.");
    }
}
