
<?php

session_start();

require_once __DIR__ . '/../../app/config/database.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("
        SELECT id, nome, usuario, senha
        FROM usuarios
        WHERE usuario = :usuario
        AND status = 'ativo'
        LIMIT 1
    ");

    $stmt->execute(['usuario' => $usuario]);

    $admin = $stmt->fetch();

    if ($admin && password_verify($senha, $admin['senha'])) {

        session_regenerate_id(true);

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_nome'] = $admin['nome'];

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET ultimo_acesso = NOW()
            WHERE id = :id
        ");

        $stmt->execute(['id' => $admin['id']]);

        header('Location: dashboard.php');
        exit;

    } else {
        $mensagem = 'Usuário ou senha incorretos.';
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   
    <link rel="stylesheet" href="assets/css/admin.css">

    <title>Acesso Administrativo | Mimo de Sofia</title>
</head>

<body>

    <header>

        <a href="../index.php">
            <img
                src="../assets/images/logo.png"
                alt="Logo Mimo de Sofia"
            >
        </a>

        <h1>Área Administrativa</h1>

    </header>

    <main>

        <section>

            <h2>Acesso do Administrador</h2>

            <p>
                Entre com seu usuário e senha para acessar
                o painel administrativo.
            </p>

            <form method="POST" action="login.php">

                <div>
                    <label for="usuario">Usuário:</label>

                    <input
                        type="text"
                        id="usuario"
                        name="usuario"
                        placeholder="Digite seu usuário"
                        autocomplete="username"
                        required
                    >
                </div>

                <div>
                    <label for="senha">Senha:</label>

                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div>
                    <button type="submit">
                        Entrar
                    </button>
                </div>

            </form>

            <p>
                <a href="../index.php">Voltar para o site</a>
            </p>

        </section>

    </main>

    <footer>
        <p>Mimo de Sofia - Área Administrativa</p>
    </footer>

</body>
</html>
