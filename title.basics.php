<?php

require_once 'Tools.php';

use ImdbDataFiles\Tools;

$source_file = __DIR__ . '/data/title.basics.tsv';
$target_file = __DIR__ . '/sql/title.basics.sql';

$fh = fopen($source_file, 'r');
$tfh = fopen($target_file, 'w');

$file_start = "
SET NAMES UTF8;

TRUNCATE TABLE `title_basics`;
";

$query_start = "
INSERT INTO `title_basics`
(`tconst`, `title_type`, `primary_title`, `original_title`, `is_adult`, `start_year`, `end_year`, `runtime_minutes`, `genres`)
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
        Tools::string($row[1]),
        Tools::string($row[2]),
        Tools::string($row[3]),
        $row[4] ? 1 : 0,
        intval($row[5]) ?: 'NULL',
        intval($row[6]) ?: 'NULL',
        intval($row[7]) ?: 'NULL',
        Tools::string($row[8])
    ];

    fwrite($tfh, ($batch > 1 ? "," : "")."\n(".join(", ", $values).")");

    if ($total % 100000 == 0) {
        echo $total . " rows written\n";
    }

    // if ($total >= 500000) {
    //     break;
    // }

    if ($batch >= 5000) {
        fwrite($tfh, $query_end);
        fwrite($tfh, $query_start);
        $batch = 0;
    }
}

fwrite($tfh, $query_end);

exit('DONE');