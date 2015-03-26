<?php
require __DIR__ . '/bootstrap.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

Request::enableHttpMethodParameterOverride();

$app = new Silex\Application();
// session_start();

// TWIG EXTENSION
$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path' => __DIR__ . '/templates',
    )
);

const PATH_DATABASES = '/databases';
const PATH_LOGIN = '/';
const PATH_LOGOUT = '/logout';
const PATH_QUERY = '/query';

// require_once("functions.inc.php");

// CONFIG EXTENSION
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/config/config.yml"));

// SECURITY SERVICE
$app->register(
    new Silex\Provider\SecurityServiceProvider(),
    array(
        'security.firewalls' => array(
            'admin' => array(
                'pattern' => '^/admin',
                'http'    => true,
                'users'   => array(
                    // raw password is foo
                    'admin' => array('ROLE_ADMIN', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
                ),
            ),
        )
    )
);

// SESSION SERVICE
$app->register(new \Silex\Provider\SessionServiceProvider());


// ROUTES
// login
$app->get(
    '/',
    function (Request $request) use ($app)
    {
        $loggedIn      = false;
        $error_message = null;
        session_start();

        require_once("login.inc.php");

        if ($loggedIn)
        {
            return $app->redirect(PATH_DATABASES);
        } else {
            $_SESSION['host'] = "";
            $_SESSION['user'] = "";
        }

        return $app['twig']->render(
            'index.twig',
            array(

                'title' => "Welcome",
                'error' => $error_message,
                'user'  => $_SESSION['user'],
                'host'  => $_SESSION['host'],
            )
        );
    }
);

// logout
$app->get(
    PATH_LOGOUT,
    function (Request $request) use ($app)
    {
        // logout
        // destroy session
        // etc

        return $app->redirect('/');
    }
);


// databases
$app->get(
    PATH_DATABASES,
    function (Request $request) use ($app)
    {
        $databases = null;
        $redirect  = false;
        require_once("list_databases.inc.php");

        if ($redirect)
        { // Database has been selected and stored in the session
            return $app->redirect(PATH_QUERY);
        }

        return $app['twig']->render(
            'databases.twig',
            array(
                'title'     => 'Databases',
                'databases' => $databases,
            )
        );
    }
);

// query
$app->get(
    PATH_QUERY,
    function (Request $request) use ($app)
    {
        $query           = "";
        $results         = null;
        $is_series_list  = false;
        $timestamp       = 0;
        $is_cached       = false;
        $error_message   = null;
        $page            = 1;
        $number_of_pages = 1;

        require_once("run_query.inc.php");

        return $app['twig']->render(
            'query.twig',
            array(
                'title'           => 'Results',
                'query'           => $query,
                'results'         => $results,
                'is_series_list'  => $is_series_list,
                'timestamp'       => $timestamp,
                'is_cached'       => $is_cached,
                'error_message'   => $error_message,
                'page'            => $page,
                'number_of_pages' => $number_of_pages
            )
        );
    }
);

return $app;
