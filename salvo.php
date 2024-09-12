<?php
include 'db_connection.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Estoque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilo do elemento que exibe o stream da câmera do código de barras */
        #barcodeScannerContainer {
            position: relative;
            width: 100%;
            height: 300px;
            /* Ajuste conforme necessário */
            background-color: #000;
            margin-bottom: 2rem;
        }

        #barcodeScanner {
            width: 100%;
            height: 100%;
        }

        .scanner-line {
            position: absolute;
            width: 100%;
            height: 4px;
            background-color: red;
            animation: scanner-animation 2s infinite;
            top: 0;
            left: 0;
        }

        @keyframes scanner-animation {
            0% {
                top: 0;
            }

            50% {
                top: 50%;
            }

            100% {
                top: 100%;
            }
        }

        /* Estilo do elemento que exibe o stream da câmera para a foto do produto */
        #productCameraContainer {
            width: 100%;
            height: 300px;
            /* Ajuste conforme necessário */
            background-color: #000;
            margin-bottom: 2rem;
            display: none;
        }

        #productCamera {
            width: 100%;
            height: 100%;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center">Estoque Frimesa</h1>
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="add-product-tab" data-bs-toggle="tab" data-bs-target="#add-product" type="button" role="tab" aria-controls="add-product" aria-selected="true">Adicionar Produto</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="view-stock-tab" data-bs-toggle="tab" data-bs-target="#view-stock" type="button" role="tab" aria-controls="view-stock" aria-selected="false">Visualizar Estoque</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="add-category-tab" data-bs-toggle="tab" data-bs-target="#add-category" type="button" role="tab" aria-controls="add-category" aria-selected="false">Adicionar Categoria</button>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <!-- Adicionar Produto -->
            <div class="tab-pane fade show active" id="add-product" role="tabpanel" aria-labelledby="add-product-tab">
                <form action="adicionar_produto.php" method="POST" enctype="multipart/form-data" class="mt-4">
                    <div class="row mb-3">
                        <div class="col">
                            <label for="bloco" class="form-label">Câmara</label>
                            <input type="text" class="form-control" id="bloco" name="bloco" required>
                        </div>
                        <div class="col">
                            <label for="posicaoBloco" class="form-label">Bloco</label>
                            <input type="text" class="form-control" id="posicaoBloco" name="posicaoBloco" required>
                        </div>
                        <div class="col">
                            <label for="posicaoNivel" class="form-label">Posição</label>
                            <input type="text" class="form-control" id="posicaoNivel" name="posicaoNivel" required>
                        </div>
                        <div class="col">
                            <label for="nivel" class="form-label">Nível</label>
                            <input type="text" class="form-control" id="nivel" name="nivel" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="categoria" class="form-label">Categoria</label>
                            <select id="categoria" name="categoria" class="form-control" required>
                                <option value="">Selecione uma Categoria</option>
                                <?php
                                include 'db_connection.php';

                                try {
                                    $sql = "SELECT id, nome FROM categorias";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute();
                                    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($categorias as $categoria) {
                                        echo "<option value='{$categoria['id']}'>{$categoria['nome']}</option>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<option value=''>Erro ao carregar categorias</option>";
                                }

                                $conn = null;
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="nomeProduto" class="form-label">Nome do Produto</label>
                            <input type="text" class="form-control" id="nomeProduto" name="nomeProduto" required>
                        </div>
                        <div class="col">
                            <label for="quantidade" class="form-label">Quantidade (Caixas)</label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="pesoLiquido" class="form-label">Peso Líquido (kg)</label>
                            <input type="number" class="form-control" id="pesoLiquido" name="pesoLiquido" step="0.01" required>
                        </div>
                        <div class="col">
                            <label for="pesoBruto" class="form-label">Peso Bruto (kg)</label>
                            <input type="number" class="form-control" id="pesoBruto" name="pesoBruto" step="0.01" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="codigoBarras" class="form-label">Código de Barras</label>
                            <input type="text" class="form-control" id="codigoBarras" name="codigoBarras" required>

                            <div id="barcodeScannerContainer">
                                <video id="barcodeScanner"></video>
                                <div class="scanner-line"></div> <!-- Linha de scanner animada -->
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="startScanner()">Ler Código de Barras</button>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <label for="dataFabricacao" class="form-label">Data de Fabricação</label>
                            <input type="date" class="form-control" id="dataFabricacao" name="dataFabricacao" required>
                        </div>
                        <div class="col">
                            <label for="dataValidade" class="form-label">Data de Validade</label>
                            <input type="date" class="form-control" id="dataValidade" name="dataValidade" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="fotoProduto" class="form-label">Foto do Produto</label>
                            <input type="file" class="form-control" id="fotoProduto" name="fotoProduto" accept="image/*" capture="camera" required>
                            <button type="button" class="btn btn-secondary mt-2" onclick="startProductCamera()">Abrir Câmera</button>
                            <button type="button" class="btn btn-secondary mt-2" onclick="captureProductPhoto()">Capturar Foto</button>
                            <div id="productCameraContainer">
                                <video id="productCamera" autoplay></video>
                                <canvas id="photoCanvas" style="display:none;"></canvas>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Adicionar Produto</button>
                </form>
            </div>
            <!-- Visualizar Estoque -->
            <div class="tab-pane fade" id="view-stock" role="tabpanel" aria-labelledby="view-stock-tab">
                <div class="mt-4">
                    <a href="visualizar_estoque.php" class="btn btn-success">Visualizar Estoque</a>
                </div>
            </div>
            <!-- Adicionar Categoria -->
            <div class="tab-pane fade" id="add-category" role="tabpanel" aria-labelledby="add-category-tab">
                <form action="adicionar_categoria.php" method="POST" class="mt-4">
                    <div class="mb-3">
                        <label for="categoriaNome" class="form-label">Nome da Categoria</label>
                        <input type="text" class="form-control" id="categoriaNome" name="categoriaNome" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Adicionar Categoria</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
    <script>
        function startScanner() {
            const video = document.querySelector('#barcodeScanner');
            const barcodeInput = document.querySelector('#codigoBarras');

            document.querySelector('#barcodeScannerContainer').style.display = 'block';

            // Configuração do QuaggaJS
            Quagga.init({
                inputStream: {
                    type: 'LiveStream',
                    target: video, // O vídeo da câmera será exibido aqui
                    constraints: {
                        facingMode: 'environment'
                    }
                },
                decoder: {
                    readers: ['code_128_reader'] // Adapte para os tipos de código de barras que você deseja ler
                },
                locate: true
            }, function(err) {
                if (err) {
                    console.log(err);
                    return;
                }
                Quagga.start();
            });

            // Processamento do código de barras detectado
            Quagga.onDetected(function(data) {
                if (data.codeResult) {
                    barcodeInput.value = data.codeResult.code; // Define o valor do campo de texto com o código detectado
                    Quagga.stop(); // Parar o scanner após detectar o código
                    document.querySelector('#barcodeScannerContainer').style.display = 'none';
                }
            });

            // Parar o scanner e a câmera ao sair da página
            window.addEventListener('beforeunload', function() {
                Quagga.stop();
                video.srcObject.getTracks().forEach(track => track.stop());
                document.querySelector('#barcodeScannerContainer').style.display = 'none';
            });
        }

        function startProductCamera() {
            const video = document.querySelector('#productCamera');
            navigator.mediaDevices.getUserMedia({
                    video: true
                })
                .then(stream => {
                    video.srcObject = stream;
                    video.play();
                    document.querySelector('#productCameraContainer').style.display = 'block';
                })
                .catch(err => console.error('Erro ao acessar a câmera: ', err));
        }

        function captureProductPhoto() {
            const video = document.querySelector('#productCamera');
            const canvas = document.querySelector('#photoCanvas');
            const context = canvas.getContext('2d');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Converte a imagem para base64 e pode ser usada conforme necessário
            const dataURL = canvas.toDataURL('image/png');
            console.log(dataURL); // Ou envie para o servidor
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>