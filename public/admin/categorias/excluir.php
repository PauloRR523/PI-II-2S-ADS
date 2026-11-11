
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../../app/config/database.php';

$erro = '';

// Recebe e valida o ID da categoria
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: listar.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, nome, descricao, status
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

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM produtos
    WHERE categoria_id = :id
");

$stmt->execute([':id' => $id]);

$totalProdutos = (int) $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($totalProdutos > 0) {

        $erro = 'Esta categoria possui produtos associados e não pode ser excluída.';

    } else {

        try {

            $stmt = $pdo->prepare("
                DELETE FROM categorias
                WHERE id = :id
            ");

            $stmt->execute([':id' => $id]);

            header('Location: listar.php?sucesso=excluida');
            exit;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $erro = 'Não foi possível excluir a categoria. Verifique se existem produtos vinculados.';

        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Excluir Categoria | Mimo de Sofia</title>

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

        <h1>Excluir Categoria</h1>

        <nav>
            <a href="../dashboard.php">Painel</a>
            <a href="listar.php">Categorias</a>
        </nav>

    </header>

    <main>

        <section>

            <h2>Confirmar exclusão</h2>

            <p>
                Você está prestes a excluir a seguinte categoria:
            </p>

            <div>

                <h3>
                    <?php echo htmlspecialchars($categoria['nome']); ?>
                </h3>

                <p>
                    <?php echo htmlspecialchars($categoria['descricao'] ?? ''); ?>
                </p>

                <p>
                    Status:
                    <?php echo $categoria['status'] === 'ativo' ? 'Ativa' : 'Inativa'; ?>
                </p>

                <p>
                    Produtos associados: <?php echo $totalProdutos; ?>
                </p>

            </div>

            <?php if ($erro !== ''): ?>

                <p role="alert">
                    <?php echo htmlspecialchars($erro); ?>
                </p>

            <?php endif; ?>

            <?php if ($totalProdutos > 0): ?>

                <p>
                    Esta categoria não pode ser excluída porque possui
                    produtos associados. Altere a categoria desses produtos
                    antes de tentar novamente.
                </p>

                <a href="listar.php">
                    Voltar para a lista
                </a>

            <?php else: ?>

                <p>
                    Tem certeza de que deseja excluir esta categoria?
                </p>

                <p>
                    Atenção: esta ação não poderá ser desfeita.
                </p>

                <form
                    action="excluir.php?id=<?php echo $id; ?>"
                    method="post"
                >

                    <button type="submit">
                        Sim, excluir categoria
                    </button>

                    <a href="listar.php">
                        Não, voltar para a lista
                    </a>

                </form>

            <?php endif; ?>

        </section>

    </main>

    <footer>
        <p>Painel Administrativo - Mimo de Sofia</p>
    </footer>

</body>
</html>

