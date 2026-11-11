
<?php

require_once __DIR__ . '/../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$nome = is_string($_POST['nome'] ?? null)
    ? trim($_POST['nome'])
    : '';

$whatsappCliente = is_string($_POST['whatsapp'] ?? null)
    ? trim($_POST['whatsapp'])
    : '';

$categoria = is_string($_POST['categoria'] ?? null)
    ? trim($_POST['categoria'])
    : '';

$mensagem = is_string($_POST['mensagem'] ?? null)
    ? trim($_POST['mensagem'])
    : '';

if ($nome === '' || $whatsappCliente === '' || $mensagem === '') {
    http_response_code(400);
    exit('Preencha todos os campos obrigatórios.');
}

if (
    strlen($nome) > 200 ||
    strlen($whatsappCliente) > 30 ||
    strlen($categoria) > 200 ||
    strlen($mensagem) > 4000
) {
    http_response_code(400);
    exit('Um ou mais campos ultrapassaram o limite permitido.');
}

$stmt = $pdo->query("
    SELECT whatsapp
    FROM configuracoes
    ORDER BY id ASC
    LIMIT 1
");

$configuracao = $stmt->fetch(PDO::FETCH_ASSOC);

$whatsappLoja = $configuracao['whatsapp'] ?? '';

$whatsappLoja = preg_replace('/\D/', '', $whatsappLoja);

if (strlen($whatsappLoja) === 10 || strlen($whatsappLoja) === 11) {
    $whatsappLoja = '55' . $whatsappLoja;
}

if (
    strlen($whatsappLoja) < 12 ||
    strlen($whatsappLoja) > 13
) {
    http_response_code(500);
    exit('O WhatsApp da loja não está configurado corretamente.');
}

$texto = "Olá! Gostaria de fazer uma encomenda pelo site Mimo de Sofia.\n\n";

$texto .= "Nome: {$nome}\n";
$texto .= "WhatsApp para contato: {$whatsappCliente}\n";

if ($categoria !== '') {
    $texto .= "Categoria: {$categoria}\n";
}

$texto .= "\nMensagem:\n{$mensagem}";

$url = 'https://wa.me/' . $whatsappLoja . '?text=' . rawurlencode($texto);

header('Location: ' . $url, true, 303);
exit;

