<?php
  require_once "/usr/local/lib/php/vendor/autoload.php";
  include("db.php");
  
  $loader = new \Twig\Loader\FilesystemLoader('SIBW');
  $twig = new \Twig\Environment($loader);

  session_start();

  $login = 0;

  if(isset($_SESSION['user']) && isset($_SESSION['password'])){
    $login = 1;
    $userData = getUser($_SESSION['user']);
  }

  echo $twig->render('newquienes.html', ['login' =>$login,'userData'=>$userData]);
?>
