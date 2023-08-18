<?php
spl_autoload_register(function ($class) {
    $class = str_replace("\\", "/", $class);
    $fileName = __DIR__ . "/lib/" . $class . ".php";

    if (file_exists($fileName)) {
        include_once $fileName;
    }
});
