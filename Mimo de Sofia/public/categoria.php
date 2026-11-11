<?php

require_once __DIR__ . '/../app/config/database.php';

$slug = isset($_GET['categoria']) && is_string($_GET['categoria'])
    ? trim($_GET['categoria'])
    : '';

$stmt = $pdo->prepare("
    SELECT id, nome, slug, descricao
    FROM categorias
    WHERE slug = :slug
    AND status = 'ativo'
    LIMIT 1
");

$stmt->execute(['slug' => $slug]);

$categoria = $stmt->fetch();

if (!$categoria) {
    http_response_code(404);
    exit('Categoria não encontrada.');
}

$stmtProdutos = $pdo->prepare("
    SELECT
        p.id,
        p.nome,
        p.descricao,
        p.preco,

        (
            SELECT i.caminho
            FROM imagens_produtos i
            WHERE i.produto_id = p.id
            ORDER BY i.imagem_principal DESC, i.ordem ASC, i.id ASC
            LIMIT 1
        ) AS imagem,

        (
            SELECT GROUP_CONCAT(
                i.caminho
                ORDER BY i.imagem_principal DESC, i.ordem ASC, i.id ASC
                SEPARATOR ','
            )
            FROM imagens_produtos i
            WHERE i.produto_id = p.id
        ) AS fotos

    FROM produtos p

    WHERE p.categoria_id = :categoria_id
    AND p.status = 'ativo'

    ORDER BY p.id DESC
");

$stmtProdutos->execute([
    'categoria_id' => $categoria['id']
]);

$produtos = $stmtProdutos->fetchAll();

$stmtCategorias = $pdo->query("
    SELECT nome, slug
    FROM categorias
    WHERE status = 'ativo'
    ORDER BY id ASC
");

$categorias = $stmtCategorias->fetchAll();

$stmtConfig = $pdo->query("
    SELECT *
    FROM configuracoes
    ORDER BY id ASC
    LIMIT 1
");

$configuracao = $stmtConfig->fetch();

$nomeSite = $configuracao['nome_site'] ?? 'Mimo de Sofia';

$descricaoSite = $configuracao['descricao_site']
    ?? 'Costura afetiva feita à mão, com peças personalizadas.';

$logoSite = $configuracao['logo'] ?? '';

$emailSite = $configuracao['email'] ?? '';
$telefoneSite = $configuracao['telefone'] ?? '';
$whatsappSite = $configuracao['whatsapp'] ?? '';
$instagramSite = $configuracao['instagram'] ?? '';
$facebookSite = $configuracao['facebook'] ?? '';

$numeroWhatsApp = preg_replace('/\D+/', '', $whatsappSite);

if (strlen($numeroWhatsApp) === 10 || strlen($numeroWhatsApp) === 11) {
    $numeroWhatsApp = '55' . $numeroWhatsApp;
}

$linkWhatsApp = !empty($numeroWhatsApp)
    ? 'https://wa.me/' . $numeroWhatsApp
    : '';

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="<?php
            echo htmlspecialchars(
                !empty($categoria['descricao'])
                    ? $categoria['descricao']
                    : $descricaoSite,
                ENT_QUOTES,
                'UTF-8'
            );
        ?>"
    >

    <link rel="stylesheet" href="assets/css/style.css">

    <title>
        <?php echo htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?>
        | <?php echo htmlspecialchars($nomeSite, ENT_QUOTES, 'UTF-8'); ?>
    </title>

</head>

<body>

    <header class="site-header">

        <div class="header-container">

            <a
                class="site-logo"
                href="index.php#inicio"
                aria-label="Voltar para o início"
            >

                <?php if (!empty($logoSite)): ?>

                    <img
                        src="<?php echo htmlspecialchars($logoSite, ENT_QUOTES, 'UTF-8'); ?>"
                        alt="<?php echo htmlspecialchars($nomeSite, ENT_QUOTES, 'UTF-8'); ?>"
                    >

                <?php endif; ?>

            </a>

            <nav class="main-nav" aria-label="Navegação principal">

                <ul class="nav-list">

                    <li class="nav-item">
                        <a class="nav-link" href="index.php#inicio">
                            Início
                        </a>
                    </li>

                    <li class="nav-item-dropdown">

                        <a
                            class="nav-link"
                            href="index.php#produtos"
                            aria-current="page"
                        >
                            Produtos
                        </a>

                        <ul class="produto-dropdown">

                            <?php foreach ($categorias as $item): ?>

                                <li class="produto-dropdown-item">

                                    <a href="categoria.php?categoria=<?php echo urlencode($item['slug']); ?>">

                                        <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>

                                    </a>

                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="index.php#sobre">
                            Sobre mim
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contato">
                            Contatos
                        </a>
                    </li>

                </ul>

            </nav>

        </div>

    </header>


    <main>

        <section class="categoria-section">

            <header class="section-header">

                <h1 class="section-title">

                    <?php echo htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?>

                </h1>

                <p class="section-descricao">

                    <?php echo htmlspecialchars($categoria['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>

                </p>

            </header>

            <div class="container-cards">

                <?php if (count($produtos) > 0): ?>

                    <?php foreach ($produtos as $produto): ?>

                        <article
                            class="card"
                            data-fotos="<?php echo htmlspecialchars($produto['fotos'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-descricao="<?php echo htmlspecialchars($produto['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        >

                            <?php if (!empty($produto['imagem'])): ?>

                                <img
                                    src="<?php echo htmlspecialchars($produto['imagem'], ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>"
                                    loading="lazy"
                                >

                            <?php endif; ?>

                            <h2>
                                <?php echo htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>
                            </h2>

                            <p>
                                <?php echo htmlspecialchars($produto['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <p>
                                R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                            </p>

                            <button type="button" class="button-mais">
                                Ver produto
                            </button>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p>Nenhum produto disponível nesta categoria no momento.</p>

                <?php endif; ?>

            </div>

        </section>


        <!-- FORMULÁRIO DE CONTATO -->

        <section class="contato-section" id="contato">

            <div class="section-container">

                <header class="section-header">

                    <h2 class="section-title">
                        Vamos conversar?
                    </h2>

                    <p class="section-descricao">
                        Transforme sua ideia em um lindo mimo de tecido.
                    </p>

                </header>

                <form class="contato-form" action="contato.php" method="post">

                    <div class="form-campo">

                        <label class="form-label" for="nome">
                            Seu Nome
                        </label>

                        <input
                            class="form-input"
                            type="text"
                            id="nome"
                            name="nome"
                            autocomplete="name"
                            placeholder="Como posso te chamar?"
                            required
                        >

                    </div>

                    <div class="form-campo">

                        <label class="form-label" for="whatsapp">
                            Seu WhatsApp
                        </label>

                        <input
                            class="form-input"
                            type="tel"
                            id="whatsapp"
                            name="whatsapp"
                            autocomplete="tel"
                            placeholder="(19) 90000-0000"
                            required
                        >

                    </div>

                    <div class="form-campo">

                        <label class="form-label" for="categoria">
                            Categoria preferida
                        </label>

                        <select
                            class="form-input"
                            id="categoria"
                            name="categoria"
                        >

                            <option value="">
                                Escolha uma categoria
                            </option>

                            <?php foreach ($categorias as $item): ?>

                                <option
                                    value="<?php echo htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo $item['slug'] === $categoria['slug'] ? 'selected' : ''; ?>
                                >

                                    <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>

                                </option>

                            <?php endforeach; ?>

                            <option value="outro">
                                Outro / ainda não sei
                            </option>

                        </select>

                    </div>

                    <div class="form-campo">

                        <label class="form-label" for="mensagem">
                            Sua ideia para encomenda
                        </label>

                        <textarea
                            class="form-textarea"
                            id="mensagem"
                            name="mensagem"
                            rows="6"
                            placeholder="Me conte um pouco sobre o que você deseja..."
                            required
                        ></textarea>

                    </div>

                    <button class="button-submit" type="submit">
                        Quero fazer um orçamento
                    </button>

                </form>

            </div>

        </section>

    </main>


     <footer class="site-footer">
        <div class="footer-container">

            <a class="site-logo-footer" href="#inicio" aria-label="Voltar para o início">
                <img
                    src="<?php echo htmlspecialchars($logoSite); ?>"
                    alt="<?php echo htmlspecialchars($nomeSite); ?>">
            </a>

            <nav class="footer-nav" aria-label="Navegação do rodapé">
                <ul class="footer-nav-list">
                    <li><a href="index.php#inicio">Início</a></li>
                    <li><a href="index.php#produtos">Produtos</a></li>
                    <li><a href="index.php#sobre">Sobre mim</a></li>
                    <li><a href="#contato">Contatos</a></li>
                </ul>
            </nav>

            <div class="social-links">

                <?php if (!empty($instagramSite)): ?>
                    <a href="<?= htmlspecialchars($instagramSite) ?>"
                       class="social-link"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="5"/>
                            <circle cx="12" cy="12" r="4"/>
                            <circle cx="18" cy="6" r="1" fill="currentColor" stroke="none"/>
                        </svg>
                    </a>
                <?php endif; ?>

                <?php if (!empty($facebookSite)): ?>
                    <a href="<?= htmlspecialchars($facebookSite) ?>"
                       class="social-link"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="Facebook">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M13.4 21v-8h2.7l.4-3.1h-3.1v-2c0-.9.3-1.5 1.6-1.5h1.7V3.6c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.5-4 4.2v2.2H7.6V13h2.7v8h3.1z"/>
                        </svg>
                    </a>
                <?php endif; ?>

                <?php if (!empty($linkWhatsApp)): ?>
                    <a href="<?= htmlspecialchars($linkWhatsApp) ?>"
                       class="social-link"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="WhatsApp">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4A8 8 0 1 1 20 11.5z"/>
                            <path d="M9 8.5c.3-.5.6-.5.9-.5l.7 1.7-.8 1c.7 1.3 1.5 2 2.8 2.7l.9-.9 1.7.8c0 .3 0 .7-.4 1-.5.5-1.2.7-2 .5-2.2-.6-4.6-3-5.2-5.2-.2-.8 0-1.5.5-2.1z"/>
                        </svg>
                    </a>
                <?php endif; ?>

                <?php if (!empty($emailSite)): ?>
                    <a href="mailto:<?= htmlspecialchars($emailSite) ?>"
                       class="social-link"
                       aria-label="E-mail">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="m4 7 8 6 8-6"/>
                        </svg>
                    </a>
                <?php endif; ?>

                <?php if (!empty($telefoneSite)): ?>
                    <a href="tel:<?= htmlspecialchars(preg_replace('/\D+/', '', $telefoneSite)) ?>"
                       class="social-link"
                       aria-label="Telefone">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="M7 3h3l2 5-2 1.5a14 14 0 0 0 4.5 4.5L16 12l5 2v3c0 1.1-.9 2-2 2C10.5 19 5 13.5 5 5c0-1.1.9-2 2-2z"/>
                        </svg>
                    </a>
                <?php endif; ?>

            </div>

        </div>
    </footer>


    <!-- MODAL DE PRODUTO -->

    <dialog
        class="modal-produto"
        id="modal-produto"
        aria-labelledby="modal-titulo"
    >

        <button
            class="modal-fechar"
            type="button"
            aria-label="Fechar"
        ></button>

        <div class="modal-conteudo">

            <div class="modal-galeria">

                <figure class="modal-imagem-principal">

                    <img id="modal-imagem" src="" alt="">

                    <button
                        class="modal-anterior"
                        type="button"
                        aria-label="Foto anterior"
                    ></button>

                    <button
                        class="modal-proxima"
                        type="button"
                        aria-label="Próxima foto"
                    ></button>

                    <span
                        class="modal-contador"
                        aria-live="polite"
                    ></span>

                </figure>

                <ul class="modal-miniaturas"></ul>

            </div>

            <div class="modal-info">

                <h2 class="modal-titulo" id="modal-titulo"></h2>

                <p class="modal-descricao"></p>

                <button
                    class="button-encomenda modal-encomendar"
                    type="button"
                >
                    Quero encomendar
                </button>

            </div>

        </div>

    </dialog>


    <script src="assets/js/produtos.js"></script>

</body>

</html>
```
