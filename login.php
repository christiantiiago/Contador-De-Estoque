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
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Cria uma instância PDO
        $pdo = new PDO($dsn, $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verifica as credenciais do usuário
        $stmt = $pdo->prepare("SELECT id, password_hash FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Salva o ID do usuário na sessão
            $_SESSION['user_id'] = $user['id'];

            // Redireciona para a página inicial
            header('Location: index.php');
            exit();
        } else {
            $error_message = 'E-mail ou senha inválidos.';
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
    <title>Login - Despensa Certa</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="login-container">
            <h1 class="text-center">Login</h1>
            <?php if (isset($error_message)) : ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Entrar</button>
            </form>
            <div class="text-center mt-3">
                <a href="register.php" class="btn btn-secondary">Cadastrar</a>
                <a href="forgot_password.php" class="btn btn-link">Esqueci minha senha</a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>