<?php
function curPageURL() {
 $pageURL = 'http';

 if ($_SERVER["SERVER_PORT"] == "443") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER['SERVER_PORT'] != "443") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}

//load image info
//load template
$imageId = $_GET['image_id'];

/*$url = 'http://'.$_SERVER['HTTP_HOST'];
$url = str_replace(':80','',$url);*/

if(!is_numeric($imageId)){
    $imageData = [];
    $imageData['image']['resizedUrl'] = curPageUrl() . '/frontimage.jpg';
    $imageData['image']['id'] = null;
    $imageData['tags'] = [];
}
else{
//include('../api/library/UrlHelper.php');

//$url = UrlHelper::getUrl();

$jsonurl = curPageUrl() . "/api/image/" . $imageId;
//echo $jsonurl;
$imageData = json_decode(file_get_contents($jsonurl), true);

if(!$imageData)
    return;
    
}

include('static_template.php');
?>