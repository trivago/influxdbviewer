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


// ROUTES
// login
$app->get(
    '/',
    function (Request $request) use ($app)
    {
        require_once("login.inc.php");

        //$message = $request->get('message');
        return $app['twig']->render(
            'index.twig',
            array(
                'title'  => "AdminfluxDB",
                'colors' => array("red", "green", "yellow"),
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

        return $app->redirect('/hello');
    }
);


// databases
$app->get(
    'databases',
    function (Request $request) use ($app)
    {
        require_once("list_databases.inc.php");
        return $app['twig']->render(
            'databases.twig',
            array(
                'title' => 'databases',
                'databases' => [1,2,3,4,5],
            )
        );
    }
);

// query
$app->get(
    'query',
    function (Request $request) use ($app)
    {
        require_once("run_query.inc.php");
        return $app['twig']->render(
            'query.twig',
            array(
                'title' => 'databases',
                'query' => "select * from i_heart_css",
            )
        );
    }
);

return $app;
