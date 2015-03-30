<?php

require_once("config.inc.php");
require_once("func.inc.php");

//require(VENDOR_PATH . 'twig/twig/lib/Twig/Autoloader.php');
// Twig_Autoloader::register();
session_start();

session_destroy();
redirectTo("index.php");

