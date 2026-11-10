<?php
declare(strict_types=1);
require_once __DIR__ . '/MahasiswaReguler.php';
require_once __DIR__ . '/MahasiswaBeasiswa.php';

class DataMahasiswa {
    /** @var array<string, Mahasiswa> */
    private array $data = [];

    public function __construct() {
        if (isset($_SESSION['data_mhs_tugas3_p6'])) {
            $this->data = $_SESSION['data_mhs_tugas3_p6'];
        }
    }

    private function simpanKeSession(): void {
        $_SESSION['data_mhs_tugas3_p6'] = $this->data;
    }

    // Menerima Mahasiswa apa pun (Reguler/Beasiswa) karena keduanya turunan Mahasiswa
    public function tambah(Mahasiswa $m): void {
        $nim = $m->getNim();
        if (isset($this->data[$nim])) {
            throw new InvalidArgumentException("NIM '{$nim}' sudah terdaftar!");
        }
        $this->data[$nim] = $m;
        $this->simpanKeSession();
    }

    // NIM tidak boleh berubah; jenis boleh berubah (objek diganti pada NIM yang sama)
    public function ubah(string $nimLama, Mahasiswa $m): void {
        if (!isset($this->data[$nimLama])) {
            throw new InvalidArgumentException("Data yang diedit tidak ditemukan");
        }
        if ($m->getNim() !== $nimLama) {
            throw new InvalidArgumentException("NIM tidak boleh diubah saat edit");
        }
        $this->data[$nimLama] = $m;
        $this->simpanKeSession();
    }

    public function hapus(string $nim): void {
        if (isset($this->data[$nim])) {
            unset($this->data[$nim]);
            $this->simpanKeSession();
        }
    }

    public function cari(string $nim): ?Mahasiswa {
        return $this->data[$nim] ?? null;
    }

    // Pencarian berdasarkan kata kunci pada NIM, nama, atau prodi
    public function cariKeyword(string $kata): array {
        $kata = mb_strtolower(trim($kata));
        if ($kata === '') return $this->data;
        return array_filter($this->data, function (Mahasiswa $m) use ($kata): bool {
            return str_contains(mb_strtolower($m->getNim()), $kata)
                || str_contains(mb_strtolower($m->getNama()), $kata)
                || str_contains(mb_strtolower($m->getProdi()), $kata);
        });
    }

    public function getAll(): array {
        return $this->data;
    }
}