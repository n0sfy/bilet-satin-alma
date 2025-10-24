<?php

$db =__DIR__ . "/../database/database.db";

$dsn = "sqlite:$db";

try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
    //echo 'Database bağlandı.';
} catch (\PDOException $e) {
    echo $e->getMessage();
}

function generateUUID() {
    $uuid ='';
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    return $uuid;
}

?>