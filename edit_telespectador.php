<?php
require_once 'connection.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "Telespectador inválido.";
    exit;
}

// Busca dados atuais do telespectador
$stmt = $pdo->prepare("SELECT * FROM telespectadores WHERE id = :id");
$stmt->execute([':id' => $id]);
$telespectador = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$telespectador) {
    echo "Telespectador não encontrado.";
    exit;
}

// Atualiza telespectador se enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    if ($nome !== '') {
        $stmt = $pdo->prepare("UPDATE telespectadores SET nome = :nome WHERE id = :id");
        $stmt->execute([':nome' => $nome, ':id' => $id]);
        header("Location: telespectadores.php");
        exit;
    } else {
        $erro = "O nome não pode ser vazio.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Telespectador</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<nav>
    <ul>
        <li><a href="index.php" title="Reservas">📅</a></li>
        <li><a href="telespectadores.php" title="Telespectadores" class="active">👤</a></li>
        <li><a href="lugares.php" title="Lugares">📍</a></li>
    </ul>
</nav>
<main>
    <h1>Editar Telespectador</h1>
    <?php if (!empty($erro)): ?>
        <div class="error-msg"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($telespectador['nome']) ?>" required>
        <button type="submit">Salvar Alterações</button>
        <a href="telespectadores.php" class="action-btn">Cancelar</a>
    </form>
</main>
<footer>
    <p>&copy; 2023 Circus Management System</p>
</footer>