<?php
  require_once "/usr/local/lib/php/vendor/autoload.php";
  include("db.php");

  $loader = new \Twig\Loader\FilesystemLoader('SIBW');
  $twig = new \Twig\Environment($loader);

  session_start();

  $login = 0;

  if(isset($_SESSION['user']) && isset($_SESSION['password'])){
    $login = 1;
    $userData = getUser($_SESSION['user']); /*Obtenemos toda la info del usuario, para enviarsela al formulario de edición de datos*/
  }

  $nameEvent = "not valid"; /*Lo iniciamos con este valor para que, si escribimos en la URL la variable con un valor que no existe, nos redirigirá a la página de inicio con un aviso*/
  $totalNames = getEventNames(true); /*Para comprobar si el nombre, al ser metido desde URL, coincide con algun evento*/


  if(isset($_GET['ev'])){ /*Variable que llega para indicarnos el evento del que mostrar la pantalla de impresión*/
    foreach($totalNames as &$name){ /*Comprobamos que sea un nombre válido*/
      if($name == $_GET['ev']){
        $nameEvent = $_GET['ev'];
        break;
      }
    } 
  }

  /*En función del evento que nos llegue introduciremos un nombre de evento u otro*/
 
  $evento = getEvent($nameEvent);
  unset($eventos['todoComentarios']);
  unset($eventos['censurada']);

  echo $twig->render('newnewimprimir.html', ['evento'=>$evento,'login'=>$login,'userData'=>$userData]);
?>
