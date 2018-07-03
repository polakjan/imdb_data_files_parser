<?php

namespace polakjan\ImdbDataFiles;

class Analyzer
{
    public $cli = null;
    public $data_file = null;

    public $column_names = [];
    public $column_types = [];
    public $column_sizes = [];
    public $columns_nullable = [];
    public $change_values = []; // values that forced the last change of the type of this column
    public $max_values = []; // highest values in this column

    public $columns = null;

    public function __construct($cli, $data_file)
    {
        $this->cli = $cli;
        $this->data_file = $data_file;
    }

    public function analyze()
    {
        if (!file_exists($this->data_file)) {
            $this->cli->outputError('Specified file '.$this->data_file. ' does not exist');
            exit();
        }
        
        $fh = fopen($this->data_file, 'r');
        
        $this->column_names = [];
        $this->column_types = [];
        $this->column_sizes = [];
        $this->columns_nullable = [];
        $this->change_values = [];
        $this->max_values = [];

        // pocet radku, po kterych se to utne
        $max_rows = $this->cli->getSwitch(['rows']);
        
        $row_nr = 0;
        while ($line = fgets($fh)) {
            $row = explode("\t", trim($line));
            if ($row_nr++ == 0) {
                $this->column_names = array_map(function($name) {
                    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
                }, $row);
                $this->column_types = array_fill_keys(array_keys($this->column_names), 'boolean');
                $this->column_sizes = array_fill_keys(array_keys($this->column_names), 0);
                $this->column_nullable = array_fill_keys(array_keys($this->column_names), false);
                $this->change_values = array_fill_keys(array_keys($this->column_names), null);
                $this->max_values = array_fill_keys(array_keys($this->column_names), 1);
                continue;
            }
        
            foreach ($row as $col => $value) {
        
                if ($value == '\N') {
                    $this->column_nullable[$col] = true;
                    continue;
                }
        
                if ($this->column_types[$col] == 'string') {
                    if (strlen($value) > $this->column_sizes[$col]) {
                        $this->column_sizes[$col] = strlen($value);
                        $this->max_values[$col] = $value;
                    }            
                    continue; // neni treba pokracovat
                }
        
                $possibles = null;
                
                if ($this->column_types[$col] == 'boolean' && $value !== '1' && $value !== '0') {
                    $this->change_values[$col] = $value;
                    $possibles = ['integer', 'decimal', 'string'];
                }
        
                if ($this->column_types[$col] == 'integer' && !preg_match('#^\d+$#', $value)) {
                    $this->change_values[$col] = $value;
                    $possibles = ['decimal', 'string'];
                }
        
                if ($this->column_types[$col] == 'decimal' && !is_numeric($value)) {
                    $this->change_values[$col] = $value;
                    $possibles = ['string'];
                }
        
                if ($possibles) {
                    foreach ($possibles as $possible) {
                        if ($possible == 'integer' && preg_match('#^\d+$#', $value)) {
                            $this->column_types[$col] = 'integer';
                            break;
                        } elseif ($possible == 'decimal' && preg_match('#^\d+$#', $value)) {
                            $this->column_types[$col] = 'decimal';
                            break;
                        } elseif ($possible == 'string') {
                            $this->column_types[$col] = 'string';
                            break;
                        }
                    }
                }
        
                if ($this->column_types[$col] == 'integer' || $this->column_types[$col] == 'decimal') {
                    if ($value > $this->column_sizes[$col]) {
                        $this->column_sizes[$col] = strlen((string)$value);
                    }
                    if ($value > $this->max_values[$col]) {
                        $this->max_values[$col] = $value;
                    }
                } elseif ($this->column_types[$col] == 'string') {
                    if (strlen($value) > $this->column_sizes[$col]) {
                        $this->column_sizes[$col] = strlen($value);
                        $this->max_values[$col] = $value;
                    }   
                }
            }

            if ($max_rows && $row_nr >= $max_rows) {
                break;
            }
        }
        
        fclose($fh);

        $this->columns = [];
        foreach ($this->column_names as $i => $name) {
            $this->columns[$i] = [
                'name' => $name,
                'type' => $this->column_types[$i],
                'size' => $this->column_sizes[$i],
                'null' => (boolean)$this->column_nullable[$i],
                'type_set_by' => $this->change_values[$i],
                'max_value' => $this->max_values[$i]
            ];
        }

        return $this->columns;
    }
    
    public function createAsciiTable()
    {
        if ($this->columns === null) {
            $this->analyze();
        }

        $names_col_size = max(4, max(array_map('strlen', $this->column_names)));
        $types_col_size = max(4, max(array_map('strlen', $this->column_types)));
        $sizes_col_size = max(4, max(array_map('strlen', $this->column_sizes)));
        $null_col_size = 4; // NULL

        $table_width = $names_col_size + $types_col_size + $sizes_col_size + $null_col_size + 4 * 5 + 1;

        // HEAD
        $output = '+'.str_repeat('-', $table_width-2)."+\n";
        $output .= "|  " . str_pad('NAME', $names_col_size, ' ', STR_PAD_LEFT);
        $output .= "  |  " . str_pad('TYPE', $types_col_size, ' ', STR_PAD_RIGHT);
        $output .= "  |  " . str_pad('SIZE', $sizes_col_size, ' ', STR_PAD_RIGHT);
        $output .= "  |  " . str_pad('NULL', 4, ' ', STR_PAD_RIGHT);
        $output .= "  |\n";
        $output .= '+'.str_repeat('-', $table_width-2)."+\n";

        // BODY
        foreach ($this->columns as $i => $column) {
            $output .= "|  " . str_pad($column['name'], $names_col_size, ' ', STR_PAD_LEFT);
            $output .= "  |  " . str_pad($column['type'], $types_col_size, ' ', STR_PAD_BOTH);
            $output .= "  |  " . str_pad($column['size'], $sizes_col_size, ' ', STR_PAD_LEFT);
            $output .= "  |  " . ($column['null'] ? 'null' : '    ');
            $output .= "  |\n";
        }
        $output .= '+'.str_repeat('-', $table_width-2)."+\n";

        return $output;
    }

    public function createYamlSchema()
    {
        if ($this->columns === null) {
            $this->analyze();
        }

        $table_name = $this->cli->getSwitch(['t', 'table'], 'table_name');

        $output = $table_name.":\n";
        foreach ($this->columns as $i => $column) {
            $output .= "  ".$column['name'].": { ".static::yamlColumnType($column['type'], $column['size'], $column['null'])." }\n";
        }

        return $output;
    }

    public function showMaxValues()
    {
        if ($this->columns === null) {
            $this->analyze();
        }

        $output = "MAX VALUES:\n";
        foreach ($this->columns as $i => $column) {
            $output .= "\n".$column['name'].": ".((strlen($column['max_value']) + strlen($column['name']) + 2 > 80) ? "\n" : "").$column['max_value']."\n";
        }

        return $output;
    }

    protected static function yamlColumnType($type, $size = null, $nullable = true)
    {
        switch ($type) {
            default: 
                $type = strtolower($type);
                break;
            case 'string':
                if ($size > 512) {
                    $type = 'type: text';
                } else {
                    $type = 'type: varchar('.static::optimalVarcharSize($size).')';
                }
                break;
            case 'integer':
                $type = 'type: integer('.static::optimalIntegerSize($size).')';
                break;
            case 'decimal':
                $type = 'type: double, size: '.static::optimalIntegerSize($size).', precision: 4';
                break;
            case 'boolean':
                $type = 'type: integer, size: 1, default: 0, null: '.($nullable ? 'true' : 'false');
                break;
        }

        return $type;
    }

    protected static function optimalVarcharSize($size)
    {
        $sizes = [8, 16, 32, 64, 127, 255, 512];
        foreach ($sizes as $border) {
            if ($size <= $border) {
                return $border;
            }
        }
        return 512;
    }

    protected static function optimalIntegerSize($size)
    {
        $sizes = [1, 2, 4, 11];
        foreach ($sizes as $border) {
            if ($size <= $border) {
                return $border;
            }
        }
        return 11;
    }
}