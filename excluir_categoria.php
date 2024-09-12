<?php
include 'db_connection.php';
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Lógica para excluir a categoria do banco de dados
// ...

if ($categoria_excluida_com_sucesso) {
    // Registra a movimentação
    $sql = "INSERT INTO historico_movimentacoes (data, codigo_barras, quantidade, operacao, usuario_id)
            VALUES (NOW(), 'N/A', 0, 'excluir_categoria', :usuario_id)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':usuario_id', $_SESSION['user_id']);
    $stmt->execute();
}
?>

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize the input
    $nomeCategoria = filter_input(INPUT_POST, 'nomeCategoria', FILTER_SANITIZE_STRING);

    if (!empty($nomeCategoria)) {
        try {
            // Find the category ID
            $sql = "SELECT id FROM categorias WHERE nome = :nomeCategoria";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nomeCategoria', $nomeCategoria);
            $stmt->execute();
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($categoria) {
                $categoriaId = $categoria['id'];

                // Remove or update associated products
                // Option 1: Update products (set category_id to NULL)
                // $sql = "UPDATE produtos SET categoria_id = NULL WHERE categoria_id = :categoriaId";
                // $stmt = $conn->prepare($sql);
                // $stmt->bindParam(':categoriaId', $categoriaId);
                // $stmt->execute();

                // Option 2: Delete associated products
                $sql = "DELETE FROM produtos WHERE categoria_id = :categoriaId";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':categoriaId', $categoriaId);
                $stmt->execute();

                // Delete the category
                $sql = "DELETE FROM categorias WHERE id = :categoriaId";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':categoriaId', $categoriaId);
                $stmt->execute();

                echo "Categoria excluída com sucesso!";
            } else {
                echo "Categoria não encontrada.";
            }
        } catch (PDOException $e) {
            echo "Erro: " . $e->getMessage();
        }
    } else {
        echo "O nome da categoria não pode estar vazio.";
    }
}

$conn = null;
?>
