<?php

function loadClass_isuydb85df2as74fddf56a2sasf4d7fs($class)
{
    if(substr($class, 0, 12) === '\EasyCaptcha'){
        $file = dirname(__DIR__).$class.'.php';
        if(file_exists($file)) {
            require_once $file;
        }
    }
}
$funcs = spl_autoload_functions();
if(!in_array('loadClass_isuydb85df2as74fddf56a2sasf4d7fs', $funcs)){
    spl_autoload_register('loadClass_isuydb85df2as74fddf56a2sasf4d7fs');
}