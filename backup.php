<?php
  require_once "/usr/local/lib/php/vendor/autoload.php";
  include("db.php");

    if(is_dir("backup/")){

        /*Escaneamos el interior del directorio*/
        $folder = scandir("backup/");
        
        if(count($folder) > 2){  /*Tiene dentro algo más que la referencia al propio directorio y a su padre*/
            $files = glob("backup/*");  /*obtenemos el nombre de los ficheros que haya dentro (backup, pero lo haremos así por si por algún casual hay alguno más)*/
            
            foreach($files as &$file){
                if($file == "backup/backupDB.txt"){ /*Si es el .txt del backup, lo borramos*/
                unlink($file);
                }
            }

        }

        /*Obtenemos de vuelta toda la info relativa a la base de datos*/
        $textFile = backup();

        /*Creamos el fichero en el que vamos a guardarlo, dentro del directorio "backup"*/
        $backupFile = fopen("backup/backupDB.txt","w"); 

        fwrite($backupFile,$textFile);
        fclose($backupFile);
        chmod("backup/backupDB.txt", 0777);

        /*Una vez ya lo tenemos almacenado, procedemos a su descarga (establecemos las cabeceras de php necesarias)*/
            header("Content-disposition: attachment; filename=backupBD.txt");
            header("Content-type: MIME");
                
            /*Leemos el archivo para mostrarlo en el navegador*/
            readfile("backup/backupDB.txt");
    }
  
?>
