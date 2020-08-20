<?php
  require_once "/usr/local/lib/php/vendor/autoload.php";
  include("db.php");

  $loader = new \Twig\Loader\FilesystemLoader('SIBW');
  $twig = new \Twig\Environment($loader);

  session_start();  /*Iniciamos sesión*/

  $URLNotValid = 0; /* Variable para controlar si hemos llegado a la página de inicio por un error*/
  $updateUserSent = 0;
  $createEventSent = 0;
  $updateEventSent = 0;
  $createUserSent = 0;
  $pirate = 0;
  $badOption = 0;
  $userData = []; /*Información relacionada con el usuario*/

  /*Si hemos llegado a la página por desloguearnos durante la creación de un evento, quizás puede que haya imágenes en el directorio "tmp", así que por si acaso lo borramos*/
  $files = glob('tmp/*'); /*obtenemos todos los nombres de los ficheros*/
  
  foreach($files as $file){
    unlink($file);
  }
  
  /*Vemos si hemos llegado a la página por introducir una URL no válida*/
  if(isset($_GET['error'])){
    $URLNotValid = $_GET['error'];
  }

  /*Vemos si hemos llegado a la página por actualizar los datos del usuario*/
  if(isset($_GET['updateUserSent'])){
    $updateUserSent = $_GET['updateUserSent'];
    $_SESSION['user'] = $_GET['nameUser']; /*Cambiamos el nombre del usuario, para mostrar los nuevos datos*/
  }

  /*Vemos si hemos llegado a la página por haber creado un evento*/
  if(isset($_GET['createEventSent'])){
    $createEventSent = $_GET['createEventSent'];
  }

  /*Vemos si hemos llegado a la página por haber actualizado un evento*/
  if(isset($_GET['updateEventSent'])){
    $updateEventSent = $_GET['updateEventSent'];
  }

  /*Vemos si hemos llegado a la página por haber creado un usuario*/
  if(isset($_GET['createUserSent'])){
    $createUserSent = $_GET['createUserSent'];
  }

  /*Vemos si hemos llegado a la página por intentar acceder a una página en la que no tenemos permiso*/
  if(isset($_GET['pirate'])){
    $pirate = $_GET['pirate'];
  }

  /*Vemos si hemos llegado a la página porque no sabíamos a que formulario redirigir*/
  if(isset($_GET['badOption'])){
    $badOption = $_GET['badOption'];
  }

  $eventNames = getEventNames(true);  /*Método con el que vamos a obtener el nombre de todos los métodos, que nos ayudará a modularizar la portada*/
  $indexPhotos = getIndexImage(); /*Método para obetener la imágen de portada de todos los eventos*/

  /*------------------------------------------------------ CONTROL DE LOGIN -----------------------------------------------------------*/
  $login = 0; /*Variable que nos indica si el usuario está logeado o no*/
  $filename = $_SERVER['PHP_SELF']; /*Indicamos el nombre del fichero actual, de forma que cuando nos logueemos nos reenvíe a la misma página*/
  $usernameError = "";  /*Para indicar el error si hemos introducido el usuario o la contraseña mal*/
  $passwordError = "";
  $loginError = [];  /*Para controlar errores en el logueo, devolviendo mensajes*/

  if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST['login'])){ /*Si hemos pulsado en el botón de logueo*/
      
      if(isset($_POST['user'])){  
        if(!empty($_POST['user'])){ /*Si el campo no está vacío*/
          if(findName($_POST['user'])){  /*Si el usuario es correcto, lo introducimos*/
            $_SESSION['user'] = $_POST['user'];
            
          }
  
          else{
            if(!empty($_SESSION['user'])){  /*Si despues de introducir un usuario bien lo introducimos mal, simplemente lo borramos del text input*/
              $_SESSION['user'] = null;
            }
            else{
              $usernameError = "Usuario incorrecto";  /*Si introduce mal el usuario, mostramos mensaje de error*/
              $loginError['user'] = $usernameError;
            }
            
          }
        }

        else{
          $usernameError = "Introduce un nombre de usuario";  /*Si deja el campo vacío mostramos mensaje de error*/
            $_SESSION['user'] = null;
            $loginError['user'] = $usernameError;
        }
      }

      if(isset($_POST['password'])){  

        if(!empty($_POST['password'])){
          if($_SESSION['user'] == $_POST['user']){  /*Solo mostraremos mensajes de que la contraseña es correcta o incorrecta si ha introducido un usuario correcto*/
            
            if(matchPassword($_SESSION['user'],$_POST['password'])){  /*Si la contraseña es correcta, la introducimos*/
              $_SESSION['password'] = $_POST['password'];
            }
    
            else{
              $passwordError = "Contraseña incorrecta";  /*Si introduce mal el usuario, mostramos mensaje de error*/
              $loginError['password'] = $passwordError;
            }
          }
         
        }

        else{
          $passwordError = "Introduce una contraseña";  /*Si deja el campo vacío mostramos mensaje de error*/
            $loginError['password'] = $passwordError;
        }
      }
    }

    if(isset($_POST['unlogin'])){/*Si hemos pulsado el botón de desloguear*/
      if(session_status()==PHP_SESSION_NONE){
          session_start();
      }
      
      session_unset();
      $cookieParams= session_get_cookie_params(); 
      
      setcookie(session_name(), $_COOKIE[session_name()], time()-2592000, $cookieParams['path'], 
      $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
      
      session_destroy();
    } 
  }

  if($_SESSION['user'] != null && isset($_SESSION['password'])){
    $login = 1;
    $userData = getUser($_SESSION['user']); /*Obtenemos toda la info del usuario, para enviarsela al formulario de edición de datos*/
  }

  echo $twig->render('newportada.html', ['eventNames'=>$eventNames,'indexPhotos'=>$indexPhotos,'URLNotValid'=>$URLNotValid,'login'=>$login,'loginError'=>$loginError,
                    'updateUserSent'=>$updateUserSent,'userData'=>$userData,'createEventSent'=>$createEventSent,'createUserSent'=>$createUserSent,'username'=>$_SESSION['user'],
                    'pirate'=>$pirate,'badOption'=>$badOption,'updateEventSent'=>$updateEventSent]);
?>
