<?php

defined('MAIN_PATH') or define('MAIN_PATH', __DIR__.'/');

// 简单的自动加载
// app\ -> app/ , core\ -> core/
spl_autoload_register( function($class){
    $prefix = substr($class, 0, 3);
    if ($prefix == 'app') {
        $file = MAIN_PATH.'app/'. str_replace('\\', '/', substr($class, 4)). '.php';
    } elseif ($prefix == 'cor') {
        $file = MAIN_PATH. 'core/'. str_replace('\\', '/', substr($class, 5)). '.php';
    } else {
        return;
    }
    if (file_exists($file)) {
        require $file;
    }
} );

new \core\Worker(2333);