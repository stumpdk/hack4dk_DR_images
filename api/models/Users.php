<?php

use Phalcon\Mvc\Model;

class Users extends Model
{
    public function checkLogin(){
        $this->getDI()->get('session')->start();
        return $this->getDI()->get('session')->has('fb_access_token');
    }
    
    public function logout()
    {
        $this->getDI()->get('session')->remove('fb_access_token');
        $this->getDI()->get('session')->remove('fb_user_id');
        $this->getDI()->get('session')->remove('fb_user_name');
        $this->getDI()->get('session')->remove('fb_user_first_name');
    }
    
    public function getFbName()
    {
        return $this->getDI()->get('session')->get("fb_user_first_name");
    }
    
    public function getFbId(){
        return $this->getDI()->get('session')->get("fb_user_id");
    }
    
    public function test()
    {
        
        $fb = $this->getDI()->get('facebook');
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
        $fb->get('/me', $accessToken);
    }
}