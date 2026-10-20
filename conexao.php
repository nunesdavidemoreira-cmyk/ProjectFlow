<?php
$host = "db.iysrnayskoogcsqiwls.supabase.co";
$port = "5432";
$db   = "postgres";
$user = "postgres";
$pass = "Tugalux.010247";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $conn = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Erro na conexão com a base de dados: " . $e->getMessage());
}
?>