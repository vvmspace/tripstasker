<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})
->bind('homepage')
;
// Первый запуск (не забудьте поправить/создать config/secured/db.php вида:
//   $app->pdo = new PDO("mysql:host=ХОСТ;dbname=ИМЯ_БАЗЫ_ДАННЫХ;charset=utf8","ИМЯ_ПОЛЬЗОВАТЕЛЯ", "ПАРОЛЬ");
$app->get('generate', function () use ($app) {
    $app->Generate();
    return $app['twig']->render('generate.html.twig', array());
});

// Список доступных курьеров в JSON для функции UpdateFreeCouriers() в web/js/app.js
$app->get('/{region_id}/{departure_date}/couriers.json', function (Silex\Application $app, $region_id, $departure_date) {
    return $app->GetAvailableCouriersJSON($region_id, $departure_date);
});

// Список регионов в JSON
$app->get('/regions.json', function () use ($app) {
    return $app->GetRegionsJSON();
});

// Список курьеров в JSON
$app->get('/couriers.json', function () use ($app) {
    return $app->GetCouriersJSON();
});

// Список поездок для курьера в JSON (нужно доработать)
$app->get('/{courier_id}/trips.json', function (Silex\Application $app, $courier_id) {
    return $app->GetTripsJSON(array('courier_id' => $courier_id));
});

// Список поездок за период
$app->get('/{trips_from}/{trips_till}/trips.json', function (Silex\Application $app, $trips_from, $trips_till) {
    $options['from'] = $trips_from;
    $options['till'] = $trips_till;
    return $app->GetTripsJSON($options);
});

// Добавление поездок
$app->post('/record.json', function () use ($app) {
    return $app->AddTrip($_POST['region_id'], $_POST['courier_id'], $_POST['trip_departure']);
});

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
