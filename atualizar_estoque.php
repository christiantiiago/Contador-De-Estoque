<?php
include 'db_connection.php';
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Lógica para atualizar o estoque
// ...

if ($estoque_atualizado_com_sucesso) {
    // Registra a movimentação
    $codigo_barras = $_POST['codigo_barras'];
    $quantidade = $_POST['quantidade'];
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

// Lógica para atualizar o estoque
// ...

if ($estoque_atualizado_com_sucesso) {
    // Registra a movimentação
    $codigo_barras = $_POST['codigo_barras'];
    $quantidade = $_POST['quantidade'];
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantidades = $_POST['quantidade'];

    // Valida os dados
    if (empty($quantidades) || !is_array($quantidades)) {
        echo "Dados inválidos.";
        exit();
    }

    try {
        // Atualiza a quantidade dos produtos
        $sql = "UPDATE produtos SET quantidade = quantidade + :quantidade WHERE id = :produto_id";
        $stmt = $pdo->prepare($sql);

        foreach ($quantidades as $produto_id => $quantidade) {
            $quantidade = (int) $quantidade;
            if ($quantidade > 0) {
                $stmt->bindParam(':quantidade', $quantidade, PDO::PARAM_INT);
                $stmt->bindParam(':produto_id', $produto_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        $message = "Produtos reabastecidos com sucesso!";
    } catch (PDOException $e) {
        $message = "Erro: " . $e->getMessage();
    }

    echo $message;
}
?>
