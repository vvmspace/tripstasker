<?php

// Подключаем конфигурацию БД
require __DIR__.'/secured/db.php';

// configure your app for the production environment

$app['twig.path'] = array(__DIR__.'/../templates');
$app['twig.options'] = array('cache' => false);
