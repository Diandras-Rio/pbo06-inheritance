<?php
require_once __DIR__ . '/MahasiswaReguler.php';
$m = new MahasiswaReguler('23001', 'Andi', 'FST', 'Informatika', 'SMA 1', 4, 'Coding', 'Islam', 5000000);
echo $m->nim;