<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeopleSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|required_without:id|string|min:2|max:100',
            'id' => 'nullable|string|max:100',
            'type' => 'required|in:siswa,gtk,all',
        ]);

        $query = trim($validated['q'] ?? '');
        $selectedId = $validated['id'] ?? null;
        $type = $validated['type'];
        $results = collect();

        if (in_array($type, ['siswa', 'all'], true)) {
            $students = DB::table('peserta_didik as pd')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->when($selectedId, fn ($builder) => $builder->where('pd.peserta_didik_id', $selectedId))
                ->when(!$selectedId, function ($builder) use ($query) {
                    $builder->where(function ($search) use ($query) {
                        $search->where('pd.nama', 'like', "%{$query}%")
                            ->orWhere('pd.nisn', 'like', "%{$query}%")
                            ->orWhere('pd.nipd', 'like', "%{$query}%");
                    });
                })
                ->orderBy('pd.nama')
                ->limit($selectedId ? 1 : 20)
                ->get(['pd.peserta_didik_id as id', 'pd.nama', 'pd.nisn', 'pd.nipd', 'rb.nama as context'])
                ->map(fn ($student) => [
                    'id' => $student->id,
                    'nama' => $student->nama,
                    'identifier' => collect(['NISN: ' . ($student->nisn ?: '-'), 'NIPD: ' . ($student->nipd ?: '-')])->implode(' | '),
                    'context' => $student->context ?: 'Peserta Didik',
                    'type' => 'siswa',
                ]);

            $results = $results->concat($students);
        }

        if (in_array($type, ['gtk', 'all'], true)) {
            $gtkList = DB::table('gtk')
                ->when($selectedId, fn ($builder) => $builder->where('ptk_id', $selectedId))
                ->when(!$selectedId, function ($builder) use ($query) {
                    $builder->where(function ($search) use ($query) {
                        $search->where('nama', 'like', "%{$query}%")
                            ->orWhere('nip', 'like', "%{$query}%")
                            ->orWhere('nuptk', 'like', "%{$query}%")
                            ->orWhere('nik', 'like', "%{$query}%");
                    });
                })
                ->orderBy('nama')
                ->limit($selectedId ? 1 : 20)
                ->get(['ptk_id as id', 'nama', 'nip', 'nuptk', 'nik', 'jenis_ptk_id_str'])
                ->map(function ($gtk) {
                    $position = $gtk->jenis_ptk_id_str ?: 'GTK';
                    return [
                        'id' => $gtk->id,
                        'nama' => $gtk->nama,
                        'identifier' => 'NIP/NUPTK/NIK: ' . ($gtk->nip ?: $gtk->nuptk ?: $gtk->nik ?: '-'),
                        'context' => $position,
                        'type' => str_contains(mb_strtolower($position), 'guru') ? 'guru' : 'tendik',
                    ];
                });

            $results = $results->concat($gtkList);
        }

        return response()->json($results->sortBy('nama')->take($selectedId ? 1 : 30)->values());
    }
}
