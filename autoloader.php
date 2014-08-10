<?php

spl_autoload_register('phpDraftAutoload');

/**
 * Autoloader
 *
 * @param string $classname name of class to load
 *
 * @return boolean
 */
function phpDraftAutoload($classname)
{
    if (false !== strpos($classname, '.')) {
        // this was a filename, don't bother
        exit;
    }

    $isModel =  preg_match('/[a-zA-Z]+_object$/', $classname) ||
                preg_match('/[a-zA-Z]+_model$/', $classname);

    $isService = preg_match('/[a-zA-Z]+_service$/', $classname);

    $isLibrary = preg_match('/[a-zA-Z]+library$/', $classname);

    if ($isModel) {
        include_once 'models/' . $classname . '.php';
        return true;
    } elseif ($isService) {
        include 'services/' . $classname . '.php';
        return true;
    } elseif ($isLibrary) {
        include 'libraries/' . $classname . '.php';
        return true;
    } else {
        include 'includes/' . $classname . '.php';
        return true;
    }
}