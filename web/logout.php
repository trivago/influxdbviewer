<?php

require("config.inc.php");
require("func.inc.php");

//require(VENDOR_PATH . 'twig/twig/lib/Twig/Autoloader.php');
// Twig_Autoloader::register();
session_start();

session_destroy();
redirectTo("index.php");

function redirectTo($path)
{
    header("Location: " . $path);
    die();
}