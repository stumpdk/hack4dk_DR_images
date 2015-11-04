<?php
//load image info
//load template
$imageId = $_GET['image_id'];

$url = 'http://'.$_SERVER['HTTP_HOST'];
$url = str_replace(':80','',$url);

if(!is_numeric($imageId)){
    $imageData = [];
    $imageData['image']['resizedUrl'] = $url . '/frontimage.jpg';
    $imageData['image']['id'] = null;
    $imageData['tags'] = [];
}
else{
//include('../api/library/UrlHelper.php');

//$url = UrlHelper::getUrl();

$jsonurl = $url . "/api/image/" . $imageId;
//echo $jsonurl;
$imageData = json_decode(file_get_contents($jsonurl), true);

if(!$imageData)
    return;
    
}

include('static_template.php');
?>