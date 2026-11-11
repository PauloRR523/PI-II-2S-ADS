
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../../app/config/database.php';

$erro = '';
$sucesso = '';

/*
|--------------------------------------------------------------------------
| Proteção CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

function validarToken(): void
{
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        throw new RuntimeException('Sessão inválida. Atualize a página e tente novamente.');
    }
}

function salvarImagem(array $arquivo, string $tipo): array
{
    if (!isset($arquivo['error']) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ocorreu um erro no envio da imagem.');
    }

    if ($arquivo['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('A imagem deve ter no máximo 5 MB.');
    }

    $temporario = $arquivo['tmp_name'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($temporario);

    $extensoes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (
        !isset($extensoes[$mime]) ||
        getimagesize($temporario) === false
    ) {
        throw new RuntimeException('Formato inválido. Utilize JPG, PNG ou WEBP.');
    }

    $diretorio = __DIR__ . '/../assets/uploads/site/' . $tipo . '/';

    if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true)) {
        throw new RuntimeException('Não foi possível criar a pasta de imagens.');
    }

    $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensoes[$mime];

    $arquivoDestino = $diretorio . $nomeArquivo;

    if (!move_uploaded_file($temporario, $arquivoDestino)) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }

    return [
        'arquivo' => $arquivoDestino,
        'caminho' => 'assets/uploads/site/' . $tipo . '/' . $nomeArquivo
    ];
}

function excluirArquivoImagem(?string $caminho): void
{
    if (empty($caminho)) {
        return;
    }

    $base = realpath(__DIR__ . '/../assets/uploads/site/');

    if ($base === false) {
        return;
    }

    $arquivo = realpath(__DIR__ . '/../' . $caminho);

    if (
        $arquivo !== false &&
        strpos($arquivo, $base . DIRECTORY_SEPARATOR) === 0 &&
        is_file($arquivo)
    ) {
        unlink($arquivo);
    }
}

/*
|--------------------------------------------------------------------------
| Carregar configurações
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT *
    FROM configuracoes
    ORDER BY id ASC
    LIMIT 1
");

$configuracao = $stmt->fetch();

if (!$configuracao) {
    $stmt = $pdo->prepare("
        INSERT INTO configuracoes (nome_site, descricao_site)
        VALUES (:nome, :descricao)
    ");

    $stmt->execute([
        ':nome' => 'Mimo de Sofia',
        ':descricao' => 'Ateliê artesanal Mimo de Sofia.'
    ]);

    $id = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        SELECT *
        FROM configuracoes
        WHERE id = :id
    ");

    $stmt->execute([':id' => $id]);
    $configuracao = $stmt->fetch();
}

/*
|--------------------------------------------------------------------------
| Carregar categorias
|--------------------------------------------------------------------------
*/

$stmtCategorias = $pdo->query("
    SELECT id, nome, slug
    FROM categorias
    WHERE status = 'ativo'
    ORDER BY id ASC
");

$categorias = $stmtCategorias->fetchAll();

/*
|--------------------------------------------------------------------------
| Processar formulários
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        validarToken();

        $acao = $_POST['acao'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | Salvar configurações gerais
        |--------------------------------------------------------------------------
        */

        if ($acao === 'salvar_configuracoes') {

            $nomeSite = trim($_POST['nome_site'] ?? '');
            $descricaoSite = trim($_POST['descricao_site'] ?? '');
            $sobreMim = trim($_POST['sobre_mim'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefone = trim($_POST['telefone'] ?? '');
            $whatsapp = trim($_POST['whatsapp'] ?? '');
            $instagram = trim($_POST['instagram'] ?? '');
            $facebook = trim($_POST['facebook'] ?? '');

            if ($nomeSite === '') {
                throw new RuntimeException('Informe o nome do site.');
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Informe um endereço de e-mail válido.');
            }

            if (
                ($instagram !== '' && !filter_var($instagram, FILTER_VALIDATE_URL)) ||
                ($facebook !== '' && !filter_var($facebook, FILTER_VALIDATE_URL))
            ) {
                throw new RuntimeException('Informe URLs válidas para as redes sociais.');
            }

            $logo = $configuracao['logo'];
            $arquivoNovo = null;

            if (
                isset($_FILES['logo']) &&
                $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                $resultado = salvarImagem($_FILES['logo'], 'logos');

                $arquivoNovo = $resultado['arquivo'];
                $logo = $resultado['caminho'];
            }

            try {

                $stmt = $pdo->prepare("
                    UPDATE configuracoes
                    SET nome_site = :nome,
                        descricao_site = :descricao,
                        sobre_mim = :sobre_mim,
                        logo = :logo,
                        email = :email,
                        telefone = :telefone,
                        whatsapp = :whatsapp,
                        instagram = :instagram,
                        facebook = :facebook
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':nome' => $nomeSite,
                    ':descricao' => $descricaoSite,
                    ':sobre_mim' => $sobreMim,
                    ':logo' => $logo,
                    ':email' => $email ?: null,
                    ':telefone' => $telefone ?: null,
                    ':whatsapp' => $whatsapp ?: null,
                    ':instagram' => $instagram ?: null,
                    ':facebook' => $facebook ?: null,
                    ':id' => $configuracao['id']
                ]);

            } catch (Throwable $e) {

                if ($arquivoNovo && file_exists($arquivoNovo)) {
                    unlink($arquivoNovo);
                }

                throw $e;
            }

            header('Location: config.php?sucesso=config');
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Salvar imagens
        |--------------------------------------------------------------------------
        */

        if ($acao === 'salvar_imagem') {

            $tipo = $_POST['tipo'] ?? '';
            $textoAlt = trim($_POST['texto_alt'] ?? '');
            $categoriaId = null;

            if (!in_array($tipo, ['carrossel', 'categoria', 'sobre'], true)) {
                throw new RuntimeException('Tipo de imagem inválido.');
            }

            if (
                !isset($_FILES['imagem']) ||
                $_FILES['imagem']['error'] === UPLOAD_ERR_NO_FILE
            ) {
                throw new RuntimeException('Selecione uma imagem.');
            }

            if ($tipo === 'categoria') {

                $categoriaId = filter_var(
                    $_POST['categoria_id'] ?? '',
                    FILTER_VALIDATE_INT
                );

                if (!$categoriaId) {
                    throw new RuntimeException('Selecione uma categoria.');
                }

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM categorias
                    WHERE id = :id
                    AND status = 'ativo'
                ");

                $stmt->execute([':id' => $categoriaId]);

                if (!$stmt->fetch()) {
                    throw new RuntimeException('Categoria não encontrada.');
                }
            }

            $resultado = salvarImagem($_FILES['imagem'], $tipo);

            $arquivoNovo = $resultado['arquivo'];
            $caminhoNovo = $resultado['caminho'];
            $caminhoAntigo = null;

            try {

                $pdo->beginTransaction();

                if ($tipo === 'carrossel') {

                    $stmt = $pdo->query("
                        SELECT COALESCE(MAX(ordem), 0) + 1
                        FROM imagens_site
                        WHERE tipo = 'carrossel'
                    ");

                    $ordem = (int) $stmt->fetchColumn();

                    $stmt = $pdo->prepare("
                        INSERT INTO imagens_site
                            (tipo, caminho, texto_alt, ordem, ativo)
                        VALUES
                            ('carrossel', :caminho, :texto_alt, :ordem, 1)
                    ");

                    $stmt->execute([
                        ':caminho' => $caminhoNovo,
                        ':texto_alt' => $textoAlt ?: null,
                        ':ordem' => $ordem
                    ]);
                }

                if ($tipo === 'categoria') {

                    $stmt = $pdo->prepare("
                        SELECT id, caminho
                        FROM imagens_site
                        WHERE tipo = 'categoria'
                        AND categoria_id = :categoria_id
                        LIMIT 1
                    ");

                    $stmt->execute([':categoria_id' => $categoriaId]);

                    $imagemExistente = $stmt->fetch();

                    if ($imagemExistente) {

                        $caminhoAntigo = $imagemExistente['caminho'];

                        $stmt = $pdo->prepare("
                            UPDATE imagens_site
                            SET caminho = :caminho,
                                texto_alt = :texto_alt,
                                ativo = 1
                            WHERE id = :id
                        ");

                        $stmt->execute([
                            ':caminho' => $caminhoNovo,
                            ':texto_alt' => $textoAlt ?: null,
                            ':id' => $imagemExistente['id']
                        ]);

                    } else {

                        $stmt = $pdo->prepare("
                            INSERT INTO imagens_site
                                (tipo, categoria_id, caminho, texto_alt, ordem, ativo)
                            VALUES
                                ('categoria', :categoria_id, :caminho, :texto_alt, 0, 1)
                        ");

                        $stmt->execute([
                            ':categoria_id' => $categoriaId,
                            ':caminho' => $caminhoNovo,
                            ':texto_alt' => $textoAlt ?: null
                        ]);
                    }
                }

                if ($tipo === 'sobre') {

                    $stmt = $pdo->query("
                        SELECT id, caminho
                        FROM imagens_site
                        WHERE tipo = 'sobre'
                        LIMIT 1
                    ");

                    $imagemExistente = $stmt->fetch();

                    if ($imagemExistente) {

                        $caminhoAntigo = $imagemExistente['caminho'];

                        $stmt = $pdo->prepare("
                            UPDATE imagens_site
                            SET caminho = :caminho,
                                texto_alt = :texto_alt,
                                ativo = 1
                            WHERE id = :id
                        ");

                        $stmt->execute([
                            ':caminho' => $caminhoNovo,
                            ':texto_alt' => $textoAlt ?: null,
                            ':id' => $imagemExistente['id']
                        ]);

                    } else {

                        $stmt = $pdo->prepare("
                            INSERT INTO imagens_site
                                (tipo, caminho, texto_alt, ordem, ativo)
                            VALUES
                                ('sobre', :caminho, :texto_alt, 0, 1)
                        ");

                        $stmt->execute([
                            ':caminho' => $caminhoNovo,
                            ':texto_alt' => $textoAlt ?: null
                        ]);
                    }
                }

                $pdo->commit();

                if ($caminhoAntigo !== null) {
                    excluirArquivoImagem($caminhoAntigo);
                }

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if (isset($arquivoNovo) && file_exists($arquivoNovo)) {
                    unlink($arquivoNovo);
                }

                throw $e;
            }

            header('Location: config.php?sucesso=imagem');
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Excluir imagem
        |--------------------------------------------------------------------------
        */

        if ($acao === 'excluir_imagem') {

            $idImagem = filter_var(
                $_POST['imagem_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$idImagem) {
                throw new RuntimeException('Imagem inválida.');
            }

            $stmt = $pdo->prepare("
                SELECT caminho
                FROM imagens_site
                WHERE id = :id
            ");

            $stmt->execute([':id' => $idImagem]);

            $imagem = $stmt->fetch();

            if (!$imagem) {
                throw new RuntimeException('Imagem não encontrada.');
            }

            $stmt = $pdo->prepare("
                DELETE FROM imagens_site
                WHERE id = :id
            ");

            $stmt->execute([':id' => $idImagem]);

            excluirArquivoImagem($imagem['caminho']);

            header('Location: config.php?sucesso=exclusao');
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Alterar ordem do carrossel
        |--------------------------------------------------------------------------
        */

        if ($acao === 'alterar_ordem') {

            $idImagem = filter_var(
                $_POST['imagem_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            $ordem = filter_var(
                $_POST['ordem'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$idImagem || $ordem === false || $ordem < 1) {
                throw new RuntimeException('Informe uma posição válida.');
            }

            $stmt = $pdo->prepare("
                UPDATE imagens_site
                SET ordem = :ordem
                WHERE id = :id
                AND tipo = 'carrossel'
            ");

            $stmt->execute([
                ':ordem' => $ordem,
                ':id' => $idImagem
            ]);

            header('Location: config.php?sucesso=ordem');
            exit;
        }

        throw new RuntimeException('Ação não reconhecida.');

    } catch (Throwable $e) {

        error_log($e->getMessage());

        $erro = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Não foi possível concluir a operação.';
    }
}

/*
|--------------------------------------------------------------------------
| Mensagens de retorno
|--------------------------------------------------------------------------
*/

if (isset($_GET['sucesso'])) {

    $mensagens = [
        'config' => 'Configurações atualizadas com sucesso!',
        'imagem' => 'Imagem salva com sucesso!',
        'exclusao' => 'Imagem excluída com sucesso!',
        'ordem' => 'Ordem do carrossel atualizada!'
    ];

    $sucesso = $mensagens[$_GET['sucesso']] ?? '';
}

/*
|--------------------------------------------------------------------------
| Atualizar dados exibidos
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM configuracoes
    WHERE id = :id
");

$stmt->execute([':id' => $configuracao['id']]);
$configuracao = $stmt->fetch();

$stmt = $pdo->query("
    SELECT
        imagens_site.*,
        categorias.nome AS nome_categoria
    FROM imagens_site
    LEFT JOIN categorias
        ON imagens_site.categoria_id = categorias.id
    ORDER BY
        FIELD(imagens_site.tipo, 'carrossel', 'categoria', 'sobre'),
        imagens_site.ordem ASC,
        imagens_site.id ASC
");

$imagens = $stmt->fetchAll();

$imagensCarrossel = [];
$imagensCategorias = [];
$imagemSobre = null;

foreach ($imagens as $imagem) {

    if ($imagem['tipo'] === 'carrossel') {
        $imagensCarrossel[] = $imagem;
    }

    if ($imagem['tipo'] === 'categoria') {
        $imagensCategorias[] = $imagem;
    }

    if ($imagem['tipo'] === 'sobre') {
        $imagemSobre = $imagem;
    }
}

$logoAtual = !empty($configuracao['logo'])
    ? '../' . $configuracao['logo']
    : '../assets/images/logo.png';

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Configurações | Mimo de Sofia</title>

    <link rel="stylesheet" href="assets/css/admin.css">
</head>

<body>

    <header>

        <a href="dashboard.php">
            <img src="../assets/images/logo.png" alt="Logo Mimo de Sofia">
        </a>

        <h1>Configurações do Site</h1>

        <nav>
            <a href="dashboard.php">Painel</a>
            <a href="produtos/listar.php">Produtos</a>
            <a href="categorias/listar.php">Categorias</a>
        </nav>

    </header>

    <main>

        <?php if ($erro !== ''): ?>
            <p role="alert">
                <?php echo htmlspecialchars($erro); ?>
            </p>
        <?php endif; ?>

        <?php if ($sucesso !== ''): ?>
            <p role="status">
                <?php echo htmlspecialchars($sucesso); ?>
            </p>
        <?php endif; ?>

        <!-- ==========================================================
             CONFIGURAÇÕES GERAIS
        =========================================================== -->

        <section>

            <h2>Informações gerais</h2>

            <form
                action="config.php"
                method="post"
                enctype="multipart/form-data"
            >

                <input type="hidden" name="csrf_token"
                    value="<?php echo htmlspecialchars($csrfToken); ?>">

                <input type="hidden" name="acao" value="salvar_configuracoes">

                <fieldset>

                    <legend>Identificação do site</legend>

                    <div>
                        <label for="nome_site">Nome do site:</label>

                        <input
                            type="text"
                            id="nome_site"
                            name="nome_site"
                            value="<?php echo htmlspecialchars($configuracao['nome_site']); ?>"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div>
                        <label for="descricao_site">Descrição do site:</label>

                        <textarea
                            id="descricao_site"
                            name="descricao_site"
                            rows="4"
                        ><?php echo htmlspecialchars($configuracao['descricao_site'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label for="sobre_mim">Texto da seção Sobre mim:</label>

                        <textarea
                            id="sobre_mim"
                            name="sobre_mim"
                            rows="6"
                            placeholder="Conte um pouco sobre a artesã e o trabalho realizado no ateliê."
                        ><?php echo htmlspecialchars(
                            $configuracao['sobre_mim'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?></textarea>
                    </div>

                    <div>
                        <label>Logotipo atual:</label>

                        <img
                            src="<?php echo htmlspecialchars($logoAtual); ?>"
                            alt="Logotipo atual"
                            style="max-width: 180px; height: auto;"
                        >
                    </div>

                    <div>
                        <label for="logo">Alterar logotipo:</label>

                        <input
                            type="file"
                            id="logo"
                            name="logo"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <small>JPG, PNG ou WEBP. Máximo de 5 MB.</small>
                    </div>

                </fieldset>

                <fieldset>

                    <legend>Informações de contato</legend>

                    <div>
                        <label for="email">E-mail:</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($configuracao['email'] ?? ''); ?>"
                            maxlength="150"
                            placeholder="contato@exemplo.com"
                        >
                    </div>

                    <div>
                        <label for="telefone">Telefone:</label>

                        <input
                            type="tel"
                            id="telefone"
                            name="telefone"
                            value="<?php echo htmlspecialchars($configuracao['telefone'] ?? ''); ?>"
                            maxlength="30"
                            placeholder="(00) 00000-0000"
                        >
                    </div>

                    <div>
                        <label for="whatsapp">WhatsApp:</label>

                        <input
                            type="tel"
                            id="whatsapp"
                            name="whatsapp"
                            value="<?php echo htmlspecialchars($configuracao['whatsapp'] ?? ''); ?>"
                            maxlength="30"
                            placeholder="(00) 00000-0000"
                        >
                    </div>

                </fieldset>

                <fieldset>

                    <legend>Redes sociais</legend>

                    <div>
                        <label for="instagram">Instagram:</label>

                        <input
                            type="url"
                            id="instagram"
                            name="instagram"
                            value="<?php echo htmlspecialchars($configuracao['instagram'] ?? ''); ?>"
                            maxlength="255"
                            placeholder="https://www.instagram.com/"
                        >
                    </div>

                    <div>
                        <label for="facebook">Facebook:</label>

                        <input
                            type="url"
                            id="facebook"
                            name="facebook"
                            value="<?php echo htmlspecialchars($configuracao['facebook'] ?? ''); ?>"
                            maxlength="255"
                            placeholder="https://www.facebook.com/"
                        >
                    </div>

                </fieldset>

                <div>
                    <button type="submit">Salvar configurações</button>
                    <a href="dashboard.php">Cancelar</a>
                </div>

            </form>

        </section>


        <!-- ==========================================================
             GERENCIAMENTO DE IMAGENS
        =========================================================== -->

        <section>

            <h2>Imagens da página principal</h2>

            <p>
                Gerencie aqui as imagens exibidas no carrossel,
                nas categorias e na seção Sobre mim.
            </p>


            <!-- CARROSSEL -->

            <section>

                <h3>Carrossel principal</h3>

                <form action="config.php" method="post" enctype="multipart/form-data">

                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <input type="hidden" name="acao" value="salvar_imagem">
                    <input type="hidden" name="tipo" value="carrossel">

                    <div>
                        <label for="imagem_carrossel">Adicionar imagem:</label>

                        <input
                            type="file"
                            id="imagem_carrossel"
                            name="imagem"
                            accept="image/jpeg,image/png,image/webp"
                            required
                        >
                    </div>

                    <div>
                        <label for="alt_carrossel">Descrição da imagem:</label>

                        <input
                            type="text"
                            id="alt_carrossel"
                            name="texto_alt"
                            maxlength="255"
                            placeholder="Descreva a imagem"
                        >
                    </div>

                    <button type="submit">Adicionar ao carrossel</button>

                </form>

                <h4>Imagens cadastradas</h4>

                <?php if (empty($imagensCarrossel)): ?>

                    <p>Nenhuma imagem cadastrada no carrossel.</p>

                <?php else: ?>

                    <?php foreach ($imagensCarrossel as $imagem): ?>

                        <article>

                            <img
                                src="../<?php echo htmlspecialchars($imagem['caminho']); ?>"
                                alt="<?php echo htmlspecialchars($imagem['texto_alt'] ?? 'Imagem do carrossel'); ?>"
                                style="max-width: 180px; height: auto;"
                            >

                            <p>
                                <?php echo htmlspecialchars($imagem['texto_alt'] ?? 'Sem descrição'); ?>
                            </p>

                            <form action="config.php" method="post">

                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($csrfToken); ?>">

                                <input type="hidden" name="acao" value="alterar_ordem">

                                <input type="hidden" name="imagem_id"
                                    value="<?php echo (int) $imagem['id']; ?>">

                                <label for="ordem_<?php echo (int) $imagem['id']; ?>">
                                    Posição:
                                </label>

                                <input
                                    type="number"
                                    id="ordem_<?php echo (int) $imagem['id']; ?>"
                                    name="ordem"
                                    min="1"
                                    value="<?php echo (int) $imagem['ordem']; ?>"
                                    required
                                >

                                <button type="submit">Salvar posição</button>

                            </form>

                            <form action="config.php" method="post"
                                onsubmit="return confirm('Deseja excluir esta imagem?');">

                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($csrfToken); ?>">

                                <input type="hidden" name="acao" value="excluir_imagem">

                                <input type="hidden" name="imagem_id"
                                    value="<?php echo (int) $imagem['id']; ?>">

                                <button type="submit">Excluir imagem</button>

                            </form>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </section>


            <!-- IMAGENS DAS CATEGORIAS -->

            <section>

                <h3>Imagens das categorias</h3>

                <form action="config.php" method="post" enctype="multipart/form-data">

                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <input type="hidden" name="acao" value="salvar_imagem">
                    <input type="hidden" name="tipo" value="categoria">

                    <div>
                        <label for="categoria_id">Categoria:</label>

                        <select id="categoria_id" name="categoria_id" required>

                            <option value="">Selecione uma categoria</option>

                            <?php foreach ($categorias as $categoria): ?>

                                <option value="<?php echo (int) $categoria['id']; ?>">
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div>
                        <label for="imagem_categoria">Imagem:</label>

                        <input
                            type="file"
                            id="imagem_categoria"
                            name="imagem"
                            accept="image/jpeg,image/png,image/webp"
                            required
                        >
                    </div>

                    <div>
                        <label for="alt_categoria">Descrição da imagem:</label>

                        <input
                            type="text"
                            id="alt_categoria"
                            name="texto_alt"
                            maxlength="255"
                            placeholder="Descreva a imagem"
                        >
                    </div>

                    <button type="submit">Salvar imagem da categoria</button>

                    <small>
                        Se a categoria já tiver uma imagem cadastrada,
                        ela será substituída.
                    </small>

                </form>

                <h4>Imagens cadastradas</h4>

                <?php if (empty($imagensCategorias)): ?>

                    <p>Nenhuma imagem de categoria cadastrada.</p>

                <?php else: ?>

                    <?php foreach ($imagensCategorias as $imagem): ?>

                        <article>

                            <h4><?php echo htmlspecialchars($imagem['nome_categoria'] ?? 'Categoria'); ?></h4>

                            <img
                                src="../<?php echo htmlspecialchars($imagem['caminho']); ?>"
                                alt="<?php echo htmlspecialchars($imagem['texto_alt'] ?? 'Imagem da categoria'); ?>"
                                style="max-width: 180px; height: auto;"
                            >

                            <form action="config.php" method="post"
                                onsubmit="return confirm('Deseja excluir esta imagem?');">

                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($csrfToken); ?>">

                                <input type="hidden" name="acao" value="excluir_imagem">

                                <input type="hidden" name="imagem_id"
                                    value="<?php echo (int) $imagem['id']; ?>">

                                <button type="submit">Excluir imagem</button>

                            </form>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </section>


            <!-- SOBRE MIM -->

            <section>

                <h3>Imagem da seção Sobre mim</h3>

                <form action="config.php" method="post" enctype="multipart/form-data">

                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <input type="hidden" name="acao" value="salvar_imagem">
                    <input type="hidden" name="tipo" value="sobre">

                    <div>
                        <label for="imagem_sobre">Selecionar imagem:</label>

                        <input
                            type="file"
                            id="imagem_sobre"
                            name="imagem"
                            accept="image/jpeg,image/png,image/webp"
                            required
                        >
                    </div>

                    <div>
                        <label for="alt_sobre">Descrição da imagem:</label>

                        <input
                            type="text"
                            id="alt_sobre"
                            name="texto_alt"
                            maxlength="255"
                            placeholder="Foto da artesã"
                        >
                    </div>

                    <button type="submit">Salvar imagem Sobre mim</button>

                    <small>
                        Uma nova imagem substituirá a imagem cadastrada anteriormente.
                    </small>

                </form>

                <?php if ($imagemSobre): ?>

                    <h4>Imagem atual</h4>

                    <img
                        src="../<?php echo htmlspecialchars($imagemSobre['caminho']); ?>"
                        alt="<?php echo htmlspecialchars($imagemSobre['texto_alt'] ?? 'Imagem Sobre mim'); ?>"
                        style="max-width: 180px; height: auto;"
                    >

                    <form action="config.php" method="post"
                        onsubmit="return confirm('Deseja excluir esta imagem?');">

                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <input type="hidden" name="acao" value="excluir_imagem">

                        <input type="hidden" name="imagem_id"
                            value="<?php echo (int) $imagemSobre['id']; ?>">

                        <button type="submit">Excluir imagem</button>

                    </form>

                <?php else: ?>

                    <p>Nenhuma imagem personalizada cadastrada.</p>

                <?php endif; ?>

            </section>

        </section>

    </main>

    <footer>
        <p>Painel Administrativo - Mimo de Sofia</p>
    </footer>

</body>
</html>

