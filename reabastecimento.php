<?php
session_start();
include 'db_connection.php';

// Ativa a exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$profile_picture = ''; // Inicializa a variável para evitar erro
$username = ''; // Inicializa a variável para evitar erro

// Inicializa as variáveis para evitar erros
$produtos = [];
$todosProdutos = [];

// Função para enviar alertas de reabastecimento
function enviarAlertaReabastecimento($produto, $quantidade_critica)
{
    $to = "christiantiiago@gmail.com";
    $subject = "Alerta de Reabastecimento";
    $message = "O produto {$produto['produto_nome']} está abaixo do nível crítico. Quantidade atual: {$produto['quantidade']}.";
    //mail($to, $subject, $message); // Descomente para enviar e-mail

    // Exibir notificação no sistema
    echo "<div class='alert alert-warning'>Alerta: Produto {$produto['produto_nome']} abaixo do nível crítico!</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_barras = $_POST['codigo_barras'] ?? '';
    $quantidade = $_POST['quantidade'] ?? 0;
    $operacao = $_POST['operacao'] ?? '';

    if (empty($codigo_barras) || empty($quantidade) || $quantidade <= 0) {
        $message = "Dados inválidos.";
    } else {
        try {
            if ($operacao === 'subtrair') {
                $sql = "UPDATE produtos SET quantidade = quantidade - :quantidade WHERE codigo_barras = :codigo_barras AND quantidade >= :quantidade";
            } else {
                $sql = "UPDATE produtos SET quantidade = quantidade + :quantidade WHERE codigo_barras = :codigo_barras";
            }

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':quantidade', $quantidade, PDO::PARAM_INT);
            $stmt->bindParam(':codigo_barras', $codigo_barras);

            if ($stmt->execute()) {
                $message = $operacao === 'subtrair' ? "Quantidade retirada com sucesso!" : "Produto reabastecido com sucesso!";

                // Registra a movimentação no histórico
                $sql = "INSERT INTO historico_movimentacoes (codigo_barras, quantidade, operacao, usuario_id) VALUES (:codigo_barras, :quantidade, :operacao, :usuario_id)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':codigo_barras', $codigo_barras);
                $stmt->bindParam(':quantidade', $quantidade, PDO::PARAM_INT);
                $stmt->bindParam(':operacao', $operacao);
                $stmt->bindParam(':usuario_id', $_SESSION['user_id']);
                $stmt->execute();
            } else {
                $message = "Erro ao atualizar o produto.";
            }
        } catch (PDOException $e) {
            $message = "Erro: " . $e->getMessage();
        }
    }
}

try {
    // Consulta para obter produtos com quantidade menor ou igual a 25 e suas localizações
    $sql = "SELECT p.nome AS produto_nome, p.codigo_barras, p.quantidade, p.camara, p.bloco, p.posicao_bloco, p.nivel, c.nome AS categoria_nome
            FROM produtos p
            JOIN categorias c ON p.categoria_id = c.id
            WHERE p.quantidade <= 25
            ORDER BY p.quantidade ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Envia alertas de reabastecimento
    foreach ($produtos as $produto) {
        if ($produto['quantidade'] <= 10) { // Defina o nível crítico
            enviarAlertaReabastecimento($produto, 10);
        }
    }

    // Consulta para obter todos os produtos e suas localizações
    $sql = "SELECT p.nome AS produto_nome, p.codigo_barras, p.quantidade, p.camara, p.bloco, p.posicao_bloco, p.nivel, c.nome AS categoria_nome
            FROM produtos p
            JOIN categorias c ON p.categoria_id = c.id
            ORDER BY p.camara, p.bloco, p.posicao_bloco, p.nivel";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $todosProdutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Função para obter produtos da mesma categoria, ordenados pela quantidade e pelo endereço
    function produtosDaCategoria($categoria_nome, $todosProdutos)
    {
        $produtosCategoria = array_filter($todosProdutos, function ($p) use ($categoria_nome) {
            return isset($p['categoria_nome']) && $p['categoria_nome'] == $categoria_nome;
        });

        usort($produtosCategoria, function ($a, $b) {
            if ($a['quantidade'] == $b['quantidade']) {
                return ($a['camara'] - $b['camara']) ?: ($a['bloco'] - $b['bloco']) ?: ($a['posicao_bloco'] - $b['posicao_bloco']) ?: ($a['nivel'] - $b['nivel']);
            }
            return $a['quantidade'] - $b['quantidade'];
        });

        return $produtosCategoria;
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Estoque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/picking.css">
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center">
            <img src="fotos/frimesa-logo-1.png" alt="Estoque Frimesa" style="max-width: 40%; height: auto;">
        </h1>
        <!-- Barra de Navegação -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <div class="container-fluid">
                <a class="nav-link d-flex align-items-center" href="profile.php">
                    <?php if ($profile_picture) : ?>
                        <img src="profile_pictures/<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto do Perfil" class="rounded-circle profile-photo">
                    <?php else : ?>
                        <img src="profile_pictures/default-profile.png" alt="Foto do Perfil" class="rounded-circle profile-photo">
                    <?php endif; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Perfil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Menu</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="aereo.php">Produtos Aéreo</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="notificacao.php">Produtos à Vencer</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="expired_products.php">Produtos Vencidos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="historico_movimentacoes.php">Histórico de Movimentações</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Sair</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        <hr>

        <h2 class="text-center">Exportar Dados</h2>
        <form action="exportar_dados.php" method="post" class="mb-4">
            <button class="btn btn-success" type="submit" name="exportar_excel">Exportar para Excel</button>
        </form>

        <form action="importar_dados.php" method="post" enctype="multipart/form-data">
            <input type="file" name="arquivo" accept=".csv">
            <button type="submit">Importar Dados</button>
        </form>


        <form action="reabastecimento.php" method="post">
            <div class="input-group">
                <div class="mb-3">
                    <label for="codigo_barras" class="form-label">ID Caixas:</label>
                    <input type="text" id="codigo_barras" name="codigo_barras" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="quantidade" class="form-label">Quantidade:</label>
                    <input type="number" id="quantidade" name="quantidade" class="form-control" min="1" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="operacao" class="form-label">Operação:</label>
                <select id="operacao" name="operacao" class="form-select">
                    <option value="adicionar">Adicionar</option>
                    <option value="subtrair">Subtrair</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Atualizar Estoque</button>
        </form>

        <h1>Nível Crítico de Produtos</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nome do Produto</th>
                    <th>Código de Barras</th>
                    <th>Quantidade</th>
                    <th>Câmara</th>
                    <th>Bloco</th>
                    <th>Posição do Bloco</th>
                    <th>Nível</th>
                    <th>Produtos Próximos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $produto): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($produto['produto_nome']); ?></td>
                        <td><?php echo htmlspecialchars($produto['codigo_barras']); ?></td>
                        <td><?php echo htmlspecialchars($produto['quantidade']); ?></td>
                        <td><?php echo htmlspecialchars($produto['camara']); ?></td>
                        <td><?php echo htmlspecialchars($produto['bloco']); ?></td>
                        <td><?php echo htmlspecialchars($produto['posicao_bloco']); ?></td>
                        <td><?php echo htmlspecialchars($produto['nivel']); ?></td>
                        <td>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-<?php echo htmlspecialchars($produto['codigo_barras']); ?>">
                                Exibir
                            </button>
                        </td>
                    </tr>
                    <!-- Modal -->
                    <div class="modal fade" id="modal-<?php echo htmlspecialchars($produto['codigo_barras']); ?>" tabindex="-1" aria-labelledby="modalLabel-<?php echo htmlspecialchars($produto['codigo_barras']); ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalLabel-<?php echo htmlspecialchars($produto['codigo_barras']); ?>">Detalhes do Produto</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="card">
                                        <div class="card-header"><?php echo htmlspecialchars($produto['produto_nome']); ?></div>
                                        <div class="card-body">
                                            <div class="details">
                                                <span><label>ID Caixas:</label> <?php echo htmlspecialchars($produto['codigo_barras']); ?></span>
                                                <span><label>Quantidade:</label> <?php echo htmlspecialchars($produto['quantidade']); ?></span>
                                                <span><label>Câmara:</label> <?php echo htmlspecialchars($produto['camara']); ?></span>
                                                <span><label>Bloco:</label> <?php echo htmlspecialchars($produto['bloco']); ?></span>
                                                <span><label>Posição do Bloco:</label> <?php echo htmlspecialchars($produto['posicao_bloco']); ?></span>
                                                <span><label>Nível:</label> <?php echo htmlspecialchars($produto['nivel']); ?></span>
                                                <span><label>Categoria:</label> <?php echo htmlspecialchars($produto['categoria_nome']); ?></span>
                                            </div>
                                            <h6>Produtos da Categoria <?php echo htmlspecialchars($produto['categoria_nome']); ?></h6>
                                            <?php
                                            $produtosCategoria = produtosDaCategoria($produto['categoria_nome'], $todosProdutos);
                                            if (!empty($produtosCategoria)): ?>
                                                <ul class="list-group">
                                                    <?php foreach ($produtosCategoria as $p): ?>
                                                        <li class="list-group-item">
                                                            <?php echo htmlspecialchars($p['produto_nome']); ?> - <?php echo htmlspecialchars($p['quantidade']); ?>
                                                            (<?php echo htmlspecialchars($p['camara']); ?>, <?php echo htmlspecialchars($p['bloco']); ?>, <?php echo htmlspecialchars($p['posicao_bloco']); ?>, <?php echo htmlspecialchars($p['nivel']); ?>)
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                Nenhum produto encontrado.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </tbody>
        </table>

        <h1>Tabela de Produtos</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nome do Produto</th>
                    <th>ID Caixas</th>
                    <th>Quantidade</th>
                    <th>Câmara</th>
                    <th>Bloco</th>
                    <th>Posição do Bloco</th>
                    <th>Nível</th>
                    <th>Categoria</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($todosProdutos as $produto): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($produto['produto_nome']); ?></td>
                        <td><?php echo htmlspecialchars($produto['codigo_barras']); ?></td>
                        <td><?php echo htmlspecialchars($produto['quantidade']); ?></td>
                        <td><?php echo htmlspecialchars($produto['camara']); ?></td>
                        <td><?php echo htmlspecialchars($produto['bloco']); ?></td>
                        <td><?php echo htmlspecialchars($produto['posicao_bloco']); ?></td>
                        <td><?php echo htmlspecialchars($produto['nivel']); ?></td>
                        <td><?php echo htmlspecialchars($produto['categoria_nome']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>