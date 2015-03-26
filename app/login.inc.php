<?php
if ($_POST)
{
    $b = checkLoginValid();

    if ($b)
    {
        redirectToDatabasePage();
    }
    else
    {
        $errorMessage = "Invalid credentials";
        showLoginForm($errorMessage);
    }
}
else
{
    showLoginForm();
}


function showLoginForm($error = null)
{
// TODO template rendern und falls error != null dann das form mit den vorherigen postdaten füllen und die fehlermeldung anzeigen
}


function redirectToDatabasePage()
{
// TODO redirect to other rute. Yes, rute.
}

function checkLoginValid()
{
}
