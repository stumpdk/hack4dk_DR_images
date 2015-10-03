<?php
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\DI\FactoryDefault;
use Phalcon\Http\Response as Response;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;

require( __DIR__ . '/../vendor/autoload.php');

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
       // $images = Images::findFirstById($id);
       // $response->setJsonContent($images->imagesTags);
        //$response->setJsonContent($images);

        
        //$image = Images::findFirstById($id);
        //$response->setJsonContent($image->imagesTags);
        $images = Images::findById($id);//(['limit' => 10]);
        $data = [];
        $i = 0;
        foreach ($images as $image) {
            foreach($image->getImagesTags() as $tag){
                foreach($tag->getTags() as $tag2)
                {
                    $data[$i]['id'] = $image;
                    $data[$i]['tag'] = $tag2;
                    $data[$i]['imageTagId'] = $tag;
                    $i++;
                }
            }
        }
        $response->setJsonContent($data);
    });
    
    $app->get('/images/{offset:[0-9]+}/{limit:[0-9]+}', function($offset, $limit) use ($response){
        $robots = Images::find(['offset' => $offset, 'limit' => $limit]);
        $data = [];
        foreach($robots as $robot){
            $data[] = $robot;
        }
        
        $response->setJsonContent($data);
    });
    
    $app->get('/img_resize/{id:[0-9]+}', function($id) use ($app, $response) {
        $image = Images::findFirstById($id);
        if(count($image) > 0){
            $resized_file = str_replace('http://hack4dk.dr.dk/', '/home/ubuntu/workspace/resized_images/', $image->url);
            $percent = 0.2;
            
            if(!file_exists($resized_file)){
                //Lets create folder structure if it doesn't exist
                if(!file_exists(dirname($resized_file))){
                    mkdir('./' . dirname($resized_file), '0777', true);
                }
                $image = new \Eventviva\ImageResize($image->url);
                $image->resizeToWidth(800);
                $image->save($resized_file);
            }
            //echo 'her' . $resized_file;
            $response->setHeader('Content-Type', 'image/jpeg');
            $response->send();
            readfile($resized_file);
        }
        $response->setJsonContent(['could not load image!']);
    });
    
    $app->handle();
    
    $response->send();