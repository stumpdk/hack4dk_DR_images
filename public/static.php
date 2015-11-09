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

if (
    strpos($_SERVER["HTTP_USER_AGENT"], "facebookexternalhit/") === false &&          
    strpos($_SERVER["HTTP_USER_AGENT"], "Facebot") === false/* && 
    !isset($_GET['debug'])*/
) {
  //It's not Facebook looking, let's redirect the user
  header('Location: ' . str_replace('static.php','', curPageUrl()));
/*  echo 'redirects here. Real user assumed. This is user agent: ' . $_SERVER["HTTP_USER_AGENT"];
  var_dump( strpos($_SERVER["HTTP_USER_AGENT"], "facebookexternalhit/"));
  var_dump( strpos($_SERVER["HTTP_USER_AGENT"], "Facebot"));*/
}

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

$jsonurl = substr(curPageUrl(), 0, strrpos(curPageUrl(), '/')) . "/api/image/" . $imageId;

//echo $jsonurl;
$imageData = json_decode(file_get_contents($jsonurl), true);
}
if(!$imageData){
    echo 'no image data found!';
    return;
}

include('static_template.php');

?>