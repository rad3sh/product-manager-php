<?php
session_start();

// defina $required_action antes de incluir este arquivo (ex.: 'produtos_delete')
// se não definido, usa o nome do script
if (!isset($required_action) || $required_action === '') {
    $required_action = basename($_SERVER['SCRIPT_NAME']);
}

// TTL em segundos (0 = sem expiração). Ajuste se quiser expirar autorizações.
$TTL_SECONDS = 0;//300;

$ok = false;
if (!empty($_SESSION['authorized_actions'][$required_action])) {
    $ts = (int) $_SESSION['authorized_actions'][$required_action];
    if ($TTL_SECONDS <= 0 || (time() - $ts) <= $TTL_SECONDS) {
        $ok = true;
    } else {
        unset($_SESSION['authorized_actions'][$required_action]);
    }
}

if (!$ok) {
    // se não autorizado, redireciona para a página de autenticação (fallback)
    $next = $_SERVER['REQUEST_URI'];
    header('Location: index.php?next=');
    //header('Location: auth.php?next=' . urlencode($next) . '&action=' . urlencode($required_action));
    exit;
}