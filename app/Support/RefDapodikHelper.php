<?php

namespace App\Support;

class RefDapodikHelper
{
    public static function getAgama(): array
    {
        return [
            '1' => 'Islam',
            '2' => 'Kristen',
            '3' => 'Katholik',
            '4' => 'Hindu',
            '5' => 'Budha',
            '6' => 'Khonghucu',
            '7' => 'Kepercayaan kpd Tuhan YME',
            '99' => 'Lainnya',
        ];
    }

    public static function getKebutuhanKhusus(): array
    {
        return [
            '0' => 'Tidak ada',
            'A' => 'Netra (A)',
            'B' => 'Rungu (B)',
            'C' => 'Grahita ringan (C)',
            'C1' => 'Grahita sedang (C1)',
            'D' => 'Daksa ringan (D)',
            'D1' => 'Daksa sedang (D1)',
            'E' => 'Laras (E)',
            'F' => 'Wicara (F)',
            'H' => 'Hyperaktif (H)',
            'I' => 'Cerdas istimewa (I)',
            'J' => 'Bakat istimewa (J)',
            'K' => 'Kesulitan belajar (K)',
            'N' => 'Narkoba (N)',
            'O' => 'Indigo (O)',
            'P' => 'Down syndrome (P)',
            'Q' => 'Autis (Q)',
            '99' => 'Lainnya',
        ];
    }

    public static function getJenjangPendidikan(): array
    {
        return [
            '0' => 'Tidak sekolah',
            '1' => 'PAUD',
            '2' => 'TK / sederajat',
            '3' => 'Putus SD',
            '4' => 'SD / sederajat',
            '5' => 'SMP / sederajat',
            '6' => 'SMA / sederajat',
            '7' => 'Paket A',
            '8' => 'Paket B',
            '9' => 'Paket C',
            '10' => 'D1',
            '11' => 'D2',
            '12' => 'D3',
            '13' => 'D4',
            '14' => 'S1',
            '15' => 'S2',
            '16' => 'S2 Terapan',
            '17' => 'S3',
            '18' => 'S3 Terapan',
            '19' => 'Profesi',
            '20' => 'Sp-1',
            '21' => 'Sp-2',
            '22' => 'Non formal',
            '23' => 'Informal',
            '99' => 'Lainnya',
        ];
    }

    public static function getPekerjaan(): array
    {
        return [
            '1' => 'Tidak bekerja',
            '2' => 'Nelayan',
            '3' => 'Petani',
            '4' => 'Peternak',
            '5' => 'PNS/TNI/Polri',
            '6' => 'Aparatur Sipil Negara (ASN/PPPK)',
            '7' => 'Karyawan Swasta',
            '8' => 'Karyawan BUMN',
            '9' => 'Pedagang Kecil',
            '10' => 'Pedagang Besar',
            '11' => 'Perdagangan',
            '12' => 'Wiraswasta',
            '13' => 'Wirausaha',
            '14' => 'Buruh',
            '15' => 'Pensiunan',
            '16' => 'Tenaga Kerja Indonesia (PMI)',
            '97' => 'Tidak dapat diterapkan',
            '98' => 'Sudah Meninggal',
            '99' => 'Lainnya',
        ];
    }

    public static function getPenghasilan(): array
    {
        return [
            '0' => 'Tidak Berpenghasilan',
            '1' => 'Kurang dari Rp. 500,000',
            '2' => 'Rp. 500,000 - Rp. 999,999',
            '3' => 'Rp. 1.000.000 - Rp. 1,999,999',
            '4' => 'Rp. 2.000.000 - Rp. 4.999.999',
            '5' => 'Rp. 5,000,000 - Rp. 20,000,000',
            '6' => 'Lebih dari Rp. 20,000,000',
        ];
    }

    public static function getTempatTinggal(): array
    {
        return [
            '1' => 'Bersama orang tua',
            '2' => 'Wali',
            '3' => 'Kost',
            '4' => 'Asrama',
            '5' => 'Panti asuhan',
            '6' => 'Pesantren',
            '99' => 'Lainnya',
        ];
    }

    public static function getTransportasi(): array
    {
        return [
            '1' => 'Jalan kaki',
            '2' => 'Sepeda',
            '3' => 'Sepeda motor',
            '4' => 'Mobil pribadi',
            '5' => 'Angkutan umum/bus/pete-pete',
            '6' => 'Mobil/bus antar jemput',
            '7' => 'Kereta api',
            '8' => 'Ojek',
            '9' => 'Andong/bendi/sado/dokar/delman/becak',
            '10' => 'Perahu penyeberangan/rakit/getek',
            '11' => 'Kuda',
            '99' => 'Lainnya',
        ];
    }

    public static function getHobi(): array
    {
        return [
            '0' => '(Belum diisi)',
            '1' => 'Belanja',
            '2' => 'Berkemah',
            '3' => 'Berlari',
            '4' => 'Bermain Biola',
            '5' => 'Bermain Bola',
            '6' => 'Bermain Bola Tenis',
            '7' => 'Bermain Boneka',
            '8' => 'Bermain Bulu Tangkis',
            '9' => 'Bermain Gitar',
            '10' => 'Bermain Musik',
            '11' => 'Bermain Piano',
            '12' => 'Berselancar',
            '13' => 'Fitness',
            '14' => 'Fotografi',
            '15' => 'Jogging',
            '16' => 'Kesenian',
            '17' => 'Main Puzzle',
            '18' => 'Makan',
            '19' => 'Memancing',
            '20' => 'Membaca',
            '21' => 'Mendaki',
            '22' => 'Menggambar',
            '23' => 'Menjahit',
            '24' => 'Menulis',
            '25' => 'Mewarnai',
            '26' => 'Olah Raga',
            '27' => 'Traveling',
            '99' => 'Lainnya',
        ];
    }

    public static function getCitaCita(): array
    {
        return [
            '1' => 'Dai/Ustadz',
            '2' => 'Designer',
            '3' => 'Dokter',
            '4' => 'Entertainer / Pekerja Seni',
            '5' => 'Arsitek',
            '6' => 'Astronot',
            '7' => 'Atlet',
            '8' => 'Atlet E-Sport Profesional',
            '9' => 'Atlit Olahraga',
            '10' => 'Bidan',
            '11' => 'Content Creator',
            '12' => 'Guru / Dosen',
            '13' => 'Koki',
            '14' => 'Masinis Kereta Api',
            '15' => 'Pengusaha / Bisnismen',
            '16' => 'Politikus',
            '17' => 'Presiden',
            '18' => 'Seni / Lukis / Artis / Sejenis',
            '19' => 'Penulis',
            '20' => 'Perawat / Suster',
            '21' => 'TNI/Polri',
            '22' => 'Pegawai Negeri Sipil / PNS',
            '23' => 'Pelaut',
            '24' => 'Pemadam Kebakaran',
            '25' => 'Pembalap',
            '26' => 'Pembawa Acara / Master Ceremony',
            '27' => 'Pendata',
            '28' => 'Pengacara',
            '29' => 'Penghafal Al-Qur\'an',
            '30' => 'Pilot',
            '31' => 'Polisi',
            '32' => 'Translator',
            '33' => 'Vloger',
            '34' => 'Wartawan',
            '35' => 'Wiraswasta',
            '99' => 'Lainnya',
        ];
    }

    public static function getJenisPendaftaran(): array
    {
        return [
            '1' => 'Siswa baru (PPDB)',
            '2' => 'Pindahan (Mutasi Masuk)',
            '3' => 'Naik kelas',
            '4' => 'Kembali bersekolah',
        ];
    }

    public static function getJenisPrestasi(): array
    {
        return ['Sains', 'Seni', 'Olahraga', 'Teknologi', 'Keagamaan', 'Lain-lain'];
    }

    public static function getTingkatPrestasi(): array
    {
        return ['Sekolah', 'Kecamatan', 'Kab/kota', 'Propinsi', 'Nasional', 'Internasional', 'Lainnya'];
    }

    public static function getKesejahteraan(): array
    {
        return [
            'Kartu Keluarga Sejahtera (KKS)',
            'Kartu Indonesia Pintar (KIP)',
            'Kartu Indonesia Sehat (KIS)',
            'Program Indonesia Pintar (PIP)',
            'Orang Asli Papua (OAP)',
            'Bantuan Pemerintah Daerah',
            'Lainnya',
        ];
    }
}
