<?php

// local autoloading
spl_autoload_register(function($class) {
    if (substr($class, 0, 22) == 'polakjan\\ImdbDataFiles') {
        $file = 'lib/'.str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 9)).'.php';
        if (file_exists($file)) {
            require_once($file);
        }
    }
});

$dispatcher = new polakjan\ImdbDataFiles\Dispatcher($argv);
$dispatcher->dispatch();