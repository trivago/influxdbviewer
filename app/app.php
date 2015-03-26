<?php
require __DIR__ . '/bootstrap.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Provider\FormServiceProvider;

$app = new Silex\Application();


// TWIG EXTENSION
$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path' => __DIR__ . '/templates',
    )
);

// FORM EXTENSION
$app->register(new FormServiceProvider());

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
        $loggedIn = false;

        require_once("login.inc.php");

        if ($loggedIn)
        {
            return $app->redirect('/databases');
        }

        // TODO template rendern und falls error != null dann das form mit den vorherigen postdaten füllen
        // und die fehlermeldung anzeigen

        return $app['twig']->render(
            'index.twig',
            array(
                'title'         => "AdminfluxDB",
                'error'         => $app['security.last_error']($request),
                'last_username' => $app['session']->get('_security.last_username'),
            )
        );
    }
);

// logout
$app->get(
    '/logout',
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
    'databases',
    function (Request $request) use ($app)
    {
        require_once("list_databases.inc.php");

        //$message = $request->get('message');

        return $app['twig']->render(
            'databases.twig',
            array(
                'title'     => 'databases',
                'databases' => [1, 2, 3, 4, 5],
            )
        );
    }
);

// query
$app->get(
    'query',
    function (Request $request) use ($app)
    {
        $query = "";
        $results = null;
        $is_series_list = false;
        $timestamp = 0;
        $is_cached = false;

        require_once("run_query.inc.php");

        return $app['twig']->render(
            'query.twig',
            array(
                'title' => 'Results',
                'query' => $query,
                'results' => $results,
                'is_series_list' => $is_series_list,
                'timestamp' => $timestamp,
                'is_cached' =>  $is_cached
            )
        );
    }
);

return $app;
