<?php
$RootPath=dirname(dirname(__FILE__)).'/';
include_once $RootPath.'config/serverinfo.php';
$test_ip = $_SERVER ['REMOTE_ADDR'];
$MyClasses=$RootPath."classes/";
///////////////////////////////////
function __autoload($name) {
    include_once dirname(dirname(__FILE__)).'/classes/'.$name.'.php';
}
///////////////////////////////////
ini_set("session.use_cookies", "on");
ini_set("session.use_trans_sid", "on");
session_start();
///////////////////////////
try {
    $dbh = new PDO("mysql:host=$db_server;dbname=$db_name", $db_username, $db_password);
    }
catch(PDOException $e)
    {
    echo $e->getMessage();
    echo 'SERVER IS NOT WORKING!';
    return;
    }
?>