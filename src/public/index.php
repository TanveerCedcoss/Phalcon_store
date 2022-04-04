<?php

use App\Components\Translate;
use Phalcon\Di\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\Url;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Config;
use Phalcon\Session\Manager;
use Phalcon\Session\Adapter\Stream;
use Phalcon\Http\Response\Cookies;
use Phalcon\Config\ConfigFactory;
use Phalcon\Events\Manager as EventsManager;

$config = new Config([]);

// Defining some absolute path constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

//requiring autoload file
require_once(BASE_PATH . '/vendor/autoload.php');

// Register an autoloader
$loader = new Loader();

//-------------------------------------REGISTERING DIRECTORIES START-----------------------------------
$loader->registerDirs(
    [
        APP_PATH . "/controllers/",
        APP_PATH . "/models/",
    ]
);
//-------------------------------------------------END----------------------------------------------------

//------------------------------------REGISTERING NAMESPACES START----------------------------------
$loader->registerNamespaces(
    [
        'App\Components' => APP_PATH . '/components',
        'App\Listeners' => APP_PATH . '/listeners'
    ]
);
$loader->register();
//----------------------------------------------------END------------------------------------------------
$container = new FactoryDefault();


//-------------------------------------VIEW START---------------------------------------------------
$container->set(
    'view',
    function () {
        $view = new View();
        $view->setViewsDir(APP_PATH . '/views/');
        return $view;
    }
);
//---------------------------------------------------END--------------------------------------------


//-----------------------------------------------------------------------------------------------------
$container->set(
    'url',
    function () {
        $url = new Url();
        $url->setBaseUri('/');
        return $url;
    }
);
//-----------------------------------------------------------------------------------------------------
$application = new Application($container);

//-----------------------------------------------------------------------------------------------------
$fileName = '../app/etc/config.php';
$factory  = new ConfigFactory();
$config = $factory->newInstance('php', $fileName);

$container->set(
    'config',
    $config,
    true
);

$container->set(
    'db',
    getDBConnection($config)
);

function getDBConnection($config)
{
    return new Mysql(
        [
            'host'     => $config->db->host,
            'username' => $config->db->username,
            'password' => $config->db->password,
            'dbname'   => $config->db->dbname,
        ]
    );
}
//-----------------------------------------------------------------------------------------------------
$container->set('locale', (new \App\Components\Translate())->getTranslator());
//-----------------------------------------------------------------------------------------------------
$container->set(
    'session',
    function () {
        $session = new Manager();
        $files = new Stream(
            [
                'savePath' => '/tmp',
            ]
        );

        $session
            ->setAdapter($files)
            ->start();

        return $session;
    }
);
//-----------------------------------------------------------------------------------------------------
$container->set(
    'cookies',
    function () {
        $cookies = new Cookies();

        $cookies->useEncryption(false);

        return $cookies;
    }
);
//-----------------------------------------------------------------------------------------------------
$eventsManager = new EventsManager();
$eventsManager->attach(
    'notification',
    new App\Listeners\NotificationListeners()
);
$eventsManager->attach(
    'application:beforeHandleRequest',
    new App\Listeners\NotificationListeners()
);
$application->setEventsManager($eventsManager);
$container->set(
    'EventsManager',
    $eventsManager
);
//-----------------------------------------------------------------------------------------------------
try {
    // Handle the request
    $response = $application->handle(
        $_SERVER["REQUEST_URI"]
    );

    $response->send();
} catch (\Exception $e) {
    echo 'Exception: ', $e->getMessage();
}
