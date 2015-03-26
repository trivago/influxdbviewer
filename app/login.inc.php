<?php
if ($_POST)
{
    $loggedIn = checkLoginValid();

    if ($loggedIn)
    {
        storeToSession();
        addLoginToCookie();
    }
    else
    {
        $error_message = "Invalid login";
    }
}


function checkLoginValid()
{
    $url        = "http://" . $_POST['host'] . "/db/?u=" . $_POST['user'] . "&p=" . $_POST['pw'];
    $httpResult = getUrlContent($url);

    return (200 == $httpResult['status_code']);
}

function storeToSession()
{
    $_SESSION['host'] = $_POST['host'];
    $_SESSION['user'] = $_POST['user'];
    $_SESSION['pw']   = $_POST['pw'];
}


function addLoginToCookie(){
    $cookie_name = "last_logins";
    $saveMe = $_SESSION['user'] . "@" . $_SESSION['host'];
    $oldValue = readCookie($cookie_name);
    $newValue = $oldValue . "|" . $saveMe;
    setcookie($cookie_name, $newValue, time() + (86400 * 30), '/');
}

function readCookie($cookie_name){
    return (isset($_COOKIE[$cookie_name])) ? $_COOKIE[$cookie_name] : "";

}