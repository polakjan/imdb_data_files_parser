<?php


if (empty($argv[1])) {
    echo 'You must specify a file name';
    exit();
}

if (!file_exists($argv[1])) {
    echo 'Specified file does not exist';
    exit();
}

$file = $argv[1];

$fh = fopen($file, 'r');

$column_names = [];
$column_types = [];
$column_sizes = [];
$columns_nullable = [];
$change_values = []; // hodnoty, kvuli kterym jsme nastavili sloupec na tento typ

$row_nr = 0;
while ($line = fgets($fh)) {
    $row = explode("\t", trim($line));
    if ($row_nr++ == 0) {
        $column_names = $row;
        $column_types = array_fill_keys(array_keys($column_names), 'boolean');
        $column_sizes = array_fill_keys(array_keys($column_names), 0);
        $column_nullable = array_fill_keys(array_keys($column_names), false);
        continue;
    }

    foreach ($row as $col => $value) {

        if ($value == '\N') {
            $column_nullable[$col] = true;
            continue;
        }

        if ($column_types[$col] == 'string') {
            $column_sizes[$col] = max($column_sizes[$col], strlen($value));
            continue; // neni treba pokracovat
        }

        $possibles = null;
        
        if ($column_names[$col] == 'boolean' && $value !== '1' && $value !== '0') {
            $change_values[$col] = $value;
            $possibles = ['integer', 'decimal', 'string'];
        }

        if ($column_names[$col] == 'integer' && !preg_match('#^\d+$#', $value)) {
            $change_values[$col] = $value;
            $possibles = ['decimal', 'string'];
        }

        if ($column_names[$col] == 'decimal' && !is_numeric($value)) {
            $change_values[$col] = $value;
            $possibles = ['string'];
        }

        if ($possibles) {
            foreach ($possibles as $possible) {
                if ($possible == 'integer' && preg_match('#^\d+$#', $value)) {
                    $column_types[$col] = 'integer';
                } elseif ($possible == 'decimal' && preg_match('#^\d+$#', $value)) {
                    $column_types[$col] = 'decimal';
                } elseif ($possible == 'string') {
                    $column_types[$col] = 'string';
                }
            }

            if ($column_types[$col] == 'integer' || $column_types[$col] == 'decimal') {
                $column_sizes[$col] = max($column_sizes[$col], $value);
            } elseif ($column_types[$col] == 'string') {
                $column_sizes[$col] = max($column_sizes[$col], strlen($value));
            }
        }
    }


    var_dump($row);
    break;
}

fclose($fh);