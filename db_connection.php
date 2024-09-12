<?php
$servername = "localhost";
$port = ""; // Deixe vazio se não precisar especificar a porta
$username = "root"; // Usuário correto
$password = ""; // Senha correta
$dbname = "frimesa";

try {
    // Cria uma instância PDO
    $dsn = "mysql:host=$servername;dbname=$dbname" . ($port ? ";port=$port" : "");
    $conn = new PDO($dsn, $username, $password);
    
    // Definindo o modo de erro para exceções
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Opcional: Definir o charset
    $conn->exec("set names utf8mb4");
    
    // Confirmando a conexão
    echo "Conexão bem-sucedida!";
} catch (PDOException $e) {
    // Exibindo mensagem de erro
    echo "Erro de conexão: " . $e->getMessage();
}
?>
