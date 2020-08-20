<?php

  $conectado = false;


  function connectDB(){
    $mysqli = new mysqli("mysql", "usuario", "usuario", "SIBW");


    if ($mysqli->connect_errno) {
        echo ("Fallo al conectar: " . $mysqli->connect_error);
    }

    return $mysqli;

  }

  /*---------------------------------------------- FUNCIÓN PARA OBTENER LOS DATOS DEL EVENTO SELECCIONADO -------------------------------*/
  function getEvent($nombre) {

    global $conectado;

    if($conectado == false){
        $mysqli = connectDB();
        $conectado = true;
    }

    $mysqli = connectDB();
    
    $nombreComentarios = [];
    $fechaComentarios = [];
    $contenidoComentarios = [];
    $correosComentarios = [];
    $modificados = [];
    $censuradas = [];  
    $evento = [];
    $src = [];
    $tags = [];
    $figcaption = [];

    /*Almacenamos el texto relativo al evento*/
    $texto = $mysqli->prepare("SELECT * FROM eventosSIBW WHERE titulo= ?");
    $texto->bind_param("s", $nombre);

    $texto->execute();

    $resultado = $texto->get_result();

    if($resultado->num_rows > 0){
        
        $textoPagina = $resultado->fetch_assoc();
        $evento['textoPagina'] = $textoPagina;
    }

    /*Almacenamos los tags*/
    $etiquetasTotal = $mysqli->prepare("SELECT etiqueta FROM etiquetas WHERE evento= ?");
    $etiquetasTotal->bind_param("s", $nombre);

    $etiquetasTotal->execute();

    $resultado = $etiquetasTotal->get_result();

    if($resultado->num_rows > 0){
        while($etiqueta = $resultado->fetch_assoc()){
          array_push($tags,$etiqueta['etiqueta']);
        }

        $evento['tags'] = $tags;

    }
    
    $texto->close();

    /*Almacenamos las imágenes*/
    $fotosEvento = $mysqli->prepare("SELECT * FROM fotos WHERE evento= ?");
    $fotosEvento->bind_param("s", $nombre);

    $fotosEvento->execute();

    $resultado = $fotosEvento->get_result();

    if($resultado->num_rows > 0){
     
      while($foto = $resultado->fetch_assoc()){
        array_push($src,$foto['src']);
        array_push($figcaption,$foto['figcaption']);
      }
      $fotosPagina = ['src'=>$src,'figcaption'=>$figcaption];
      $evento['fotosPagina'] = $fotosPagina;
    }
    
    $fotosEvento->close();

    /*VAMOS A ALMECENAR RESULTADOS DE TODOS LOS COMENTARIOS, EL OBJETIVO ES ALMACENAR TODOS LOS VALORES DE TODOS LOS COMENTARIOS A LA VEZ, DE FORMA
    QUE CUANDO LLAMEMOS A LA FUNCIÓN JAVASCRIPT DE ABRIR EL MENÚ DE COMENTARIOS, SEA ESTA LA QUE, TENIENDO DENTRO "LA PLANTILLA" DE LOS COMENTARIOS, CREE UNO POR CADA
    ENTRADA EN LA TABLA, SEA CUAL SEA ESTE NÚMERO*/

    $totalComentarios = $mysqli->prepare("SELECT * FROM comentarios WHERE evento=?"); 
    $totalComentarios->bind_param("s", $nombre);

    $totalComentarios->execute();

    $resultado = $totalComentarios->get_result();

    if($resultado->num_rows > 0){
      while($comentario = $resultado->fetch_assoc()){
        array_push($nombreComentarios, $comentario['nombre']);
        array_push($fechaComentarios, $comentario['fecha']);
        array_push($contenidoComentarios, $comentario['comentario']);
        array_push($correosComentarios, $comentario['correo']);
        array_push($modificados,$comentario['modificado']);
      }
      
      $todoComentarios = ['nombres'=>$nombreComentarios,'fechas'=>$fechaComentarios,'contenidos'=>$contenidoComentarios,'correos'=>$correosComentarios,'modificados'=>$modificados];
      $evento['todoComentarios'] = $todoComentarios;
    }
    
    /*Almacenamos las palabras censuradas*/
    $palabrasCensuradas = $mysqli->query("SELECT palabra FROM censuradas");

    while($palabra = $palabrasCensuradas->fetch_assoc()){
        array_push($censuradas,$palabra['palabra']);
    }
    $evento['censuradas'] = $censuradas;


    return $evento;

  }


  /*------------------------------------------------ FUNCIÓN PARA OBTENER LOS NOMBRES DE TODOS LOS EVENTOS ----------------------------------------------*/

  function getEventNames($onlyPublished){
    global $conectado;

    if($conectado == false){
        $mysqli = connectDB();
        $conectado = true;
    }

    $mysqli = connectDB();

    $result = [];  /*Array donde almacenaremos el nombre de los eventos*/

    if($onlyPublished == true){
      $totalEvents = $mysqli->query("SELECT titulo FROM eventosSIBW WHERE publicado = 1");
    }

    else{
      $totalEvents = $mysqli->query("SELECT titulo FROM eventosSIBW");
    }


    if($totalEvents->num_rows > 0){
        while($event = $totalEvents->fetch_assoc()){
          array_push($result,$event['titulo']);
        }
    }

    return $result;
  }

  /*-------------------------------------------------- FUNCIÓN PARA OBTENER TODAS LAS IMÁGENES DE PORTADA DE LOS EVENTOS ----------------------------------*/
  function getIndexImage(){
    global $conectado;

    if($conectado == false){
        $mysqli = connectDB();
        $conectado = true;
    }

    $mysqli = connectDB();

    $events = getEventNames(true);
    $imageList = [];  /*Array donde almacenaremos el nombre de los eventos*/


    foreach($events as $key => $value){

      $select = $mysqli->prepare("SELECT src FROM fotos WHERE evento=? LIMIT 1"); /*Vamos a tomar como imágen de portada la primera que se introduzca sobre el evento en la DB*/
      $select->bind_param("s",$value);

      $select->execute();
      $result = $select->get_result();

      if($result->num_rows > 0){
          while($photo = $result->fetch_assoc()){
            array_push($imageList,$photo['src']);
          }
      }

      $select->close();

    }
    return $imageList;

  }

  /*------------------------------------------------------- FUNCIÓN PARA OBTENER LOS DATOS RELATIVOS A LOS COMENTARIOS -------------------------------------------------*/
  function getComments($comment){
       global $conectado;

    if($conectado == false){
        $mysqli = connectDB();
        $conectado = true;
    }

    $mysqli = connectDB();

    $names = [];
    $dates = [];
    $comments = [];
    $result = [];
    $eventNames = [];
    $emails = [];
    $modificados = [];
    $comment = (int)$comment;

    $totalNames = getEventNames(true);


    if($comment == 0){

      foreach($totalNames as &$event){  /*Para mostrar todos los comentarios de los eventos en el orden en el que se encuentra en la página*/
        
        $totalComments = $mysqli->prepare("SELECT * FROM comentarios WHERE evento=?");
        $totalComments->bind_param("s",$event);

        $totalComments->execute();
        $getResult = $totalComments->get_result();

        if($getResult->num_rows > 0){
          while($comment = $getResult->fetch_assoc()){
            array_push($names,$comment['nombre']);
            array_push($dates,$comment['fecha']);
            array_push($comments,$comment['comentario']);
            array_push($emails, $comment['correo']);
            array_push($modificados,$comment['modificado']);
            array_push($eventNames,$event);
          }
        }
      }
      
      $result = ['names'=>$names,'dates'=>$dates,'comments'=>$comments,'eventNames'=>$eventNames,'emails'=>$emails,'modify'=>$modificados];

    }

    else{
      /*Usando el numero de $comment, vamos a obtener el nombre del evento que se corresopnde*/
      $value = $totalNames[$comment-1];


      $totalComments = $mysqli->prepare("SELECT * FROM comentarios WHERE evento=?");
      $totalComments->bind_param("s",$value);

      $totalComments->execute();
      $getResult = $totalComments->get_result();

      if($getResult->num_rows > 0){
        while($comment = $getResult->fetch_assoc()){
          array_push($names,$comment['nombre']);
          array_push($dates,$comment['fecha']);
          array_push($comments,$comment['comentario']);
          array_push($emails, $comment['correo']);
          array_push($modificados,$comment['modificado']);
        }

        $result = ['names'=>$names,'dates'=>$dates,'comments'=>$comments,'eventNames'=>$value,'emails'=>$emails,'modify'=>$modificados];
      }

      $totalComments->close();
    }
  
    return $result;
  }

  /*------------------------------------------------------------ FUNCIÓN PARA ELIMINAR LOS COMENTARIOS ------------------------------*/
  function removeComment($name,$user,$date){
    $mysqli = connectDB();

        $deleteCommand = $mysqli->prepare("DELETE FROM comentarios WHERE evento=? AND nombre=? AND fecha=?");   
        $deleteCommand->bind_param("sss",$name,$user,$date);
        $deleteCommand->execute();
        $deleteCommand->close();

  }

  /*----------------------------------------------------------- FUNCIÓN PARA AÑADIR LOS COMENTARIOS --------------------------------*/
  function addComment($name,$date,$email,$comment,$eventName){
    $mysqli = connectDB();
    
    $insertCommand = $mysqli->prepare("INSERT INTO comentarios(nombre,fecha,correo,comentario,evento) VALUES(?,?,?,?,?)");

    $insertCommand->bind_param("sssss",$name,$date,$email,$comment,$eventName);
    $insertCommand->execute();
    $insertCommand->close();
  }

  /*--------------------------------------------------------- FUNCIÓN PARA EDITAR COMENTARIOS --------------------------*/
  function EditComment($name,$date,$comment,$eventName){
    $mysqli = connectDB();
    
    $insertCommand = $mysqli->prepare("UPDATE comentarios set comentario=?,modificado=1 WHERE nombre=? AND fecha=? AND evento=?");

    $insertCommand->bind_param("ssss",$comment,$name,$date,$eventName);
    $insertCommand->execute();
    $insertCommand->close();
  }

  /*------------------------------------------ FUNCIÓN PARA OBTENER EL ID DEL USUARIO ----------------------------------*/
  function getUser($user){
    $mysqli = connectDB();

    $select = $mysqli->prepare("SELECT * FROM usuarios WHERE nombre=?");
      $select->bind_param("s",$user);

      $select->execute();
      $getResult = $select->get_result();

      if($getResult->num_rows > 0){
        while($user = $getResult->fetch_assoc()){
          $userID = $user['id'];
          $userName = $user['nombre'];
          $userEmail = $user['correo'];
          $userRol = $user['rol'];
          $userPassword = $user['clave'];
        }

        $result = ['id'=>$userID,'name'=>$userName,'email'=>$userEmail,'rol'=>$userRol,'password'=>$userPassword];
      }

      $select->close();

      return $result;

  }

  /*--------------------------------------- FUNCIÓN PARA SABER SI EL NOMBRE SE ENCUENTRA EN LA BASE DE DATOS --------------------------*/
  function findName($name){
    $mysqli = connectDB();

    $select = $mysqli->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre=?");
    $select->bind_param("s",$name);

    $select->execute();

    $getResult = $select->get_result();

      if($getResult->num_rows > 0){ /*Si hay mas de 0 filas es porque el nombre ya está*/
        while($user = $getResult->fetch_assoc()){
          $result = $user['COUNT(*)'];
        }        
      }

    $select->close();

    return $result;
    
  }

  /*--------------------------------------------- FUNCIÓN PARA QUE EL USUARIO MODIFIQUE SUS DATOS -----------------------------------------*/
  function modifyMyData($id,$name,$email,$password){
    $mysqli = connectDB();
    
    $insertCommand = $mysqli->prepare("UPDATE usuarios set nombre=?,correo=?,clave=? WHERE id=?");

    $insertCommand->bind_param("ssss",$name,$email,$password,$id);
    $insertCommand->execute();
    $insertCommand->close();
  }

  /*Funciones por si pudiera usarlas en la práctica final*/

  /*------------------------------------------- FUNCIÓN PARA SABER SI EL CORREO SE ENCUENTRA EN LA BASE DE DATOS ----------------------*/
  function findEmail($email){
    $mysqli = connectDB();

    $select = $mysqli->prepare("SELECT COUNT(*) FROM usuarios WHERE correo=?");
    $select->bind_param("s",$name);

    $select->execute();

    $getResult = $select->get_result();

      if($getResult->num_rows > 0){ /*Si hay mas de 0 filas es porque el nombre ya está*/
        while($user = $getResult->fetch_assoc()){
          $result = $user['COUNT(*)'];
        }        
      }

    $select->close();

    return $result;
  }

  /*-------------------------------------------- FUNCIÓN PARA ELIMINAR UN EVENTO --------------------------------------------------*/
  function removeEvent($name){
    $mysqli = connectDB();
    $photosArray = []; /*Contiene un array con todas las fotos, para borrarlas del directorio*/

        /*Eliminamos las entradas de la tabla "comentarios"*/
        $deleteCommand = $mysqli->prepare("DELETE FROM comentarios WHERE evento=?");   
        $deleteCommand->bind_param("s",$name);
        $deleteCommand->execute();
        $deleteCommand->close();

        /*Eliminamos las entradas de la tabla "etiquetas"*/
        $deleteCommand = $mysqli->prepare("DELETE FROM etiquetas WHERE evento=?");   
        $deleteCommand->bind_param("s",$name);
        $deleteCommand->execute();
        $deleteCommand->close();


        /*Eliminamos las entradas de la tabla "fotos"*/
          /*Primero, vamos a obtener las imágenes relativas a ese evento, para moverlas al directrorio de borradas*/
          $selectCommand = $mysqli->prepare("SELECT src FROM fotos WHERE evento=?");
          $selectCommand->bind_param("s",$name);
          $selectCommand->execute();

          $getResult = $selectCommand->get_result();

          if($getResult->num_rows > 0){ /*Si hay mas de 0 filas es porque el nombre ya está*/
            while($photo = $getResult->fetch_assoc()){
              array_push($photosArray,$photo['src']);
            }        
          }
    
          $selectCommand->close();

            foreach($photosArray as &$photo){
              if($photo != "no-image.jpg"){
                rename("images_and_gifs_portada/".$photo, "fotos_borradas/".$photo); /*Movemos la imagen al directorio de borrados*/
              }
           }



          /*Luego, las eliminamos de la base de datos*/
          $deleteCommand = $mysqli->prepare("DELETE FROM fotos WHERE evento=?");   
          $deleteCommand->bind_param("s",$name);
          $deleteCommand->execute();
          $deleteCommand->close();

        /*Eliminamos las entradas de la tabla "eventos"*/
        $deleteCommand = $mysqli->prepare("DELETE FROM eventosSIBW WHERE titulo=?");   
        $deleteCommand->bind_param("s",$name);
        $deleteCommand->execute();
        $deleteCommand->close();

  }

  /*------------------------------------------ FUNCIÓN PARA AÑADIR UN EVENTO --------------------------------------------------------*/
  function addEvent($eventName,$eventDescription,$howPickup,$linkPickup,$textPickup,$beginningTime,$endTime,$tags,$tripAdvisorLink,$publish){
    $mysqli = connectDB();
    
    /*Añadimos el evento*/
    $insertCommand = $mysqli->prepare("INSERT INTO eventosSIBW(titulo,descripcion,texto_recogida,enlace_recogida,hora_inicio,hora_fin,enlace_tripadvisor,
                                      texto_enlace,publicado) VALUES(?,?,?,?,?,?,?,?,?)");

    $insertCommand->bind_param("ssssssssi",$eventName,$eventDescription,$howPickup,$linkPickup,$beginningTime,$endTime,$tripAdvisorLink,$textPickup,$publish);
    $insertCommand->execute();
    $insertCommand->close();


    /*Añadimos las etiquetas*/
    if(!empty($tags)){
      $tagsSplit = explode(",",$tags); /*Como las etiquetas vienen separadas por comas, las separamos en strings distintos*/

      foreach($tagsSplit as &$tag){
        $insertCommand = $mysqli->prepare("INSERT INTO etiquetas(evento,etiqueta) VALUES(?,?)");
        $insertCommand->bind_param("ss",$eventName,$tag);
        $insertCommand->execute();
        $insertCommand->close();
      }
    }
  
  }

  /*------------------------------------------ FUNCIÓN PARA CREAR UN USUARIO -------------------------------------------------*/
  function createUser($name,$password,$email){
    $mysqli = connectDB();
    /*El rol por defecto es el de usuario; ya lo cambiará el super usuario*/

    $rol = "usuario";
    $password = password_hash($password,PASSWORD_DEFAULT);
    
    $insertCommand = $mysqli->prepare("INSERT INTO usuarios(nombre,clave,correo,rol) VALUES(?,?,?,?)");

    $insertCommand->bind_param("ssss",$name,$password,$email,$rol);
    $insertCommand->execute();
    $insertCommand->close();
  }

  /*------------------------------------------ FUNCIÓN PARA COMPROBAR QUE LA CONTRASEÑA SE CORRESPONDE A ESE USUARIO -----------------*/
  function matchPassword($name,$password){
    $mysqli = connectDB();
    $match = 0; 

    /*Obtenemos la contraseña del usuario*/

    $select = $mysqli->prepare("SELECT clave FROM usuarios WHERE nombre=?");
    $select->bind_param("s",$name);

    $select->execute();
    $getResult = $select->get_result();

    if($getResult->num_rows > 0){
      while($user = $getResult->fetch_assoc()){
        $BDPass = $user['clave'];
      }
    }
    $select->close();

    /*Vemos si las claves coinciden*/
    if(password_verify($password,$BDPass)){
      $match = 1;
    }

    return $match;

  }

  /*----------------------------------------- FUNCIÓN PARA METER IMÁGENES --------------------------------------------------*/
  function addImages($images,$event){
    $mysqli = connectDB();
    $totalPhotos = []; /*Array donde almacenamos las imágenes para buscar a "no-image.jpg"*/

    if(!empty($images)){  /*Si tenemos imagenes*/

       /*Comprobamos si tenemos la imagen "no-image.jpg" para eliminarla (significa que en la modificación hemos añadido las primeras imágenes al evento)*/
       $select = $mysqli->prepare("SELECT * FROM fotos WHERE evento=?");
       $select->bind_param("s",$event);
 
       $select->execute();
       $getResult = $select->get_result();
 
       if($getResult->num_rows > 0){
         while($photo = $getResult->fetch_assoc()){
           array_push($totalPhotos,$photo['src']);
         }
       }
       $select->close();
 
       $isInside = array_search('no-image.jpg',$totalPhotos);

 
       if($isInside == 0){ /*La imagen se encuentra denrtro de la BD para ese evento, así que la borramos*/
         $photoName = "no-image.jpg";
 
         $deleteCommand = $mysqli->prepare("DELETE FROM fotos WHERE src=? and evento=?");   
         $deleteCommand->bind_param("ss",$photoName,$event);
         $deleteCommand->execute();
         $deleteCommand->close();
       }
       

      foreach($images as &$image){  /*Añadimos las imágenes una a una */
        $insertCommand = $mysqli->prepare("INSERT INTO fotos(src,evento) VALUES(?,?)");
        $insertCommand->bind_param("ss",$image,$event);
        $insertCommand->execute();
        $insertCommand->close();
      }

    }

    else{ /*No se ha incluido imagen*/
      /*Primero, veremos si ya teniamos imagenes previas; si no, introducimos la imagen de "no-image"*/

      $select = $mysqli->prepare("SELECT * FROM fotos WHERE evento=?");
      $select->bind_param("s",$event);

      $select->execute();
      $getResult = $select->get_result();

      if($getResult->num_rows > 0){
        while($photo = $getResult->fetch_assoc()){
          array_push($totalPhotos,$photo['src']);
        }
      }

      $select->close();

      if(empty($totalPhotos)){  /*No tenemos imagenes previas*/
        $photoName='no-image.jpg';

        $insertCommand = $mysqli->prepare("INSERT INTO fotos(src,evento) VALUES(?,?)");
        $insertCommand->bind_param("ss",$photoName,$event);
        $insertCommand->execute();
        $insertCommand->close();
      }

    }
 
    
  }


   /*---------------------------------------------- FUNCIÓN PARA OBTENER LOS DATOS DE LOS EVENTOS A TRAVÉS DEL NÚMERO -------------------------------*/
   function getEventByNumber($number, $onlyPublished){

    global $conectado;

    if($conectado == false){
        $mysqli = connectDB();
        $conectado = true;
    }

    $mysqli = connectDB();

    $evento = [];
    $textoPagina = [];
    $fotosPagina = [];
    $tags = [];
    $i = 0; /*Para averiguar el índice*/
    $totalEvents = getEventNames($onlyPublished); /*Obtenemos todos los eventos*/


    if($number != 0){ /*Es un evento en concreto*/
      $value = $number-1;

      $evento = getEvent($totalEvents[$value]);
    }

    else{ /*Listamos todos los eventos*/

      foreach($totalEvents as &$singleEvent){
      

        /*Almacenamos el texto relativo al evento*/
        $textos = $mysqli->prepare("SELECT * FROM eventosSIBW WHERE titulo=?");
        $textos->bind_param("s",$singleEvent);
        $textos->execute();
        $getResult = $textos->get_result();
      
        if($getResult->num_rows > 0){
            while($totalTextos = $getResult->fetch_assoc()){
              array_push($textoPagina,$totalTextos);
            }
        }

        $textos->close();

        /*Almacenamos los tags*/
        $etiquetasTotal = $mysqli->prepare("SELECT * FROM etiquetas WHERE evento=?");
        $etiquetasTotal->bind_param("s",$singleEvent);
        $etiquetasTotal->execute();
        $getResult = $etiquetasTotal->get_result();


        if($getResult->num_rows > 0){
            while($etiqueta = $getResult->fetch_assoc()){
              array_push($tags,$etiqueta);
            }

        }

        $etiquetasTotal->close();
        
        /*Almacenamos las imágenes*/
        $fotosEvento = $mysqli->prepare("SELECT * FROM fotos WHERE evento=?");
        $fotosEvento->bind_param("s",$singleEvent);
        $fotosEvento->execute();
        $getResult = $fotosEvento->get_result();
      

        if($getResult->num_rows > 0){
        
          while($foto = $getResult->fetch_assoc()){
            array_push($fotosPagina,$foto);
          }

        }

        $fotosEvento->close();
      
      }

      $evento['textoPagina'] = $textoPagina;
      $evento['tags'] = $tags;
      $evento['fotosPagina'] = $fotosPagina;

    }

    return $evento;
  }

  /*------------------------------------------------- FUNCIÓN PARA ELIMINAR IMAGEN DEL EVENTO ---------------------------------*/
  function deleteImage($event,$photo,$listPhotos){

    $mysqli = connectDB();

    $photoTrimmed = trim($photo);


    /*Borramos la imagen de la base de datos*/
    $deleteCommand = $mysqli->prepare("DELETE FROM fotos WHERE src=? and evento=?");   
    $deleteCommand->bind_param("ss",$photoTrimmed,$event);
    $deleteCommand->execute();
    $deleteCommand->close();
    

    $photosInArray = explode(",",$listPhotos); /*Las pasamos a una lista para eliminar más fácilmente la que nos sobra*/

    for($i = 0; $i < sizeof($photosInArray); $i++){ /*eliminamos de la lista a la foto*/
      if($photosInArray[$i] == $photo){
        unset($photosInArray[$i]);
        break;
      }
    }


    /*Eliminamos la imagen del directorio final*/
    rename("images_and_gifs_portada/".$photo, "fotos_borradas/".$photo); /*Movemos la imagen al directorio de borrados*/
  
    $photosInArray = array_values($photosInArray); /*Para reordenar los elementos, ya que por defecto el unset deja el hueco del eliminado como "null"*/
    
    $listPhotos = $photosInArray[0]; /*Vamos a introducir la nueva lista en el valor a retornar*/

    for($i = 1; $i < sizeof($photosInArray); $i++){
      $listPhotos = $listPhotos.",".$photosInArray[$i];
    }




    return $listPhotos;

  }
  
  /*------------------------------------------------------ FUNCIÓN PARA MODIFICAR UN EVENTO ----------------------------------------------------*/
  function modifyEvent($eventName,$eventDescription,$howPickup,$linkPickup,$textPickup,$beginningTime,$endTime,$tags,$tripAdvisorLink,$publishEvent){
    $mysqli = connectDB();

    /*Modificamos la tabla de los eventos*/
    $insertCommand = $mysqli->prepare("UPDATE eventosSIBW set descripcion=?,texto_recogida=?,enlace_recogida=?,hora_inicio=?,hora_fin=?,
                                      enlace_tripadvisor=?,texto_enlace=?, publicado=? WHERE titulo=?");

    $insertCommand->bind_param("sssssssis",$eventDescription,$howPickup,$linkPickup,$beginningTime,$endTime,$tripAdvisorLink,$textPickup,$publishEvent,$eventName);
    $insertCommand->execute();
    $insertCommand->close();

    /*Modificamos la lista de etiquetas*/
      /*Primero, eliminamos las etiquetas relativas al evento*/
      $deleteCommand = $mysqli->prepare("DELETE FROM etiquetas WHERE evento=?");   
      $deleteCommand->bind_param("s",$eventName);
      $deleteCommand->execute();
      $deleteCommand->close();

      /*Después, añadimos las nuevas*/
      $tagsSplit = explode(",",$tags); /*Como las etiquetas vienen separadas por comas, las separamos en strings distintos*/

      foreach($tagsSplit as &$tag){
        $insertCommand = $mysqli->prepare("INSERT INTO etiquetas(evento,etiqueta) VALUES(?,?)");
        $insertCommand->bind_param("ss",$eventName,$tag);
        $insertCommand->execute();
        $insertCommand->close();
      }
  }

  /*------------------------------------------------- FUNCIÓN PARA OBTENER LOS DATOS DE TODOS LOS USUARIOS ----------------------------------*/
  function getUsersData(){
    $result = []; /*Resultado*/
    $mysqli = connectDB();
    $names = [];
    $emails = [];
    $rols = [];

    $selectCommand = $mysqli->query("SELECT * FROM usuarios");

    if($selectCommand->num_rows > 0){
      while($getData = $selectCommand->fetch_assoc()){
        array_push($names,$getData['nombre']);
        array_push($emails,$getData['correo']);
        array_push($rols,$getData['rol']);
      }

      $result = ['names'=>$names,'emails'=>$emails,'rols'=>$rols];
    }

    return $result;
  }

  /*----------------------------------------------- FUNCIÓN PARA ELIMINAR UN USUARIO --------------------------------------*/
  function deleteUser($userName){
    $mysqli = connectDB();

        $deleteCommand = $mysqli->prepare("DELETE FROM usuarios WHERE nombre=?");   
        $deleteCommand->bind_param("s",$userName);
        $deleteCommand->execute();
        $deleteCommand->close();
  }

  /*---------------------------------------------- FUNCIÓN PARA CAMBIAR LOS PERMISOS DE UN USUARIO -----------------------------*/
  function changePermission($user,$newRol){
    $mysqli = connectDB();
    
    $updateCommand = $mysqli->prepare("UPDATE usuarios set rol=? WHERE nombre=?");

    $updateCommand->bind_param("ss",$newRol,$user);
    $updateCommand->execute();
    $updateCommand->close();
  }

  /*--------------------------------------- FUNCIÓN PARA OBTENER EL NOMBRE DE LOS EVENTOS A PARTIR DE UNA CADENA--------------------*/
  function getEventsByName($text,$onlyPublished){
    $result = []; /*Aquí almacenaremos todos los resultados*/
    $mysqli = connectDB();
    $textoEventos = [];


    /*Guardamos la info relativa al texto de los eventos*/

    if($onlyPublished == true){
      $selectCommand = mysqli_prepare($mysqli,"SELECT * FROM eventosSIBW WHERE titulo LIKE '%".$text."%' AND publicado=".$onlyPublished);
    }

    else{
      $selectCommand = mysqli_prepare($mysqli,"SELECT  FROM eventosSIBW WHERE titulo LIKE '%".$text."%'");
    }
 
     $selectCommand->execute();
 
     $resultado = $selectCommand->get_result();
 
     if($resultado->num_rows > 0){
      
       while($texto = $resultado->fetch_assoc()){
         array_push($textoEventos,$texto);
       }

     }

     $result['textoEventos'] = $textoEventos;
     
     $selectCommand->close();


    return $result;

  }


  /*------------------------------------ FUNCIÓN PARA REALIZAR BACKUP (NO IMPLEMENTADO EN LA WEB) ------------------------------*/
   /*------------------------------------------------ FUNCIÓN PARA HACER UN BACKUP DE LA DB -----------------------------------------------*/
   function backup(){
    $mysqli = connectDB();
    $tables = []; /*Almacenamos el nombre de las tablas*/

    /* Obtener listado de tablas*/

    $result= $mysqli->query("SHOW TABLES");
    while($row = mysqli_fetch_row($result)){
        $tables[] = $row[0];

    }

    /* Salvar cada tabla*/
    $salida = '';
    
    foreach($tables as &$singleTable) {
        $result= $mysqli->query('SELECT * FROM '.$singleTable);
        $num= mysqli_num_fields($result);
        $salida .= 'DROP TABLE '.$singleTable.';';
        
        $row2 = mysqli_fetch_row($mysqli->query('SHOW CREATE TABLE '.$singleTable));
        $salida .= "\n\n".$row2[1].";\n\n";  // row2[0]=nombre de tabla
        
        while($row = mysqli_fetch_row($result)) {
            $salida .= 'INSERT INTO '.$singleTable.' VALUES(';
            
            for($j=0; $j<$num; $j++) {
                $row[$j] = addslashes($row[$j]);$row[$j] = preg_replace("/\n/","\\n",$row[$j]);
                if(isset($row[$j]))
                    $salida .= '"'.$row[$j].'"';
                    
                else
                    $salida .= '""';
                    
                if($j < ($num-1))  
                    $salida .= ',';
            }
                
            $salida .= ");\n"; 
        }
            
        $salida .= "\n\n\n";

    }

    return $salida;
}


?>