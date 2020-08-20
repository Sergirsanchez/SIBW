<?php
  require_once "/usr/local/lib/php/vendor/autoload.php";
  include("db.php");

  $loader = new \Twig\Loader\FilesystemLoader('SIBW');
  $twig = new \Twig\Environment($loader);
  $URLNotValid = 0; /* Variable para controlar si hemos llegado a la página de inicio por un error*/
  $commentSent = 0; /*Variable que nos indica si hemos llegado a la página porque hemos modificado un comentario*/
  $intError = 0; /*Error si intentamos introducir otra variable que no sea entero en el formulario de eventos y en el de comentarios*/
  $result = [];/*Aquí vamos a almacenar la info relativa a todos los comentarios*/
  $userList = 0;/*Para saber si la lista que vamos a mostrar es de los usuarios*/
  $userData = [];
  $userDeleted = 0; /*Nos indica si hemos eliminado o no un usuario*/
  $userModified = 0;/*Si hemos modificado los permisos de un usuario o no*/

  /*Vamos a ver si hemos recibido la variable correspondiente al exito en la edición de un comentario*/
  if(isset($_GET['commentSent'])){
    $commentSent = 1;
    $result['commentList'] = 1;
  }

   /*Comprobación de si hemos escrito mal una URL*/
  if(isset($_GET['error'])){ 
    $URLNotValid = $_GET['error'];
  }

  if(isset($_GET['userList'])){  /*Si vamos a listar a los usuarios*/
    $userList = 1;
    $usersData = getUsersData();  /*Obtenemos la info de todos los usuarios*/
  }

  if(isset($_GET['userModified'])){  /*Los listamos tras haber modificado los permisos de uno*/
    $userModified = 1;
    $usersData = getUsersData(); 
  }

/*-------------------- Proceso para eliminar un comentario/evento ----------------------------------*/
  
  if(isset($_POST['delete']) == 1){  /*Si hemos pulsado en borrar un evento*/
    if($_POST['deleteComment'] == 1){ /*Vamos a eliminar un comentario*/
      removeComment($_POST['eventName'],$_POST['user'],$_POST['date']); 
      $removed = "comment";
      $result = insertDataCommentIntoResult(0,0); /*Añadimos todos los datos relativos a los comentarios*/
      $result['removed'] = $removed;
    }

    if($_POST['deleteList'] == 1){
      
      removeEvent($_POST['eventName']);

      $removed = "list";

      $result = insertDataEventIntoResult(0,0,true);

      $result['removed'] = $removed;
    }

  }

  

/*--------------------Obtener los resultados de la búsqueda--------------------------------------*/
  if(isset($_GET['listComments'])){
    if($_GET['listComments'] == 1){ /*Esta variable GET con valor 1 la recibimos al pinchar en el enlace que nos lleva a la lista de comentarios*/
      $result = insertDataCommentIntoResult(0,0); /*Añadimos todos los datos relativos a los comentarios*/
    }
  }

  if($result['commentList'] == 1){  /*Venimos de actualizar un comentario*/
    if($result == null){
      $result = insertDataCommentIntoResult(0,0); /*Añadimos todos los datos relativos a los comentarios*/
    }
  }

  if(isset($_GET['listEvents'])){
    if($_GET['listEvents'] == 1){ /*Esta variable GET con valor 1 la recibimos al pinchar en el enlace que nos lleva a la lista de eventos*/

      $result = insertDataEventIntoResult(0,0,true);

    }
  }

  if(isset($_POST['event'])){
    
      $result['commentList'] = $_POST['commentList'];
      $result['eventList'] = $_POST['eventList'];
  
      if(!empty($_POST['event'])){ 
        if((int)$_POST['event'] == null){ /*Hemos introducido texto*/
          $intError = 1;

        $result = insertDataEventIntoResult(0,0,true);

        }

        else{ /*Si introudcimos número*/
          if($result['commentList'] == 1){  /*Estamos en una lista de comentarios*/
            $result = insertDataCommentIntoResult($_POST['event'],1); /*Añadimos todos los datos relativos a los comentarios*/   
          }
    
          else{ /*Estamos en una lista de un solo evento*/

            if(isset($_POST["published"])){
              $result = insertDataEventIntoResult($_POST['event'],1,false);
            }

            else{
              $result = insertDataEventIntoResult($_POST['event'],1,true);
            }
            
          }

        }
        
      }
  
      else{ /*Si no introducimos nada, volvemos a listar todos los eventos/comentarios*/
        if($result['commentList'] == 1){  /*Si estamos en una lista de comentarios, los obtenemos*/
  
          $result = insertDataCommentIntoResult(0,0); /*Añadimos todos los datos relativos a los comentarios*/
        }
  
        else{ /*Si no, obtenemos los eventos*/

          if(isset($_POST["published"])){  /*Si obtenemos el resultado del checkbox, mostramos también los no publicados*/ 
            $result = insertDataEventIntoResult(0,0,false);
          }
          
          else{
            $result = insertDataEventIntoResult(0,0,true);
          }
        }
      }
  }

  if(isset($_POST['deleteUser'])){ /*Vamos a eliminar al usuario de la base de datos*/
    deleteUser($_POST['userToDelete']);
    $userList = 1;
    $usersData = getUsersData();  /*Obtenemos la info de todos los usuarios*/
    $userDeleted = 1;
  }

  /*------------------------------------------------------ CONTROL DE LOGIN -----------------------------------------------------------*/
  $login = 0; /*Variable que nos indica si el usuario está logeado o no*/
  $filename = "index.php"; /*Si el usuario se desloguea estando en la lista, se envía automáticamente a index.php*/
  $usernameError = "";  /*Para indicar el error si hemos introducido el usuario o la contraseña mal*/
  $passwordError = "";
  $loginError = [];  /*Para controlar errores en el logueo, devolviendo mensajes*/

  session_start();  /*Iniciamos sesión*/

  if($_SERVER["REQUEST_METHOD"] == "POST"){
   
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

  /*------------------------------------------- FUNCIÓN PARA INSERTAR LOS ELEMENTOS DE LOS COMENTARIOS EN EL RESULT --------------------*/
  function insertDataCommentIntoResult($number,$singleEvent){
    $resultAux = getComments($number); /*Método con el que obtenemos la info de los comentarios (si el parámetro es 0, se obtiene la info de todos)*/

    $result['names'] = $resultAux['names'];
    $result['dates'] = $resultAux['dates'];
    $result['comments'] = $resultAux['comments'];
    $result['eventNames'] = $resultAux['eventNames'];
    $result['emails'] = $resultAux['emails'];
    $result['modify'] = $resultAux['modify'];
    $result['singleEvent'] = $singleEvent; 
    $result['commentList'] = 1;
    $result['eventList'] = 0;

    return $result;
  }

  /*---------------------------------- FUNCIÓN PARA OBTENER LOS DATOS DE LOS EVENTOS --------------------------------------------------*/
  function insertDataEventIntoResult($number,$singleEvent,$onlyPublished){
    $resultAux = getEventByNumber($number,$onlyPublished); /*Método con el que obtenemos la info de los comentarios (si el parámetro es 0, se obtiene la info de todos)*/

    $result['textoPagina'] = $resultAux['textoPagina'];

    /*Vamos a almacenar ya las etiquetas como se deben mostrar*/
    if($singleEvent == 1){
      $tags = $resultAux['tags'][0];

      if(sizeof($resultAux) < 0){ /*Si no meto este if y el de la línea 223 tengo error de que en los for de justo debajo sizeof() debe tener un objeto contable*/
        for($i = 1; $i < sizeof($resultAux['tags']);$i++){
          $tags = $tags.", ".$resultAux['tags'][$i];
        }
      }
    }

    else{
      $tags = [];
      $eventNames = getEventNames($onlyPublished);
      $firstIteration = 0;
      
      for($i = 0; $i < sizeof($eventNames); $i++){ 
        for($j = 0; $j < sizeof($resultAux['tags']);$j++){
          if($resultAux['tags'][$j]['evento'] == $eventNames[$i]){

            if($firstIteration == 0){
              $eventTags = $resultAux['tags'][$j]['etiqueta'];
              $firstIteration = 1;
            }
            else{
              $eventTags = $eventTags.", ".$resultAux['tags'][$j]['etiqueta'];
            }

          }
        }
        array_push($tags,$eventTags);
        $eventTags = "";
        $firstIteration = 0;
      }
    }

    $result['tags'] = $tags;

    /*Como no podemos pasar un array de Twig desde un HTML a un PHP, almacenaremos las fotos separadas con comas, del mismo modo que hicimos con las etiquetas,
      y en el PHP de destino ya lo introduciremos dentro de un array*/

    if($singleEvent == 1){
      $eventPhotos = $resultAux['fotosPagina']['src'];
      $photos = $resultAux['fotosPagina']['src'][0];

      if(sizeof($resultAux)  < 0){
        for($i = 1; $i < sizeof($resultAux['fotosPagina']['src']); $i++){
          $photos = $photos.",".$resultAux['fotosPagina']['src'][$i];
        }
      }

    }
    else{
      $photos = [];
      $eventNames = getEventNames($onlyPublished);
      $firstIteration = 0;
      

      for($i = 0; $i < sizeof($eventNames); $i++){ 
        for($j = 0; $j < sizeof($resultAux['fotosPagina']);$j++){
          if($resultAux['fotosPagina'][$j]['evento'] == $eventNames[$i]){
            if($firstIteration == 0){
              $eventPhotos = $resultAux['fotosPagina'][$j]['src'];
              $firstIteration = 1;
            }
            else{
              $eventPhotos = $eventPhotos.",".$resultAux['fotosPagina'][$j]['src'];
            }

          }
        }
        array_push($photos,$eventPhotos);
        $eventPhotos = "";
        $firstIteration = 0;
      }
    }
    
    $result['fotosPagina'] = $photos;
    $result['singleEvent'] = $singleEvent;

    
    if($singleEvent == 1){/*Almacenamos el número del evento*/
      $result['eventNumber'] = $number;
    }
    $result['commentList'] = 0;
    $result['eventList'] = 1;

    /*Tal y como lo teníamos hecho ya para los comentarios, tenemos que introducir datos en la variable "eventNames"*/
    if($singleEvent == 0){
      $result['eventNames'] = getEventNames($onlyPublished);
    }

    else{
      $currentEvent = getEventNames($onlyPublished);
      $result['eventNames'] = $currentEvent[$number-1];
    }

    return $result;
  }


  if(isset($_SESSION['user']) && isset($_SESSION['password'])){
    $login = 1;
    $userData = getUser($_SESSION['user']); /*Obtenemos toda la info del usuario, para enviarsela al formulario de edición de datos*/


  }

  
  if($unlogin == 1){
    header("Location:index.php");
  }

  /*Estamos accediendo a la web poniendo la URL, pero así no sabemos a que lista queremos acceder, por lo que mandamos mensaje*/
  if(!isset($_GET['commentSent']) && !isset($_GET['userList']) && !isset($_GET['userModified']) && !isset($_POST['delete']) && !isset($_GET['listComments']) && !isset($_GET['listEvents']) 
    && !isset($_POST['event']) && !isset($_POST['deleteUser'])){ 
    if($login == 1){
      header("Location:index.php?badOption=2");
    }
  }

  if($userList == 1){ /*vamos a acceder a una lista de usuarios*/
    echo $twig->render('listaUsuarios.html', ['usersData'=>$usersData,'login'=>$login,'userData'=>$userData,'userDeleted'=>$userDeleted,'userModified'=>$userModified]);
  }
  
  else{
    /*Si todo va bien mostramos la lista*/
    echo $twig->render('lista.html', ['result'=>$result,'filename'=>$filename, 'URLNotValid'=>$URLNotValid,'login'=>$login,'loginError'=>$loginError,'commentSent'=>$commentSent,
    'userData'=>$userData,'intError'=>$intError]);
  }
  
?>
