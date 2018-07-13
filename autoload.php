<?php

function loadClass_856bc953c99c81d40b453fa064851218($class)
{
    if(substr($class, 0, 12) === '\EasyCaptcha'){
        $file = dirname(__DIR__).$class.'.php';
        if(file_exists($file)) {
            require_once $file;
        }
    }
}
$funcs = spl_autoload_functions();
if(!in_array('loadClass_856bc953c99c81d40b453fa064851218', $funcs)){
    spl_autoload_register('loadClass_856bc953c99c81d40b453fa064851218');
}