<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;

class vApp extends Application
{
    public $pdo;

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

    function Drop()
    {
        $tables = array(
            'couriers',
            'regions',
            'trips'
        );
        foreach ($tables as $table) {
            $sql = 'DROP TABLE ' . $table;
            $this->pdo->exec($sql);
        }
    }
    function CreateTables(){
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
        foreach ($sql as $query){$this->pdo->exec($query);}
    }
    function CreateData(){
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

        foreach ($sql as $query){$this->pdo->exec($query);}
    }
    function Generate()
    {
        $this->CreateTables();
        $this->CreateData();

    }
}

$app = new vApp();
$app->get('generate', function () use ($app) {
    $app->Drop();
    $app->Generate();
    return 'Generating...';
});

return $app;
