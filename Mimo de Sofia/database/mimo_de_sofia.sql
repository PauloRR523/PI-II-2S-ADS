
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `imagens_produtos`;
DROP TABLE IF EXISTS `imagens_site`;
DROP TABLE IF EXISTS `produtos`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `configuracoes`;
DROP TABLE IF EXISTS `categorias`;

CREATE TABLE `categorias` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(100) NOT NULL,
    `slug` varchar(100) NOT NULL,
    `descricao` text DEFAULT NULL,
    `status` enum('ativo','inativo') DEFAULT 'ativo',
    `data_criacao` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categorias` (`id`, `nome`, `slug`, `descricao`, `status`) VALUES
(1, 'Cozinha', 'cozinha', 'Produtos artesanais para cozinha', 'ativo'),
(2, 'Infantil', 'infantil', 'Produtos artesanais infantis', 'ativo'),
(3, 'Dia a Dia', 'dia-a-dia', 'Produtos artesanais para o dia a dia', 'ativo'),
(4, 'Brinquedos Educacionais', 'brinquedo', 'Brinquedos educativos artesanais', 'ativo');

CREATE TABLE `configuracoes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome_site` varchar(150) NOT NULL,
    `descricao_site` text DEFAULT NULL,
    `sobre_mim` text DEFAULT NULL,
    `logo` varchar(255) DEFAULT NULL,
    `email` varchar(150) DEFAULT NULL,
    `telefone` varchar(30) DEFAULT NULL,
    `whatsapp` varchar(30) DEFAULT NULL,
    `instagram` varchar(255) DEFAULT NULL,
    `facebook` varchar(255) DEFAULT NULL,
    `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracoes` (
    `id`,
    `nome_site`,
    `descricao_site`,
    `sobre_mim`,
    `logo`,
    `email`,
    `telefone`,
    `whatsapp`,
    `instagram`,
    `facebook`
) VALUES (
    1,
    'Mimo de Sofia',
    'Produtos artesanais feitos com carinho.',
    NULL,
    'assets/uploads/logos/logo.jpg',
    'teste@email.com',
    '19 00000000',
    '19 00000000',
    'https://www.instagram.com/teste',
    'https://www.facebook.com/teste'
);

CREATE TABLE `produtos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `categoria_id` int(11) NOT NULL,
    `nome` varchar(150) NOT NULL,
    `descricao` text DEFAULT NULL,
    `preco` decimal(10,2) NOT NULL,
    `status` enum('ativo','inativo') DEFAULT 'ativo',
    `data_criacao` timestamp NULL DEFAULT current_timestamp(),
    `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `fk_produtos_categorias` (`categoria_id`),
    CONSTRAINT `fk_produtos_categorias`
        FOREIGN KEY (`categoria_id`)
        REFERENCES `categorias` (`id`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `imagens_produtos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `produto_id` int(11) NOT NULL,
    `caminho` varchar(255) NOT NULL,
    `imagem_principal` tinyint(1) DEFAULT 0,
    `ordem` int(11) DEFAULT 0,
    `data_criacao` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `fk_imagens_produtos` (`produto_id`),
    CONSTRAINT `fk_imagens_produtos`
        FOREIGN KEY (`produto_id`)
        REFERENCES `produtos` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `imagens_site` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tipo` enum('carrossel','categoria','sobre') NOT NULL,
    `categoria_id` int(11) DEFAULT NULL,
    `caminho` varchar(255) NOT NULL,
    `texto_alt` varchar(255) DEFAULT NULL,
    `ordem` int(11) NOT NULL DEFAULT 0,
    `ativo` tinyint(1) NOT NULL DEFAULT 1,
    `data_criacao` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unica_imagem_categoria` (`categoria_id`),
    KEY `idx_tipo_ativo_ordem` (`tipo`, `ativo`, `ordem`),
    CONSTRAINT `fk_imagens_site_categoria`
        FOREIGN KEY (`categoria_id`)
        REFERENCES `categorias` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(100) NOT NULL,
    `usuario` varchar(50) NOT NULL,
    `senha` varchar(255) NOT NULL,
    `status` enum('ativo','inativo') DEFAULT 'ativo',
    `ultimo_acesso` datetime DEFAULT NULL,
    `data_criacao` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (
    `id`,
    `nome`,
    `usuario`,
    `senha`,
    `status`,
    `ultimo_acesso`,
) VALUES (

SET FOREIGN_KEY_CHECKS = 1;

