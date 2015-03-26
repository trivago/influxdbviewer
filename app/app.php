<?php
require __DIR__ . '/bootstrap.php';

$app = new Silex\Application();

// Twig Extension
$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path' => __DIR__ . '/templates',
    )
);

// Config Extension
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/config/config.yml"));


// Routes
$app->get(
    '/',
    function () use ($app)
    {
        return $app['twig']->render(
            'index.twig',
            array(
                'title'  => "Hello World",
                'colors' => array("red", "green", "yellow"),
            )
        );
    }
);

return $app;
