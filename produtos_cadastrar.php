<?php
require_once 'connection.php';
$required_action = 'produtos_cadastrar';
require_once __DIR__ . '/auth_check.php';
if (!isset($pdo) || !$pdo) {
    echo "<!DOCTYPE html><html><head><title>Erro de Conexão</title></head><body style='padding:0; margin:0;background:#18181b;color:#fff;font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;'><h2>Falha na conexão com o banco de dados.</h2></body></html>";
    exit;
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
    <?php include __DIR__ . '/partials/nav.php'; ?>
    <main>
        <h1>Gestor de Estoque</h1>
        <div>
            <h3>Cadastrar produto</h3>

            <a href="index.php" class="btn-back action-btn secondary" aria-label="Voltar à página inicial">Voltar</a>

            <form action="insert.php" method="POST">
<label for="produto_id">ID:</label>
<input
  type="text"
  id="produto_id"
  name="produto_id"
  placeholder="Opcional — será gerado automaticamente se vazio (6 dígitos se informado)"
  inputmode="numeric"
  pattern="\d{6}"
  maxlength="6"
  title="Opcional: informe exatamente 6 dígitos (ex: 012345)"
>
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required maxlength="60" title="Máximo 60 caracteres">

                <label for="referencia">Referência:</label>
                <input type="text" id="referencia" name="referencia" required maxlength="50" title="Máximo 50 caracteres">

                <label for="quantidade">Quantidade:</label>
                <input type="number" id="quantidade" name="quantidade" min="0" step="1" required>

                <label for="local">Local:</label>
                <input type="text" id="local" name="local" required maxlength="50" title="Máximo 50 caracteres">

                <div style="display:inline-flex; gap:8px; margin-top:0.75rem;">
                    <button type="submit" class="action-btn edit">Adicionar produto</button>
                </div>
            </form>
        </div>
    </main>
    <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>