<?php
if ($_POST)
{
    $loggedIn = checkLoginValid();
    if($loggedIn){
        storeToSession();
    } else {
        $error_message = "Invalid login";
    }
}



function checkLoginValid()
{

    $url = "http://" + $_POST['host'] + "/db/?u=" + $_POST['user'] + "&p=" + $_POST['pw'];
    $httpResult = getUrlContent($url);
    return (200 == $httpResult['status_code']);
}

function storeToSession(){
    $_SESSION['host'] = $_POST['host'];
    $_SESSION['user'] = $_POST['user'];
    $_SESSION['pw'] = $_POST['pw'];
}
