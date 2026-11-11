
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../../app/config/database.php';

$sql = "SELECT id, nome, descricao, slug, status
        FROM categorias
        ORDER BY id ASC";

$stmt = $pdo->query($sql);

$categorias = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Listar Categorias | Mimo de Sofia</title>

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

        <h1>Gerenciar Categorias</h1>

        <nav>
            <a href="../dashboard.php">Painel</a>
            <a href="../produtos/listar.php">Produtos</a>
            <a href="listar.php">Categorias</a>
        </nav>

    </header>

    <main>

        <section>

            <h2>Categorias cadastradas</h2>

            <p>
                Nesta página você pode consultar e gerenciar
                as categorias dos produtos.
            </p>

            <a href="cadastrar.php">Cadastrar nova categoria</a>

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome da categoria</th>
                        <th>Descrição</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($categorias) > 0): ?>

                        <?php foreach ($categorias as $categoria): ?>

                            <tr>

                                <td>
                                    <?php echo $categoria['id']; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $categoria['descricao'] ?? ''
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo $categoria['status'] === 'ativo'
                                        ? 'Ativa'
                                        : 'Inativa';
                                    ?>
                                </td>

                                <td>
                                    <a href="editar.php?id=<?php echo $categoria['id']; ?>">
                                        Editar
                                    </a>

                                    |

                                    <a href="excluir.php?id=<?php echo $categoria['id']; ?>">
                                        Excluir
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5">
                                Nenhuma categoria cadastrada.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </section>

    </main>

    <footer>
        <p>Painel Administrativo - Mimo de Sofia</p>
    </footer>

</body>
</html>

