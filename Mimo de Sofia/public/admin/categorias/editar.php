
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../../app/config/database.php';

$erro = '';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: listar.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, nome, slug, descricao, status
    FROM categorias
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([':id' => $id]);

$categoria = $stmt->fetch();

if (!$categoria) {
    header('Location: listar.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $status = $_POST['status'] ?? '';

    if ($nome === '') {

        $erro = 'Informe o nome da categoria.';

    } elseif (!in_array($status, ['ativo', 'inativo'], true)) {

        $erro = 'Status inválido.';

    } else {

        if ($nome === $categoria['nome']) {

            $slug = $categoria['slug'];

        } else {

            $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome);
            $slug = strtolower($slug);
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');

        }

        if ($slug === '') {

            $erro = 'Não foi possível gerar o identificador da categoria.';

        } else {

            $stmt = $pdo->prepare("
                SELECT id
                FROM categorias
                WHERE (nome = :nome OR slug = :slug)
                AND id != :id
                LIMIT 1
            ");

            $stmt->execute([
                ':nome' => $nome,
                ':slug' => $slug,
                ':id' => $id
            ]);

            if ($stmt->fetch()) {

                $erro = 'Já existe outra categoria com esse nome ou identificador.';

            } else {

                try {

                    $stmt = $pdo->prepare("
                        UPDATE categorias
                        SET nome = :nome,
                            slug = :slug,
                            descricao = :descricao,
                            status = :status
                        WHERE id = :id
                    ");

                    $stmt->execute([
                        ':nome' => $nome,
                        ':slug' => $slug,
                        ':descricao' => $descricao,
                        ':status' => $status,
                        ':id' => $id
                    ]);

                    header('Location: listar.php?sucesso=atualizada');
                    exit;

                } catch (PDOException $e) {

                    error_log($e->getMessage());
                    $erro = 'Não foi possível atualizar a categoria.';

                }
            }
        }
    }

    $categoria['nome'] = $nome;
    $categoria['descricao'] = $descricao;
    $categoria['status'] = $status;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Categoria | Mimo de Sofia</title>

    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

    <header>

        <a href="../dashboard.php">
            <img
                src="../../assets/images/logo.png"
                alt="Logo Mimo de Sofia"
            >
        </a>

        <h1>Editar Categoria</h1>

        <nav>
            <a href="../dashboard.php">Painel</a>
            <a href="listar.php">Categorias</a>
        </nav>

    </header>

    <main>

        <section>

            <h2>Alterar informações da categoria</h2>

            <?php if ($erro !== ''): ?>
                <p role="alert">
                    <?php echo htmlspecialchars($erro); ?>
                </p>
            <?php endif; ?>

            <form action="editar.php?id=<?php echo $id; ?>" method="post">

                <div>
                    <label for="nome">Nome da categoria:</label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        value="<?php echo htmlspecialchars($categoria['nome']); ?>"
                        maxlength="100"
                        required
                    >
                </div>

                <div>
                    <label for="descricao">Descrição:</label>

                    <textarea
                        id="descricao"
                        name="descricao"
                        rows="5"
                    ><?php echo htmlspecialchars($categoria['descricao'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label for="status">Status:</label>

                    <select id="status" name="status" required>

                        <option
                            value="ativo"
                            <?php echo $categoria['status'] === 'ativo' ? 'selected' : ''; ?>
                        >
                            Ativa
                        </option>

                        <option
                            value="inativo"
                            <?php echo $categoria['status'] === 'inativo' ? 'selected' : ''; ?>
                        >
                            Inativa
                        </option>

                    </select>
                </div>

                <div>
                    <button type="submit">
                        Salvar alterações
                    </button>

                    <a href="listar.php">Cancelar</a>
                </div>

            </form>

        </section>

    </main>

    <footer>
        <p>Painel Administrativo - Mimo de Sofia</p>
    </footer>

</body>
</html>

