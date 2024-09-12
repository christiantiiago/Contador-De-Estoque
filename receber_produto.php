<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Inicializa variáveis
$mensagem = '';
$categorias = [];

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

// Busca as categorias do banco de dados
try {
    $sql = "SELECT id, nome FROM categorias";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao buscar categorias: " . $e->getMessage();
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura e limpa os dados do POST
    $codigo_barras = trim($_POST['codigo_barras'] ?? '');
    $quantidade = $_POST['quantidade'] ?? 0;
    $camara = trim($_POST['camara'] ?? '');
    $bloco = trim($_POST['bloco'] ?? '');
    $posicao_bloco = trim($_POST['posicao_bloco'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    $categoria_id = $_POST['categoria_id'] ?? 0;
    $data_fabricacao = $_POST['data_fabricacao'] ?? '';
    $data_validade = $_POST['data_validade'] ?? '';
    $usuario_id = $_SESSION['user_id'];

    // Valida os dados
    if (empty($codigo_barras) || $quantidade <= 0) {
        $mensagem = "Dados inválidos.";
    } else {
        try {
            // Verifica se o categoria_id existe na tabela de categorias
            $sql = "SELECT COUNT(*) FROM categorias WHERE id = :categoria_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':categoria_id', $categoria_id);
            $stmt->execute();
            $categoriaExiste = $stmt->fetchColumn();

            if (!$categoriaExiste) {
                $mensagem = "Categoria não encontrada.";
            } else {
                // Verifica se o código de barras existe na tabela de produtos
                $sql = "SELECT COUNT(*) FROM produtos WHERE codigo_barras = :codigo_barras";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':codigo_barras', $codigo_barras);
                $stmt->execute();
                $produtoExiste = $stmt->fetchColumn();
                
                if (!$produtoExiste) {
                    // Se o produto não existir, cria um novo código de barras e insere um novo produto
                    $novoCodigoBarras = uniqid(); // Gera um novo código de barras único
                    $sql = "INSERT INTO produtos (codigo_barras) VALUES (:codigo_barras)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':codigo_barras', $novoCodigoBarras);
                    $stmt->execute();
                
                    $codigo_barras = $novoCodigoBarras; // Atualiza o código de barras para o novo valor
                }
                
                // Insere o recebimento no banco de dados
                $sql = "INSERT INTO recebimentos (
                            data, codigo_barras, quantidade, camara, bloco, posicao_bloco, nivel, categoria_id, data_fabricacao, data_validade, usuario_id
                        ) VALUES (
                            NOW(), :codigo_barras, :quantidade, :camara, :bloco, :posicao_bloco, :nivel, :categoria_id, :data_fabricacao, :data_validade, :usuario_id
                        )";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':codigo_barras', $codigo_barras);
                $stmt->bindParam(':quantidade', $quantidade);
                $stmt->bindParam(':camara', $camara);
                $stmt->bindParam(':bloco', $bloco);
                $stmt->bindParam(':posicao_bloco', $posicao_bloco);
                $stmt->bindParam(':nivel', $nivel);
                $stmt->bindParam(':categoria_id', $categoria_id);
                $stmt->bindParam(':data_fabricacao', $data_fabricacao);
                $stmt->bindParam(':data_validade', $data_validade);
                $stmt->bindParam(':usuario_id', $usuario_id);
                $stmt->execute();
                
                // Atualiza o estoque
                $sql = "INSERT INTO estoque (codigo_barras, quantidade, camara, bloco, posicao_bloco, nivel, categoria_id, data_fabricacao, data_validade)
                        VALUES (:codigo_barras, :quantidade, :camara, :bloco, :posicao_bloco, :nivel, :categoria_id, :data_fabricacao, :data_validade)
                        ON DUPLICATE KEY UPDATE quantidade = quantidade + VALUES(quantidade)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':codigo_barras', $codigo_barras);
                $stmt->bindParam(':quantidade', $quantidade);
                $stmt->bindParam(':camara', $camara);
                $stmt->bindParam(':bloco', $bloco);
                $stmt->bindParam(':posicao_bloco', $posicao_bloco);
                $stmt->bindParam(':nivel', $nivel);
                $stmt->bindParam(':categoria_id', $categoria_id);
                $stmt->bindParam(':data_fabricacao', $data_fabricacao);
                $stmt->bindParam(':data_validade', $data_validade);
                $stmt->execute();
                
                $mensagem = "Recebimento registrado e estoque atualizado com sucesso!";
            }
        } catch (PDOException $e) {
            $mensagem = "Erro: " . $e->getMessage();
        }
    }
}

// Obtém o histórico dos recebimentos recentes
try {
    $sql = "SELECT r.*, p.nome AS nome_produto, u.username AS nome_usuario, c.nome AS nome_categoria
            FROM recebimentos r
            JOIN produtos p ON r.codigo_barras = p.codigo_barras
            JOIN usuarios u ON r.usuario_id = u.id
            JOIN categorias c ON r.categoria_id = c.id
            ORDER BY r.data DESC
            LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao buscar histórico: " . $e->getMessage();
}

function formatDateBR($date)
{
    $dateTime = new DateTime($date);
    return $dateTime->format('d/m/Y');
}

// Fecha a conexão
$conn = null;
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recebimento de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/picking.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                        <img src="profile_pictures/<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto do Perfil" class="rounded-circle profile-photo" width="40" height="40">
                    <?php else : ?>
                        <img src="profile_pictures/default-profile.png" alt="Foto do Perfil" class="rounded-circle profile-photo" width="40" height="40">
                    <?php endif; ?>
                    <span class="ms-2"><?php echo htmlspecialchars($username); ?></span>
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
                            <a class="nav-link" href="visualizar_estoque.php">Visualizar Estoque</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sair.php">Sair</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Mensagem de Erro ou Sucesso -->
        <?php if (isset($_SESSION['mensagem'])) : ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($_SESSION['mensagem']); ?>
                <?php unset($_SESSION['mensagem']); // Remover a mensagem após exibir 
                ?>
            </div>
        <?php endif; ?>


        <!-- Formulário de Recebimento -->
        <h2>Registrar Recebimento de Produtos</h2>
        <form method="POST" action="receber_produto.php">
            <div class="mb-3">
                <label for="codigo_barras" class="form-label">Código de Barras</label>
                <input type="text" name="codigo_barras" id="codigo_barras" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="quantidade" class="form-label">Quantidade</label>
                <input type="number" name="quantidade" id="quantidade" class="form-control" min="1" required>
            </div>
            <div class="mb-3">
                <label for="camara" class="form-label">Câmara</label>
                <input type="text" name="camara" id="camara" class="form-control">
            </div>
            <div class="mb-3">
                <label for="bloco" class="form-label">Bloco</label>
                <input type="text" name="bloco" id="bloco" class="form-control">
            </div>
            <div class="mb-3">
                <label for="posicao_bloco" class="form-label">Posição do Bloco</label>
                <input type="text" name="posicao_bloco" id="posicao_bloco" class="form-control">
            </div>
            <div class="mb-3">
                <label for="nivel" class="form-label">Nível</label>
                <input type="text" name="nivel" id="nivel" class="form-control">
            </div>
            <div class="mb-3">
                <label for="categoria_id" class="form-label">Categoria</label>
                <select name="categoria_id" id="categoria_id" class="form-select">
                    <?php foreach ($categorias as $categoria) : ?>
                        <option value="<?php echo htmlspecialchars($categoria['id']); ?>">
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="data_fabricacao" class="form-label">Data de Fabricação</label>
                <input type="date" name="data_fabricacao" id="data_fabricacao" class="form-control">
            </div>
            <div class="mb-3">
                <label for="data_validade" class="form-label">Data de Validade</label>
                <input type="date" name="data_validade" id="data_validade" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Registrar Recebimento</button>
        </form>

        <!-- Histórico de Recebimento -->
        <h2 class="mt-5">Histórico de Recebimentos</h2>
        <table class="table table-striped mt-3">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Câmara</th>
                    <th>Bloco</th>
                    <th>Posição do Bloco</th>
                    <th>Nível</th>
                    <th>Categoria</th>
                    <th>Data de Fabricação</th>
                    <th>Data de Validade</th>
                    <th>Recebido por</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($historico)) : ?>
                    <?php foreach ($historico as $recebimento) : ?>
                        <tr>
                            <td><?php echo formatDateBR($recebimento['data']); ?></td>
                            <td><?php echo htmlspecialchars($recebimento['nome_produto']); ?></td>
                            <td><?php echo htmlspecialchars($recebimento['quantidade']); ?></td>
                            <td><?php echo htmlspecialchars($recebimento['camara']); ?></td>
                            <td><?php echo htmlspecialchars($recebimento['bloco']); ?></td>
                            <td><?php echo htmlspecialchars($recebimento['posicao_bloco']); ?></td>
                            <td><?php echo htmlspecialchars($recebimento['nivel']); ?></td>
                            <td><?php echo htmlspecialchars($recebimento['nome_categoria']); ?></td>
                            <td><?php echo formatDateBR($recebimento['data_fabricacao']); ?></td>
                            <td><?php echo formatDateBR($recebimento['data_validade']); ?></td>
                            <td><?php echo htmlspecialchars($recebimento['nome_usuario']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="11">Nenhum recebimento registrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#codigo_barras').on('blur', function() {
                var codigoBarras = $(this).val();
                if (codigoBarras) {
                    $.ajax({
                        url: 'buscar_produto.php',
                        method: 'GET',
                        data: {
                            codigo_barras: codigoBarras
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            if (data.success) {
                                $('#quantidade').val(data.quantidade);
                                $('#camara').val(data.camara);
                                $('#bloco').val(data.bloco);
                                $('#posicao_bloco').val(data.posicao_bloco);
                                $('#nivel').val(data.nivel);
                                $('#categoria_id').val(data.categoria_id);
                                $('#data_fabricacao').val(data.data_fabricacao);
                                $('#data_validade').val(data.data_validade);
                            } else {
                                alert(data.message || 'Produto não encontrado.');
                            }
                        },
                        error: function() {
                            alert('Erro ao buscar informações do produto.');
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>