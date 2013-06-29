<?php

// web/index.php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();
$app['report_dir'] = __DIR__.'/../report';
$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app['aggregator'] = $app->share(function ($app) {
    return new DTL\Ghag\Aggregator;
});

// definitions
$app->get('/', function () use ($app) {
    $dom = new \DOMDocument();
    $file = $app['report_dir'].'/report.xml';

    if (!file_exists($file)) {
        return "File ".$file." does not exist.";
    }

    $dom->load($file);
    $xpath = new \DOMXPath($dom);
    $domUtil = new \DTL\Ghag\DOMUtil;

    if (isset($_GET['xml'])) {
        $dom->formatOutput = true;
        return $dom->saveXml();
    }

    return $app['twig']->render('index.html.twig', array(
        'xp' => $xpath,
        'du' => $domUtil,
    ));
});

$app->run();
