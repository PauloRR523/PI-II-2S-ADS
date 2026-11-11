
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

$stmt = $pdo->prepare("
    SELECT id, nome
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

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $stmtImagens = $pdo->prepare("
            SELECT caminho
            FROM imagens_produtos
            WHERE produto_id = :id
        ");
        $stmtImagens->execute(['id' => $id]);
        $imagens = $stmtImagens->fetchAll();

        $stmtExcluir = $pdo->prepare("
            DELETE FROM produtos
            WHERE id = :id
        ");
        $stmtExcluir->execute(['id' => $id]);

        foreach ($imagens as $imagem) {

            $caminho = __DIR__ . '/../../' . $imagem['caminho'];

            if (is_file($caminho)) {
                unlink($caminho);
            }
        }

        header('Location: listar.php');
        exit;

    } catch (PDOException $e) {

        error_log($e->getMessage());
        $mensagem = 'Não foi possível excluir o produto. Tente novamente.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Exclusão de produtos do Mimo de Sofia.">

    <title>Excluir Produto | Mimo de Sofia</title>

    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

    <header class="admin-header">

        <div class="admin-header-container">

            <a href="../dashboard.php" class="admin-logo">
                <img src="../../assets/images/logo.png" alt="Mimo de Sofia">
            </a>

            <h1>Excluir Produto</h1>

            <a href="listar.php" class="admin-back">
                Voltar para produtos
            </a>

        </div>

    </header>

    <main class="admin-main">

        <section class="admin-intro">

            <h2>Confirmar exclusão</h2>

            <p>
                Você está prestes a excluir o seguinte produto:
            </p>

            <h3>
                <?php echo htmlspecialchars($produto['nome']); ?>
            </h3>

            <p>
                Essa ação não poderá ser desfeita.
            </p>

        </section>

        <section class="admin-form-section">

            <?php if ($mensagem !== ''): ?>
                <p class="mensagem-erro">
                    <?php echo htmlspecialchars($mensagem); ?>
                </p>
            <?php endif; ?>

            <form method="POST" action="excluir.php?id=<?php echo (int) $id; ?>">

                <div class="form-actions">

                    <button type="submit">
                        Confirmar exclusão
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

