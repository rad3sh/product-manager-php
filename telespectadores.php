<?php
require_once 'connection.php';

// Deletar telespectador
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    if ($delete_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM telespectadores WHERE id = :id");
        $stmt->execute([':id' => $delete_id]);
    }
    header("Location: telespectadores.php");
    exit;
}

// Adiciona telespectador se enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    $nome = trim($_POST['nome']);
    if ($nome !== '') {
        $stmt = $pdo->prepare("INSERT INTO telespectadores (nome) VALUES (:nome)");
        $stmt->execute([':nome' => $nome]);
    }
    header("Location: telespectadores.php");
    exit;
}

// Busca todos os telespectadores
$stmt = $pdo->query("SELECT id, nome FROM telespectadores ORDER BY nome ASC");
$telespectadores = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Telespectadores - Circus Management</title>
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
    <h1>Telespectadores</h1>

    <div>
        <h3>Adicionar Telespectador</h3>
        <form method="POST" action="telespectadores.php">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>
            <button type="submit">Adicionar</button>
        </form>
    </div>

    <div>
        <h3>Lista de Telespectadores</h3>
        <?php if (empty($telespectadores)): ?>
            <form class="empty-table">Nenhum telespectador cadastrado.</form>
        <?php else: ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($telespectadores as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['id']) ?></td>
                        <td><?= htmlspecialchars($t['nome']) ?></td>
                        <td>
                            <a href="edit_telespectador.php?id=<?= $t['id'] ?>" class="action-btn edit" title="Editar">✏️</a>
                            <a href="telespectadores.php?delete=<?= $t['id'] ?>" class="action-btn delete" title="Excluir" onclick="return confirm('Deseja realmente excluir este telespectador?');">🗑️</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<footer>
    <p>&copy; 2023 Circus Management System</p>
</footer>
</body>