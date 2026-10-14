<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersuratanTemplate extends Model
{
    use HasFactory;

    protected $table = 'persuratan_templates';

    protected $fillable = [
        'nomor_surat',
        'kode_indeks',
        'template_type',
        'perihal',
        'lampiran',
        'tujuan_penerima',
        'tujuan_lokasi',
        'tanggal_surat',
        'salam_pembuka',
        'paragraf_pembuka',
        'rincian_kegiatan',
        'isi_surat',
        'paragraf_penutup',
        'tembusan',
        'pejabat_ptk_id',
        'pejabat_nama',
        'pejabat_nip',
        'pejabat_jabatan',
        'doc_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal_surat'    => 'date',
        'rincian_kegiatan' => 'array',
    ];

    public function persuratan()
    {
        return $this->belongsTo(Persuratan::class, 'nomor_surat', 'nomor_surat');
    }

    public static function getTemplatePresets(): array
    {
        return [
            'undangan' => [
                'label'   => 'Surat Undangan Resmi / Rapat',
                'icon'    => 'fa-envelope-open-text',
                'indeks'  => 'HM.01.02',
                'perihal' => 'Undangan Rapat Koordinasi dan Pembinaan',
                'salam'   => 'Dengan hormat,',
                'pembuka' => 'Sehubungan dengan agenda evaluasi pembelajaran serta koordinasi program kerja satuan pendidikan, kami mengharapkan kehadiran Bapak/Ibu pada:',
                'penutup' => 'Mengingat pentingnya agenda tersebut, kami sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktu yang telah ditentukan. Atas perhatian dan kerja samanya, kami sampaikan terima kasih.',
            ],
            'pemberitahuan' => [
                'label'   => 'Surat Pemberitahuan / Edaran',
                'icon'    => 'fa-bullhorn',
                'indeks'  => 'HM.01.00',
                'perihal' => 'Pemberitahuan Pelaksanaan Kegiatan Sekolah',
                'salam'   => 'Dengan hormat,',
                'pembuka' => 'Diberitahukan kepada seluruh warga satuan pendidikan, orang tua/wali murid, serta pihak terkait bahwa satuan pendidikan akan menyelenggarakan kegiatan:',
                'penutup' => 'Demikian surat pemberitahuan ini kami sampaikan untuk menjadi maklum dan pedoman bersama.',
            ],
            'pengantar' => [
                'label'   => 'Surat Pengantar Dokumen / Berkas',
                'icon'    => 'fa-paper-plane',
                'indeks'  => 'TU.02.01',
                'perihal' => 'Pengantar Pengiriman Dokumen / Laporan',
                'salam'   => 'Dengan hormat,',
                'pembuka' => 'Bersama surat ini kami kirimkan dokumen/berkas resmi satuan pendidikan sebagaimana rincian berikut untuk dapat dipergunakan sebagaimana mestinya:',
                'penutup' => 'Demikian surat pengantar ini dibuat untuk diketahui dan dipergunakan sebagaimana mestinya.',
            ],
            'rekomendasi' => [
                'label'   => 'Surat Keterangan / Rekomendasi Umum',
                'icon'    => 'fa-award',
                'indeks'  => 'KS.02.25',
                'perihal' => 'Surat Rekomendasi / Keterangan',
                'salam'   => 'Dengan hormat,',
                'pembuka' => 'Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan dan memberikan rekomendasi resmi kepada pihak yang berkepentingan:',
                'penutup' => 'Demikian surat rekomendasi ini kami berikan dengan penuh tanggung jawab untuk dipergunakan sesuai ketentuan yang berlaku.',
            ],
            'kustom' => [
                'label'   => 'Format Bebas / Autocrat Merge',
                'icon'    => 'fa-wand-magic-sparkles',
                'indeks'  => 'HM.01.01',
                'perihal' => 'Surat Resmi Sekolah',
                'salam'   => 'Dengan hormat,',
                'pembuka' => 'Bersama ini kami sampaikan hal-hal sebagai berikut:',
                'penutup' => 'Demikian surat ini kami sampaikan atas perhatian dan kerja samanya diucapkan terima kasih.',
            ],
        ];
    }
}
