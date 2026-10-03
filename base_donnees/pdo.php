<?php
define('DB_HOST','localhost');
define('DB_NAME','projet_web');
define('DB_USER','root');
define('DB_PASS','');
$dsn='mysql:host='.DB_HOST.';dbname='.DB_NAME;
$options=array(PDO::ATTR_PERSISTENT=>true,
PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION);
try{
    $db=new PDO($dsn,DB_USER,DB_PASS,$options);
}
catch(PDOException $e){
    echo'connexion n\'est pas établie'.$e->getMessage();
}
?>
