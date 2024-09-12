<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Função para adicionar uma nova categoria
function adicionarCategoria($conn, $nomeCategoria)
{
    try {
        $sql = "SELECT id FROM categorias WHERE nome = :nomeCategoria";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nomeCategoria', $nomeCategoria);
        $stmt->execute();
        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($categoria) {
            echo "Categoria já existe!";
        } else {
            $sql = "INSERT INTO categorias (nome) VALUES (:nomeCategoria)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nomeCategoria', $nomeCategoria);
            $stmt->execute();
            echo "Categoria adicionada com sucesso!";
        }
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

// Função para excluir categoria e produtos associados
function excluirCategoria($conn, $nomeCategoria)
{
    try {
        $sql = "SELECT id FROM categorias WHERE nome = :nomeCategoria";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nomeCategoria', $nomeCategoria);
        $stmt->execute();
        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($categoria) {
            $categoriaId = $categoria['id'];

            $sql = "DELETE FROM produtos WHERE categoria_id = :categoriaId";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':categoriaId', $categoriaId);
            $stmt->execute();

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
}

// Processar o envio do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['adicionar'])) {
        $nomeCategoria = filter_input(INPUT_POST, 'nomeCategoria', FILTER_SANITIZE_STRING);
        if (!empty($nomeCategoria)) {
            adicionarCategoria($conn, $nomeCategoria);
        } else {
            echo "O nome da categoria não pode estar vazio.";
        }
    } elseif (isset($_POST['excluir'])) {
        $nomeCategoria = filter_input(INPUT_POST, 'nomeCategoria', FILTER_SANITIZE_STRING);
        if (!empty($nomeCategoria)) {
            excluirCategoria($conn, $nomeCategoria);
        } else {
            echo "O nome da categoria não pode estar vazio.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Categorias</title>
    <link rel="stylesheet" href="css/categoria.css">
</head>

<body>
    <div class="container">
        <a href='index.php' class='btn btn-primary'>Voltar ao Menu</a>
        <h1>Gerenciamento de Categorias</h1>
        <!-- Formulário de Adição de Categoria -->
        <form action="adicionar_categoria.php" method="POST">
            <div class="form-group">
                <label for="nomeCategoria">Nome da Categoria:</label>
                <input type="text" id="nomeCategoria" name="nomeCategoria" class="form-control" required>
            </div>
            <button type="submit" name="adicionar" class="btn btn-primary">Adicionar Categoria</button>
        </form>

        <h2>Categorias Existentes</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $sql = "SELECT nome FROM categorias";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($categorias as $categoria) {
                        echo "<tr>
                            <td>{$categoria['nome']}</td>
                            <td class='actions'>
                                <form action='adicionar_categoria.php' method='POST'>
                                    <input type='hidden' name='nomeCategoria' value='{$categoria['nome']}'>
                                    <button type='submit' name='excluir' class='btn btn-danger'>Excluir</button>
                                </form>
                            </td>
                        </tr>";
                    }
                } catch (PDOException $e) {
                    echo "Erro: " . $e->getMessage();
                }
                ?>
            </tbody>
        </table>
    </div>

    <footer>
        <div class="container">
            <p>© <span>Copyright</span> <strong class="px-1 sitename">Christian Group</strong> <span>Todos os direitos reservados</span></p>
            <div class="credits">
                Designed by <a href="https://christiantiago.com/">Christian Tiago</a>
            </div>
        </div>
    </footer>

</body>

</html>