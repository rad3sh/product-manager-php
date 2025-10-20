<?php
require_once 'connection.php';

// Deletar lugar
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    if ($delete_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM lugares WHERE id = :id");
        $stmt->execute([':id' => $delete_id]);
    }
    header("Location: lugares.php");
    exit;
}

// Adiciona lugar se enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = trim($_POST['descricao'] ?? '');
    if ($descricao !== '') {
        $stmt = $pdo->prepare("INSERT INTO lugares (descricao) VALUES (:descricao)");
        $stmt->execute([':descricao' => $descricao]);
        header("Location: lugares.php");
        exit;
    }
}

// Busca todos os lugares
$stmt = $pdo->query("SELECT id, descricao FROM lugares ORDER BY descricao ASC");
$lugares = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lugares - Circus Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<nav>
    <ul>
        <li><a href="index.php" title="Reservas">📅</a></li>
        <li><a href="telespectadores.php" title="Telespectadores">👤</a></li>
        <li><a href="lugares.php" title="Lugares" class="active">📍</a></li>
    </ul>
</nav>
<main>
    <h1>Lugares</h1>

    <div>
        <h3>Adicionar Lugar</h3>
        <form method="POST" action="lugares.php">
            <label for="descricao">Descrição:</label>
            <input type="text" id="descricao" name="descricao" required>
            <button type="submit">Adicionar</button>
        </form>
    </div>

    <div>
        <h3>Lista de Lugares</h3>
        <?php if (empty($lugares)): ?>
            <form class="empty-table">Nenhum lugar cadastrado.</form>
        <?php else: ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lugares as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['id']) ?></td>
                        <td><?= htmlspecialchars($l['descricao']) ?></td>
                        <td>
                            <a href="lugares.php?delete=<?= $l['id'] ?>" class="action-btn delete" title="Excluir" onclick="return confirm('Deseja realmente excluir este lugar?');">🗑️</a>
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