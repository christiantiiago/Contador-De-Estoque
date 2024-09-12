<?php
session_start();

// Configurações do banco de dados
$host = 'localhost'; // Host do banco de dados
$port = '';
$db_name = 'frimesa'; // Nome correto do banco de dados
$db_user = 'root'; // Usuário do banco de dados (ou o que você estiver usando)
$db_password = ''; // Senha do banco de dados (ou o que você estiver usando)

// DSN (Data Source Name) para PDO
$dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Cria uma instância PDO
        $pdo = new PDO($dsn, $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verifica se o usuário já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $error_message = 'Usuário ou e-mail já cadastrado.';
        } else {
            // Cria o hash da senha
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Insere o novo usuário
            $stmt = $pdo->prepare("INSERT INTO usuarios (username, email, password_hash) VALUES (:username, :email, :password_hash)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $password_hash);

            if (!$stmt->execute()) {
                die('Execute failed: ' . htmlspecialchars($stmt->errorInfo()[2]));
            }

            // Salva o ID do usuário na sessão
            $_SESSION['user_id'] = $pdo->lastInsertId();

            // Redireciona para a página de login
            header('Location: login.php');
            exit();
        }
    } catch (PDOException $e) {
        die('Database error: ' . htmlspecialchars($e->getMessage()));
    }
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Despensa Certa</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="login-container">
            <h1 class="text-center">Registro</h1>
            <?php if (isset($error_message)) : ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">Nome de Usuário</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Cadastrar</button>
            </form>
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-secondary">Voltar ao Login</a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>