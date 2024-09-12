<?php
header('Content-Type: application/json');
include 'db_connection.php';

$codigo_barras = $_GET['codigo_barras'] ?? '';

if ($codigo_barras) {
    try {
        $sql = "SELECT p.*, c.nome AS categoria_nome FROM produtos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.codigo_barras = :codigo_barras";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':codigo_barras', $codigo_barras);
        $stmt->execute();
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($produto) {
            echo json_encode([
                'success' => true,
                'quantidade' => $produto['quantidade'],
                'camara' => $produto['camara'],
                'bloco' => $produto['bloco'],
                'posicao_bloco' => $produto['posicao_bloco'],
                'nivel' => $produto['nivel'],
                'categoria_id' => $produto['categoria_id'],
                'data_fabricacao' => $produto['data_fabricacao'],
                'data_validade' => $produto['data_validade']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Produto não encontrado.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar produto: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Código de barras não fornecido.']);
}

$conn = null;
?>
