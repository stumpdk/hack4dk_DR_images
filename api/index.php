<?php
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\DI\FactoryDefault;
use Phalcon\Http\Response as Response;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Session\Adapter\Files as Session;

require( __DIR__ . '/../vendor/autoload.php');

    // Use Loader() to autoload our model
    $loader = new Loader();
    
    $loader->registerDirs(
        array(
            __DIR__ . '/models/',
            __DIR__ . '/library/'
        )
    )->register();
    
    //New DI
    $di = new FactoryDefault();
    
    //Run config.php
    require './config/config.php';
    
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

    });
    
    /**
     * Present stats
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
        $imagesThumbs = Images::count(['conditions' => 's3_preview = 1']);
        $tags = ImagesTags::count(['distinct' => 'tag_id', 'conditions' => 'confidence is null']);
        $users = Users::count();
        
       // $latestTag = ImagesTags::findFirst(['orderBy' => 'created DESC', 'conditions' => 'confidence is null', 'limit' => 1])->getTags();
        $response->setJsonContent([
            'imagesWithTags' => $rowcount, 
            'images' => $imagesCount, 
            'tags' => $tags, 
            'userTags' =>$userTags, 
            'latestTag' => 0, 
            'users' => $users,
            'ImagesThumbs' => $imagesThumbs
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
     * Resizing images
     */ 
    $app->get('/img_resize/{id:[0-9]+}/{size}', function($id, $size) use ($app, $response) {
        $image = Images::findFirstById($id);
        $s3_thumb = 0;
        $s3_preview = 0;
        $imageData = false;
        
        if($size == 'preview'){
            $size = Images::SIZE_PREVIEW;
            $s3_preview = 1;
        }
        else{
            $size = Images::SIZE_THUMB;
            $s3_thumb = 1;
        }
        
        if(!$image){
            $response->setJsonContent(['status' => 'image not found!']);
            $response->send();
        }
        else{
          $imageData = $image->loadFileContent($id.$size, $image->url, $size);
          if($imageData == false){
              $imageData = $image->resizeExternalFile($image->url, $size);
          }
          
          header("Content-type: image/jpeg");
          echo $imageData;
          $image->s3_preview = $s3_preview;
          $image->s3_thumb = $s3_thumb;
          $image->save();
          
          $image->saveFileContent($id.$size, $imageData);
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
            $termNew = $termNew . 'tags.name LIKE \'%' . $term . '%\' AND ';
        }
        
        $termNew = substr($termNew, 0, strlen($termNew)-4);

        $url = UrlHelper::getUrl($app->getDI()) . '/api/img_resize/';
        $sql = 'select distinct(images.id), s3_thumb, CONCAT("'. $url .'",images.id,"/thumb") as url from images left join images_tags ON images.id = images_tags.image_id LEFT JOIN tags on images_tags.tag_id = tags.id WHERE ' . $termNew . ' ';
        $phql = "select Images.* from Images left join ImagesTags ON Images.id = ImagesTags.image_id LEFT JOIN Tags on ImagesTags.tag_id = Tags.id WHERE Tags.is_used = 1 AND Tags.name LIKE '%" . $request->getQuery('term', null, false) . "%'";
        
        $resultSet = $app->getDI()->get('db')->query($sql);
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        
        $data = [];
        foreach($resultSet->fetchAll() as $row){
            if($row['s3_thumb'] == 1)
                $row['url'] = 'https://s3-eu-west-1.amazonaws.com/crowdsourcing-dr-images/' . $row['id'] . Images::SIZE_THUMB;
            
            $data[] = $row;
        }
        echo json_encode($data);
    });
    
    /**
     * Set image tags
     */ 
    $app->post('/image/metadata/{id:[0-9]+}', function($id) use ($app){
        $request = new Phalcon\Http\Request();
        $user = new Users();
        $data = $request->getPost('tags', null, false);

        $image = Images::findFirst("id = '" . $id . "'");   

        $tags = [];   
        
        foreach($data as $tagRow){
            $tag = Tags::findFirst("name = '" . $tagRow['name'] . "' AND category_id = '" . $tagRow['category_id']  . "'");
            
            if(!$tag)
                $tag = new Tags();

            $tag->name = $tagRow['name'];
            $tag->category_id = $tagRow['category_id'];
            
            if(!$tag->save()){
                echo 'could not save tag.';
                var_dump($tagRow);
                var_dump($tag->getMessages());
                $app->response->setStatusCode('500');
                $app->response->send();
            }
            $tag->refresh();
            
            $imagesTags = new ImagesTags();
            $imagesTags->tag_id = $tag->id;
            $imagesTags->image_id = $image->id;
            $imagesTags->x = $tagRow['x'];
            $imagesTags->y = $tagRow['y'];
            $imagesTags->user_id = $user->getFbId();
                        
            if(!$imagesTags->save()){
                var_dump($imagesTags->getMessages());
                $app->response->setStatusCode('500');
                $app->response->send();
            }
            
            $tags[] = $tag;
        }
        
        $image->imagesTags->tags = $tags;
        
        $hasThumb = $image->s3_thumb;
        
        $image->s3_thumb = 1;
        if(!$image->save()){
            var_dump($image->getMessages());
            $app->response->setStatusCode('500');
            $app->response->send();
        }

        //We only resize if the image is not resized already
        if($hasThumb == 0){
            $data = $image->resizeExternalFile($image->url, Images::SIZE_THUMB);
            $image->saveFileContent($id.Images::SIZE_THUMB, $data);
        }
        
        $app->response->setStatusCode('200');
        $app->response->setJsonContent([]);
        $app->response->send();
        
    });
    
    $app->get('/tags', function(){
        $request = new Phalcon\Http\Request();

        $term = $request->getQuery('term', null, false);
        $resultSet = Tags::find('name LIKE \'%' . $term . '%\' AND is_used = 1');
        
        echo json_encode($resultSet->toArray());
    });

    $app->handle();