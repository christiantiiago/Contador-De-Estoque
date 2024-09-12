<?php
include 'db_connection.php';
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Lógica para adicionar o produto ao banco de dados
// ...

if ($produto_adicionado_com_sucesso) {
    // Registra a movimentação
    $codigo_barras = $_POST['codigo_barras'];
    $quantidade = $_POST['quantidade'];
    $operacao = 'entrada'; // ou outra descrição relevante

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
    // Obtém os dados do formulário
    $camara = $_POST['camara'];
    $bloco = $_POST['bloco'];
    $posicao_bloco = $_POST['posicao_bloco'];
    $nivel = $_POST['nivel'];
    $categoria_id = $_POST['categoria'];
    $nome = $_POST['nomeProduto'];
    $quantidade = $_POST['quantidade'];
    $peso_liquido = $_POST['pesoLiquido'];
    $peso_bruto = $_POST['pesoBruto'];
    $codigo_barras = $_POST['codigoBarras'];
    $data_fabricacao = $_POST['dataFabricacao'];
    $data_validade = $_POST['dataValidade'];

    // Verifica se o campo 'camara' está vazio
    if (empty($camara)) {
        $message = "O campo 'câmara' não pode estar vazio.";
        echo $message;
        exit();
    }

    // Inicializa a variável $foto_produto
    $foto_produto = null;

    // Upload da Foto
    if (isset($_FILES['fotoProduto']) && $_FILES['fotoProduto']['error'] == 0) {
        $originalFileName = $_FILES['fotoProduto']['name'];
        $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

        // Gerar um nome único para o arquivo
        $uniqueFileName = uniqid() . '.' . $fileExtension;
        $target_dir = "uploads/";
        $target_file = $target_dir . $uniqueFileName;
        $uploadOk = 1;

        // Verifica o tipo de arquivo
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array($fileExtension, $allowed_types)) {
            $uploadOk = 0;
            $message = "Desculpe, apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
        }

        // Verifica o tamanho do arquivo
        if ($_FILES["fotoProduto"]["size"] > 5000000) { // 5 MB
            $uploadOk = 0;
            $message = "Desculpe, o arquivo é muito grande.";
        }

        // Verifica se $uploadOk está definido como 0 por um erro
        if ($uploadOk == 0) {
            $message = "Desculpe, seu arquivo não foi carregado.";
        } else {
            // Opcional: redimensionar e otimizar a imagem
            if (move_uploaded_file($_FILES["fotoProduto"]["tmp_name"], $target_file)) {
                $foto_produto = $uniqueFileName;
            } else {
                $message = "Desculpe, ocorreu um erro ao fazer o upload do arquivo.";
            }
        }
    } else {
        $message = "Nenhum arquivo foi enviado ou houve um erro no upload.";
    }

    // Se a foto foi carregada com sucesso, insere o produto no banco de dados
    if ($foto_produto !== null) {
        try {
            $sql = "INSERT INTO produtos (camara, bloco, posicao_bloco, nivel, categoria_id, nome, quantidade, peso_liquido, peso_bruto, codigo_barras, data_fabricacao, data_validade, foto_produto) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$camara, $bloco, $posicao_bloco, $nivel, $categoria_id, $nome, $quantidade, $peso_liquido, $peso_bruto, $codigo_barras, $data_fabricacao, $data_validade, $foto_produto]);

            $message = "Produto adicionado com sucesso!";
            $redirect = true;
        } catch (PDOException $e) {
            $message = "Erro: " . $e->getMessage();
        }
    } else {
        $message = "Produto não adicionado. Verifique os erros e tente novamente.";
    }

    // Fecha a conexão
    $conn = null;

    // Redireciona se o produto foi adicionado com sucesso
    if (isset($redirect) && $redirect) {
        header("Location: index.php?message=" . urlencode($message));
        exit();
    } else {
        echo $message;
    }
}
?>