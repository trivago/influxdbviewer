<?php


require('../vendor/twig/twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();
session_start();

session_destroy();
redirectTo("index.php");

function redirectTo($path)
{
    header("Location: " . $path);
    die();
}