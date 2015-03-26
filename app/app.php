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
        return $app['twig']->render(
            'databases.twig',
            array(
                'title' => 'databases',

            )
        );
    }
);


// query

return $app;
