<?php
  include("db.php");

    header('Content-Type: application/json');
    
    if(isset($_POST['lookForEvent']) & isset($_POST['onlyPublished'])){
      $result = getEventsByName($_POST['lookForEvent'],$_POST['onlyPublished']);
    }

    echo(json_encode($result));
  ?>