<?php
declare(strict_types=1);
// Class dimuat SEBELUM session_start agar objek di session bisa dibentuk ulang
require_once __DIR__ . '/DataMahasiswa.php';
session_start();

function aman(mixed $data): string {
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

$repo = new DataMahasiswa();
$error = '';
$sukses = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$editData = null;
$modeEdit = false;
$kataCari = trim((string)($_GET['q'] ?? ''));

if (isset($_GET['edit'])) {
    $editData = $repo->cari((string)$_GET['edit']);
    $modeEdit = $editData !== null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string)($_POST['aksi'] ?? 'simpan');

    if ($aksi === 'hapus') {
        $repo->hapus(trim((string)($_POST['nim'] ?? '')));
        $_SESSION['flash'] = 'Data berhasil dihapus!';
        header('Location: index.php');
        exit;
    }

    $isEditPost = ($_POST['is_edit'] ?? '0') === '1';

    try {
        $semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT);
        $ukt      = filter_var($_POST['ukt'] ?? null, FILTER_VALIDATE_INT);
        if ($semester === false || $ukt === false) {
            throw new InvalidArgumentException('Semester dan UKT harus bilangan bulat');
        }

        $nim          = trim((string)($_POST['nim'] ?? ''));
        $nama         = trim((string)($_POST['nama'] ?? ''));
        $fakultas     = trim((string)($_POST['fakultas'] ?? ''));
        $prodi        = trim((string)($_POST['prodi'] ?? ''));
        $asalSekolah  = trim((string)($_POST['asal_sekolah'] ?? ''));
        $hobi         = trim((string)($_POST['hobi'] ?? ''));
        $agama        = trim((string)($_POST['agama'] ?? ''));
        $jenis        = (string)($_POST['jenis'] ?? '');

        if ($jenis === 'reguler') {
            $m = new MahasiswaReguler($nim, $nama, $fakultas, $prodi, $asalSekolah,
                                      $semester, $hobi, $agama, $ukt);
        } elseif ($jenis === 'beasiswa') {
            $potongan = filter_var($_POST['potongan'] ?? null, FILTER_VALIDATE_INT);
            if ($potongan === false) {
                throw new InvalidArgumentException('Potongan harus bilangan bulat 0-100');
            }
            $m = new MahasiswaBeasiswa($nim, $nama, $fakultas, $prodi, $asalSekolah,
                                       $semester, $hobi, $agama, $ukt, $potongan);
        } else {
            throw new InvalidArgumentException('Jenis mahasiswa tidak dikenal');
        }

        if ($isEditPost) {
            $repo->ubah(trim((string)($_POST['nim_lama'] ?? '')), $m);
            $_SESSION['flash'] = 'Data berhasil diupdate!';
        } else {
            $repo->tambah($m);
            $_SESSION['flash'] = 'Data berhasil ditambah!';
        }
        header('Location: index.php');   // PRG: refresh tidak mengirim ulang form
        exit;

    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
        $modeEdit = $isEditPost;
    }
}

// Nilai awal form: dari data yang diedit, atau dari POST (saat error), atau kosong
$f = [
    'nim' => '', 'nama' => '', 'fakultas' => '', 'prodi' => '', 'asal_sekolah' => '',
    'semester' => '', 'hobi' => '', 'agama' => '', 'jenis' => 'reguler',
    'ukt' => '', 'potongan' => '0',
];
if ($editData !== null) {
    $f = [
        'nim' => $editData->getNim(), 'nama' => $editData->getNama(),
        'fakultas' => $editData->getFakultas(), 'prodi' => $editData->getProdi(),
        'asal_sekolah' => $editData->getAsalSekolah(), 'semester' => $editData->getSemester(),
        'hobi' => $editData->getHobi(), 'agama' => $editData->getAgama(),
        'jenis' => strtolower($editData->getJenis()),
        'ukt' => $editData->getUkt(), 'potongan' => $editData instanceof MahasiswaBeasiswa ? $editData->getPotonganPersen() : 0,
    ];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $v) {
        $f[$k] = (string)($_POST[$k] ?? $v);
    }
}
$nimLama = $modeEdit ? ($editData?->getNim() ?? (string)($_POST['nim_lama'] ?? '')) : '';

$semuaData = $repo->cariKeyword($kataCari);
$totalData = count($repo->getAll());
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Mahasiswa - Inheritance</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }
        .card {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            font-size: 1.4em;
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; color: #555; }
        input[type="text"], input[type="number"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: inherit;
        }
        input[readonly] { background-color: #e9ecef; cursor: not-allowed; color: #6c757d; }
        
        .btn-group { display: flex; gap: 10px; margin-top: 20px; }
        button, .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1em;
            text-decoration: none;
            text-align: center;
        }
        .btn-primary { background-color: #3498db; color: white; width: 100%; }
        .btn-primary:hover { background-color: #2980b9; }
        .btn-danger { background-color: #e74c3c; color: white; }
        .btn-danger:hover { background-color: #c0392b; }
        .btn-secondary { background-color: #95a5a6; color: white; width: 100%; display: inline-block;}
        .btn-secondary:hover { background-color: #7f8c8d; }
        .btn-sm { padding: 6px 10px; font-size: 0.85em; margin-right: 2px;}

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background-color: #f8f9fa; color: #2c3e50; }
        tr:hover { background-color: #f1f4f6; }
        
        .table-responsive { overflow-x: auto; }
        .search-bar { display: flex; gap: 8px; margin-bottom: 10px; }
        .search-bar input { flex: 1; }
        .search-bar button, .search-bar .btn { width: auto; }
        .inline { display: inline; margin: 0; }
        .hidden { display: none; }
        .badge { padding: 3px 8px; border-radius: 10px; font-size: 0.85em; font-weight: 600; }
        .badge-reguler { background: #e8f1fb; color: #1f5f9e; }
        .badge-beasiswa { background: #e6f6ea; color: #1e7a3a; }

        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2><?= $modeEdit ? 'Edit Mahasiswa' : 'Tambah Mahasiswa' ?></h2>

            <?php if ($error): ?> <div class="alert alert-error"><?= aman($error) ?></div> <?php endif; ?>
            <?php if ($sukses): ?> <div class="alert alert-success"><?= aman($sukses) ?></div> <?php endif; ?>

            <form method="POST" action="index.php">
                <input type="hidden" name="is_edit" value="<?= $modeEdit ? '1' : '0' ?>">
                <input type="hidden" name="nim_lama" value="<?= aman($nimLama) ?>">

                <div class="form-group">
                    <label>Jenis Mahasiswa</label>
                    <select name="jenis" id="jenis">
                        <option value="reguler" <?= $f['jenis'] === 'reguler' ? 'selected' : '' ?>>Reguler</option>
                        <option value="beasiswa" <?= $f['jenis'] === 'beasiswa' ? 'selected' : '' ?>>Beasiswa</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>NIM</label>
                    <input type="text" name="nim" value="<?= aman($f['nim']) ?>"
                        <?= $modeEdit ? 'readonly' : '' ?> placeholder="Masukkan NIM">
                </div>

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?= aman($f['nama']) ?>" placeholder="Masukkan Nama">
                </div>

                <div class="form-group">
                    <label>Fakultas</label>
                    <input type="text" name="fakultas" value="<?= aman($f['fakultas']) ?>" placeholder="Fakultas">
                </div>

                <div class="form-group">
                    <label>Program Studi</label>
                    <input type="text" name="prodi" value="<?= aman($f['prodi']) ?>" placeholder="Program Studi">
                </div>

                <div class="form-group">
                    <label>Semester (1-14)</label>
                    <input type="number" name="semester" value="<?= aman($f['semester']) ?>" placeholder="1">
                </div>

                <div class="form-group">
                    <label>Asal Sekolah</label>
                    <input type="text" name="asal_sekolah" value="<?= aman($f['asal_sekolah']) ?>" placeholder="Nama SMA/SMK">
                </div>

                <div class="form-group">
                    <label>Hobi</label>
                    <input type="text" name="hobi" value="<?= aman($f['hobi']) ?>" placeholder="Contoh: Membaca, Coding">
                </div>

                <div class="form-group">
                    <label>Agama</label>
                    <select name="agama">
                        <option value="">-- Pilih Agama --</option>
                        <?php foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as $agm): ?>
                            <option value="<?= $agm ?>" <?= $f['agama'] === $agm ? 'selected' : '' ?>><?= $agm ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>UKT (Rp)</label>
                    <input type="number" name="ukt" value="<?= aman($f['ukt']) ?>" placeholder="5000000">
                </div>

                <div class="form-group <?= $f['jenis'] === 'beasiswa' ? '' : 'hidden' ?>" id="grup-potongan">
                    <label>Potongan Beasiswa (0-100 %)</label>
                    <input type="number" name="potongan" value="<?= aman($f['potongan']) ?>" placeholder="60">
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary"><?= $modeEdit ? 'Update Data' : 'Simpan Data' ?></button>
                    <?php if ($modeEdit): ?>
                        <a href="index.php" class="btn btn-secondary" style="box-sizing: border-box;">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Data Mahasiswa (<?= count($semuaData) ?><?= $kataCari !== '' ? ' dari ' . $totalData : '' ?>)</h2>

            <form method="GET" action="index.php" class="search-bar">
                <input type="text" name="q" value="<?= aman($kataCari) ?>" placeholder="Cari NIM, nama, atau prodi">
                <button type="submit" class="btn btn-primary">Cari</button>
                <?php if ($kataCari !== ''): ?><a href="index.php" class="btn btn-secondary">Reset</a><?php endif; ?>
            </form>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Prodi</th>
                            <th>Smt</th>
                            <th>Jenis</th>
                            <th>Biaya Akhir</th>
                            <th style="min-width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($semuaData)): ?>
                            <tr><td colspan="7" style="text-align:center; color:#7f8c8d;">
                                <?= $kataCari !== '' ? 'Data tidak ditemukan.' : 'Belum ada data tersimpan.' ?>
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($semuaData as $m): ?>
                            <tr>
                                <td><strong><?= aman($m->getNim()) ?></strong></td>
                                <td><?= aman($m->getNama()) ?></td>
                                <td><?= aman($m->getProdi()) ?></td>
                                <td><?= aman($m->getSemester()) ?></td>
                                <td><span class="badge badge-<?= aman(strtolower($m->getJenis())) ?>"><?= aman($m->getJenis()) ?></span></td>
                                <td>Rp<?= aman(number_format($m->hitungBiaya(), 0, ',', '.')) ?></td>
                                <td>
                                    <a href="index.php?edit=<?= urlencode($m->getNim()) ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <form method="POST" action="index.php" class="inline"
                                          onsubmit="return confirm('Yakin ingin menghapus data <?= aman($m->getNama()) ?>?')">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="nim" value="<?= aman($m->getNim()) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Tampilkan input potongan hanya untuk jenis Beasiswa
        const jenis = document.getElementById('jenis');
        const grup = document.getElementById('grup-potongan');
        jenis.addEventListener('change', () => {
            grup.classList.toggle('hidden', jenis.value !== 'beasiswa');
        });
    </script>
</body>
</html>