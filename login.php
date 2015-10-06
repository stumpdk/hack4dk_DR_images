<?php
require( __DIR__ . '/vendor/autoload.php');
session_start();

$fb = new Facebook\Facebook([
  'app_id' => '976309079106997',
  'app_secret' => '3d08707832a17ab10369f4f0643618aa',
  'default_graph_version' => 'v2.4',
]);

$helper = $fb->getRedirectLoginHelper();

$permissions = []; // Optional permissions
$loginUrl = $helper->getLoginUrl('https://hack4dk-2015-stumpdk-1.c9.io/fb-callback.php', $permissions);

echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';