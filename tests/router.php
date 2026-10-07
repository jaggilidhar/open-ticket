<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(preg_match('#^/(?:app|storage|docs|tests|scripts)(?:/|$)#',$path)||preg_match('#/\.#',$path)){http_response_code(404);exit;}
if(str_starts_with($path,'/assets/'))return false;
require __DIR__.'/../index.php';
