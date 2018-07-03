<?php

namespace polakjan\ImdbDataFiles;

class Dispatcher
{
    public $cli = null;
    public $param_names = [
        'action',
        'data_file',
        'output_file'
    ];

    public function __construct($argv, $param_names = [])
    {
        $this->cli = new Cli($argv);

        if ($param_names) {
            foreach ($param_names as $i => $name) {
                $this->param_names[$i] = $name;
            }
        }

        $this->cli->setParamNames($this->param_names);
    }

    public function dispatch()
    {
        if (!$action = $this->cli->get('action')) {
            $this->cli->outputFile('lib/help/index.txt');
            exit();
        }

        if ($action == 'analyze') {
            return $this->analyze();
        } elseif ($action == 'load') {
            return $this->load();
        } else {
            $this->cli->outputError('Unknown action');
            $this->cli->outputFile('lib/help/index.txt');
            exit();
        }
    }

    public function analyze()
    {
        if (!$data_file = $this->cli->get('data_file')) {
            $this->cli->outputError('Data file not set');
            $this->cli->outputFile('lib/help/index.txt');
            exit();
        }

        $analyzer = new Analyzer($this->cli, $data_file);

        $this->cli->output('Analyzing file '.$data_file);
        $columns = $analyzer->analyze();

        if ($output_file = $this->cli->get('output_file')) {
            $this->cli->outputInternalError('Outputting schema to SQL not yet implemented');
        } else {
            switch ($this->cli->get('format')) {
                case 'yaml':
                    $this->cli->output($analyzer->createYamlSchema());    
                    break;
                default:
                case 'table':
                    $this->cli->output($analyzer->createAsciiTable());
                    break;
                case 'both':
                    $this->cli->output($analyzer->createAsciiTable());
                    $this->cli->output($analyzer->createYamlSchema());    
                    break;
            }

            if ($this->cli->hasSwitch('show_max_values')) {
                $this->cli->output($analyzer->showMaxValues());
            }
        }
    }

    public function load()
    {
        if (!$data_file = $this->cli->get('data_file')) {
            $this->cli->outputError('Data file not set');
            $this->cli->outputFile('lib/help/index.txt');
            exit();
        }

        if (!$output_file = $this->cli->get('output_file')) {
            $this->cli->outputError('Output file not set');
            $this->cli->outputFile('lib/help/index.txt');
            exit();
        }

        if (!$table_name = $this->cli->getSwitch(['t', 'table'])) {
            $this->cli->outputError('Table name not set');
            $this->cli->outputFile('lib/help/load.txt');
            exit();
        }

        if (is_dir($output_file)) {
            $output_file = $output_file . '/' . pathinfo($data_file, PATHINFO_FILENAME) . '.sql';
        }

        $analyzer = new Analyzer($this->cli, $data_file);

        $this->cli->output('Analyzing file '.$data_file);
        $columns = $analyzer->analyze();

        $this->cli->output('Converting '.$data_file.' to '.$output_file);
        $convertor = new Convertor($this->cli, $data_file);
        $convertor->table_name = $table_name;
        $convertor->columns = $columns;
        $convertor->toSQL($output_file);
    }
}