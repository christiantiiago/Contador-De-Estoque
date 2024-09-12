<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];

    // Verifica o token
    $stmt = $pdo->prepare("SELECT user_id, expires FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset && $reset['expires'] > time()) {
        // Atualiza a senha do usuário
        $user_id = $reset['user_id'];
        $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_password_hash, $user_id]);

        // Remove o token usado
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);

        $success_message = 'Senha atualizada com sucesso!';
    } else {
        $error_message = 'O token de recuperação é inválido ou expirou.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Despensa Certa</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="login-container">
            <h1 class="text-center">Redefinir Senha</h1>
            <?php if (isset($error_message)) : ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($success_message)) : ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">
                <div class="form-group">
                    <label for="new_password">Nova Senha</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Redefinir Senha</button>
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