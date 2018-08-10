<?php

include("commands.php");


if($_POST){
  
  if($_POST['action'] == "anexos"){

  $SQL_info = unserialize($_POST['data']);
  $data = result_query($SQL_info);
  //header('Content-type: application/json');
  echo $data;

  }else if($_POST['action'] == "upload"){

  $SQL_info = unserialize($_POST['info']);
  $grades = unserialize($_POST['grades']);
  $data = atualizaQuestoes($SQL_info,$grades);
  //header('Content-type: application/json');
  echo $data;

  }elseif($_POST['action'] == "rubric"){
    $SQL_info = unserialize($_POST['info']);
    $USER_info = unserialize($_POST['userinfo']);
    $data = obterRubric($SQL_info, $USER_info);
    echo $data;
  }
}
?>
