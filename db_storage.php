<?php

$server = "192.168.0.149";
$login="root";
$password= "";
$name_bd= "items_bd";

$link=mysqli_connect($server, $login, $password, $name_bd);

if ($link == False) {
    echo "Не вдалось з'єднатися з сервером.";
}
