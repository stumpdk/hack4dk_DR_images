<?php
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\DI\FactoryDefault;
use Phalcon\Http\Response as Response;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
    
    // Use Loader() to autoload our model
    $loader = new Loader();
    
    $loader->registerDirs(
        array(
            __DIR__ . '/models/'
        )
    )->register();
    
    $di = new FactoryDefault();

    // Set up the database service
    $di->set('db', function () {
        return new PdoMysql(
            array(
                "host"     => "127.0.0.1",
                "username" => "stumpdk",
                "password" => "",
                "dbname"   => "test"
            )
        );
    });
    
    $app = new Micro($di);
    $response = new Response();
    
    // Define the routes here
    
    // Retrieves all robots
    $app->get('/image/random', function () use ($app) {
        $robots = Images::find();
        echo "There are ", count($robots), "\n";
       // $result = Images::query('select min(id), max(id) FROM images;');
    });
    
    $app->get('/image/{id:[0-9]+}', function($id) use ($app, $response) {
        $images = Images::findFirstById($id);

        $response->setJsonContent($images);
    });
    
    $app->handle();
    
    $response->send();