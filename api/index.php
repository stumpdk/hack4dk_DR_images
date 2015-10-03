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
                "dbname"   => "test",
                "charset"  => "utf8"
            )
        );
    });
    
    $app = new Micro($di);
    $response = new Response();
    
    // Define the routes here
    
    // Retrieves all robots
    $app->get('/images/random', function () use ($app, $response) {
     //   $robots = Images::find();
      //      echo "There are ", count($robots), "\n";
        $result = $app->getDI()->get('db')->query('select min(id) as minimum, max(id) as maximum FROM images;');
        $result->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $data = $result->fetchAll();
        $number = rand($data[0]['minimum'], $data[0]['maximum']);
        $result = Images::findFirstById($number);
        
        $response->setJsonContent($result);
        $response->send();
    });
    
    $app->get('/image/{id:[0-9]+}', function($id) use ($app, $response) {
        $image = Images::findFirstById($id);
        //$response->setJsonContent($image->imagesTags);
         $images = Images::findById($id);//(['limit' => 10]);
        //echo json_encode($image->toArray(), JSON_NUMERIC_CHECK);
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
        $response->send();
    });
    
    $app->get('/images/{offset:[0-9]+}/{limit:[0-9]+}', function($offset, $limit) use ($response){
        //$robots = Images::find(['offset' => $offset, 'limit' => $limit]);
        /*$data = [];
        foreach($robots as $robot){
            $data[] = $robot;
        }*/
        echo json_encode(Images::find(['offset' => $offset, 'limit' => $limit])->toArray(), JSON_NUMERIC_CHECK);
       // $response->setJsonContent($robots);
    });
    
    $app->get('/img_resize/{id:[0-9]+}', function($id) use ($app, $response) {
        $image = Images::findFirstById($id);
        if(count($image) > 0){
            $resized_file = str_replace('http://hack4dk.dr.dk/', '/home/ubuntu/workspace/resized_images/', $image->url);
            //$percent = 0.2;
            
            if(!file_exists($resized_file)){
                //Folder creation (local caching) temporary disabled
             /*   if(!file_exists(dirname($resized_file))){
                    mkdir('./' . dirname($resized_file), '0777', true);
                }*/
                $image = new \Eventviva\ImageResize($image->url);
                $image->resizeToHeight(800);
                //$image->save($resized_file);
                $response->setHeader('Content-Type', 'image/jpeg');
                $response->send();
                $image->output();
            }
            else{
                //echo 'her' . $resized_file;
                $response->setHeader('Content-Type', 'image/jpeg');
                $response->send();
                readfile($resized_file);
            }
        }
    });
    
    $app->post('/image/metadata/{id:[0-9]+}', function($id){
        $request = new Phalcon\Http\Request();
       // var_dump($_POST);
        $data = $request->getPost('tags', null, false);
//        $image = Images::findById($id);
        
        $image = Images::findFirst("id = '" . $id . "'");   
            
        $tags = [];   
        foreach($data as $tagRow){
            $tag = Tags::findFirst("name = '" . $tagRow['name'] . "' AND category_id = '" . $tagRow['category_id']  . "'");
            
            if(!$tag)
                $tag = new Tags();

            $tag->name = $tagRow['name'];
            $tag->category_id = $tagRow['category_id'];
            $tag->save();
            $tag->refresh();
            
            $imagesTags = new ImagesTags();
            $imagesTags->tag_id = $tag->id;
            $imagesTags->image_id = $image->id;
            $imagesTags->x = $tagRow['x'];
            $imagesTags->y = $tagRow['y'];
            $imagesTags->user_id = 1;
            
            //$imagesTags->save();
            if(!$imagesTags->save()){
                var_dump($imagesTags->getMessages());
            }
            
            $tags[] = $tag;
        }
        
        $image->imagesTags->tags = $tags;
        
        if(!$image->save()){
            var_dump($image->getMessages());
        }
        
    });
    
    $app->handle();