<?php
require( '../vendor/autoload.php');
require( '../application/config/config_no_phalcon.php');
session_start();

$fb = new Facebook\Facebook([  
  'app_id' => '976309079106997',  
  'app_secret' => '3d08707832a17ab10369f4f0643618aa',  
  'default_graph_version' => 'v2.4',  
  ]);  
  
//$helper = $fb->getRedirectLoginHelper();  
$helper = $fb->getJavaScriptHelper();
try {  
  $accessToken = $helper->getAccessToken();  
} catch(Facebook\Exceptions\FacebookResponseException $e) {  
  // When Graph returns an error  
  echo 'Graph returned an error: ' . $e->getMessage();  
  exit;  
} catch(Facebook\Exceptions\FacebookSDKException $e) {  
  // When validation fails or other local issues  
  echo 'Facebook SDK returned an error: ' . $e->getMessage();  
  exit;  
}  

// Unauthorized access
if (! isset($accessToken)) {  
  if ($helper->getError()) {  
    header('HTTP/1.0 401 Unauthorized');  
    echo "Error: " . $helper->getError() . "\n";
    echo "Error Code: " . $helper->getErrorCode() . "\n";
    echo "Error Reason: " . $helper->getErrorReason() . "\n";
    echo "Error Description: " . $helper->getErrorDescription() . "\n";
  } else {  
    header('HTTP/1.0 400 Bad Request');  
    echo 'Bad request';  
  }  
  exit;  
}  

// Logged in

/**
 * User integration
*/

// Get the user
$user = $fb->get('/me?fields=id,name,first_name', $accessToken->getValue())->getGraphUser();

$my = new mysqli($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['dbname']);
$my->set_charset("utf8");
if(!$stmt = $my->prepare('select count(id) as num from users where id = ?')){
    die ('could not prepare statement');
}
                $stmt->bind_param("s", $user->getId());
if(!$stmt->execute()){
    die ('could not check user: ' . $my->error);
}
    /* bind result variables */
    $stmt->bind_result($count);

    /* fetch values */
    $stmt->fetch();
    
    /* close statement */
    $stmt->close();

    if($count == 0){
        //User does not exist. Create user
        if(!$stmt2 = $my->prepare('insert into users (id, name, created) VALUES (?,?, NOW())')){
            die('could not prepare statement: '. $my->error());
        }

        $stmt2->bind_param('ss',$user->getId(), $user->getName());
        if(!$stmt2->execute()){
            die('could not create user.' . $my->error);
        }
    }
    //Create user session, and redirect
    $_SESSION['fb_access_token'] = (string) $accessToken;
    $_SESSION['fb_user_name'] = (string) $user->getName();
    $_SESSION['fb_user_id'] = $user->getId();
    $_SESSION['fb_user_first_name'] = $user['first_name'];
    
    header('Location: ' . $_GET['redirect']);