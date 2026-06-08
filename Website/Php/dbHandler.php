<?php
    $dbHandler = Null;

    try{
        $dbHandler = new PDO("mysql:host=mysql;dbname=winnest_db;charset=utf8", "root", "qwerty");
    }
    catch(Exception $er){
        echo $er;
    }
?>