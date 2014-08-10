<?php

spl_autoload_register('phpDraftAutoload');

/**
 * Autoloader
 *
 * @param string $classname name of class to load
 *
 * @throws Exception
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
    $modelFile = "models/$classname.php";

    $isService = preg_match('/[a-zA-Z]+_service$/', $classname);
    $serviceFile = "services/$classname.php";

    $isLibrary = preg_match('/[a-zA-Z]+_library$/', $classname);
    $libraryFile = "libraries/$classname.php";

    $includesFile = "includes/$classname.php";

    if ($isModel && file_exists($modelFile)) {
        include_once($modelFile);
        return true;
    } elseif ($isService && file_exists($serviceFile)) {
        include_once($serviceFile);
        return true;
    } elseif ($isLibrary && file_exists($libraryFile)) {
        include_once($libraryFile);
        return true;
    } elseif(file_exists($includesFile)) {
        include_once($includesFile);
        return true;
    } else {
        throw new Exception("Class $classname unable to be auto-loaded.", 500);
    }
}