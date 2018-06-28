<?php

require_once 'Tools.php';

use ImdbDataFiles\Tools;

$source_file = __DIR__ . '/data/title.akas.tsv';
$target_file = __DIR__ . '/sql/title.akas.sql';

$fh = fopen($source_file, 'r');
$tfh = fopen($target_file, 'w');

$file_start = "
SET NAMES UTF8;

TRUNCATE TABLE `title_akas`;
";

$query_start = "
INSERT INTO `title_akas`
(`title_id`, `ordering`, `title`, `region`, `language`, `types`, `attributes`, `is_original_title`)
VALUES";

$query_end = ";\n\n";

fwrite($tfh, $file_start);
fwrite($tfh, $query_start);

$total = 0;
$batch = 0;
// while ($row = fgetcsv($fh, 0, "\t", '')) {
while ($line = fgets($fh)) {
    $row = explode("\t", trim($line));
    $total++;
    if ($total == 1) { // radek s nazvy sloupcu
        continue;
    }
    $batch++;

    $row = array_map('trim', $row);

    $values = [
        Tools::string($row[0]),
        intval($row[1]),
        Tools::string($row[2]),
        $row[3] == '\N' ? 'NULL' : Tools::string($row[3]),
        $row[4] == '\N' ? 'NULL' : Tools::string($row[4]),
        $row[5] == '\N' ? 'NULL' : Tools::string($row[5]),
        $row[6] == '\N' ? 'NULL' : Tools::string($row[6]),
        $row[7] ? 1 : 0
    ];

    fwrite($tfh, ($batch > 1 ? "," : "")."\n(".join(", ", $values).")");

    if ($total % 100000 == 0) {
        echo $total . " rows written\n";
    }

    if ($total >= 500) {
        break;
    }

    if ($batch >= 5000) {
        fwrite($tfh, $query_end);
        fwrite($tfh, $query_start);
        $batch = 0;
    }
}

fwrite($tfh, $query_end);

exit('DONE');