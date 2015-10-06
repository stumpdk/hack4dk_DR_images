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
    $di->set("request", "Phalcon\Http\Request", true);
    
    $app = new Micro();
    $app->setDI($di);
    $response = new Response();
    
    /**
     * Get random image
     */ 
    $app->get('/images/random', function () use ($app, $response) {
        
        $manager = $app->getDI()->get('modelsManager');
        $minMax = $manager->createBuilder()
        ->from('Images')
        ->columns('min(id) as minimum, max(id) as maximum')
        ->getQuery()
        ->getSingleResult();
        
        $number = rand($minMax->minimum, $minMax->maximum);
        $result = Images::findFirstById($number);
        
        $response->setJsonContent($result);
        $response->send();        
    });
    
    /**
     * Get specific image
     */ 
    $app->get('/image/{id:[0-9]+}', function($id) use ($app, $response) {
        $image = Images::findFirstById($id);
        
        $sql = 'select x, y, value, name as text FROM images_tags left join tags on images_tags.tag_id = tags.id WHERE images_tags.image_id = ' . $id;
        
        $resultSet = $app->getDI()->get('db')->query($sql);

        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);

        $tags = $image->getTags()->toArray();
        $imageTags = $image->getImagesTags()->toArray();
        $result = [];
        $result['image'] = $image;
        
        $i = 0;
        $result['tags'] = $resultSet->fetchAll();
        echo json_encode($result);
       
     /*   $manager = $app->getDI()->get('modelsManager');
        $tags = $manager->createBuilder()
        ->from('Images')
        ->columns('*')
        ->leftjoin('Tags')
        ->where('ImagesTags.id = :name', ['id' => $id])
        ->limit(100)
        ->getQuery()
        ->getSingleResult();        
        
        $response->setJsonContent($tags->toArray());
        $response->send();
       */ 
    });

    $app->get('/tags/latest', function() use ($app, $response){
        $request = $app->getDI()->get('request');
        $name = $request->getQuery('term', null, false);

        $manager = $app->getDI()->get('modelsManager');
        $tags = $manager->createBuilder()
        ->from('Tags')
        ->columns('*')
        ->orderBy('time_added DESC')
        ->limit(5)
        ->getQuery()
        ->execute();

        $response->setJsonContent($tags->toArray());
        $response->send();
    });
    
    $app->get('/test', function() use ($app, $response){
        $request = $app->getDI()->get('request');
        $name = $request->getQuery('term', null, false);

        $manager = $app->getDI()->get('modelsManager');
        $tags = $manager->createBuilder()
        ->from('Images')
        ->columns('*')
        ->leftjoin('Tags')
        ->where('Tags.name LIKE :name:', ['name' => '%'.$name.'%'])
        ->limit(100)
        ->getQuery()
        ->execute();
        
        $response->setJsonContent($tags->toArray());
        $response->send();
    });
    
    /**
     * Present image list by offset and limit
     */ 
    $app->get('/images/{offset:[0-9]+}/{limit:[0-9]+}', function($offset, $limit) use ($response){
        echo json_encode(Images::find(['offset' => $offset, 'limit' => $limit])->toArray(), JSON_NUMERIC_CHECK);
    });
    
    /**
     * resizing images.
     * TODO: Get caching to work.
     */ 
    $app->get('/img_resize/{id:[0-9]+}/{size}', function($id, $size) use ($app, $response) {
        $image = Images::findFirstById($id);
        
        if($size == 'preview'){
            $size = Images::$SIZE_PREVIEW;
        }
        else{
            $size = Images::$SIZE_THUMB;
        }
        
        if(!$image){
            $response->setJsonContent(['status' => 'image not found!']);
            $response->send();
        }
        else{
            $response->setHeader('Content-Type', 'image/jpeg');
            $response->send();
            readfile($image->resize($image, $size));
        }
    });

    /**
     * Searching images for tags
     */ 
    $app->get('/images/search', function() use ($app, $response){
        $request = new Phalcon\Http\Request();
        $terms = explode(' ',$request->getQuery('term', null, false));
        if(count($terms) == 0){
            die("no term!");
        }


        $termNew = "";
        foreach($terms as $term){
            $termNew = $termNew . 'tags.name LIKE \'%' . $term . '%\' OR ';
        }
        
        $termNew = substr($termNew, 0, strlen($termNew)-4);
        $sql = 'select distinct(images.id), CONCAT("https://hack4dk-2015-stumpdk-1.c9.io/api/img_resize/",images.id,"/thumb") as url from images left join images_tags ON images.id = images_tags.image_id LEFT JOIN tags on images_tags.tag_id = tags.id WHERE ' . $termNew . ' LIMIT 20';

        $resultSet = $app->getDI()->get('db')->query($sql);

        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        echo json_encode($resultSet->fetchAll());
    });
    
    /**
     * Set image tags
     */ 
    $app->post('/image/metadata/{id:[0-9]+}', function($id){
        $request = new Phalcon\Http\Request();

        $data = $request->getPost('tags', null, false);

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
    
    $app->get('/tags', function(){
        $request = new Phalcon\Http\Request();

        $term = $request->getQuery('term', null, false);
        $resultSet = Tags::find('name LIKE \'%' . $term . '%\'');
        
        echo json_encode($resultSet->toArray());
    });


    $app->handle();