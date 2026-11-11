
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../../app/config/database.php';

$mensagem = '';
$tipoMensagem = '';
$nome = '';
$categoriaId = '';
$descricao = '';
$preco = '';
$status = 'ativo';

$stmt = $pdo->query("
    SELECT id, nome
    FROM categorias
    WHERE status = 'ativo'
    ORDER BY nome ASC
");
$categorias = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $categoriaId = (int) ($_POST['categoria'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $preco = str_replace(',', '.', trim($_POST['preco'] ?? ''));
    $status = $_POST['status'] ?? 'ativo';

    $arquivosSalvos = [];

    try {

        if ($nome === '' || $categoriaId <= 0 || $descricao === '') {
            throw new Exception('Preencha todos os campos obrigatórios.');
        }

        if (!is_numeric($preco) || (float) $preco < 0) {
            throw new Exception('Informe um preço válido.');
        }

        if (!in_array($status, ['ativo', 'inativo'], true)) {
            throw new Exception('Status inválido.');
        }

        // Confirma se a categoria existe e está ativa
        $stmtCategoria = $pdo->prepare("
            SELECT id FROM categorias
            WHERE id = :id AND status = 'ativo'
            LIMIT 1
        ");
        $stmtCategoria->execute(['id' => $categoriaId]);

        if (!$stmtCategoria->fetch()) {
            throw new Exception('Selecione uma categoria válida.');
        }

        // Diretório físico das imagens
        $diretorio = __DIR__ . '/../../assets/uploads/produtos/';

        if (!is_dir($diretorio)) {
            if (!mkdir($diretorio, 0755, true)) {
                throw new Exception('Não foi possível criar a pasta de imagens.');
            }
        }

        $salvarImagem = function ($arquivo) use ($diretorio, &$arquivosSalvos) {

            if (!isset($arquivo['error']) || $arquivo['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Ocorreu um erro no envio de uma imagem.');
            }

            if ($arquivo['size'] > 5 * 1024 * 1024) {
                throw new Exception('Cada imagem deve ter no máximo 5 MB.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($arquivo['tmp_name']);

            $tiposPermitidos = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($tiposPermitidos[$mime])) {
                throw new Exception('Formato inválido. Use JPG, PNG ou WEBP.');
            }

            $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $tiposPermitidos[$mime];
            $destino = $diretorio . $nomeArquivo;

            if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
                throw new Exception('Não foi possível salvar uma imagem.');
            }

            $arquivosSalvos[] = $destino;

            return 'assets/uploads/produtos/' . $nomeArquivo;
        };

        if (
            !isset($_FILES['imagem_principal']) ||
            $_FILES['imagem_principal']['error'] === UPLOAD_ERR_NO_FILE
        ) {
            throw new Exception('Selecione uma imagem principal.');
        }

        $imagemPrincipal = $salvarImagem($_FILES['imagem_principal']);

        $imagensAdicionais = [];

        if (
            isset($_FILES['imagens_adicionais']) &&
            is_array($_FILES['imagens_adicionais']['name'])
        ) {
            foreach ($_FILES['imagens_adicionais']['name'] as $indice => $nomeOriginal) {

                if (
                    $_FILES['imagens_adicionais']['error'][$indice]
                    === UPLOAD_ERR_NO_FILE
                ) {
                    continue;
                }

                $arquivo = [
                    'name' => $_FILES['imagens_adicionais']['name'][$indice],
                    'type' => $_FILES['imagens_adicionais']['type'][$indice],
                    'tmp_name' => $_FILES['imagens_adicionais']['tmp_name'][$indice],
                    'error' => $_FILES['imagens_adicionais']['error'][$indice],
                    'size' => $_FILES['imagens_adicionais']['size'][$indice]
                ];

                $imagensAdicionais[] = $salvarImagem($arquivo);
            }
        }

        $pdo->beginTransaction();

        $stmtProduto = $pdo->prepare("
            INSERT INTO produtos
                (categoria_id, nome, descricao, preco, status)
            VALUES
                (:categoria_id, :nome, :descricao, :preco, :status)
        ");

        $stmtProduto->execute([
            'categoria_id' => $categoriaId,
            'nome' => $nome,
            'descricao' => $descricao,
            'preco' => $preco,
            'status' => $status
        ]);

        $produtoId = $pdo->lastInsertId();

        $stmtImagem = $pdo->prepare("
            INSERT INTO imagens_produtos
                (produto_id, caminho, imagem_principal, ordem)
            VALUES
                (:produto_id, :caminho, :principal, :ordem)
        ");

        $stmtImagem->execute([
            'produto_id' => $produtoId,
            'caminho' => $imagemPrincipal,
            'principal' => 1,
            'ordem' => 0
        ]);

        foreach ($imagensAdicionais as $indice => $caminho) {
            $stmtImagem->execute([
                'produto_id' => $produtoId,
                'caminho' => $caminho,
                'principal' => 0,
                'ordem' => $indice + 1
            ]);
        }

        $pdo->commit();

        $mensagem = 'Produto cadastrado com sucesso!';
        $tipoMensagem = 'sucesso';

        $nome = '';
        $categoriaId = '';
        $descricao = '';
        $preco = '';
        $status = 'ativo';

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        foreach ($arquivosSalvos as $arquivo) {
            if (file_exists($arquivo)) {
                unlink($arquivo);
            }
        }

        $mensagem = $e instanceof PDOException
            ? 'Não foi possível cadastrar o produto. Verifique os dados e tente novamente.'
            : $e->getMessage();

        $tipoMensagem = 'erro';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Cadastro de produtos do Mimo de Sofia.">

    <title>Cadastrar Produto | Mimo de Sofia</title>

    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

    <header class="admin-header">

        <div class="admin-header-container">

            <a href="../dashboard.php" class="admin-logo">
                <img src="../../assets/images/logo.png" alt="Mimo de Sofia">
            </a>

            <h1>Cadastrar Produto</h1>

            <a href="listar.php" class="admin-back">
                Voltar para produtos
            </a>

        </div>

    </header>

    <main class="admin-main">

        <section class="admin-intro">

            <h2>Novo produto</h2>

            <p>
                Preencha as informações abaixo para adicionar
                um produto ao catálogo do Mimo de Sofia.
            </p>

        </section>

        <section class="admin-form-section">

            <?php if ($mensagem !== ''): ?>
                <p class="mensagem-<?php echo htmlspecialchars($tipoMensagem); ?>">
                    <?php echo htmlspecialchars($mensagem); ?>
                </p>
            <?php endif; ?>

            <form
                class="admin-form"
                action="cadastrar.php"
                method="POST"
                enctype="multipart/form-data">

                <div class="form-group">

                    <label for="nome">Nome do produto</label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Digite o nome do produto"
                        value="<?php echo htmlspecialchars($nome); ?>"
                        maxlength="150"
                        required>

                </div>

                <div class="form-group">

                    <label for="categoria">Categoria</label>

                    <select id="categoria" name="categoria" required>

                        <option value="">Selecione uma categoria</option>

                        <?php foreach ($categorias as $categoria): ?>
                            <option
                                value="<?php echo (int) $categoria['id']; ?>"
                                <?php echo ((string) $categoriaId === (string) $categoria['id']) ? 'selected' : ''; ?>>

                                <?php echo htmlspecialchars($categoria['nome']); ?>

                            </option>
                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label for="descricao">Descrição do produto</label>

                    <textarea
                        id="descricao"
                        name="descricao"
                        rows="6"
                        placeholder="Descreva as características do produto"
                        required><?php echo htmlspecialchars($descricao); ?></textarea>

                </div>

                <div class="form-group">

                    <label for="preco">Preço</label>

                    <input
                        type="number"
                        id="preco"
                        name="preco"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        value="<?php echo htmlspecialchars($preco); ?>"
                        required>

                </div>

                <div class="form-group">

                    <label for="imagem-principal">Imagem principal</label>

                    <input
                        type="file"
                        id="imagem-principal"
                        name="imagem_principal"
                        accept=".jpg,.jpeg,.png,.webp"
                        required>

                    <small>Formatos aceitos: JPG, PNG e WEBP. Máximo de 5 MB.</small>

                </div>

                <div class="form-group">

                    <label for="imagens-adicionais">Imagens adicionais</label>

                    <input
                        type="file"
                        id="imagens-adicionais"
                        name="imagens_adicionais[]"
                        accept=".jpg,.jpeg,.png,.webp"
                        multiple>

                    <small>
                        Você pode selecionar mais de uma imagem. Máximo de 5 MB por imagem.
                    </small>

                </div>

                <div class="form-group">

                    <label for="status">Status do produto</label>

                    <select id="status" name="status" required>

                        <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>
                            Ativo
                        </option>

                        <option value="inativo" <?php echo $status === 'inativo' ? 'selected' : ''; ?>>
                            Inativo
                        </option>

                    </select>

                </div>

                <div class="form-actions">

                    <button type="submit">
                        Cadastrar produto
                    </button>

                    <a href="listar.php">
                        Cancelar
                    </a>

                </div>

            </form>

        </section>

    </main>

    <footer class="admin-footer">

        <p>
            Mimo de Sofia — Painel Administrativo
        </p>

    </footer>

</body>
</html>

