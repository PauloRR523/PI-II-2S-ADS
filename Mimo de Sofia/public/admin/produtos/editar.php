
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../../app/config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: listar.php');
    exit;
}

$mensagem = '';
$tipoMensagem = '';
$arquivosSalvos = [];

$stmt = $pdo->prepare("
    SELECT *
    FROM produtos
    WHERE id = :id
    LIMIT 1
");
$stmt->execute(['id' => $id]);
$produto = $stmt->fetch();

if (!$produto) {
    header('Location: listar.php');
    exit;
}

$stmt = $pdo->query("
    SELECT id, nome, status
    FROM categorias
    ORDER BY nome ASC
");
$categorias = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT *
    FROM imagens_produtos
    WHERE produto_id = :id
    ORDER BY imagem_principal DESC, ordem ASC, id ASC
");
$stmt->execute(['id' => $id]);
$imagens = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $categoriaId = (int) ($_POST['categoria'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $preco = str_replace(',', '.', trim($_POST['preco'] ?? ''));
    $status = $_POST['status'] ?? '';

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

        $stmtCategoria = $pdo->prepare("
            SELECT id
            FROM categorias
            WHERE id = :id
            LIMIT 1
        ");
        $stmtCategoria->execute(['id' => $categoriaId]);

        if (!$stmtCategoria->fetch()) {
            throw new Exception('Categoria inválida.');
        }

        $diretorio = __DIR__ . '/../../assets/uploads/produtos/';

        if (!is_dir($diretorio)) {
            throw new Exception('A pasta de imagens não foi encontrada.');
        }

        $salvarImagem = function ($arquivo) use ($diretorio, &$arquivosSalvos) {

            if ($arquivo['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Erro no envio de uma imagem.');
            }

            if ($arquivo['size'] > 5 * 1024 * 1024) {
                throw new Exception('Cada imagem deve ter no máximo 5 MB.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($arquivo['tmp_name']);

            $tiposPermitidos = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
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

        $novaPrincipal = null;

        if (
            isset($_FILES['imagem_principal']) &&
            $_FILES['imagem_principal']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $novaPrincipal = $salvarImagem($_FILES['imagem_principal']);
        }

        $novasImagens = [];

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

                $novasImagens[] = $salvarImagem($arquivo);
            }
        }

        $pdo->beginTransaction();

        $stmtAtualizar = $pdo->prepare("
            UPDATE produtos
            SET
                categoria_id = :categoria_id,
                nome = :nome,
                descricao = :descricao,
                preco = :preco,
                status = :status
            WHERE id = :id
        ");

        $stmtAtualizar->execute([
            'categoria_id' => $categoriaId,
            'nome' => $nome,
            'descricao' => $descricao,
            'preco' => $preco,
            'status' => $status,
            'id' => $id
        ]);

        if ($novaPrincipal !== null) {

            $stmtDesmarcar = $pdo->prepare("
                UPDATE imagens_produtos
                SET imagem_principal = 0
                WHERE produto_id = :id
            ");
            $stmtDesmarcar->execute(['id' => $id]);

            $stmtImagem = $pdo->prepare("
                INSERT INTO imagens_produtos
                    (produto_id, caminho, imagem_principal, ordem)
                VALUES
                    (:produto_id, :caminho, 1, 0)
            ");

            $stmtImagem->execute([
                'produto_id' => $id,
                'caminho' => $novaPrincipal
            ]);
        }

        $stmtOrdem = $pdo->prepare("
            SELECT COALESCE(MAX(ordem), 0)
            FROM imagens_produtos
            WHERE produto_id = :id
        ");
        $stmtOrdem->execute(['id' => $id]);
        $ordem = (int) $stmtOrdem->fetchColumn();

        $stmtImagemAdicional = $pdo->prepare("
            INSERT INTO imagens_produtos
                (produto_id, caminho, imagem_principal, ordem)
            VALUES
                (:produto_id, :caminho, 0, :ordem)
        ");

        foreach ($novasImagens as $caminho) {
            $ordem++;

            $stmtImagemAdicional->execute([
                'produto_id' => $id,
                'caminho' => $caminho,
                'ordem' => $ordem
            ]);
        }

        $pdo->commit();

        $mensagem = 'Produto atualizado com sucesso!';
        $tipoMensagem = 'sucesso';

        $stmt = $pdo->prepare("
            SELECT *
            FROM produtos
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        $produto = $stmt->fetch();

        $stmt = $pdo->prepare("
            SELECT *
            FROM imagens_produtos
            WHERE produto_id = :id
            ORDER BY imagem_principal DESC, ordem ASC, id ASC
        ");
        $stmt->execute(['id' => $id]);
        $imagens = $stmt->fetchAll();

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
            ? 'Não foi possível atualizar o produto.'
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

    <meta name="description" content="Edição de produtos do Mimo de Sofia.">

    <title>Editar Produto | Mimo de Sofia</title>

    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

    <header class="admin-header">

        <div class="admin-header-container">

            <a href="../dashboard.php" class="admin-logo">
                <img src="../../assets/images/logo.png" alt="Mimo de Sofia">
            </a>

            <h1>Editar Produto</h1>

            <a href="listar.php" class="admin-back">
                Voltar para produtos
            </a>

        </div>

    </header>

    <main class="admin-main">

        <section class="admin-intro">

            <h2>Editar informações</h2>

            <p>
                Altere as informações e imagens do produto cadastrado.
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
                action="editar.php?id=<?php echo (int) $id; ?>"
                method="POST"
                enctype="multipart/form-data">

                <div class="form-group">

                    <label for="nome">Nome do produto</label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        value="<?php echo htmlspecialchars($produto['nome']); ?>"
                        maxlength="150"
                        required>

                </div>

                <div class="form-group">

                    <label for="categoria">Categoria</label>

                    <select id="categoria" name="categoria" required>

                        <?php foreach ($categorias as $categoria): ?>

                            <option
                                value="<?php echo (int) $categoria['id']; ?>"
                                <?php echo ((int) $produto['categoria_id'] === (int) $categoria['id']) ? 'selected' : ''; ?>>

                                <?php
                                echo htmlspecialchars($categoria['nome']);

                                if ($categoria['status'] === 'inativo') {
                                    echo ' (Inativa)';
                                }
                                ?>

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
                        required><?php echo htmlspecialchars($produto['descricao']); ?></textarea>

                </div>

                <div class="form-group">

                    <label for="preco">Preço</label>

                    <input
                        type="number"
                        id="preco"
                        name="preco"
                        min="0"
                        step="0.01"
                        value="<?php echo htmlspecialchars($produto['preco']); ?>"
                        required>

                </div>

                <div class="form-group">

                    <label>Imagens cadastradas</label>

                    <div class="admin-product-images">

                        <?php foreach ($imagens as $imagem): ?>

                            <div class="admin-product-image">

                                <img
                                    src="../../<?php echo htmlspecialchars($imagem['caminho']); ?>"
                                    alt="Imagem do produto"
                                    width="120">

                                <small>
                                    <?php echo $imagem['imagem_principal'] ? 'Imagem principal' : 'Imagem adicional'; ?>
                                </small>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

                <div class="form-group">

                    <label for="imagem-principal">Substituir imagem principal</label>

                    <input
                        type="file"
                        id="imagem-principal"
                        name="imagem_principal"
                        accept=".jpg,.jpeg,.png,.webp">

                    <small>
                        Se não selecionar uma nova imagem, a atual será mantida.
                        Máximo de 5 MB.
                    </small>

                </div>

                <div class="form-group">

                    <label for="imagens-adicionais">Adicionar novas imagens</label>

                    <input
                        type="file"
                        id="imagens-adicionais"
                        name="imagens_adicionais[]"
                        accept=".jpg,.jpeg,.png,.webp"
                        multiple>

                    <small>
                        Você pode selecionar mais de uma imagem.
                        Máximo de 5 MB por imagem.
                    </small>

                </div>

                <div class="form-group">

                    <label for="status">Status do produto</label>

                    <select id="status" name="status" required>

                        <option
                            value="ativo"
                            <?php echo $produto['status'] === 'ativo' ? 'selected' : ''; ?>>
                            Ativo
                        </option>

                        <option
                            value="inativo"
                            <?php echo $produto['status'] === 'inativo' ? 'selected' : ''; ?>>
                            Inativo
                        </option>

                    </select>

                </div>

                <div class="form-actions">

                    <button type="submit">
                        Salvar alterações
                    </button>

                    <a href="listar.php">
                        Cancelar
                    </a>

                </div>

            </form>

        </section>

    </main>

    <footer class="admin-footer">

        <p>Mimo de Sofia — Painel Administrativo</p>

    </footer>

</body>
</html>

