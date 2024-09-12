<?php
session_start();
include 'db_connection.php';

// Inicializa variáveis
$mensagem = '';
$historico = [];
$profile_picture = '';
$username = '';

// Define o fuso horário
date_default_timezone_set('America/Sao_Paulo'); // Ajuste o fuso horário conforme necessário

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Obtém o perfil do usuário logado
if (isset($_SESSION['user_id'])) {
    try {
        $sql = "SELECT username, profile_picture FROM usuarios WHERE id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $username = $user['username'];
            $profile_picture = $user['profile_picture'] ?? '';
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao buscar informações do usuário: " . $e->getMessage();
    }
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura os dados do POST
    $codigo_barras = $_POST['codigo_barras'] ?? '';
    $quantidade = $_POST['quantidade'] ?? 0;
    $data_validade = $_POST['data_validade'] ?? '';
    $data_fabricacao = $_POST['data_fabricacao'] ?? '';
    $bloco = $_POST['bloco'] ?? '';
    $nivel = $_POST['nivel'] ?? 0;
    $destino = $_POST['destino'] ?? '';
    $camara = $_POST['camara'] ?? '';
    $categoria_id = $_POST['categoria_id'] ?? 1; // Definido como 1 por padrão se não fornecido
    $usuario_id = $_SESSION['user_id'];

    // Valida os dados
    if (empty($codigo_barras) || $quantidade <= 0 || empty($data_validade) || empty($data_fabricacao)) {
        $mensagem = "Dados inválidos.";
    } else {
        try {
            // Inicia uma transação
            $conn->beginTransaction();

            // Verifica se o código de barras existe na tabela de produtos
            $sql = "SELECT COUNT(*) FROM produtos WHERE codigo_barras = :codigo_barras";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':codigo_barras', $codigo_barras);
            $stmt->execute();
            $produtoExiste = $stmt->fetchColumn();

            if (!$produtoExiste) {
                // Adiciona o produto à tabela de produtos
                $sql = "INSERT INTO produtos (camara, bloco, posicao_bloco, nivel, categoria_id, nome, quantidade, peso_liquido, peso_bruto, codigo_barras, data_fabricacao, data_validade)
                        VALUES (:camara, :bloco, '', :nivel, :categoria_id, '', 0, 0, 0, :codigo_barras, :data_fabricacao, :data_validade)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':camara', $camara);
                $stmt->bindParam(':bloco', $bloco);
                $stmt->bindParam(':nivel', $nivel);
                $stmt->bindParam(':categoria_id', $categoria_id);
                $stmt->bindParam(':codigo_barras', $codigo_barras);
                $stmt->bindParam(':data_fabricacao', $data_fabricacao);
                $stmt->bindParam(':data_validade', $data_validade);
                $stmt->execute();
            }

            // Insere o recebimento no banco de dados
            $sql = "INSERT INTO recebimentos (data, codigo_barras, quantidade, camara, bloco, posicao_bloco, nivel, categoria_id, data_fabricacao, data_validade, usuario_id)
                    VALUES (NOW(), :codigo_barras, :quantidade, :camara, :bloco, '', :nivel, :categoria_id, :data_fabricacao, :data_validade, :usuario_id)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':codigo_barras', $codigo_barras);
            $stmt->bindParam(':quantidade', $quantidade);
            $stmt->bindParam(':camara', $camara);
            $stmt->bindParam(':bloco', $bloco);
            $stmt->bindParam(':nivel', $nivel);
            $stmt->bindParam(':categoria_id', $categoria_id);
            $stmt->bindParam(':data_fabricacao', $data_fabricacao);
            $stmt->bindParam(':data_validade', $data_validade);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->execute();

            // Atualiza a tabela de estoque
            $sql = "INSERT INTO estoque (codigo_barras, quantidade, data_validade, data_fabricacao, bloco, nivel, destino)
                    VALUES (:codigo_barras, :quantidade, :data_validade, :data_fabricacao, :bloco, :nivel, :destino)
                    ON DUPLICATE KEY UPDATE
                    quantidade = quantidade + VALUES(quantidade),
                    data_validade = VALUES(data_validade),
                    data_fabricacao = VALUES(data_fabricacao),
                    bloco = VALUES(bloco),
                    nivel = VALUES(nivel),
                    destino = VALUES(destino)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':codigo_barras', $codigo_barras);
            $stmt->bindParam(':quantidade', $quantidade);
            $stmt->bindParam(':data_validade', $data_validade);
            $stmt->bindParam(':data_fabricacao', $data_fabricacao);
            $stmt->bindParam(':bloco', $bloco);
            $stmt->bindParam(':nivel', $nivel);
            $stmt->bindParam(':destino', $destino);
            $stmt->execute();

            // Adiciona o histórico de movimentações
            $sql = "INSERT INTO historico_movimentacoes (codigo_barras, quantidade, operacao, usuario_id)
                    VALUES (:codigo_barras, :quantidade, 'Adicionar Quantidade', :usuario_id)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':codigo_barras', $codigo_barras);
            $stmt->bindParam(':quantidade', $quantidade);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->execute();

            // Confirma a transação
            $conn->commit();
            $mensagem = "Recebimento registrado e estoque atualizado com sucesso!";
        } catch (PDOException $e) {
            $conn->rollBack();
            $mensagem = "Erro ao registrar recebimento: " . $e->getMessage();
        }
    }
}

// Consulta o histórico de recebimentos
try {
    $sql = "SELECT r.data, p.nome AS nome_produto, r.quantidade, r.camara, r.bloco, r.nivel, r.destino, u.username AS nome_usuario
            FROM recebimentos r
            JOIN produtos p ON r.codigo_barras = p.codigo_barras
            JOIN usuarios u ON r.usuario_id = u.id
            ORDER BY r.data DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao buscar histórico: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recebimentos Frimesa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="#">Estoque Frimesa</a>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">Recebimentos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="historico.php">Histórico</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Sair</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <!-- Mensagem de status -->
        <?php if ($mensagem) : ?>
            <div class="alert alert-info" role="alert">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>
        <!-- Formulário de Recebimento -->
        <h2>Recebimento de Produtos</h2>
        <form action="" method="post">
            <div class="mb-3">
                <label for="codigo_barras" class="form-label">Código de Barras</label>
                <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" required>
            </div>
            <div class="mb-3">
                <label for="quantidade" class="form-label">Quantidade</label>
                <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" required>
            </div>
            <div class="mb-3">
                <label for="data_fabricacao" class="form-label">Data de Fabricação</label>
                <input type="date" class="form-control" id="data_fabricacao" name="data_fabricacao" required>
            </div>
            <div class="mb-3">
                <label for="data_validade" class="form-label">Data de Validade</label>
                <input type="date" class="form-control" id="data_validade" name="data_validade" required>
            </div>
            <div class="mb-3">
                <label for="camara" class="form-label">Câmara</label>
                <input type="text" class="form-control" id="camara" name="camara" required>
            </div>
            <div class="mb-3">
                <label for="bloco" class="form-label">Bloco</label>
                <input type="text" class="form-control" id="bloco" name="bloco" required>
            </div>
            <div class="mb-3">
                <label for="nivel" class="form-label">Nível</label>
                <input type="number" class="form-control" id="nivel" name="nivel" min="0" required>
            </div>
            <div class="mb-3">
                <label for="destino" class="form-label">Destino</label>
                <input type="text" class="form-control" id="destino" name="destino">
            </div>
            <div class="mb-3">
                <label for="categoria_id" class="form-label">Categoria</label>
                <select id="categoria_id" name="categoria_id" class="form-control">
                    <?php
                    // Consulta para preencher o dropdown com categorias
                    $sql = "SELECT id, nome FROM categorias";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($categorias as $categoria) {
                        echo "<option value=\"{$categoria['id']}\">{$categoria['nome']}</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Registrar Recebimento</button>
        </form>

        <!-- Histórico de Recebimentos -->
        <h2 class="mt-5">Histórico de Recebimentos</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Câmara</th>
                    <th>Bloco</th>
                    <th>Nível</th>
                    <th>Destino</th>
                    <th>Usuário</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historico as $item) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['data']); ?></td>
                        <td><?php echo htmlspecialchars($item['nome_produto']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantidade']); ?></td>
                        <td><?php echo htmlspecialchars($item['camara']); ?></td>
                        <td><?php echo htmlspecialchars($item['bloco']); ?></td>
                        <td><?php echo htmlspecialchars($item['nivel']); ?></td>
                        <td><?php echo htmlspecialchars($item['destino']); ?></td>
                        <td><?php echo htmlspecialchars($item['nome_usuario']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
