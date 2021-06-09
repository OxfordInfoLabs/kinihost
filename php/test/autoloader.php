<?php

// Call the core autoloader.
include_once __DIR__ . "/../vendor/autoload.php";

/**
 * Test autoloader - includes src one as well.
 */
spl_autoload_register(function ($class) {

    // Now check for source classes.
    $srcClass = str_replace("Kinihost\\", "", $class);
    if ($srcClass !== $class) {
        $file = DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $srcClass) . '.php';
        if (file_exists(__DIR__ . $file)) {
            require __DIR__ . $file;
            return true;
        }
        if (file_exists(__DIR__ . "/../src$file")) {
            require __DIR__ . "/../src$file";
            return true;
        }
    }


    return false;

});
