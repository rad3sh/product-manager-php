<?php
require_once 'connection.php';
if (!isset($pdo) || !$pdo) {
    echo "<!DOCTYPE html><html><head><title>Erro de Conexão</title></head><body style='padding:0; margin:0;background:#18181b;color:#fff;font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;'><h2>Falha na conexão com o banco de dados.</h2></body></html>";
    exit;
}

// Carrega telespectadores
$telespectadores = [];
$stmt = $pdo->query("SELECT id, nome FROM telespectadores ORDER BY nome ASC");
if ($stmt) {
    $telespectadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Carrega lugares
$lugares = [];
$stmt = $pdo->query("SELECT id, descricao FROM lugares ORDER BY descricao ASC");
if ($stmt) {
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Circus Management System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="index.php" title="Reservas">📅</a></li>
            <li><a href="telespectadores.php" title="Telespectadores">👤</a></li>
            <li><a href="lugares.php" title="Lugares">📍</a></li>
        </ul>
    </nav>
    <main>
        <h1>Magik Circus</h1>
         <div>
            <h3>Reservas</h3>
            <?php 
                require_once 'select.php';
            ?>
        </div>
        <div>
            <h3>Adicionar reserva</h3>
            <form action="insert.php" method="POST">
                <label for="telespectador">Telespectador:</label>
                <select id="telespectador" name="telespectador_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($telespectadores as $t): ?>
                        <option value="<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="lugar">Lugar:</label>
                <select id="lugar" name="lugar_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($lugares as $l): ?>
                        <option value="<?= htmlspecialchars($l['id']) ?>"><?= htmlspecialchars($l['descricao']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="data_reserva">Data e Hora:</label>
                <input type="datetime-local" id="data_reserva" name="data_reserva" required>
                <button type="submit">Adicionar reserva</button>
            </form>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 Magik Circus Management System</p>
    </footer>
</body>
</html>