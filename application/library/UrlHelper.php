<?php

class UrlHelper
{
    public static function getUrl($di = null){
        $subFolder = '';
        if($subFolder !== null){
            $subFolder = '/' . $di->get('serverLocation')[0];
        }
        
        $protocol = strpos($_SERVER["SERVER_PROTOCOL"], 'HTTPS') === false ?  'http://' : 'https://';//strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,strpos( $_SERVER["SERVER_PROTOCOL"],'/'))).'://';
        return $protocol . str_replace(':80','', $_SERVER['HTTP_HOST']) . $subFolder;
    }
}