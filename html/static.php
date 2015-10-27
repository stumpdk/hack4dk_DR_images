<?php
//load image info
//load template
$imageId = $_GET['image_id'];
if(!is_numeric($imageId))
    return;
    
//include('../api/library/UrlHelper.php');

//$url = UrlHelper::getUrl();
$url = 'http://'.$_SERVER['HTTP_HOST'];
$url = str_replace(':80','',$url);
$jsonurl = $url . "/api/image/" . $imageId;
//echo $jsonurl;
$imageData = json_decode(file_get_contents($jsonurl), true);

if(!$imageData)
    return;
    
include('static_template.php');
?>