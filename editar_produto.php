<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = htmlspecialchars($_GET['id'] ?? '', ENT_QUOTES, 'UTF-8');

// Recupera as informações do produto
$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo "Produto não encontrado.";
    exit;
}

// Exibe o formulário de edição
echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Editar Produto</title>";
echo "<link rel='stylesheet' href='css/editar.css'>"; // Link para o CSS atualizado
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>Editar Produto</h1>";

echo "<form action='atualizar_produto.php' method='POST'>";
echo "<input type='hidden' name='id' value='" . htmlspecialchars($produto['id']) . "'>";

echo "<div class='mb-3'>";
echo "<label for='camara' class='form-label'>Camara</label>";
echo "<input type='text' class='form-control' id='camara' name='camara' value='" . htmlspecialchars($produto['camara'] ?? '') . "' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label for='bloco' class='form-label'>Bloco</label>";
echo "<input type='text' class='form-control' id='bloco' name='bloco' value='" . htmlspecialchars($produto['bloco'] ?? '') . "' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label for='posicao' class='form-label'>Posição</label>";
echo "<input type='text' class='form-control' id='posicao' name='posicao' value='" . htmlspecialchars($produto['posicao'] ?? '') . "' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label for='nivel' class='form-label'>Nível</label>";
echo "<input type='text' class='form-control' id='nivel' name='nivel' value='" . htmlspecialchars($produto['nivel'] ?? '') . "' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label for='nome' class='form-label'>Nome</label>";
echo "<input type='text' class='form-control' id='nome' name='nome' value='" . htmlspecialchars($produto['nome'] ?? '') . "' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label for='quantidade' class='form-label'>Quantidade</label>";
echo "<input type='number' class='form-control' id='quantidade' name='quantidade' value='" . htmlspecialchars($produto['quantidade'] ?? '') . "' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label for='peso_liquido' class='form-label'>Peso Líquido</label>";
echo "<input type='number' step='0.01' class='form-control' id='peso_liquido' name='peso_liquido' value='" . htmlspecialchars($produto['peso_liquido'] ?? '') . "' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label for='peso_bruto' class='form-label'>Peso Bruto</label>";
echo "<input type='number' step='0.01' class='form-control' id='peso_bruto' name='peso_bruto' value='" . htmlspecialchars($produto['peso_bruto'] ?? '') . "' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label for='codigo_barras' class='form-label'>Código de Barras</label>";
echo "<input type='text' class='form-control' id='codigo_barras' name='codigo_barras' value='" . htmlspecialchars($produto['codigo_barras'] ?? '') . "' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label for='data_fabricacao' class='form-label'>Data de Fabricação</label>";
echo "<input type='date' class='form-control' id='data_fabricacao' name='data_fabricacao' value='" . htmlspecialchars($produto['data_fabricacao'] ?? '') . "' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label for='data_validade' class='form-label'>Data de Validade</label>";
echo "<input type='date' class='form-control' id='data_validade' name='data_validade' value='" . htmlspecialchars($produto['data_validade'] ?? '') . "' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label for='categoria' class='form-label'>Categoria</label>";
echo "<select class='form-control' id='categoria' name='categoria_id' required>";

$stmt = $conn->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);

foreach ($stmt as $row) {
    $selected = ($row['id'] == $produto['categoria_id']) ? ' selected' : '';
    echo "<option value='" . $row['id'] . "'" . $selected . ">" . htmlspecialchars($row['nome']) . "</option>";
}

echo "</select>";
echo "</div>";

echo "<button type='submit' class='btn btn-primary'>Atualizar Produto</button>";
echo "</form>";

echo "</div>"; // Fechar a div container
echo "</body>";
echo "</html>";

$conn = null;
