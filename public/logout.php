<?php
    session_start();
    
$my = new mysqli("127.0.0.1", "stumpdk", "", "test");
if(!$stmt = $my->prepare('update users set last_seen = NOW() where id = ?')){
    die ('could not prepare statement');
}
                $stmt->bind_param("s", $_SESSION['fb_user_id']);
if(!$stmt->execute()){
    die ('could update user log: ' . $my->error);
}    
    
    //Create user session, and redirect
    unset($_SESSION['fb_access_token']);
    unset($_SESSION['fb_user_name']);
    unset($_SESSION['fb_user_id']);
    unset($_SESSION['fb_user_first_name']);
    
    header('Location: ' . $_GET['redirect']);