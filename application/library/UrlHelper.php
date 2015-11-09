<?php

class UrlHelper
{
    public static function getUrl($di = null){
        $subFolder = '/';
        if($di !== null){
            if(strlen($di->get('serverLocation')[0]) > 0){
                $subFolder = $di->get('serverLocation')[0] . '/';
            }
        }
        
        $protocol = strpos($_SERVER["SERVER_PROTOCOL"], 'HTTPS') === false ?  'http://' : 'https://';//strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,strpos( $_SERVER["SERVER_PROTOCOL"],'/'))).'://';
        return $protocol . str_replace(':80','', $_SERVER['HTTP_HOST']) . $subFolder;
    }
}