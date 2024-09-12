<?php
session_start();
include 'db_connection.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_barras = $_POST['codigo_barras'];
    $quantidade = $_POST['quantidade'];
    $operacao = $_POST['operacao'];

    // Valida os dados
    if (empty($codigo_barras) || empty($quantidade) || $quantidade <= 0) {
        $message = "Dados inválidos.";
    } else {
        try {
            if ($operacao == 'subtrair') {
                // Subtrai a quantidade
                $sql = "UPDATE produtos SET quantidade = quantidade - :quantidade WHERE codigo_barras = :codigo_barras AND quantidade >= :quantidade";
            } else {
                // Adiciona a quantidade
                $sql = "UPDATE produtos SET quantidade = quantidade + :quantidade WHERE codigo_barras = :codigo_barras";
            }

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':quantidade', $quantidade, PDO::PARAM_INT);
            $stmt->bindParam(':codigo_barras', $codigo_barras);

            if ($stmt->execute()) {
                $message = $operacao == 'subtrair' ? "Quantidade retirada com sucesso!" : "Produto reabastecido com sucesso!";
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
    $sql = "SELECT nome, codigo_barras, quantidade, camara, bloco, posicao_bloco, nivel, categoria_id
            FROM produtos
            WHERE quantidade <= 25
            ORDER BY quantidade ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para obter todos os produtos e suas localizações
    $sql = "SELECT nome, codigo_barras, quantidade, camara, bloco, posicao_bloco, nivel, categoria_id
            FROM produtos
            ORDER BY camara, bloco, posicao_bloco, nivel";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $todosProdutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Função para obter produtos da mesma categoria, ordenados pela quantidade e pelo endereço
    function produtosDaCategoria($categoria_id, $todosProdutos)
    {
        $produtosCategoria = array_filter($todosProdutos, function ($p) use ($categoria_id) {
            return $p['categoria_id'] == $categoria_id;
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
                <a class="navbar-brand d-flex align-items-center" href="profile.php">
                    <?php if (!empty($profile_picture)) : ?>
                        <!-- Exibe a foto de perfil se disponível -->
                        <img src="profile_pictures/<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto do Perfil" class="rounded-circle profile-photo" width="40" height="40">
                    <?php else : ?>
                        <!-- Exibe a foto padrão se não houver foto de perfil -->
                        <img src="profile_pictures/default-profile.png" alt="Foto do Perfil" class="rounded-circle profile-photo" width="40" height="40">
                    <?php endif; ?>
                    <span class="ms-2"><?php echo htmlspecialchars($username); ?></span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <!-- Botão de Perfil -->
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Perfil</a>
                        </li>
                        <!-- Botão de Reabastecimento -->
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Menu</a>
                        </li>
                        <!-- Produtos Aéreo -->
                        <li class="nav-item">
                            <a class="nav-link" href="aereo.php">Produtos Aéreo</a>
                        </li>
                        <!-- Botão de Notificação -->
                        <li class="nav-item">
                            <a class="nav-link" href="notificacao.php">Produtos à Vencer</a>
                        </li>
                        <!-- Botão de Vencido -->
                        <li class="nav-item">
                            <a class="nav-link" href="expired_products.php">Produtos Vencidos</a>
                        </li>
                        <!-- Botão de Sair -->
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

        <form action="reabastecimento.php" method="post">
            <div class="input-group">
                <div>
                    <label for="codigo_barras">ID Caixas:</label>
                    <input type="text" id="codigo_barras" name="codigo_barras" required>
                </div>
                <div>
                    <label for="quantidade">Quantidade:</label>
                    <input type="number" id="quantidade" name="quantidade" min="1" required>
                </div>
            </div>
            <div>
                <label for="operacao">Operação:</label>
                <select id="operacao" name="operacao">
                    <option value="adicionar">Adicionar</option>
                    <option value="subtrair">Subtrair</option>
                </select>
            </div>
            <input type="submit" value="Atualizar Estoque">
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
                        <td><?php echo htmlspecialchars($produto['nome']); ?></td>
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
                                        <div class="card-header"><?php echo htmlspecialchars($produto['nome']); ?></div>
                                        <div class="card-body">
                                            <div class="details">
                                                <span><label>ID Caixas:</label> <?php echo htmlspecialchars($produto['codigo_barras']); ?></span>
                                                <span><label>Quantidade:</label> <?php echo htmlspecialchars($produto['quantidade']); ?></span>
                                                <span><label>Câmara:</label> <?php echo htmlspecialchars($produto['camara']); ?></span>
                                                <span><label>Bloco:</label> <?php echo htmlspecialchars($produto['bloco']); ?></span>
                                                <span><label>Posição do Bloco:</label> <?php echo htmlspecialchars($produto['posicao_bloco']); ?></span>
                                                <span><label>Nível:</label> <?php echo htmlspecialchars($produto['nivel']); ?></span>
                                            </div>
                                            <h6>Produtos da Categoria <?php echo htmlspecialchars($produto['categoria_id']); ?></h6>
                                            <?php
                                            $produtosCategoria = produtosDaCategoria($produto['categoria_id'], $todosProdutos);
                                            if (!empty($produtosCategoria)): ?>
                                                <ul class="list-group">
                                                    <?php foreach ($produtosCategoria as $p): ?>
                                                        <li class="list-group-item">
                                                            <?php echo htmlspecialchars($p['nome']); ?> - <?php echo htmlspecialchars($p['quantidade']); ?>
                                                            (<?php echo htmlspecialchars($p['camara']); ?>, <?php echo htmlspecialchars($p['bloco']); ?>, <?php echo htmlspecialchars($p['posicao_bloco']); ?>)
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
                        <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                        <td><?php echo htmlspecialchars($produto['codigo_barras']); ?></td>
                        <td><?php echo htmlspecialchars($produto['quantidade']); ?></td>
                        <td><?php echo htmlspecialchars($produto['camara']); ?></td>
                        <td><?php echo htmlspecialchars($produto['bloco']); ?></td>
                        <td><?php echo htmlspecialchars($produto['posicao_bloco']); ?></td>
                        <td><?php echo htmlspecialchars($produto['nivel']); ?></td>
                        <td><?php echo htmlspecialchars($produto['categoria_id']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
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