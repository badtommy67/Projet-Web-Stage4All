<?php
$host   = $_ENV['DB_HOST'] ?? '';
$port   = $_ENV['DB_PORT'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? '';
$user   = $_ENV['DB_USER'] ?? '';
$pass   = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // echo "Connexion réussie !"; 

} catch(PDOException $e) {
    error_log($e->getMessage());
    if ($_ENV['APP_ENV'] === 'DEVELOPPEMENT') {
        die("Erreur de connexion : " . $e->getMessage());
    } else {
        echo "Une erreur est survenue...";
    }
}
?>