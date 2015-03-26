<?php
$databases = getListOfDatabases();

if ($_REQUEST['database'] || !isset($_SESSION['database']) || empty($_SESSION['database']))
{
    {
        if (in_array($_REQUEST['database'], $databases))
        {
            $_SESSION['database'] = $_REQUEST['database'];
            $redirect             = true;
        }
    }
}

function getListOfDatabases()
    {
        $url        = "http://" . $_SESSION['host'] . "/db/?u=" . $_SESSION['user'] . "&p=" . $_SESSION['pw'];
        $httpResult = getUrlContent($url);

        if (200 == $httpResult['status_code'])
        {

            $json = json_decode($httpResult['results']);
            print_r($json);
            $result = array();
            foreach ($json as $key => $value)
            {
                $result[] = $value;
            }

            return $result;
        }
        else
        {
            // TODO error handling
        }
    }

function getUrlContent($url)
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	    $data       = curl_exec($ch);
	    $statuscode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    curl_close($ch);

	    return ['status_code' => $statuscode, 'results' => $data];
	}