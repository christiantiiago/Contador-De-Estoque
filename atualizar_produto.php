<?php
include 'db_connection.php';
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Lógica para atualizar o produto no banco de dados
// ...

if ($produto_atualizado_com_sucesso) {
    // Registra a movimentação
    $codigo_barras = $_POST['codigo_barras'];
    $quantidade = $_POST['quantidade']; // ou valor apropriado
    $operacao = 'atualizacao'; // ou outra descrição relevante

    $sql = "INSERT INTO historico_movimentacoes (data, codigo_barras, quantidade, operacao, usuario_id)
            VALUES (NOW(), :codigo_barras, :quantidade, :operacao, :usuario_id)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':codigo_barras', $codigo_barras);
    $stmt->bindParam(':quantidade', $quantidade);
    $stmt->bindParam(':operacao', $operacao);
    $stmt->bindParam(':usuario_id', $_SESSION['user_id']);
    $stmt->execute();
}
?>


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sanitização dos dados recebidos
$id = htmlspecialchars($_POST['id'] ?? '', ENT_QUOTES, 'UTF-8');
$camara = htmlspecialchars($_POST['camara'] ?? '', ENT_QUOTES, 'UTF-8');
$bloco = htmlspecialchars($_POST['bloco'] ?? '', ENT_QUOTES, 'UTF-8');
$posicao_bloco = htmlspecialchars($_POST['posicao_bloco'] ?? '', ENT_QUOTES, 'UTF-8');
$nivel = htmlspecialchars($_POST['nivel'] ?? '', ENT_QUOTES, 'UTF-8');
$nome = htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8');
$quantidade = htmlspecialchars($_POST['quantidade'] ?? '', ENT_QUOTES, 'UTF-8');
$peso_liquido = htmlspecialchars($_POST['peso_liquido'] ?? '', ENT_QUOTES, 'UTF-8');
$peso_bruto = htmlspecialchars($_POST['peso_bruto'] ?? '', ENT_QUOTES, 'UTF-8');
$codigo_barras = htmlspecialchars($_POST['codigo_barras'] ?? '', ENT_QUOTES, 'UTF-8');
$data_fabricacao = htmlspecialchars($_POST['data_fabricacao'] ?? '', ENT_QUOTES, 'UTF-8');
$data_validade = htmlspecialchars($_POST['data_validade'] ?? '', ENT_QUOTES, 'UTF-8');
$categoria_id = htmlspecialchars($_POST['categoria_id'] ?? '', ENT_QUOTES, 'UTF-8');

// Atualiza o produto no banco de dados
$sql = "UPDATE produtos SET
            camara = :camara,
            bloco = :bloco,
            posicao_bloco = :posicao_bloco,
            nivel = :nivel,
            nome = :nome,
            quantidade = :quantidade,
            peso_liquido = :peso_liquido,
            peso_bruto = :peso_bruto,
            codigo_barras = :codigo_barras,
            data_fabricacao = :data_fabricacao,
            data_validade = :data_validade,
            categoria_id = :categoria_id
        WHERE id = :id";

$stmt = $conn->prepare($sql);

$stmt->bindValue(':camara', $camara);
$stmt->bindValue(':bloco', $bloco);
$stmt->bindValue(':posicao_bloco', $posicao_bloco);
$stmt->bindValue(':nivel', $nivel);
$stmt->bindValue(':nome', $nome);
$stmt->bindValue(':quantidade', $quantidade);
$stmt->bindValue(':peso_liquido', $peso_liquido);
$stmt->bindValue(':peso_bruto', $peso_bruto);
$stmt->bindValue(':codigo_barras', $codigo_barras);
$stmt->bindValue(':data_fabricacao', $data_fabricacao);
$stmt->bindValue(':data_validade', $data_validade);
$stmt->bindValue(':categoria_id', $categoria_id);
$stmt->bindValue(':id', $id);

try {
    $stmt->execute();
    // Redireciona para a página de visualização de estoque após a atualização
    header('Location: visualizar_estoque.php');
    exit();
} catch (PDOException $e) {
    echo "Erro ao atualizar produto: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

$conn = null;
