<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Substitua pelo hash gerado localmente:
// php -r "echo password_hash('SUA_SENHA_AQUI', PASSWORD_DEFAULT);"
$PASSWORD_HASH = '$2y$10$.1H15lUS0CWzi5HbXueRbOlicaMb77OFdk4gzfYIABlvDiIwmp7Eq';

$pw = $_POST['password'] ?? '';
$action = $_POST['action'] ?? '';
$next = $_POST['next'] ?? 'index.php';

// simples validação
if ($pw === '' || $action === '') {
    echo json_encode(['ok' => false, 'msg' => 'Dados incompletos.']);
    exit;
}

if (password_verify($pw, $PASSWORD_HASH)) {
    if (!isset($_SESSION['authorized_actions']) || !is_array($_SESSION['authorized_actions'])) {
        $_SESSION['authorized_actions'] = [];
    }
    // armazena timestamp (pode usar apenas true se preferir sem expiração)
    $_SESSION['authorized_actions'][$action] = time();
    // retorno: redirecionar para next
    echo json_encode(['ok' => true, 'redirect' => $next]);
    exit;
} else {
    echo json_encode(['ok' => false, 'msg' => 'Senha incorreta.']);
    exit;
}