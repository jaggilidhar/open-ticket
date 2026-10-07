<?php
require __DIR__.'/../app/core.php';
$pdo=connection(require __DIR__.'/../storage/config.php');
try{reserve((int)$argv[1],(int)$argv[2],1,'Concurrent guest','concurrent@example.com');echo 'OK';}catch(RuntimeException $e){echo 'FULL';}
