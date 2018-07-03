<?php

namespace polakjan\ImdbDataFiles;

class Convertor
{
    public $cli = null;
    public $data_file = null;
    public $table_name = 'anonymous_table';
    public $columns = [];

    public function __construct($cli, $data_file)
    {
        $this->cli = $cli;
        $this->data_file = $data_file;
    }

    public function toSQL($target_file)
    {
        // pocet radku, po kterych se to utne
        $limit = $this->cli->getSwitch(['l', 'limit']);

        $fh = fopen($this->data_file, 'r');
        if (!file_exists(dirname($target_file))) {
            mkdir(dirname($target_file), 0777, true);
        }
        $tfh = fopen($target_file, 'w');

        $file_start = "
SET NAMES UTF8;

TRUNCATE TABLE `{$this->table_name}`;
        ";

        $column_names = array_map(function($column) {
            return $column['name'];
        }, $this->columns);

        $query_start = "
INSERT INTO `{$this->table_name}`
(`".join("`, `", $column_names)."`)
VALUES";

        $query_end = ";\n\n";

        fwrite($tfh, $file_start);
        fwrite($tfh, $query_start);

        $total = 0;
        $batch = 0;
        
        while ($line = fgets($fh)) {
            $row = explode("\t", trim($line));
            $total++;
            if ($total == 1) { // radek s nazvy sloupcu
                continue;
            }
            $batch++;

            $row = array_map('trim', $row);

            $row = array_slice(array_pad($row, count($this->columns), null), 0, count($this->columns));

            $values = [];
            foreach (array_values($this->columns) as $col_nr => $column) {
                $values[] = $this->columnValue($col_nr, $row[$col_nr]);
            }
            // $values = [
            //     static::string($row[0]),
            //     static::string($row[1]),
            //     static::string($row[2]),
            //     static::string($row[3]),
            //     $row[4] ? 1 : 0,
            //     intval($row[5]) ?: 'NULL',
            //     intval($row[6]) ?: 'NULL',
            //     intval($row[7]) ?: 'NULL',
            //     static::string($row[8])
            // ];

            fwrite($tfh, ($batch > 1 ? "," : "")."\n(".join(", ", $values).")");

            if ($total % 100000 == 0) {
                $this->cli->output($total . " rows written");
            }

            if ($limit && $total >= $limit) {
                break;
            }

            if ($batch >= 5000) {
                fwrite($tfh, $query_end);
                fwrite($tfh, $query_start);
                $batch = 0;
            }
        }

        fwrite($tfh, $query_end);
    }

    public function columnValue($column, $value)
    {
        if (trim($value) == '\\N') {
            return 'NULL';
        }
        
        $type = isset($this->columns[$column]['type']) ? $this->columns[$column]['type'] : 'string';

        switch ($type) {
            default:
            case 'string':
                return static::string($value);
                break;
            case 'integer':
                return intval($value);
                break;
            case 'decimal':
                return floatval($value);
                break;
            case 'boolean':
                return $value ? 1 : 0;
                break;
        }
    }


    public static function string($string, $nullable = true)
    {
        return ($string || !$nullable) ? "'".static::mres($string)."'" : 'NULL';
    }

    public static function mres($string)
    {
        $search = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
        $replace = ["\\\\", "\\0", "\\n", "\\r", "''", '\"', "\\Z"];

        return str_replace($search, $replace, $string);
    }
}