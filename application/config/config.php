<?php

    // Set up the database service
    $di->set('db', function () {
        return new Phalcon\Db\Adapter\Pdo\Mysql(
            array(
                "host"     => "127.0.0.1",
                "username" => "stumpdk",
                "password" => "",
                "dbname"   => "drs_historiske_billeder",
                "charset"  => "utf8"
            )
        );
    });
    
    //The location of the images (previews and thumbs)
    $di->set('imageLocation', function(){
        return 'https://s3-eu-west-1.amazonaws.com/drbilleder/';
    });
    
    //If api is placed in a subfolder (domain.com/subfolder/api), set this value
    //to the name(s) of the subfolder
    $di->set('serverLocation', function(){
        return ['/dev'];
    });