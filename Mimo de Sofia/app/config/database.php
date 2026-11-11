
<?php

$configFile = __DIR__ . '/database.local.php';

if (!file_exists($configFile)) {
    error_log('Arquivo de configuração do banco não encontrado.');
    exit('Não foi possível conectar ao banco de dados.');
}

$config = require $configFile;

try {

    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
        $config['username'],
        $config['password']
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {

    error_log($e->getMessage());
    exit('Não foi possível conectar ao banco de dados.');

}
