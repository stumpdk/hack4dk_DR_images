<?php

class UrlHelper
{
    public static function getUrl(){
        $protocol = strpos($_SERVER["SERVER_PROTOCOL"], 'HTTPS') === false ? 'https://' : 'http://';//strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,strpos( $_SERVER["SERVER_PROTOCOL"],'/'))).'://';
        return $protocol . $_SERVER[HTTP_HOST];
    }
}