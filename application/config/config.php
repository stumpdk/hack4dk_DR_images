<?php

        // Set up the database service
    $di->set('db', function () {
        return new Phalcon\Db\Adapter\Pdo\Mysql(
            array(
                "host"     => "127.0.0.1",
                "username" => "stumpdk",
                "password" => "",
                "dbname"   => "test",
                "charset"  => "utf8"
            )
        );
    });
    
    //If api is placed in a subfolder (domain.com/subfolder/api), set this value
    //to the name(s) of the subfolder
    $di->set('serverLocation', function(){
        return [''];
    });