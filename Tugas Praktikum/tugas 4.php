<?php
class Alamat {
    public function __construct(private string $jalan, private string $kota) {}
    public function getLengkap(): string { return $this->jalan . ', ' . $this->kota; }
}

class Mahasiswa {
    public function __construct(private string $nama, private Alamat $alamat) {}
    public function getAlamat(): Alamat { return $this->alamat; }
}