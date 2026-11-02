<?php

namespace Tests\Unit;

use App\Http\Controllers\GtkNonAsnController;
use PHPUnit\Framework\TestCase;

/**
 * Password awal akun GTK Non-ASN = tanggal lahir format TTTTBBHH (YYYYMMDD).
 */
class GtkNonAsnPasswordTest extends TestCase
{
    public function test_berbagai_format_tanggal_dikonversi_ke_yyyymmdd(): void
    {
        $this->assertSame('19900218', GtkNonAsnController::passwordDariTanggalLahir('1990-02-18'));
        $this->assertSame('19900208', GtkNonAsnController::passwordDariTanggalLahir('1990-2-8'));
        $this->assertSame('19900218', GtkNonAsnController::passwordDariTanggalLahir('18/02/1990'));
        $this->assertSame('19900218', GtkNonAsnController::passwordDariTanggalLahir('18-02-1990'));
        $this->assertSame('19900218', GtkNonAsnController::passwordDariTanggalLahir('1990-02-18 00:00:00'));
    }

    public function test_serial_date_excel_dikonversi(): void
    {
        // 32922 = 1990-02-18 pada serial date Excel.
        $this->assertSame('19900218', GtkNonAsnController::passwordDariTanggalLahir(32922));
    }

    public function test_tanggal_kosong_atau_tidak_valid_menghasilkan_null(): void
    {
        $this->assertNull(GtkNonAsnController::passwordDariTanggalLahir(''));
        $this->assertNull(GtkNonAsnController::passwordDariTanggalLahir(null));
        $this->assertNull(GtkNonAsnController::passwordDariTanggalLahir('bukan tanggal'));
        $this->assertNull(GtkNonAsnController::passwordDariTanggalLahir('1990-13-40'));
    }
}
