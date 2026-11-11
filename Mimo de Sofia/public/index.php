
<?php

require_once __DIR__ . '/../app/config/database.php';

$sql = "SELECT id, nome, slug, descricao
        FROM categorias
        WHERE status = 'ativo'
        ORDER BY id ASC";

$stmt = $pdo->query($sql);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sqlConfig = "SELECT *
              FROM configuracoes
              ORDER BY id ASC
              LIMIT 1";

$stmtConfig = $pdo->query($sqlConfig);
$configuracao = $stmtConfig->fetch(PDO::FETCH_ASSOC);

$nomeSite = $configuracao['nome_site'] ?? 'Mimo de Sofia';

$descricaoSite = $configuracao['descricao_site']
    ?? 'Costura afetiva feita à mão, com peças personalizadas.';

$sobreMim = $configuracao['sobre_mim'] ?? '';

$logoSite = !empty($configuracao['logo'])
    ? $configuracao['logo']
    : 'assets/images/logo.png';

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


// Descrições alternativas para categorias sem descrição cadastrada.

$descricoesPadrao = [
    'cozinha' => 'Peças artesanais para deixar sua cozinha mais bonita, organizada e acolhedora.',
    'infantil' => 'Peças personalizadas e feitas à mão para acompanhar momentos especiais da infância.',
    'dia-a-dia' => 'Produtos artesanais que unem criatividade, praticidade e carinho para o cotidiano.',
    'brinquedo' => 'Mimos artesanais e personalizados para bebês e crianças, com criatividade e carinho.'
];

$descricoesCategorias = [];

foreach ($categorias as $categoria) {
    $slug = $categoria['slug'];

    $descricao = trim($categoria['descricao'] ?? '');

    $descricoesCategorias[$slug] = !empty($descricao)
        ? $descricao
        : ($descricoesPadrao[$slug] ?? '');
}


// Imagens do carrossel.

$sqlCarrossel = "SELECT caminho, texto_alt
                 FROM imagens_site
                 WHERE tipo = 'carrossel'
                 AND ativo = 1
                 ORDER BY ordem ASC, id ASC";

$stmtCarrossel = $pdo->query($sqlCarrossel);
$imagensCarrossel = $stmtCarrossel->fetchAll(PDO::FETCH_ASSOC);

if (empty($imagensCarrossel)) {
    $imagensCarrossel = [
        [
            'caminho' => 'assets/images/cozinha/bolsa-marmita1.jpeg',
            'texto_alt' => 'Peças artesanais do Ateliê Mimo de Sofia'
        ],
        [
            'caminho' => 'assets/images/cozinha/bolsa-marmita2.jpeg',
            'texto_alt' => 'Peças artesanais do Ateliê Mimo de Sofia'
        ],
        [
            'caminho' => 'assets/images/cozinha/bolsa-marmita3.jpeg',
            'texto_alt' => 'Peças artesanais do Ateliê Mimo de Sofia'
        ]
    ];
}


// Imagens dos cards de categorias.

$imagensCategorias = [
    'cozinha' => [
        'caminho' => 'assets/images/cozinha/jogo-de-cozinha1.jpeg',
        'texto_alt' => 'Imagem para Cozinha'
    ],
    'infantil' => [
        'caminho' => 'assets/images/categoria-2.jpg',
        'texto_alt' => 'Imagem para Infantil'
    ],
    'dia-a-dia' => [
        'caminho' => 'assets/images/categoria-3.jpg',
        'texto_alt' => 'Imagem para Dia a Dia'
    ],
    'brinquedo' => [
        'caminho' => 'assets/images/categoria-4.jpg',
        'texto_alt' => 'Imagem para Brinquedos Educacionais'
    ]
];

$sqlImagensCategorias = "
    SELECT c.slug, i.caminho, i.texto_alt
    FROM imagens_site AS i
    INNER JOIN categorias AS c ON c.id = i.categoria_id
    WHERE i.tipo = 'categoria'
    AND i.ativo = 1
    AND c.status = 'ativo'
";

$stmtImagensCategorias = $pdo->query($sqlImagensCategorias);
$imagensCategoriasBanco = $stmtImagensCategorias->fetchAll(PDO::FETCH_ASSOC);

foreach ($imagensCategoriasBanco as $imagemCategoria) {
    $slug = $imagemCategoria['slug'];

    if (isset($imagensCategorias[$slug])) {
        $imagensCategorias[$slug] = [
            'caminho' => $imagemCategoria['caminho'],
            'texto_alt' => !empty($imagemCategoria['texto_alt'])
                ? $imagemCategoria['texto_alt']
                : $imagensCategorias[$slug]['texto_alt']
        ];
    }
}


// Imagem da seção Sobre mim.

$sqlSobre = "SELECT caminho, texto_alt
             FROM imagens_site
             WHERE tipo = 'sobre'
             AND ativo = 1
             ORDER BY ordem ASC, id ASC
             LIMIT 1";

$stmtSobre = $pdo->query($sqlSobre);
$imagemSobre = $stmtSobre->fetch(PDO::FETCH_ASSOC);

if (!$imagemSobre) {
    $imagemSobre = [
        'caminho' => 'assets/images/sobre.jpg',
        'texto_alt' => 'Foto da artesã do Ateliê Mimo de Sofia'
    ];
} elseif (empty($imagemSobre['texto_alt'])) {
    $imagemSobre['texto_alt'] = 'Foto da artesã do Ateliê Mimo de Sofia';
}


// Dados do carrossel enviados ao JavaScript.

$jsonImagensCarrossel = json_encode(
    $imagensCarrossel,
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="<?php echo htmlspecialchars($descricaoSite, ENT_QUOTES, 'UTF-8'); ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Allura&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">

    <title><?php echo htmlspecialchars($nomeSite, ENT_QUOTES, 'UTF-8'); ?></title>
</head>

<body>

    <header class="site-header">
        <div class="header-container">

            <a class="site-logo" href="#inicio" aria-label="Voltar para o início">
                <img
                    src="<?php echo htmlspecialchars($logoSite, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?php echo htmlspecialchars($nomeSite, ENT_QUOTES, 'UTF-8'); ?>">
            </a>

            <nav class="main-nav" aria-label="Navegação principal">
                <ul class="nav-list">

                    <li class="nav-item">
                        <a class="nav-link" href="#inicio" aria-current="page">Início</a>
                    </li>

                    <li class="nav-item-dropdown">

                        <a class="nav-link" href="#produtos">Produtos</a>

                        <ul class="produto-dropdown">

                            <?php foreach ($categorias as $categoria): ?>

                                <li class="produto-dropdown-item">
                                    <a href="categoria.php?categoria=<?php echo urlencode($categoria['slug']); ?>">
                                        <?php echo htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#sobre">Sobre mim</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#contato">Contatos</a>
                    </li>

                </ul>
            </nav>

        </div>
    </header>


    <main>

        <section class="hero-section" id="inicio">
            <div class="hero-container">

                <div class="hero-content">
                    <p class="hero-subtitle">Costura que abraça a</p>

                    <h1 class="hero-title">Alma</h1>

                    <p class="hero-descricao">
                        Criações únicas, feitas à mão com amor, dedicação e muito carinho
                        para transformar momentos em memórias inesquecíveis.
                    </p>

                    <a class="button-encomenda" href="#contato">
                        Faça sua encomenda
                    </a>
                </div>

                <div class="hero-media">

                    <button
                        class="hero-control-anterior"
                        type="button"
                        id="button-anterior"
                        aria-label="Imagem anterior">
                    </button>

                    <figure class="hero-image">
                        <img
                            id="hero-image"
                            src="<?php echo htmlspecialchars($imagensCarrossel[0]['caminho'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($imagensCarrossel[0]['texto_alt'] ?: 'Peças artesanais do Ateliê Mimo de Sofia', ENT_QUOTES, 'UTF-8'); ?>"
                            data-imagens="<?php echo htmlspecialchars($jsonImagensCarrossel, ENT_QUOTES, 'UTF-8'); ?>">
                    </figure>

                    <button
                        class="hero-control-proxima"
                        type="button"
                        id="button-proxima"
                        aria-label="Próxima imagem">
                    </button>

                </div>

            </div>
        </section>


        <section class="produtos-section" id="produtos">
            <div class="section-container">

                <header class="section-header">
                    <h2 class="section-title">Produtos</h2>

                    <p class="section-descricao">
                        Cada retalho conta uma história. Descubra produtos artesanais únicos
                        ou entre em contato para personalizar uma peça com suas cores e o seu estilo.
                    </p>
                </header>

                <div class="produtos-lista">

                    <article class="produto-card-image-esquerda">
                        <figure>
                            <img
                                src="<?php echo htmlspecialchars($imagensCategorias['cozinha']['caminho'], ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($imagensCategorias['cozinha']['texto_alt'], ENT_QUOTES, 'UTF-8'); ?>">
                        </figure>

                        <div class="produto-card-content-1" id="cozinha">
                            <h3 class="produto-card-title">Cozinha</h3>

                            <p class="produto-card-descricao">
                                <?php echo htmlspecialchars($descricoesCategorias['cozinha'] ?? $descricoesPadrao['cozinha'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <a class="button-mais" href="categoria.php?categoria=cozinha" aria-label="Mais produtos de Cozinha">Mais Produtos</a>
                        </div>
                    </article>

                    <article class="produto-card-image-direita">

                        <div class="produto-card-content-2" id="infantil">
                            <h3 class="produto-card-title">Infantil</h3>

                            <p class="produto-card-descricao">
                                <?php echo htmlspecialchars($descricoesCategorias['infantil'] ?? $descricoesPadrao['infantil'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <a class="button-mais" href="categoria.php?categoria=infantil" aria-label="Mais produtos Infantil">Mais Produtos</a>
                        </div>

                        <figure>
                            <img
                                src="<?php echo htmlspecialchars($imagensCategorias['infantil']['caminho'], ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($imagensCategorias['infantil']['texto_alt'], ENT_QUOTES, 'UTF-8'); ?>">
                        </figure>
                    </article>

                    <article class="produto-card-image-esquerda">
                        <figure>
                            <img
                                src="<?php echo htmlspecialchars($imagensCategorias['dia-a-dia']['caminho'], ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($imagensCategorias['dia-a-dia']['texto_alt'], ENT_QUOTES, 'UTF-8'); ?>">
                        </figure>

                        <div class="produto-card-content" id="dia">
                            <h3 class="produto-card-title">Dia a Dia</h3>

                            <p class="produto-card-descricao">
                                <?php echo htmlspecialchars($descricoesCategorias['dia-a-dia'] ?? $descricoesPadrao['dia-a-dia'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <a class="button-mais" href="categoria.php?categoria=dia-a-dia" aria-label="Mais produtos de Dia a Dia">Mais Produtos</a>
                        </div>
                    </article>

                    <article class="produto-card-image-direita">

                        <div class="produto-card-content" id="brinquedos">
                            <h3 class="produto-card-title">Brinquedos Educacionais</h3>

                            <p class="produto-card-descricao">
                                <?php echo htmlspecialchars($descricoesCategorias['brinquedo'] ?? $descricoesPadrao['brinquedo'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <a class="button-mais" href="categoria.php?categoria=brinquedo" aria-label="Mais produtos de Brinquedos Educacionais">Mais Produtos</a>
                        </div>

                        <figure>
                            <img
                                src="<?php echo htmlspecialchars($imagensCategorias['brinquedo']['caminho'], ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($imagensCategorias['brinquedo']['texto_alt'], ENT_QUOTES, 'UTF-8'); ?>">
                        </figure>
                    </article>

                </div>
            </div>
        </section>


        <section class="sobre-section" id="sobre">
            <div class="section-container">

                <div class="sobre-introduction">

                    <figure class="sobre-image">
                        <img
                            src="<?php echo htmlspecialchars($imagemSobre['caminho'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($imagemSobre['texto_alt'], ENT_QUOTES, 'UTF-8'); ?>">
                    </figure>

                    <div class="sobre-content">
                        <h2 class="sobre-title">Quem sou eu?</h2>

                        <?php if (!empty(trim($sobreMim))): ?>
                            <p class="sobre-description">
                                <?php echo nl2br(htmlspecialchars($sobreMim, ENT_QUOTES, 'UTF-8')); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                </div>


                <div class="sobre-historia">

                    <header class="section-header">
                        <h2 class="section-title">O que é a Costura Afetiva?</h2>
                    </header>

                    <p class="about-historia-text-1">
                        No Ateliê Mimo de Sofia, acreditamos que cada pedacinho de tecido
                        tem uma história para contar. A costura afetiva vai muito além de
                        unir retalhos: é arte de materializar sentimentos em peças artesanais.
                    </p>

                    <p class="sobre-historia-text-2">
                        Nossa especialidade é costurar carinho, presentes, personalidade,
                        utilidade e memórias delicadas, criando peças únicas e cheias de significado.
                    </p>

                    <ul class="sobre-highlights">
                        <li class="sobre-highlight">
                            Brinquedos de tecido afetivos, lúdicos e seguros.
                        </li>
                        <li class="sobre-highlight">
                            Brinquedos pedagógicos como ferramenta para desenvolvimento infantil.
                        </li>
                        <li class="sobre-highlight">
                            Bordados personalizados e costura criativa exclusiva.
                        </li>
                    </ul>

                    <a class="button-contato" href="#contato">
                        Quero encomendar minha peça
                    </a>

                </div>

            </div>
        </section>


        <section class="contato-section" id="contato">
            <div class="section-container">

                <header class="section-header">
                    <h2 class="section-title">Vamos conversar?</h2>

                    <p class="section-descricao">
                        Transforme sua ideia em um lindo mimo de tecido.
                    </p>
                </header>


                <form class="contato-form" action="contato.php" method="post">

                    <div class="form-campo">
                        <label class="form-label" for="nome">Seu Nome</label>

                        <input
                            class="form-input"
                            type="text"
                            id="nome"
                            name="nome"
                            autocomplete="name"
                            placeholder="Como posso te chamar?"
                            required>
                    </div>


                    <div class="form-campo">
                        <label class="form-label" for="whatsapp">Seu WhatsApp</label>

                        <input
                            class="form-input"
                            type="tel"
                            id="whatsapp"
                            name="whatsapp"
                            autocomplete="tel"
                            placeholder="(19) 90000-0000"
                            required>
                    </div>


                    <div class="form-campo">
                        <label class="form-label" for="categoria">Categoria preferida</label>

                        <select
                            class="form-input"
                            id="categoria"
                            name="categoria">

                            <option value="" selected>Escolha uma categoria</option>

                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>

                            <option value="outro">Outro / ainda não sei</option>

                        </select>

                    </div>


                    <div class="form-campo">
                        <label class="form-label" for="mensagem">Sua ideia para encomenda</label>

                        <textarea
                            class="form-textarea"
                            id="mensagem"
                            name="mensagem"
                            rows="6"
                            placeholder="Me conte um pouco sobre o que você deseja..."
                            required></textarea>
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
                    src="<?php echo htmlspecialchars($logoSite, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?php echo htmlspecialchars($nomeSite, ENT_QUOTES, 'UTF-8'); ?>">
            </a>

            <nav class="footer-nav" aria-label="Navegação do rodapé">
                <ul class="footer-nav-list">
                    <li><a href="#inicio">Início</a></li>
                    <li><a href="#produtos">Produtos</a></li>
                    <li><a href="#sobre">Sobre mim</a></li>
                    <li><a href="#contato">Contatos</a></li>
                </ul>
            </nav>

            <div class="social-links">

                <?php if (!empty($instagramSite)): ?>
                    <a href="<?= htmlspecialchars($instagramSite, ENT_QUOTES, 'UTF-8') ?>"
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
                    <a href="<?= htmlspecialchars($facebookSite, ENT_QUOTES, 'UTF-8') ?>"
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
                    <a href="<?= htmlspecialchars($linkWhatsApp, ENT_QUOTES, 'UTF-8') ?>"
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
                    <a href="mailto:<?= htmlspecialchars($emailSite, ENT_QUOTES, 'UTF-8') ?>"
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
                    <a href="tel:<?= htmlspecialchars(preg_replace('/\D+/', '', $telefoneSite), ENT_QUOTES, 'UTF-8') ?>"
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

    <script src="assets/js/script.js"></script>

</body>
</html>

