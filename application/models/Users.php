<?php

use Phalcon\Mvc\Model;

class Users extends Model
{
    /**
     *  Check if the user is logged in
     */ 
    public function checkLogin(){
        $this->getDI()->get('session')->start();
        return $this->getDI()->get('session')->has('fb_access_token');
    }
    
    /**
     *  The logput method resets the session information of the user
     */ 
    public function logout()
    {
        $this->getDI()->get('session')->remove('fb_access_token');
        $this->getDI()->get('session')->remove('fb_user_id');
        $this->getDI()->get('session')->remove('fb_user_name');
        $this->getDI()->get('session')->remove('fb_user_first_name');
    }
    
    /**
     *  Get the Facebook name of the user
     */ 
    public function getFbName()
    {
        return $this->getDI()->get('session')->get("fb_user_first_name");
    }
    
    /**
     * Get the Facebook user id of the user
     */ 
    public function getFbId(){
        $session = $this->getDI()->get('session');
        
        if($session->has('fb_user_id'))
            return $session->get("fb_user_id");
        
        return null;
    }
}