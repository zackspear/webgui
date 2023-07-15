#!/usr/bin/php
<?php

function httpPost($url, $data)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

$var = @parse_ini_file("/var/local/emhttp/var.ini") ?: [];
$rtn = httpPost("http://localhost/webGui/include/SysDrivers.php", ["table"=>"t1create","csrf_token" => $var['csrf_token']]) ;
echo $rtn ;
?>