
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../../app/config/database.php';

$erro = '';
$nome = '';
$descricao = '';
$status = 'ativo';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $status = $_POST['status'] ?? 'ativo';

    if ($nome === '') {

        $erro = 'Informe o nome da categoria.';

    } elseif (!in_array($status, ['ativo', 'inativo'], true)) {

        $erro = 'Status inválido.';

    } else {

        // Gera o slug a partir do nome
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome);
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if ($slug === '') {

            $erro = 'Não foi possível gerar o identificador da categoria.';

        } else {

            // Verifica se o nome ou slug já existem
            $stmt = $pdo->prepare("
                SELECT id
                FROM categorias
                WHERE nome = :nome OR slug = :slug
                LIMIT 1
            ");

            $stmt->execute([
                ':nome' => $nome,
                ':slug' => $slug
            ]);

            if ($stmt->fetch()) {

                $erro = 'Já existe uma categoria com esse nome ou identificador.';

            } else {

                try {

                    $stmt = $pdo->prepare("
                        INSERT INTO categorias (nome, slug, descricao, status)
                        VALUES (:nome, :slug, :descricao, :status)
                    ");

                    $stmt->execute([
                        ':nome' => $nome,
                        ':slug' => $slug,
                        ':descricao' => $descricao,
                        ':status' => $status
                    ]);

                    header('Location: listar.php?sucesso=cadastrada');
                    exit;

                } catch (PDOException $e) {

                    error_log($e->getMessage());
                    $erro = 'Não foi possível cadastrar a categoria.';

                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar Categoria | Mimo de Sofia</title>

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

        <h1>Cadastrar Categoria</h1>

        <nav>
            <a href="../dashboard.php">Painel</a>
            <a href="listar.php">Categorias</a>
        </nav>

    </header>

    <main>

        <section>

            <h2>Informações da categoria</h2>

            <?php if ($erro !== ''): ?>
                <p role="alert">
                    <?php echo htmlspecialchars($erro); ?>
                </p>
            <?php endif; ?>

            <form action="cadastrar.php" method="post">

                <div>
                    <label for="nome">Nome da categoria:</label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Digite o nome da categoria"
                        value="<?php echo htmlspecialchars($nome); ?>"
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
                        placeholder="Descreva a categoria"
                    ><?php echo htmlspecialchars($descricao); ?></textarea>
                </div>

                <div>
                    <label for="status">Status:</label>

                    <select id="status" name="status" required>

                        <option
                            value="ativo"
                            <?php echo $status === 'ativo' ? 'selected' : ''; ?>
                        >
                            Ativa
                        </option>

                        <option
                            value="inativo"
                            <?php echo $status === 'inativo' ? 'selected' : ''; ?>
                        >
                            Inativa
                        </option>

                    </select>
                </div>

                <div>
                    <button type="submit">
                        Cadastrar categoria
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

