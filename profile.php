<?php

session_start();
require 'db_connection.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=localhost;port=;dbname=frimesa;charset=utf8", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Conexão falhou: ' . $e->getMessage();
    exit();
}

// Obtém os dados do usuário
$stmt = $pdo->prepare("SELECT username, email, profile_picture, password_hash FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo 'Usuário não encontrado.';
    exit();
}

// Exclui a foto de perfil
if (isset($_POST['delete_picture'])) {
    $stmt = $pdo->prepare("UPDATE usuarios SET profile_picture = NULL WHERE id = ?");
    $stmt->execute([$userId]);
    header('Location: profile.php');
    exit();
}

// Atualiza o perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_picture'])) {
    $password = $_POST['password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $profilePicture = $user['profile_picture'];

    // Lógica de upload de imagem
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = 'profile_pictures/'; // Pasta para armazenar fotos de perfil
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetFile = $targetDir . basename($_FILES['profile_picture']['name']);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $uploadOk = 1;

        if ($_FILES['profile_picture']['size'] > 5000000) {
            echo 'O arquivo é muito grande.';
            $uploadOk = 0;
        }

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowedTypes)) {
            echo 'Somente arquivos JPG, JPEG, PNG e GIF são permitidos.';
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                // Salva apenas o nome do arquivo na base de dados
                $profilePicture = basename($_FILES['profile_picture']['name']);
            } else {
                echo 'Erro ao fazer upload da imagem.';
                exit();
            }
        }
    }

    // Atualiza a senha
    if (!empty($password) && !empty($newPassword)) {
        if (password_verify($password, $user['password_hash'])) {
            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ?, profile_picture = ? WHERE id = ?");
            $stmt->execute([$newPasswordHash, $profilePicture, $userId]);
        } else {
            echo 'Senha atual incorreta';
            exit();
        }
    } else {
        if ($profilePicture !== $user['profile_picture']) {
            $stmt = $pdo->prepare("UPDATE usuarios SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$profilePicture, $userId]);
        }
    }

    header('Location: profile.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Usuário</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="css/profile.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <div class="header">
            <h1>Meu Perfil</h1>
        </div>

        <form method="post" enctype="multipart/form-data">
            <label for="profile_picture">Foto de Perfil</label>
            <div class="form-group text-center">
                <?php if ($user['profile_picture']) : ?>
                    <img src="profile_pictures/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Foto de Perfil" class="img-thumbnail mt-2">
                    <button type="submit" name="delete_picture" class="btn btn-danger mt-3">Excluir Foto</button>
                <?php else: ?>
                    <p class="text-muted">Nenhuma foto de perfil disponível</p>
                <?php endif; ?>
                <input type="file" class="form-control-file mt-3" id="profile_picture" name="profile_picture">
            </div>

            <div class="form-group">
                <label for="username">Nome de Usuário</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="password">Senha Atual</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div class="form-group">
                <label for="new_password">Nova Senha</label>
                <input type="password" class="form-control" id="new_password" name="new_password">
            </div>
            <button type="submit" class="btn btn-primary">Atualizar Perfil</button>
            <a href="index.php" class="btn-back">Voltar ao Menu</a>
        </form>
    </div>
</body>

</html>