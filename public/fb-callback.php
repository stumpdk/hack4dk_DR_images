<?php
require(  '../vendor/autoload.php');
require( '../application/config_no_phalcon.php');
session_start();

$fb = new Facebook\Facebook([  
  'app_id' => '976309079106997',  
  'app_secret' => '3d08707832a17ab10369f4f0643618aa',  
  'default_graph_version' => 'v2.4',  
  ]);  
  
$helper = $fb->getRedirectLoginHelper();  
  
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
    var_dump($count);
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
    
    header('Location: /html');
/*
//echo '<h3>Access Token</h3>';  
//var_dump($accessToken->getValue());  
  
// The OAuth 2.0 client handler helps us manage access tokens  
$oAuth2Client = $fb->getOAuth2Client();  

// Get the access token metadata from /debug_token  
$tokenMetadata = $oAuth2Client->debugToken($accessToken);  
//echo '<h3>Metadata</h3>';  
//var_dump($tokenMetadata);  
  
// Validation (these will throw FacebookSDKException's when they fail)  
$tokenMetadata->validateAppId('976309079106997');  
// If you know the user ID this access token belongs to, you can validate it here  
// $tokenMetadata->validateUserId('123');  
$tokenMetadata->validateExpiration();   
   
if (! $accessToken->isLongLived()) {  
  // Exchanges a short-lived access token for a long-lived one  
  try {  
    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);  
  } catch (Facebook\Exceptions\FacebookSDKException $e) {  
    echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>";  
    exit;  
  } 
//  echo '<h3>Long-lived</h3>';  
//  var_dump($accessToken->getValue());  
}

$_SESSION['fb_access_token'] = (string) $accessToken;  
  
// User is logged in with a long-lived access token.  
// You can redirect them to a members-only page.  
// header('Location: https://example.com/members.php');*/