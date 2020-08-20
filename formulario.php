<?php
  require_once "/usr/local/lib/php/vendor/autoload.php";
  include("db.php");

  $loader = new \Twig\Loader\FilesystemLoader('SIBW');
  $twig = new \Twig\Environment($loader);

  $form = []; /*Donde vamos a almacenar toda la info*/
  $eventName = $userComment = $dateComment = $comment = "";/*Variables relacionada con la modificación de comentarios*/
  $commentSent = 0; /*Variable para saber si hemos enviado o no el comentario a la base de datos*/
  $updateUserSent = 0; /*Variable para saber si hemos actualizado los datos del usuario o no*/ 
  $createEventSent = 0; /*Variable que nos indica si hemos creado el evento o no*/
  $unlogin = 0; /*Para saber si nos hemos deslogueado*/
  $userModified = 0; /*Nos indica si hemos modificado ya o no el usuario*/
  $noModified = 0; /*No modificamos por ser el propio usuario accediendo a sus credenciales*/

  if(isset($_GET['createEvent'])){  /*Si recibimos la variable es porque hemos pulsado en añadir un evento*/
    $form['eventForm'] = 1;
    $form['OK'] = 0;
  }

  /*------------------------------------------------------ CONTROL DE LOGIN Y FORMULARIOS -----------------------------------------------------------*/
  $login = 0; /*Variable que nos indica si el usuario está logeado o no*/
  $usernameError = "";  /*Para indicar el error si hemos introducido el usuario o la contraseña mal*/
  $passwordError = "";
  $loginError = [];  /*Para controlar errores en el logueo, devolviendo mensajes*/
  $userOriginal = []; /*Alamcenamos la info original relativa al usuario*/

  session_start();  /*Iniciamos sesión*/

  if(isset($_SESSION['user']) && isset($_SESSION['password'])){  /*Si los datos se han introducido correctamente*/
    $login = 1;
    $userData = getUser($_SESSION['user']); /*Obtenemos toda la info del usuario, para enviarsela al formulario de edición de datos*/ 
  }


  if($_SERVER["REQUEST_METHOD"] == "POST"){

    /*-------------------LOGEO----------------------*/
  
    if(isset($_POST['unlogin'])){/*Si hemos pulsado el botón de desloguear*/
      if(session_status()==PHP_SESSION_NONE){
          session_start();
      }
      
      session_unset();
      $cookieParams= session_get_cookie_params(); 
      
      setcookie(session_name(), $_COOKIE[session_name()], time()-2592000, $cookieParams['path'], 
      $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
      
      session_destroy();

      $unlogin = 1;
    } 


    /*------------------------------FORMULARIOS---------------------------------------*/
    /*Formulario de editar comentario*/

    if(isset($_POST['commentForm'])){   /*Si nos llega esta variable es porque estamos en un formulario de comentario*/
        $form['commentForm'] = $_POST['commentForm'];

        $eventName = $_POST['eventName'];
        $userComment = $_POST['userComment'];
        $dateComment = $_POST['dateComment'];
        $emailComment = $_POST['emailComment'];
        $comment = $_POST['comment'];

        if($_POST['OK'] == 0){  /*Si nos llega esta variable es porque acabamos de llegar al formulario de comentarios, o aún tiene algún campo incompleto*/

            $originalComment = $_POST['originalComment']; /*Comentario original, para poder modificar al inicio*/

            if(empty($_POST['comment'])){   /*Si el moderador deja el campo vacío*/
                $commentError = "El comentario no puede estar vacío";
                $form['commentError'] = $commentError;
                $OK = 0;
            }
         
            else{   /*Si rellena el comentario*/
                if($originalComment != $comment){
                    $OK = 1;
                }
                
            }

            $form['userComment']=$userComment;
            $form['dateComment']=$dateComment;
            $form['eventName']=$eventName;
            $form['originalComment'] = $originalComment;
            $form['comment']=$comment;
            $form['commentError']=$commentError;
            $form['OK']=$OK;
        }

        else{   /*Pulsamos "enviar" cuando ya hemos checkeado el comentario*/
            $comment = test_input($comment); /*Tratamos la cadena de comentarios para evitar posibles ataques*/
            EditComment($userComment,$dateComment,$comment,$eventName);
            $commentSent = 1;
        }
    }

    /*Formulario de actualizar datos personales*/

    if(isset($_POST['updateUserForm'])){   /*Hemos llegado al formulario para actualizar los datos del usuario(formulario también reutilizado para crear usuario)*/
        $form['userForm'] = 1;
        $form['userID'] = $_POST['updateUserID'];
        $form['OK'] = $_POST['OK'];
        $form['createUser'] = $_POST['createUser'];


        if($_POST['OK'] == 0){  /*Aún hay algún campo con errores*/
          
            $updateUsernameError = $updateEmailError = $updatePasswordError = ""; /*Almacenarán los errores*/

            if(isset($_POST['updateUsername'])){    /*Si se envía el nombre de usuario*/
                if(empty($_POST['updateUsername'])){    /*Si el campo de usuario está vacío*/
                  $updateUsernameError = "Rellena el nombre de usuario";
                  $form['usernameError'] = $updateUsernameError;
                }

                
                else{  
                  if(findName($_POST['updateUsername']) == 1 && $_POST['updateUsername'] != $_SESSION['user']){ /*Intentamos cambiarnos el nick por otro que ya tiene otro usuario*/
                    
                    $updateUsernameError = "Nombre ya cogido por otro usuario";
                    $form['usernameError'] = $updateUsernameError;
                    
                  }
                    $form['userName'] = test_input($_POST['updateUsername']);/*Si es correcto, lo mantenemos*/
                }
            }
            
            if(isset($_POST['updatePassword'])){
                if(empty($_POST['updatePassword'])){    /*Si el campo de contraseña está vacío*/
                    $updatePasswordError = "Rellena la contraseña";
                    $form['passwordError'] = $updatePasswordError;
                }
    
                else{  /*Si el campo no está vacío*/
                  if($_POST['createUser'] == 1){  /*Si estamos creando un usuario cualquier contraseña nos vale*/
                    $form['userPassword'] = test_input($_POST['updatePassword']);
                  }

                  else{
                    if(password_verify($_POST['updatePassword'],$userData['password'])){  /*Si la contraseña es correcta*/
                      $form['userPassword'] = test_input($_POST['updatePassword']);
  
                      if(!empty($_POST['updateNewPassword'])){ /*Si hemos introducido contraseña nueva*/
                        $form['userNewPassword'] = test_input($_POST['updateNewPassword']);
                      }
                      
                    }
            
                    else{ /*Si no es correcta*/
                      $updatePasswordError = "Contraseña errónea";
                      $form['passwordError'] = $updatePasswordError;
                    }
                  }
                   
                }
            }
            

            if(isset($_POST['updateEmail'])){
                if(empty($_POST['updateEmail'])){    /*Si el campo de correo está vacío*/
                    $updateEmailError = "Rellena el correo electrónico";
                    $form['emailError'] = $updateEmailError;
                }

                else if (checkFormatEmail($_POST['updateEmail']) == false){
                  $updateEmailError = "Formato de correo electrónico no válido";
                    $form['emailError'] = $updateEmailError;
                }
    
                else{  
                    $form['userEmail'] = test_input($_POST['updateEmail']); /*Si es correcto, lo mantenemos*/
                }
            }

            if($updateUsernameError == "" && $updatePasswordError == "" && $updateEmailError == ""){    /*Si no hay errores*/
              if(isset($_POST['check'])){ /*Esta variable se envía solo desde el formulario, de forma que en la primera vuelta, al estar los datos originales, no los de por correctos*/
                $OK = 1;
                $form['OK'] = $OK;
              }
                
            }
            
        }

        else{   /*Le damos a enviar y todos los datos son correctos*/

            if($form['createUser'] == 1){ /*Si el formulario era para crear un usuario*/
              createUser($_POST['sendUsername'],$_POST['sendPassword'],$_POST['sendEmail']);
              $createUserSent = 1;
            }
            else{
              if(!empty($_POST['sendNewPassword'])){  /*Vamos a cambiar a una contraseña nueva*/
                $password = password_hash($_POST['sendNewPassword'],PASSWORD_DEFAULT); /*Codificamos la contraseña*/
              }
              else{
                $password = password_hash($_POST['sendPassword'],PASSWORD_DEFAULT); /*Codificamos la contraseña*/
              }
              
              modifyMyData($form['userID'],$_POST['sendUsername'],$_POST['sendEmail'],$password);
              $updateUserSent = 1;
            }
            
        }

    }

    /*Formulario de añadir/modificar evento*/
    if(isset($_POST['eventForm'])){
      if($_POST['OK'] == 0){  /*Aún hay algún campo obligatorio vacío*/
        $form['linkPickup'] = $_POST['linkPickup'];
        $form['textPickup'] = $_POST['textPickup'];
        $form['tags'] = $_POST['tags'];
        $form['fotosPagina'] = $_POST['photos'];
        $form['tripAdvisorLink'] = $_POST['tripAdvisorLink'];
        $form['OK'] = 0;
        $form['eventForm'] = 1;
        $form['modify'] = $_POST['modify'];

        $eventNameError = $eventDescriptionError = $howPickupError = $beginningTimeError = $endTimeError = $imageError =  "";

        /*Primero, vamos a ver si estamos en algún formulario de edición de imagen, y tenemos que eliminar una*/
        $totalEventNames = getEventNames(true);
        
        for ($i = 0; $i < sizeof($totalEventNames);$i++){
          if(isset($_POST['deleteImage_'.$i])){  
            if(!empty($_POST["photo_".$i])){  /*Única forma que he encontrado de acceder a la foto que quiero dentro del evento, ya que los "hidden" me daban problemas*/
              $form['fotosPagina'] = deleteImage($_POST['eventName'],$_POST["photo_".$i],$form['fotosPagina']); /*Eliminamos la imagen*/
            }
          }
        }

        /*Continuamos*/

        if(empty($_POST['eventName'])){ /*Si el campo de nombre está vacío*/
          $eventNameError="Rellena el nombre";
          $form['eventNameError'] = $eventNameError;
        }

        else{
          $form['eventName'] = test_input($_POST['eventName']);
        }

        if(!empty($_FILES['images']['name'][0])){ /*Si añadimos imágenes*/
         
          for($i = 0; $i < sizeof($_FILES['images']['name']); $i++){  /*Lo haremos de forma que podamos meter todas las imágenes o ninguna*/

            $file_name = $_FILES['images']['name'][$i];
            $file_size = $_FILES['images']['size'][$i];
            $file_tmp = $_FILES['images']['tmp_name'][$i];
            $file_type = $_FILES['images']['type'][$i];


            $file_ext = strtolower(end(explode('.',$file_name))); /*Vemos la extensión de la imagen*/
            $extensions= array("jpeg","jpg","png");

            if (in_array($file_ext,$extensions) === false){
              $imageError = "Extensión no permitida, elige una imagen JPEG o PNG.";
            }
            
            if ($file_size > 2097152){
              $imageError += 'Tamaño del fichero demasiado grande';
            }

            if($imageError != ""){  /*Encontramos algún error en alguna imagen*/
              $form['imageError'] = $imageError;
              break;
            }
          
          }
          
          if($imageError == ""){

            for($i = 0; $i < sizeof($_FILES['images']['name']); $i++){
              $file_name = $_FILES['images']['name'][$i];
              $file_tmp = $_FILES['images']['tmp_name'][$i];
              
              move_uploaded_file($file_tmp, "tmp/".$file_name); /*Lo movemos todo al fichero temporal*/
            }
          }          
        }

        if(empty($_POST['eventDescription'])){ /*Si el campo de descripción está vacío*/
          $eventDescriptionError="Rellena la descripción";
          $form['eventDescriptionError'] = $eventDescriptionError;
        }

        else{
          $form['eventDescription'] = test_input($_POST['eventDescription']);
        }


        if(empty($_POST['howPickup'])){ /*Si el texto de recogida está vacío*/
          if(empty($_POST['linkPickup']) && empty($_POST['textPickup'])){
            $howPickupError="Rellena la información de recogida";
            $form['howPickupError'] = $howPickupError;
          }
          
        }

        else{
          $form['howPickup'] = test_input($_POST['howPickup']);
        }


        if(empty($_POST['beginningTime'])){ /*Si el tiempo de inicio está vacío*/
          $beginningTimeError="Rellena la hora de inicio";
          $form['beginningTimeError'] = $beginningTimeError;
        }

        else{
          $form['beginningTime'] = test_input($_POST['beginningTime']);
        }


        if(empty($_POST['endTime'])){ /*Si el tiempo de final está vacío*/
          $endTimeError="Rellena la hora de fin";
          $form['endTimeError'] = $endTimeError;
        }

        else{
          $form['endTime'] = test_input($_POST['endTime']);
        }

        if($eventNameError == "" && $eventDesciptionError == "" && $howPickupError == "" && $beginningTimeError == "" && $endTimeError == "" && $imageError == ""){
          if(isset($_POST['check']) && isset($_POST['send'])){
            $form['OK'] = 1;
          }  
        }
      }

      else{ /*Si todos los campos están correctos*/

        if($_POST['modify'] == 1){/*Vamos a modificar un evento*/

          if(isset($_POST["cancel"])){  /*Vamos a guardar el evento como no publicado*/
            $updateEventSent = 2;
            $publish = 0;
          }
          else{
            $updateEventSent = 1;
            $publish = 1;
          }

          modifyEvent($_POST['eventName'],$_POST['eventDescription'],$_POST['howPickup'],$_POST['linkPickup'],$_POST['textPickup'],$_POST['beginningTime'],
          $_POST['endTime'],$_POST['tags'],$_POST['tripAdvisorLink'],$publish);

        }
        else{ /*Vamos a añadir evento*/

          if(isset($_POST["cancel"])){  /*Vamos a guardar el evento como no publicado*/
            $createEventSent = 2;
            $publish = 0;
          }
          else{
            $createEventSent = 1;
            $publish = 1;
          }

          addEvent($_POST['eventName'],$_POST['eventDescription'],$_POST['howPickup'],$_POST['linkPickup'],$_POST['textPickup'],$_POST['beginningTime'],
          $_POST['endTime'],$_POST['tags'],$_POST['tripAdvisorLink'],$publish);

        }


        $arrayImages = getTmpImages();  /*Pasamos las imágenes de tmp al directorio final, y las añadimos a este array para meterlas en la BD*/
        addImages($arrayImages,$_POST['eventName']);  /*Las añadimos a la BD*/
        
      }
    }
    /*formulario para modificar roles de usuarios*/
    if(isset($_POST['modifyUserRol'])){
      $form['userName'] = $_POST['userName'];
      $form['userEmail'] = $_POST['userEmail'];
      $form['userRol'] = $_POST['userRol'];
      $form['updateUserRol'] = $_POST['modifyUserRol'];

      if(isset($_POST['userModified'])){  /*Si ya lo hemos modificado*/
        if(isset($_POST['noModify'])){ /*Si llega esta variable es porque hemos entrado a nuestras propias credenciales, las cuales no podemos modificar*/
          $noModified = 1;
        }

        else{
          changePermission($_POST['userName'],$_POST['userType']);
          $userModified = 1;
        }

      }
    }

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



  /*Función para obtener las imagenes del directorio tmp*/
  function getTmpImages(){

    
    $folder = opendir("tmp");
    $images = [];

    while(($file = readdir($folder)) !== false){  /*Vemos los elementos del directorio*/
      if($file != "." && $file != ".."){
        rename("tmp/".$file,"images_and_gifs_portada/".$file); /*Lo movemos todo al fichero final*/
        chmod("images_and_gifs_portada/".$file,0777); /*Le añadimos permisos a las imágenes*/
        array_push($images,$file); /*Lo añadimos a la lista de imagenes a subir*/
      }
      
    }

    return $images;
  }



  /*Caso en el que estemos registrados pero pongamos en la URL: xxx.formulario.php, caso en el que no sabremos a que formulario redirigir, así que enviamos de vuelta a index.php*/
    if(!isset($_POST['commentForm']) && !isset($_POST['updateUserForm']) && !isset($_POST['eventForm']) && !isset($_GET['createEvent']) && !isset($_POST['modifyUserRol'])){ 
      if($login == 1){
        header("Location:index.php?badOption=1");
      }

      else {/*Intenta acceder a los formularios escribiendo la URL sin estar registrado*/
        header("Location:index.php?pirate=1");
      }
    }

  

  if($commentSent == 1){
      header("Location:lista.php?commentSent=1");
  }

  if($updateUserSent == 1){
    header("Location:index.php?updateUserSent=1&nameUser=".$_POST['sendUsername']);
    }

  if($createEventSent == 1){
    header("Location:index.php?createEventSent=1");
  }

  if($createEventSent == 2){
    header("Location:index.php?createEventSent=2");
  }

  if($updateEventSent == 1){
    header("Location:index.php?updateEventSent=1");
  }

  if($updateEventSent == 2){  /*Guardamos el evento como no publicado*/
    header("Location:index.php?updateEventSent=2");
  }

  if($createUserSent == 1){
    header("Location:index.php?createUserSent=1");
  }

  if($unlogin == 1){
    header("Location:index.php");
  }

  if($userModified == 1){
    header("Location:lista.php?userList=1&userModified=1");
  }

  if($noModified == 1){
    header("Location:lista.php?userList=1");
  }

  echo $twig->render('formulario.html', ['form'=>$form,'login'=>$login,'loginError'=>$loginError,'userData'=>$userData]);
?>