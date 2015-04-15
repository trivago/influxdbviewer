<?php

require_once("config.inc.php");
require_once("func.inc.php");

session_start();

session_destroy();
redirectTo("index.php");

