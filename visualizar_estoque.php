<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Sanitização da entrada usando htmlspecialchars
$searchTerm = htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8');
$categoriaId = htmlspecialchars($_GET['categoriaId'] ?? '', ENT_QUOTES, 'UTF-8');

// Prepara a consulta SQL
$sql = "SELECT produtos.*, categorias.nome AS categoria_nome FROM produtos
        LEFT JOIN categorias ON produtos.categoria_id = categorias.id
        WHERE produtos.nome LIKE :searchTerm";

if ($categoriaId) {
    $sql .= " AND produtos.categoria_id = :categoriaId";
}

// Adiciona ordenação pela coluna 'camara'
$sql .= " ORDER BY produtos.camara ASC";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
if ($categoriaId) {
    $stmt->bindValue(':categoriaId', $categoriaId);
}
$stmt->execute();

// Consulta para categorias
$categoriaResult = $conn->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);

// Adiciona o HTML inicial com o link para o CSS
echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Visualizar Estoque</title>";
echo "<link rel='stylesheet' href='css/estoque.css'>"; // Link para o seu CSS
echo "</head>";
echo "<body>";
echo "<div class='container'>";

// Exibe o botão de voltar ao menu
echo "<button class='btn-primary' onclick=\"window.location.href='index.php'\">Voltar ao Menu</button>";

// Exibe o formulário de busca
echo "<form method='GET' action='visualizar_estoque.php'>";
echo "<input type='text' name='search' placeholder='Buscar produto...' value='" . htmlspecialchars($searchTerm) . "'>";
echo "<select name='categoriaId'>";
echo "<option value=''>Todas as Categorias</option>";

foreach ($categoriaResult as $row) {
    $selected = ($row['id'] == $categoriaId) ? ' selected' : '';
    echo "<option value='" . htmlspecialchars($row['id']) . "'" . $selected . ">" . htmlspecialchars($row['nome']) . "</option>";
}

echo "</select>";
echo "<button type='submit'>Buscar</button>";
echo "</form>";

// Exibe as categorias com rolagem horizontal
echo "<div class='categoria-container'>";
foreach ($categoriaResult as $row) {
    echo "<div class='categoria-item'>" . htmlspecialchars($row['nome']) . "</div>";
}
echo "</div>";

// Exibe os resultados da busca
if ($stmt->rowCount() > 0) {
    echo "<table class='table'>";
    echo "<thead><tr>
            <th>Camara</th>
            <th>Bloco</th>
            <th>Posição</th>
            <th>Nível</th>
            <th>Nome</th>
            <th>Quantidade</th>
            <th>Peso Líquido</th>
            <th>Peso Bruto</th>
            <th>ID caixa</th>
            <th>Data de Fabricação</th>
            <th>Data de Validade</th>
            <th>Categoria</th>
            <th>Foto</th>
            <th>Ações</th>
          </tr></thead>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $foto = $row["foto_produto"] ?? 'sem_foto.jpg'; // Valor padrão caso a chave não exista
        $fotoPath = 'uploads/' . htmlspecialchars($foto);

        // Formatação das datas
        $dataFabricacao = DateTime::createFromFormat('Y-m-d', $row["data_fabricacao"]);
        $dataValidade = DateTime::createFromFormat('Y-m-d', $row["data_validade"]);

        $dataFabricacaoFormatada = $dataFabricacao ? $dataFabricacao->format('d/m/Y') : 'N/A';
        $dataValidadeFormatada = $dataValidade ? $dataValidade->format('d/m/Y') : 'N/A';

        // Verifica o status da data de validade
        $linhaClasse = '';
        $statusMensagem = '';
        if ($dataValidade) {
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
        echo "<td>" . $dataValidadeFormatada . " <span class='alerta'>" . htmlspecialchars($statusMensagem) . "</span></td>";
        echo "<td>" . htmlspecialchars($row["categoria_nome"]) . "</td>";

        // Verifica se a foto existe
        if ($foto !== 'sem_foto.jpg' && file_exists($fotoPath)) {
            echo "<td><img src='" . htmlspecialchars($fotoPath) . "' alt='Foto do Produto' width='100'></td>";
        } else {
            echo "<td><img src='uploads/sem_foto.jpg' alt='Foto do Produto' width='100'></td>";
        }

        // Adiciona botões de edição e exclusão
        echo "<td class='btn-container'>
                <button class='btn btn-warning' onclick=\"window.location.href='editar_produto.php?id=" . htmlspecialchars($row['id']) . "'\">Editar</button>
                <button class='btn btn-danger' onclick=\"if(confirm('Tem certeza que deseja excluir este produto?')) window.location.href='excluir_produto.php?id=" . htmlspecialchars($row['id']) . "'\">Excluir</button>
              </td>";

        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Nenhum produto encontrado.</p>";
}

echo "</div>"; // Fechar a div container
echo "</body>";
echo "</html>";

$conn = null;
