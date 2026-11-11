<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../../app/config/database.php';

$stmt = $pdo->query("
    SELECT
        p.id,
        p.nome,
        p.status,
        c.nome AS categoria,
        (
            SELECT i.caminho
            FROM imagens_produtos i
            WHERE i.produto_id = p.id
            ORDER BY i.imagem_principal DESC, i.ordem ASC, i.id ASC
            LIMIT 1
        ) AS imagem
    FROM produtos p
    INNER JOIN categorias c ON c.id = p.categoria_id
    ORDER BY p.id DESC
");

$produtos = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="descricao" content="Gerenciamento de produtos Mimo de Sofia.">

    <title>Gerenciar Produtos | Mimo de Sofia</title>

<link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

    <header class="admin-header">

        <div class="admin-header-container">

            <a href="../dashboard.php" class="admin-logo">
                <img src="../assets/images/logo.png" alt="Mimo de Sofia">
            </a>

            <h1>Gerenciamento de Produtos</h1>

            <a href="../dashboard.php" class="admin-back">
                Voltar ao painel
            </a>

        </div>

    </header>


    <main class="admin-main">

        <section class="admin-intro">

            <h2>Produtos cadastrados</h2>

            <p>
                Consulte e gerencie os produtos disponíveis
                no catálogo do Mimo de Sofia.
            </p>

            <a href="cadastrar.php" class="admin-button">
                Cadastrar novo produto
            </a>

        </section>


        <section class="admin-products">

            <h2>Lista de produtos</h2>

            <table>
                <tbody>

    <?php if (count($produtos) > 0): ?>

        <?php foreach ($produtos as $produto): ?>

            <tr>

                <td>
                    <?php if (!empty($produto['imagem'])): ?>
                        <img
                            src="../../<?php echo htmlspecialchars($produto['imagem'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>"
                            width="80">
                    <?php else: ?>
                        Sem imagem
                    <?php endif; ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($produto['categoria'], ENT_QUOTES, 'UTF-8'); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($produto['status'], ENT_QUOTES, 'UTF-8'); ?>
                </td>

                <td>
                    <a href="editar.php?id=<?php echo (int) $produto['id']; ?>">Editar</a>

                    <a href="excluir.php?id=<?php echo (int) $produto['id']; ?>">Excluir</a>
                </td>

            </tr>

        <?php endforeach; ?>

    <?php else: ?>

        <tr>
            <td colspan="5">Nenhum produto cadastrado.</td>
        </tr>

    <?php endif; ?>

                </tbody>

            </table>

        </section>

    </main>


    <footer class="admin-footer">

        <p>
            Mimo de Sofia — Painel Administrativo
        </p>

    </footer>

</body>

</html>

