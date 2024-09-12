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

// Verifica produtos prestes a vencer (aqui estou assumindo 7 dias como o limite)
$alertThreshold = new DateTime();
$alertThreshold->modify('+7 days');
$alertDate = $alertThreshold->format('Y-m-d');

$stmt = $pdo->prepare("SELECT id, nome, data_validade FROM produtos WHERE data_validade <= ?");
$stmt->execute([$alertDate]);
$expiringProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verifica produtos que já venceram
$pastDate = new DateTime();
$pastDate = $pastDate->format('Y-m-d');

$stmt = $pdo->prepare("SELECT id, nome, data_validade FROM produtos WHERE data_validade < ?");
$stmt->execute([$pastDate]);
$expiredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gerar alertas
$expiringAlert = '';
if ($expiringProducts) {
    $expiringAlert = '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
    $expiringAlert .= '<strong>Alerta:</strong> Alguns produtos estão prestes a vencer!';
    $expiringAlert .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $expiringAlert .= '</div>';
}

$expiredAlert = '';
if ($expiredProducts) {
    $expiredAlert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    $expiredAlert .= '<strong>Alerta:</strong> Alguns produtos já venceram!';
    $expiredAlert .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $expiredAlert .= '</div>';
}

$username = $user['username'];
$profile_picture = $user['profile_picture'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Estoque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
                            <a class="nav-link" href="reabastecimento.php">Reabastecimento</a>
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

        <?php if ($expiringAlert) : ?>
            <?php echo $expiringAlert; ?>
        <?php endif; ?>

        <?php if ($expiredAlert) : ?>
            <?php echo $expiredAlert; ?>
        <?php endif; ?>

        

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="add-product-tab" data-bs-toggle="tab" data-bs-target="#add-product" type="button" role="tab" aria-controls="add-product" aria-selected="true">Produto</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="view-stock-tab" data-bs-toggle="tab" data-bs-target="#view-stock" type="button" role="tab" aria-controls="view-stock" aria-selected="false">Estoque</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="add-category-tab" data-bs-toggle="tab" data-bs-target="#add-category" type="button" role="tab" aria-controls="add-category" aria-selected="false">Categorias</button>
            </li>
        </ul>


        <div class="tab-content" id="myTabContent">
            <!-- Adicionar Produto -->
            <div class="tab-pane fade show active" id="add-product" role="tabpanel" aria-labelledby="add-product-tab">
                <h3 class="text-center">Adicionar Produto</h3>
                <form action="adicionar_produto.php" method="POST" enctype="multipart/form-data" class="mt-4">
                    <div class="row mb-3">
                        <div class="col">
                            <label for="camara" class="form-label">Camara</label>
                            <input type="text" class="form-control" id="camara" name="camara" required>
                        </div>
                        <div class="col">
                            <label for="bloco" class="form-label">Bloco</label>
                            <input type="text" class="form-control" id="bloco" name="bloco" required>
                        </div>
                        <div class="col">
                            <label for="posicao_bloco" class="form-label">Posição</label>
                            <input type="text" class="form-control" id="posicao_bloco" name="posicao_bloco" required>
                        </div>
                        <div class="col">
                            <label for="nivel" class="form-label">Nível</label>
                            <input type="text" class="form-control" id="nivel" name="nivel" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="categoria" class="form-label">Categoria</label>
                            <select id="categoria" name="categoria" class="form-control" required>
                                <option value="">Selecione uma Categoria</option>
                                <?php
                                include 'db_connection.php';

                                try {
                                    $sql = "SELECT id, nome FROM categorias";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute();
                                    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($categorias as $categoria) {
                                        echo "<option value='{$categoria['id']}'>{$categoria['nome']}</option>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<option value=''>Erro ao carregar categorias</option>";
                                }

                                $conn = null;
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="nomeProduto" class="form-label">Nome do Produto</label>
                            <input type="text" class="form-control" id="nomeProduto" name="nomeProduto" required>
                        </div>
                        <div class="col">
                            <label for="quantidade" class="form-label">Quantidade (Cxs)</label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="pesoLiquido" class="form-label">Peso Líquido (kg)</label>
                            <input type="number" class="form-control" id="pesoLiquido" name="pesoLiquido" step="0.01" required>
                        </div>
                        <div class="col">
                            <label for="pesoBruto" class="form-label">Peso Bruto (kg)</label>
                            <input type="number" class="form-control" id="pesoBruto" name="pesoBruto" step="0.01" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="codigoBarras" class="form-label">ID Caixa</label>
                            <input type="text" class="form-control" id="codigoBarras" name="codigoBarras" required>
                        </div>
                        <div class="col">
                            <label for="dataFabricacao" class="form-label">Data de Fabricação</label>
                            <input type="date" class="form-control" id="dataFabricacao" name="dataFabricacao" required>
                        </div>
                        <div class="col">
                            <label for="dataValidade" class="form-label">Data de Validade</label>
                            <input type="date" class="form-control" id="dataValidade" name="dataValidade" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="fotoProduto" class="form-label">Foto do Produto</label>
                            <input type="file" class="form-control" id="fotoProduto" name="fotoProduto" accept="image/*" capture="camera" required>
                            <button type="button" class="btn btn-secondary mt-2" onclick="startCamera()">Abrir Câmera</button>
                            <button type="button" class="btn btn-secondary mt-2" onclick="capturePhoto()">Capturar Foto</button>
                            <video id="cameraStream" autoplay></video>
                            <canvas id="photoCanvas" style="display:none;"></canvas>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Adicionar Produto</button>
                </form>
            </div>
            <!-- Visualizar Estoque -->
            <div class="tab-pane fade" id="view-stock" role="tabpanel" aria-labelledby="view-stock-tab">
                <div class="mt-4">
                    <div class="table-container">
                        <table class="table">
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
                                include 'db_connection.php';

                                try {
                                    $sql = "SELECT produtos.*, categorias.nome AS categoria_nome FROM produtos
                                LEFT JOIN categorias ON produtos.categoria_id = categorias.id
                                ORDER BY produtos.id DESC
                                LIMIT 15";

                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute();

                                    if ($stmt->rowCount() > 0) {
                                        // No loop que gera as linhas da tabela
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $foto = $row["foto_produto"] ?? 'sem_foto.jpg';
                                            $fotoPath = 'uploads/' . htmlspecialchars($foto);

                                            // Formata as datas no formato brasileiro
                                            $dataFabricacaoFormatada = (new DateTime($row["data_fabricacao"]))->format('d/m/Y');
                                            $dataValidadeFormatada = (new DateTime($row["data_validade"]))->format('d/m/Y');

                                            // Verifica o status da data de validade
                                            $linhaClasse = '';
                                            $statusMensagem = '';
                                            if ($row["data_validade"]) {
                                                $dataValidade = new DateTime($row["data_validade"]);
                                                $dataAtual = new DateTime();
                                                if ($dataValidade < $dataAtual) {
                                                    $linhaClasse = 'vencido'; // Linha inteira vermelha
                                                    $statusMensagem = 'Produto Vencido';
                                                } elseif ($dataValidade <= (clone $dataAtual)->add(new DateInterval('P7D'))) {
                                                    $linhaClasse = 'vencer'; // Linha inteira amarela
                                                    $statusMensagem = 'Produto a Vencer';
                                                }
                                            }

                                            echo "<tr class='" . htmlspecialchars($linhaClasse) . "'>";
                                            echo "<td>" . htmlspecialchars($row["camara"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($row["bloco"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($row["posicao_bloco"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($row["nivel"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($row["nome"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($row["quantidade"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($row["peso_liquido"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($row["peso_bruto"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($row["codigo_barras"]) . "</td>";
                                            echo "<td>" . $dataFabricacaoFormatada . "</td>";
                                            echo "<td>" . $dataValidadeFormatada . " <span class='alerta " . ($statusMensagem == 'Produto Vencido' ? 'vencido' : 'vencer') . "'>" . htmlspecialchars($statusMensagem) . "</span></td>";
                                            echo "<td>" . htmlspecialchars($row["categoria_nome"]) . "</td>";

                                            if ($foto !== 'sem_foto.jpg' && file_exists($fotoPath)) {
                                                echo "<td><img src='" . htmlspecialchars($fotoPath) . "' alt='Foto do Produto' width='100px' class='img-thumbnail' style='cursor: pointer;' data-bs-toggle='modal' data-bs-target='#photoModal' onclick='showModal(this.src)'></td>";
                                            } else {
                                                echo "<td><img src='uploads/sem_foto.jpg' alt='Foto do Produto' class='img-thumbnail' style='max-width: 100px; cursor: pointer;' data-bs-toggle='modal' data-bs-target='#photoModal' onclick='showModal(this.src)'></td>";
                                            }

                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='13'>Nenhum produto encontrado no estoque.</td></tr>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='13'>Erro ao acessar o banco de dados: " . $e->getMessage() . "</td></tr>";
                                }

                                $conn = null;
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="visualizar_estoque.php" class="btn btn-success">Editar Estoque</a>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="photoModalLabel">Foto do Produto</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <img id="modalPhoto" src="" alt="Foto do Produto" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categorias -->
            <div class="tab-pane fade" id="add-category" role="tabpanel" aria-labelledby="add-category-tab">
                <h3 class="text-center">Categorias Cadastradas</h3>
                <div class="mt-4">
                    <?php
                    include 'db_connection.php';

                    try {
                        $sql = "SELECT * FROM categorias ORDER BY nome ASC";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                            echo "<ul class='list-group'>";
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<li class='list-group-item'>" . htmlspecialchars($row["nome"]) . "</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "<p>Nenhuma categoria cadastrada.</p>";
                        }
                    } catch (PDOException $e) {
                        echo "Erro ao acessar o banco de dados: " . $e->getMessage();
                    }

                    $conn = null;
                    ?>
                </div>
                <div class="text-center mt-4">
                    <a href="adicionar_categoria.php" class="btn btn-primary">Editar Categoria</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script>
        function showModal(src) {
            const modalPhoto = document.getElementById('modalPhoto');
            modalPhoto.src = src;
        }

        function startCamera() {
            const video = document.querySelector('#cameraStream');
            const canvas = document.getElementById('photoCanvas');
            const context = canvas.getContext('2d');
            const photoInput = document.getElementById('fotoProduto');
            let localStream;

            navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: "environment",
                        width: {
                            ideal: 1920
                        }, // Define a largura ideal para 1920px
                        height: {
                            ideal: 1080
                        } // Define a altura ideal para 1080px
                    }
                })
                .then(stream => {
                    localStream = stream;
                    video.srcObject = stream;
                    video.play();
                    document.getElementById('cameraStream').style.display = 'block';
                })
                .catch(err => {
                    console.error("Erro ao acessar a câmera: ", err);
                });

            document.querySelector('button[onclick="capturePhoto()"]').addEventListener('click', () => {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(blob => {
                    const uniqueName = 'captured-' + Date.now() + '.jpg';
                    const file = new File([blob], uniqueName, {
                        type: 'image/jpeg'
                    });
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    photoInput.files = dataTransfer.files;

                    localStream.getTracks().forEach(track => track.stop());
                    document.getElementById('cameraStream').style.display = 'none';
                });
            });
        }
    </script>
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