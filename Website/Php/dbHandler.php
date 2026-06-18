<?php
    try{
        $dbHandler = new PDO(
            "mysql:host=mysql;dbname=winnestTest_db;charset=utf8", "root", "qwerty",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    catch(Exception $ex){
        echo $ex;
    }
?>