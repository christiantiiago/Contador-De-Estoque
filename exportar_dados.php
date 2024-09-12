<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Exportar para Excel
if (isset($_POST['exportar_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="dados_estoque.xls"');

    // Cabeçalhos das colunas
    $cabecalhos = [
        'Nome',
        'Código Barras',
        'Quantidade',
        'Câmara',
        'Bloco',
        'Posição Bloco',
        'Nível',
        'Categoria',
        'Data Fabricação',
        'Data Validade'
    ];

    // Consulta os dados
    $sql = "SELECT nome, codigo_barras, quantidade, camara, bloco, posicao_bloco, nivel, categoria_id, data_fabricacao, data_validade FROM produtos";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Imprime cabeçalhos
    echo implode("\t", $cabecalhos) . "\n";

    // Imprime os dados
    foreach ($produtos as $produto) {
        $linha = [
            $produto['nome'],
            $produto['codigo_barras'],
            $produto['quantidade'],
            $produto['camara'],
            $produto['bloco'],
            $produto['posicao_bloco'],
            $produto['nivel'],
            $produto['categoria_id'],
            $produto['data_fabricacao'],
            $produto['data_validade']
        ];
        echo implode("\t", $linha) . "\n";
    }

    exit();
}
?>