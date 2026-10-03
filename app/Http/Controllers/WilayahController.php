<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WilayahController extends Controller
{
    private const BASE_URL_PRIMARY = 'https://www.emsifa.com/api-wilayah-indonesia/api';
    private const BASE_URL_MIRROR = 'https://kanglerian.github.io/api-wilayah-indonesia/api';
    private const CACHE_TTL = 2592000; // 30 hari

    /**
     * Dapatkan daftar seluruh provinsi di Indonesia
     */
    public function provinces(): JsonResponse
    {
        $data = Cache::remember('wilayah_provinces', self::CACHE_TTL, function () {
            return $this->fetchFromMirrors('provinces.json');
        });

        return response()->json($data ?: []);
    }

    /**
     * Dapatkan daftar kabupaten/kota berdasarkan ID Provinsi
     */
    public function regencies(string $provinceId): JsonResponse
    {
        $cleanId = preg_replace('/[^0-9]/', '', $provinceId);
        if (empty($cleanId)) {
            return response()->json([]);
        }

        $data = Cache::remember("wilayah_regencies_{$cleanId}", self::CACHE_TTL, function () use ($cleanId) {
            return $this->fetchFromMirrors("regencies/{$cleanId}.json");
        });

        return response()->json($data ?: []);
    }

    /**
     * Dapatkan daftar kecamatan berdasarkan ID Kabupaten/Kota
     */
    public function districts(string $regencyId): JsonResponse
    {
        $cleanId = preg_replace('/[^0-9]/', '', $regencyId);
        if (empty($cleanId)) {
            return response()->json([]);
        }

        $data = Cache::remember("wilayah_districts_{$cleanId}", self::CACHE_TTL, function () use ($cleanId) {
            return $this->fetchFromMirrors("districts/{$cleanId}.json");
        });

        return response()->json($data ?: []);
    }

    /**
     * Dapatkan daftar kelurahan/desa berdasarkan ID Kecamatan
     */
    public function villages(string $districtId): JsonResponse
    {
        $cleanId = preg_replace('/[^0-9]/', '', $districtId);
        if (empty($cleanId)) {
            return response()->json([]);
        }

        $data = Cache::remember("wilayah_villages_{$cleanId}", self::CACHE_TTL, function () use ($cleanId) {
            return $this->fetchFromMirrors("villages/{$cleanId}.json");
        });

        return response()->json($data ?: []);
    }

    /**
     * Helper request ke mirror API wilayah dengan failover & timeout aman
     */
    private function fetchFromMirrors(string $endpoint): array
    {
        // 1. Coba mirror utama (emsifa)
        try {
            $res = Http::timeout(4)->get(self::BASE_URL_PRIMARY . '/' . $endpoint);
            if ($res->successful()) {
                $json = $res->json();
                if (is_array($json) && count($json) > 0) {
                    return $json;
                }
            }
        } catch (\Throwable $e) {}

        // 2. Coba mirror cadangan (github.io)
        try {
            $res = Http::timeout(4)->get(self::BASE_URL_MIRROR . '/' . $endpoint);
            if ($res->successful()) {
                $json = $res->json();
                if (is_array($json) && count($json) > 0) {
                    return $json;
                }
            }
        } catch (\Throwable $e) {}

        return [];
    }
}
