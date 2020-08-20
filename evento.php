<?php
  require_once "/usr/local/lib/php/vendor/autoload.php";
  include("db.php");

  $loader = new \Twig\Loader\FilesystemLoader('SIBW');
  $twig = new \Twig\Environment($loader);
  $nameEvent = "not valid"; /*Lo iniciamos con este valor para que, si escribimos en la URL la variable con un valor que no existe, nos redirigirá a la página de inicio con un aviso*/
  $totalNames = getEventNames(true); /*Para comprobar si el nombre, al ser metido desde URL, coincide con algun evento*/

  session_start();  /*Iniciamos sesión*/
  
  /*Si recibimos el nombre del evento, ya sea por get (URL), o por post (un form)*/
  if(isset($_GET['nameEvent'])){
    foreach($totalNames as &$name){
      if($name == $_GET['nameEvent']){
        $nameEvent = $_GET['nameEvent'];
        break;
      }
    } 
  }


  if(isset($_POST['nameEvent'])){
    foreach($totalNames as &$name){
      if($name == $_POST['nameEvent']){
        $nameEvent = $_POST['nameEvent'];
        break;
      }
    } 
  }


  /*------------------------------------------------------ CONTROL DE LOGIN -----------------------------------------------------------*/
  $login = 0; /*Variable que nos indica si el usuario está logeado o no*/
  $filename = $_SERVER['PHP_SELF']; /*Indicamos el nombre del fichero actual, de forma que cuando nos logueemos nos reenvíe a la misma página*/
  $usernameError = "";  /*Para indicar el error si hemos introducido el usuario o la contraseña mal*/
  $passwordError = "";
  $loginError = [];  /*Para controlar errores en el logueo, devolviendo mensajes*/
  
  $nameInput = $emailInput = $commentInput = "";
  $nameInputError = $emailInputError = $commentInputError = "";

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

    if(isset($_POST['sendComment'])){ /*Si hemos pulsado el botón de enviar comentario*/
      $sent = 0; /*Variable que nos dice si se añadió o no el comentario a la base de datos*/
      
      if(empty($_POST['nameInput'])){  /*Si el campo de nombre no tiene valor*/
        $nameInputError = "Introduce un nombre de usuario";
      }
      else{   /*Si tiene valor, lo almacenamos*/
        $nameInput = test_input($_POST['nameInput']); /*Llamamos al método de tratamiento de cadena, evitando posibles ataques*/
      }
      

      /*Hacemos lo mismo para el campo de email y de comentario, pero llamando a la función checkFormatEmail para verificar que es un email válido*/
      if(empty($_POST['emailInput'])){ 
        $emailInputError = "Introduce un correo";
      }
      else if (checkFormatEmail($_POST['emailInput']) == false){
        $emailInputError = "El correo no es válido";
      }
      else{
        $emailInput = test_input($_POST['emailInput']);
      }


      if(empty($_POST['commentInput'])){  /*Si el campo de comentario no tiene valor*/
        $commentInputError = "Introduce un comentario";
      }
      else{   /*Si tiene valor, lo almacenamos*/
        $commentInput = test_input($_POST['commentInput']); /*Llamamos al método de tratamiento de cadena, evitando posibles ataques*/
      }

     
      $evento['nameInputError']=$nameInputError;
      $evento['emailInputError']=$emailInputError;
      $evento['commentInputError']=$commentInputError;

      if($nameInputError =="" && $emailInputError == "" && $commentInputError == ""){ /*Si todo ha ido bien*/
        $currentDate = getCurrentDate();
        addComment($nameInput,$currentDate,$emailInput,$commentInput,$nameEvent);
        $sent = 1;
        $evento['sent'] = $sent;
      }

      else{ /*Si sigue habiendo error*/
        $evento['nameInput']=$nameInput;
        $evento['emailInput']=$emailInput;
        $evento['commentInput']=$commentInput;
      }

    }
  }

  if(isset($_SESSION['user']) && isset($_SESSION['password'])){
    $login = 1;
    $userData = getUser($_SESSION['user']); /*Obtenemos toda la info del usuario, para enviarsela al formulario de edición de datos*/
  }


  /*Función para validar la entrada de los formularios, evitando posibles ataques*/
    function test_input($data) {
      $data = trim($data);  /*Eliminamos espacios extras que sobren*/
      $data = stripslashes($data);  /*Eliminamos los "\" */
      $data = htmlspecialchars($data); /*Evitamos que se introduzcan caracteres especiales, traduciendolos a texto plano*/
      return $data;
    }

   /*Función para ver que el correo cumple con el formato necesario*/
    function checkFormatEmail($email){
      $correct = false;

      if(preg_match('/^[A-z0-9\\._-]+@[A-z0-9][A-z0-9-]*(\\.[A-z0-9_-]+)*\\.([A-z]{2,6})$/', $email)){
        $correct = true;
      }

      return $correct;
    }

    /*Función para calcular la hora (ajustando a los segundos). El motivo para hallarla ajustando a los segundo es porque al ser una medida de tiempo tan
      exacta, con ella, el nombre y los demas datos sabemos exactamente a que comentario nos referimos para,por ejemplo, poder borrarlo, cosa que si por ejemplo
      ajustamos a los minutos, si un mismo usuario mete exactamente los mismos datos en ese minuto en el mismo evento, podría ocasionar un problema*/
      
      function getCurrentDate(){
        return date("d")."/".date("m")."/".date("y")."  ".(date("H")+2).":".date("i").":".date("s");
      }

    /*En función del evento que nos llegue introduciremos un nombre de evento u otro (Lo hacemos al final para que tenga en cuenta los nuevos
      comentarios añadidos cada vez que cargue)*/
 
    $eventoAuxiliar = getEvent($nameEvent);

    $evento['textoPagina'] = $eventoAuxiliar['textoPagina'];
    $evento['fotosPagina'] = $eventoAuxiliar['fotosPagina'];
    $evento['todoComentarios'] = $eventoAuxiliar['todoComentarios'];
    $evento['censuradas'] = $eventoAuxiliar['censuradas'];
    $evento['modificados'] = $eventoAuxiliar['modificados'];
    $evento['tags'] = $eventoAuxiliar['tags'];


  if($nameEvent == "not valid"){/*Si hemos introducido algo raro en la URL*/
    header("Location: index.php?error=1");
  }  

  echo $twig->render('evento.html', ['evento'=>$evento,'login'=>$login,'loginError'=>$loginError,'userData'=>$userData,'username'=>$_SESSION['user']]);
?>
