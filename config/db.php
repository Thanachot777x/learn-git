<?php
// เช็คว่ารันอยู่บนเครื่อง (XAMPP) หรือบน InfinityFree 
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
    //  XAMPP
    $host     = "localhost";
    $dbname   = "it_support";
    $username = "root";
    $password = "";
    define('BASE_URL', '/it_support');
} else {
    //  InfinityFree
    $host     = "sql210.infinityfree.com";
    $dbname   = "if0_42447643_it_support";
    $username = "if0_42447643";
    $password = "R5Lerv2hbG";
    define('BASE_URL', '');
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $e->getMessage());
}
?>