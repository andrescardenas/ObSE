<?php
/* Conectar a una base de datos de ODBC invocando al controlador */
$server = 'internal-db.s155458.gridserver.com';
$database = 'db155458_soyexperto';
$user = 'db155458';
$password = 'mayonesa';
$dns = 'mysql:dbname='.$database.';host='.$server;
try {
	$BD = new PDO($dns, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
} catch (PDOException $e) {echo 'connection failed: ' . $e->getMessage();}