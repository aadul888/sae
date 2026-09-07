<?php

namespace App\Http\Controllers;

use App\Models\KompetensiKeahlian;
use Illuminate\Http\Request;

class KompetensiKeahlianController extends Controller
{
    private const SORTABLE = ['kode', 'nama', 'bidang_keahlian', 'program_keahlian', 'tahun_berlaku'];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') return redirect()->route('dashboard.' . ($role ?: 'siswa'));

        $q       = trim($request->get('q', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort    = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'kode';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $query = KompetensiKeahlian::query();

        if ($q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('kode', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%")
                    ->orWhere('bidang_keahlian', 'like', "%{$q}%")
                    ->orWhere('program_keahlian', 'like', "%{$q}%");
            });
        }

        $total = (clone $query)->count();
        $list  = $query->orderBy($sort, $sortDir)->paginate($perPage)->appends($request->query());

        return view('dashboard.kompetensi-keahlian', compact(
            'list',
            'total',
            'q',
            'perPage',
            'sort',
            'sortDir'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode'             => 'required|string|max:20|unique:kompetensi_keahlian,kode',
            'nama'             => 'required|string|max:200',
            'bidang_keahlian'  => 'nullable|string|max:200',
            'program_keahlian' => 'nullable|string|max:200',
            'tahun_berlaku'    => 'nullable|integer|min:2000|max:2099',
            'is_active'        => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        KompetensiKeahlian::create($validated);

        return back()->with('success', 'Kompetensi Keahlian berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $item = KompetensiKeahlian::findOrFail($id);

        $validated = $request->validate([
            'kode'             => 'required|string|max:20|unique:kompetensi_keahlian,kode,' . $id,
            'nama'             => 'required|string|max:200',
            'bidang_keahlian'  => 'nullable|string|max:200',
            'program_keahlian' => 'nullable|string|max:200',
            'tahun_berlaku'    => 'nullable|integer|min:2000|max:2099',
            'is_active'        => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $item->update($validated);

        return back()->with('success', 'Kompetensi Keahlian berhasil diperbarui.');
    }

    public function destroy($id)
    {
        KompetensiKeahlian::findOrFail($id)->delete();
        return back()->with('success', 'Kompetensi Keahlian berhasil dihapus.');
    }
}
