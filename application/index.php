<?php
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\DI\FactoryDefault;
use Phalcon\Http\Response as Response;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Session\Adapter\Files as Session;

require( __DIR__ . '../../vendor/autoload.php');

    // Use Loader() to autoload classes
    $loader = new Loader();
    
    //Register dirs from which the autoloader should load classes
    $loader->registerDirs(
        array(
            __DIR__ . '/models/',
            __DIR__ . '/library/'
        )
    )->register();
    
    //New Dependency Injector
    $di = new FactoryDefault();
    
    //Run config.php
    require __DIR__ . '/config/config.php';
    
    // Start the session the first time when some component request the session service
    $di->setShared('session', function () {
        $session = new Session();
        $session->start();
        return $session;
    });    
    
    //Set shared Facebook SDK
    $di->setShared('facebook', function() {
        return new Facebook\Facebook([
          'app_id' => '976309079106997',
          'app_secret' => '3d08707832a17ab10369f4f0643618aa',
          'default_graph_version' => 'v2.4',
        ]);
    });
    
    //Set request object
    $di->set("request", "Phalcon\Http\Request", true);
    
    //Instantiate Phalcon Micro framework
    $app = new Micro();
    $app->setDI($di);
    
    //Create response object
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

        $id= rand($minMax->minimum, $minMax->maximum);
        
        $image = new Images();
        $response->setJsonContent($image->getImageInfo($id));
        $response->send();
    });
    
    /**
     * Get specific image
     */ 
    $app->get('/image/{id:[0-9]+}', function($id) use ($app, $response) {
        $image = new Images();

        $response->setJsonContent($image->getImageInfo($id));
        $response->send();
    });

    /**
     * Get latest tags
     */ 
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
    
    /**
     * Get stats
     */ 
    $app->get('/stats', function() use ($response){
    //    $it = new ImagesTags();
        $user = new Users();
        $user_id = $user->getFbId();
        $userTags = 0;
        if(isset($user_id)){
            $userTags = ImagesTags::count(['conditions' => 'user_id = '. $user_id]);
        }
        $rowcount = ImagesTags::count(['distinct' => 'image_id', 'conditions' => 'confidence is null']);
        $imagesCount = Images::count();
        $tags = ImagesTags::count(['distinct' => 'tag_id', 'conditions' => 'confidence is null']);
        $users = Users::count();
        
       // $latestTag = ImagesTags::findFirst(['orderBy' => 'created DESC', 'conditions' => 'confidence is null', 'limit' => 1])->getTags();
        $response->setJsonContent([
            'imagesWithTags' => $rowcount, 
            'images' => $imagesCount, 
            'tags' => $tags, 
            'userTags' =>$userTags, 
            'latestTag' => 0, 
            'users' => $users
        ]);
        $response->send();
    });
    
    /**
     * Present image list by offset and limit
     */ 
    $app->get('/images/{offset:[0-9]+}/{limit:[0-9]+}', function($offset, $limit) use ($response){
        if($limit > 10000)
            die('maximum limit is 10000');
            
        echo json_encode(Images::find(['offset' => $offset, 'limit' => $limit])->toArray(), JSON_NUMERIC_CHECK);
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

        //Create a query based on the terms
        $termNew = "";
        foreach($terms as $term){
            $termNew = $termNew . 'tags.name LIKE \'%' . $term . '%\' AND ';
        }
        
        $termNew = substr($termNew, 0, strlen($termNew)-4);

        $url = $app->getDI()->get('imageLocation');
        $sql = 'select distinct(images.id), CONCAT("'. $url .'",images.id,"_thumb.jpg") as url from images left join images_tags ON images.id = images_tags.image_id LEFT JOIN tags on images_tags.tag_id = tags.id WHERE ' . $termNew . ' ';

        $resultSet = $app->getDI()->get('db')->query($sql);
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        
        $data = [];
        foreach($resultSet->fetchAll() as $row){
            $data[] = $row;
        }
        
        echo json_encode($data);
    });
    
    /**
     * Get latest tagged images
     */ 
    $app->get('/images/latest', function() use ($app){
        $url = $app->getDI()->get('imageLocation');
        $sql = 'select distinct(images.id), CONCAT("'. $url .'",images.id,"_thumb.jpg") as url from images left join images_tags ON images.id = images_tags.image_id order by images_tags.created DESC limit 30';
        $resultSet = $app->getDI()->get('db')->query($sql);
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        
        echo json_encode($resultSet->fetchAll());
    });
    
    /**
     * Set image tags
     */ 
    $app->post('/image/metadata/{id:[0-9]+}', function($id) use ($app){
        $request = new Phalcon\Http\Request();
        $filter = new Phalcon\Filter();
        $user = new Users();
    
        $data = $request->getPost('tags', null, false);

        $image = Images::findFirst("id = '" . $id . "'");   

        $tags = [];   
        
        /**
         * Save each tag
         * This is done by:
         * 1) Getting/creating the tag
         * 2) Creating an imageTag
         * 3) Saving the imageTag
         */ 
        foreach($data as $tagRow){
            
            $name = $filter->sanitize($tagRow['name'], 'string');
            
            //Get tag if it exists already
            $tag = Tags::findFirst("name = '" . $name . "' AND category_id = '" . $tagRow['category_id']  . "'");
            
            if(!$tag)
                $tag = new Tags();

            $tag->name = $name;
            $tag->category_id = $tagRow['category_id'];
            
            //If the tag could not be saved, dump the error messages
            if(!$tag->save()){
                echo 'could not save tag.';
                var_dump($tagRow);
                var_dump($tag->getMessages());
                $app->response->setStatusCode('500');
                $app->response->send();
            }
            
            $tag->refresh();
            
            //Create an imageTag for each tag
            $imagesTags = new ImagesTags();
            $imagesTags->tag_id = $tag->id;
            $imagesTags->image_id = $image->id;
            $imagesTags->x = $tagRow['x'];
            $imagesTags->y = $tagRow['y'];
            $imagesTags->user_id = $user->getFbId();
            
            //If the imageTag could not be saved, dump the error message
            if(!$imagesTags->save()){
                var_dump($imagesTags->getMessages());
                $app->response->setStatusCode('500');
                $app->response->send();
            }
            
            $tags[] = $tag;
        }
        
        $image->imagesTags->tags = $tags;
        
        //There was an error saving the tags. Dump the error message
        if(!$image->save()){
            var_dump($image->getMessages());
            $app->response->setStatusCode('500');
            $app->response->send();
        }

        //Return status code 200 if all went well
        $app->response->setStatusCode('200');
        $app->response->setJsonContent([]);
        $app->response->send();
        
    });
    
    /**
     * Search for tags
     */ 
    $app->get('/tags', function(){
        $request = new Phalcon\Http\Request();

        $term = $request->getQuery('term', null, false);
        $resultSet = Tags::find('name LIKE \'%' . $term . '%\' AND is_used = 1');
        
        echo json_encode($resultSet->toArray());
    });

    //Handle the request
    $app->handle();