
Copiar código
<?php
include 'db_connection.php';
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Lógica para excluir o produto do banco de dados
// ...

if ($produto_excluido_com_sucesso) {
    // Registra a movimentação
    $codigo_barras = $_POST['codigo_barras'];
    $quantidade = $_POST['quantidade']; // ou valor apropriado
    $operacao = 'exclusao'; // ou outra descrição relevante

    $sql = "INSERT INTO historico_movimentacoes (data, codigo_barras, quantidade, operacao, usuario_id)
            VALUES (NOW(), :codigo_barras, :quantidade, :operacao, :usuario_id)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':codigo_barras', $codigo_barras);
    $stmt->bindParam(':quantidade', $quantidade);
    $stmt->bindParam(':operacao', $operacao);
    $stmt->bindParam(':usuario_id', $_SESSION['user_id']);
    $stmt->execute();
}


$id = htmlspecialchars($_GET['id'] ?? '', ENT_QUOTES, 'UTF-8');

$sql = "DELETE FROM produtos WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':id', $id);

if ($stmt->execute()) {
    header('Location: visualizar_estoque.php?message=Produto excluído com sucesso');
    exit;
} else {
    echo "Erro ao excluir o produto.";
}

$conn = null;
?>
