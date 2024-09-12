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
    $pdo = new PDO("mysql:host=localhost;port=;dbname=frimesa;charset=utf8", 'frimesa', 'bcT8yY5d4Cw22ja');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Conexão falhou: ' . $e->getMessage();
    exit();
}

// Obtém os dados do usuário
$stmt = $pdo->prepare("SELECT username, profile_picture FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo 'Usuário não encontrado.';
    exit();
}

// Obtém produtos com nível acima de 1
$stmt = $pdo->prepare("SELECT produtos.*, categorias.nome AS categoria_nome FROM produtos
                        LEFT JOIN categorias ON produtos.categoria_id = categorias.id
                        WHERE produtos.nivel > 1
                        ORDER BY produtos.id DESC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$username = $user['username'];
$profile_picture = $user['profile_picture'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos Acima do Nível 1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center">Produtos Acima do Nível 1</h1>
        
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
                <div class="profile-container">
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($username); ?></h2>
                    </div>
                </div>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <!-- Botão de Perfil -->
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Perfil</a>
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

        <div class="table-container">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Câmara</th>
                        <th>Bloco</th>
                        <th>Posição</th>
                        <th>Nível</th>
                        <th>Nome</th>
                        <th>Quantidade</th>
                        <th>Peso Líquido</th>
                        <th>Peso Bruto</th>
                        <th>ID Caixa</th>
                        <th>Data de Fabricação</th>
                        <th>Data de Validade</th>
                        <th>Categoria</th>
                        <th>Foto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($products) {
                        foreach ($products as $product) {
                            $foto = $product["foto_produto"] ?? 'sem_foto.jpg';
                            $fotoPath = 'uploads/' . htmlspecialchars($foto);

                            // Formata as datas no formato brasileiro
                            $dataFabricacaoFormatada = (new DateTime($product["data_fabricacao"]))->format('d/m/Y');
                            $dataValidadeFormatada = (new DateTime($product["data_validade"]))->format('d/m/Y');

                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($product["camara"]) . "</td>";
                            echo "<td>" . htmlspecialchars($product["bloco"]) . "</td>";
                            echo "<td>" . htmlspecialchars($product["posicao_bloco"]) . "</td>";
                            echo "<td>" . htmlspecialchars($product["nivel"]) . "</td>";
                            echo "<td>" . htmlspecialchars($product["nome"]) . "</td>";
                            echo "<td>" . htmlspecialchars($product["quantidade"]) . "</td>";
                            echo "<td>" . htmlspecialchars($product["peso_liquido"]) . "</td>";
                            echo "<td>" . htmlspecialchars($product["peso_bruto"]) . "</td>";
                            echo "<td>" . htmlspecialchars($product["codigo_barras"]) . "</td>";
                            echo "<td>" . $dataFabricacaoFormatada . "</td>";
                            echo "<td>" . $dataValidadeFormatada . "</td>";
                            echo "<td>" . htmlspecialchars($product["categoria_nome"]) . "</td>";

                            if ($foto !== 'sem_foto.jpg' && file_exists($fotoPath)) {
                                echo "<td><img src='" . htmlspecialchars($fotoPath) . "' alt='Foto do Produto' width='100px' class='img-thumbnail'></td>";
                            } else {
                                echo "<td><img src='uploads/sem_foto.jpg' alt='Foto do Produto' class='img-thumbnail' style='max-width: 100px;'></td>";
                            }

                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='13'>Nenhum produto encontrado.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
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
