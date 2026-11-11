<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$nomeAdmin = $_SESSION['admin_nome'];

?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Painel administrativo Mimo de Sofia.">
    <link rel="stylesheet" href="assets/css/admin.css">

    <title>Painel Adm | Mimo de Sofia</title>
</head>

<body>

    <header class="admin-header">

        <div class="admin-header-container">

            <a href="../index.php" class="admin-logo">
                <img src="../assets/images/logo.png" alt="Mimo de Sofia">
            </a>

            <h1>Painel Administrativo</h1>

            <a href="logout.php" class="admin-logout">
                Sair
            </a>

        </div>

    </header>


    <main class="admin-main">

        <section class="admin-intro">

            <h2>Bem-vinda ao painel administrativo</h2>

            <p>
                Gerencie os produtos, categorias e informações
                do Ateliê Mimo de Sofia.
            </p>

        </section>


        <section class="admin-menu">

            <h2>Gerenciamento</h2>

            <div class="admin-menu-list">

                <article class="admin-menu-card">

                    <h3>Produtos</h3>

                    <p>
                        Consulte, cadastre, edite e remova
                        os produtos disponíveis no site.
                    </p>

                    <a href="produtos/listar.php">
                        Gerenciar produtos
                    </a>

                    <a href="produtos/cadastrar.php">
                        Cadastrar produto
                    </a>

                </article>


                <article class="admin-menu-card">

                    <h3>Categorias</h3>

                    <p>
                        Gerencie as categorias utilizadas
                        para organizar os produtos.
                    </p>

                    <a href="categorias/listar.php">
                        Gerenciar categorias
                    </a>

                    <a href="categorias/cadastrar.php">
                        Cadastrar categoria
                    </a>

                </article>


                <article class="admin-menu-card">

                    <h3>Configurações</h3>

                    <p>
                        Gerencie as informações gerais
                        do site e do ateliê.
                    </p>

                    <a href="config.php">
                        Acessar configurações
                    </a>

                </article>

            </div>

        </section>

    </main>


    <footer class="admin-footer">

        <p>
            Mimo de Sofia — Painel Administrativo
        </p>

    </footer>

</body>

</html>

