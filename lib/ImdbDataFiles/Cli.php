<?php

namespace polakjan\ImdbDataFiles;

class Cli
{
    public $args = []; // all command line arguments
    public $params = []; // parameters indexed by their order
    public $switches = []; // switches indexed by their name (-x --xxx etc.)

    public function __construct($args, $names = null)
    {
        $this->args = $args;

        $this->load();

        if (is_array($names)) {
            $this->setParamNames($names);
        }
    }

    public function load()
    {
        $this->params = [];
        $this->switches = [];

        $switch_name = null;
        foreach ($this->args as $pos => $value) {
            if ($pos == 0 && $value == $_SERVER['SCRIPT_NAME']) {
                continue;
            }
            if (preg_match('#^(\-\-?)([^\-]+)$#', $value, $m)) {
                if ($switch_name) {
                    $this->switches[$switch_name] = null; // empty switch
                }
                if ($eq_pos = strpos($m[2], '=')) { // --key=value
                    $this->switches[substr($m[2], 0, $eq_pos)] = substr($m[2], $eq_pos+1);
                } else {
                    $switch_name = $m[2];
                }
            } else {
                if ($switch_name) {
                    $this->switches[$switch_name] = $value;
                } else {
                    $this->params[] = $value;
                }
                $switch_name = null;
            }
        }
        if ($switch_name) {
            $this->switches[$switch_name] = null; // empty switch
        }
    }

    protected function getNamedParams($names)
    {
        $size = max(count($names), count($this->params));
        if ($size > count($names)) {
            $names = array_merge($names, range(count($names), $size-1));
        }
        $values = array_pad($this->params, $size, null);
        return array_combine($names, $values);
    }

    public function setParamNames($names)
    {
        $this->params = $this->getNamedParams($names);
    }

    public function getParams($names = null)
    {
        if (is_array($names)) {
            return $this->getNamedParams($names);
        }
        return $this->params;
    }

    public function getParam($name, $default = null)
    {
        return array_key_exists($name, $this->params) ? $this->params[$name] : $default;
    }

    public function hasSwitch($name)
    {
        if (!is_array($name)) {
            $name = [$name];
        }
        foreach ($name as $n) {
            if (array_key_exists($n, $this->switches)) {
                return true;
            }
        }
        return false;
    }

    public function getSwitch($name, $default = null)
    {
        if (!is_array($name)) {
            $name = [$name];
        }
        foreach ($name as $n) {
            if (array_key_exists($n, $this->switches)) {
                return $this->switches[$n];
            }
        }
        return $default;
    }

    public function get($name, $default = null)
    {
        return $this->getParam($name, $this->getSwitch($name, $default));
    }

    public function output($text)
    {
        echo $text ."\n";
    }

    public function outputFile($file)
    {
        if (!file_exists($file)) {
            return $this->outputInternalError('File '.$file.' does not exist');
        }
        return $this->output(file_get_contents($file));
    }

    public function outputError($text)
    {
        return $this->output('ERROR: '.$text."\n");
    }

    public function outputInternalError($text)
    {
        return $this->output('CLI INTERNAL ERROR: '.$text."\n");
    }
}