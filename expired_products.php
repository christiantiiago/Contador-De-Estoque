<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=localhost;port=;dbname=frimesa;charset=utf8", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtém os dados do usuário
    $stmt = $pdo->prepare("SELECT username FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo 'Usuário não encontrado.';
        exit();
    }

    // Verifica produtos que já venceram
    $pastDate = new DateTime();
    $pastDate = $pastDate->format('Y-m-d');

    $stmt = $pdo->prepare("SELECT id, nome, data_fabricacao, data_validade, camara, bloco, posicao_bloco, nivel 
                           FROM produtos 
                           WHERE data_validade < ?");
    $stmt->execute([$pastDate]);
    $expiredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Conexão falhou: ' . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos Vencidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/alerta.css">
</head>

<body>
    <div class="container mt-5">
        
        <h1 class="text-center">Produtos Vencidos</h1>

        <div class="table-responsive">
            <?php if ($expiredProducts) : ?>
                <table class="table mt-4">
                    <thead>
                        <tr>
                            <th>Nome do Produto</th>
                            <th>Data de Fabricação</th>
                            <th>Data de Validade</th>
                            <th>Câmara</th>
                            <th>Bloco</th>
                            <th>Posição</th>
                            <th>Nível</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiredProducts as $product) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['nome']); ?></td>
                                <td><?php echo (new DateTime($product['data_fabricacao']))->format('d/m/Y'); ?></td>
                                <td><?php echo (new DateTime($product['data_validade']))->format('d/m/Y'); ?></td>
                                <td><?php echo htmlspecialchars($product['camara']); ?></td>
                                <td><?php echo htmlspecialchars($product['bloco']); ?></td>
                                <td><?php echo htmlspecialchars($product['posicao_bloco']); ?></td>
                                <td><?php echo htmlspecialchars($product['nivel']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="mt-4">Nenhum produto vencido encontrado.</p>
            <?php endif; ?>
        </div>

        <a href="index.php" class="btn btn-primary mt-4">Voltar</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <footer>
        <div class="container copyright text-center mt-4">
            <p>© <span>Copyright</span> <strong class="px-1 sitename">Christian Group</strong> <span>Todos os direitos reservados</span></p>
            <div class="credits">
                Designed by <a href="https://christiantiago.com/">Christian Tiago</a>
            </div>
        </div>
    </footer>
</body>

</html>
