<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;

// Создаём класс приложения на основе Silex Application
class vApp extends Application
{
    public $pdo; // соединение описывается в файле config/secured/db.php

    function __construct()
    {
        parent::__construct();
        $this->register(new ServiceControllerServiceProvider());
        $this->register(new AssetServiceProvider());
        $this->register(new TwigServiceProvider());
        $this->register(new HttpFragmentServiceProvider());
        $this['twig'] = $this->extend('twig', function ($twig, $app) {
            // add custom globals, filters, tags, ...

            return $twig;
        });
    }

    // Сброс таблиц в БД
    function Drop()
    {
        $tables = array( // список таблиц
            'couriers',
            'regions',
            'trips'
        );
        foreach ($tables as $table) {
            $sql = 'DROP TABLE ' . $table;
            $this->pdo->exec($sql);
        }
    }

    // Проверка: Доступна ли поездка (id региона, id курьера, дата прибытия в регион)
    function IsTripAvailable($region_id, $courier_id, $trip_departure)
    {
        $sql = "SELECT * FROM
	`trips` AS T,
	`regions` AS R,
	`couriers` AS C,
	`regions` AS R2
	WHERE
	R2.region_id = $region_id
	AND T.region_id=R.region_id
	AND T.courier_id=C.courier_id
	AND T.courier_id=$courier_id
	AND (
			(
				DATE_SUB(T.trip_departure, INTERVAL R.region_time DAY)
				<= DATE_SUB('$trip_departure', INTERVAL R2.region_time DAY)
				AND DATE_ADD(T.trip_departure, INTERVAL R.region_time DAY) 
				>= DATE_SUB('$trip_departure', INTERVAL R2.region_time DAY)
			) OR (
				DATE_SUB(T.trip_departure, INTERVAL R.region_time DAY)
				<= DATE_ADD('$trip_departure', INTERVAL R2.region_time DAY)
				AND DATE_ADD(T.trip_departure, INTERVAL R.region_time DAY)
				>= DATE_ADD('$trip_departure', INTERVAL R2.region_time DAY)
			)
	)";
        $query = $this->pdo->query($sql);
        if ($res = $query->fetch()) {
            return false;
        } else {
            return true;
        }
    }

    // Добавление поездки (id региона, id курьера, дата прибытия в регион)
    function AddTrip($region_id, $courier_id, $trip_departure)
    {
        $sql = "INSERT INTO `trips` VALUES (null, $region_id, $courier_id, '$trip_departure');";
        if ($this->IsTripAvailable($region_id, $courier_id, $trip_departure)) { // Проверка возможности поездки
            return $this->pdo->exec($sql);
        }
    }

    // Получение списка доступных куьеров (id региона, дата прибытия в регион)
    function GetAvailableCouriers($region_id, $trip_departure)
    {

        $sql = "SELECT c_top.*
FROM `couriers` AS c_top,
`regions` AS r_top
WHERE
r_top.region_id = $region_id
AND (NOT EXISTS(
	SELECT * FROM
	`trips` AS t2,
	`regions` AS r2,
	`couriers` AS c2
	WHERE
	t2.region_id=r2.region_id
	AND t2.courier_id=c2.courier_id
	AND t2.courier_id=c_top.courier_id
	AND (
			(
				DATE_SUB(t2.trip_departure, INTERVAL r2.region_time DAY)
				<= DATE_SUB('$trip_departure', INTERVAL r_top.region_time DAY)
				AND DATE_ADD(t2.trip_departure, INTERVAL r2.region_time DAY) 
				>= DATE_SUB('$trip_departure', INTERVAL r_top.region_time DAY)
			) OR (
				DATE_SUB(t2.trip_departure, INTERVAL r2.region_time DAY)
				<= DATE_ADD('$trip_departure', INTERVAL r_top.region_time DAY)
				AND DATE_ADD(t2.trip_departure, INTERVAL r2.region_time DAY)
				>= DATE_ADD('$trip_departure', INTERVAL r_top.region_time DAY)
			)
	)

));";

        $couriers = array();
        if ($c_query = $this->pdo->query($sql)) {
            while ($courier = $c_query->fetch()) {
                $couriers[] = $courier;
            }
        }
        return $couriers;
    }

    // Получение списка доступных куьеров в json (id региона, дата прибытия в регион)
    function GetAvailableCouriersJSON($region_id, $departure_date)
    {
        return json_encode($this->GetAvailableCouriers($region_id, $departure_date), JSON_UNESCAPED_UNICODE);
    }
    // Создание таблиц
    function CreateTables()
    {
        $sql['couriers'] = "CREATE TABLE IF NOT EXISTS `couriers` (
  `courier_id` int(11) NOT NULL AUTO_INCREMENT,
  `courier_fio` varchar(50) NOT NULL,
  PRIMARY KEY (`courier_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
        $sql['regions'] = "CREATE TABLE IF NOT EXISTS `regions` (
  `region_id` int(11) NOT NULL AUTO_INCREMENT,
  `region_name` varchar(20) NOT NULL,
  `region_time` int(11) NOT NULL,
  PRIMARY KEY (`region_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
        $sql['trips'] = "CREATE TABLE IF NOT EXISTS `trips` (
  `trip_id` int(11) NOT NULL AUTO_INCREMENT,
  `region_id` int(11) NOT NULL,
  `courier_id` int(11) NOT NULL,
  `trip_departure` date NOT NULL,
  PRIMARY KEY (`trip_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
        foreach ($sql as $query) {
            $this->pdo->exec($query);
        }
    }
    // Заполнение таблицы курьеров и регионов
    function CreateData()
    {
        $sql['regions'] = "INSERT INTO `regions` (`region_id`, `region_name`, `region_time`) VALUES
(1, 'Санкт-Петербург', 1),
(2, 'Уфа', 2),
(3, 'Нижний Новгород', 1),
(4, 'Владимир', 1),
(5, 'Кострома', 1),
(6, 'Екатеринбург', 2),
(7, 'Ковров', 1),
(8, 'Воронеж', 1),
(9, 'Самара', 1),
(10, 'Астрахань', 2);";
        $sql['couriers'] = "INSERT INTO `couriers` (`courier_id`, `courier_fio`) VALUES
(1,'Иванов И.И.'),
(2,'Петров П.П.'),
(3,'Сидоров С.С.'),
(4,'Дмитриевский Д.Д.'),
(5,'Архангельский А.А.'),
(6,'Кривошеев В.М.'),
(7,'Мягдеев В.В.'),
(8,'Владимиров Д.А.'),
(9,'Третьяков И.Д.'),
(10,'Чехов А.П.'),
(11,'Фадеев А.А.'),
(12,'Новокуйбышев А.А.');";

        foreach ($sql as $query) {
            $this->pdo->exec($query);
        }
    }

    // Получение списка регионов
    function GetRegions()
    {
        $r_query = $this->pdo->query("SELECT * FROM `regions`");
        $regions = array();
        while ($r_fetch = $r_query->fetch()) {
            foreach (array('region_id', 'region_name', 'region_time') as $key) {
                $region[$key] = $r_fetch[$key];
            }
            $regions[] = $region;
        }
        return $regions;
    }

    // Получение списка регионов в JSON
    function GetRegionsJSON()
    {
        return json_encode($this->GetRegions(), JSON_UNESCAPED_UNICODE);
    }

    // Получение списка курьеров
    function GetCouriers()
    {
        $r_query = $this->pdo->query("SELECT * FROM `couriers`");
        $couriers = array();
        while ($r_fetch = $r_query->fetch()) {
            foreach (array('courier_id', 'courier_fio') as $key) {
                $courier[$key] = $r_fetch[$key];
            }
            $couriers[] = $courier;
        }
        return $couriers;
    }

    // Получение списка курьеров в JSON
    function GetCouriersJSON()
    {
        return json_encode($this->GetCouriers(), JSON_UNESCAPED_UNICODE);
    }

    // Получение списка поездок
    function GetTrips($options = array())
    {
        $sql = "SELECT T.*, R.region_name, C.courier_fio FROM `trips` as T, `regions` as R, `couriers` as C WHERE (C.courier_id = T.courier_id AND R.region_id = T.region_id";
        if (isset($options['courier_id'])) {
            $sql .= " AND T.courier_id = {$options['courier_id']}";
        }
        if ((!isset($options['show_previous'])) && (!isset($options['from'])) || (!isset($options['till']))) {
            $sql .= " AND T.trip_departure >= CURDATE()";
        }
        if (isset($options['from'])) {
            $sql .= " AND T.trip_departure >= '{$options['from']}'";
        }
        if (isset($options['till'])) {
            $sql .= " AND T.trip_departure <= '{$options['till']}'";
        }
        $sql .= ") ORDER BY trip_departure;";
        $t_query = $this->pdo->query($sql);
        $trips = array();
        while ($t_fetch = $t_query->fetch()) {
            foreach (array('trip_id', 'region_id', 'courier_id', 'courier_fio', 'region_name', 'trip_departure',) as $key) {
                $trip[$key] = $t_fetch[$key];
            }
            $trips[] = $trip;
        }
        return $trips;
    }

    // Получение списка поездок в JSON
    function GetTripsJSON($options = array())
    {
        return json_encode($this->GetTrips($options), JSON_UNESCAPED_UNICODE);
    }

    // Заполнение таблица поездками с 2015 года
    function GenerateDemo()
    {
        $regions = $this->GetRegions();
        $inittime = strtotime('2015-01-01');
        $nowtime = time();
        for ($t = $inittime; $t <= $nowtime; $t += 24 * 60 * 60) {
            $d = date('Y-m-d', $t);
            shuffle($regions); // перемешиваем список регионов
            foreach ($regions as $region) {
                $couriers = $this->GetAvailableCouriers($region['region_id'], $d);
                if(!empty($couriers)){
                    $k = array_rand($couriers); // получаем случайного курьера из доступных
                    $courier = $couriers[$k];
                    $this->AddTrip($region['region_id'], $courier['courier_id'], $d); // добавляем поездку
                }
            }
        }
    }
}

$app = new vApp(); // создаём приложение из созданного ранее класса

// ROUTING: в controllers.php осталась только главная страница

// Первый запуск (не забудьте поправить/создать config/secured/db.php вида:
//   $app->pdo = new PDO("mysql:host=ХОСТ;dbname=ИМЯ_БАЗЫ_ДАННЫХ;charset=utf8","ИМЯ_ПОЛЬЗОВАТЕЛЯ", "ПАРОЛЬ");
$app->get('generate', function () use ($app) {
    $app->Drop();
    $app->CreateTables();
    $app->CreateData();
    $app->GenerateDemo();
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
return $app;
