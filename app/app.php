<?php
require __DIR__ . '/bootstrap.php';

$app = new Silex\Application();

// TWIG EXTENSION
$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path' => __DIR__ . '/templates',
    )
);

// CONFIG EXTENSION
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/config/config.yml"));


// ROUTES
// login
$app->get(
    '/',
    function () use ($app)
    {
        require_once("login.inc.php");
        return $app['twig']->render(
            'index.twig',
            array(
                'title'  => "AdminfluxDB",
                'colors' => array("red", "green", "yellow"),
            )
        );
    }
);

// databases
$app->get(
    'databases',
    function () use ($app)
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
    function () use ($app)
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
