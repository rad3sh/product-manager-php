<?php
require_once 'connection.php';

// Busca a reserva pelo ID
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "Reserva inválida.";
    exit;
}

// Carrega telespectadores e lugares para os selects
$telespectadores = $pdo->query("SELECT id, nome FROM telespectadores ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$lugares = $pdo->query("SELECT id, descricao FROM lugares ORDER BY descricao ASC")->fetchAll(PDO::FETCH_ASSOC);

// Busca dados atuais da reserva
$stmt = $pdo->prepare("SELECT * FROM reservas WHERE id = :id");
$stmt->execute([':id' => $id]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reserva) {
    echo "Reserva não encontrada.";
    exit;
}

// Atualiza reserva se enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telespectador_id = intval($_POST['telespectador_id'] ?? 0);
    $lugar_id = intval($_POST['lugar_id'] ?? 0);
    $data_reserva = trim($_POST['data_reserva'] ?? '');

    if ($telespectador_id > 0 && $lugar_id > 0 && $data_reserva !== '') {
        $sql = "UPDATE reservas SET telespectador_id = :telespectador_id, lugar_id = :lugar_id, data_reserva = :data_reserva WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([
                ':telespectador_id' => $telespectador_id,
                ':lugar_id' => $lugar_id,
                ':data_reserva' => $data_reserva,
                ':id' => $id
            ]);
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = "Já existe uma reserva para este lugar e horário.";
            } else {
                $erro = "Erro ao atualizar reserva: " . $e->getMessage();
            }
        }
    } else {
        $erro = "Preencha todos os campos corretamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Reserva</title>
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
    <h1>Editar Reserva</h1>
    <?php if (!empty($erro)): ?>
        <div class="error-msg"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST">
        <label for="telespectador_id">Telespectador:</label>
        <select id="telespectador_id" name="telespectador_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($telespectadores as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $t['id'] == $reserva['telespectador_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="lugar_id">Lugar:</label>
        <select id="lugar_id" name="lugar_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($lugares as $l): ?>
                <option value="<?= $l['id'] ?>" <?= $l['id'] == $reserva['lugar_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['descricao']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="data_reserva">Data e Hora:</label>
        <input type="datetime-local" id="data_reserva" name="data_reserva"
               value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($reserva['data_reserva']))) ?>" required>

        <button type="submit">Salvar Alterações</button>
        <a href="index.php" class="action-btn">Cancelar</a>
    </form>
</main>
<footer>
    <p>&copy; 2023 Circus Management System</p>
</footer>
</body>
</html>