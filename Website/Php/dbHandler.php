<?php
    try{
        $dbHandler = new PDO("mysql:host=mysql;dbname=winnest_db;charset=utf8", "root", "qwerty");
    }
    catch(PDOException $er){
        die("Database connection failed: " . $er->getMessage());
    }
    
?>