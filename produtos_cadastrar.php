<?php
require_once 'connection.php';
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
            <h3>Cadastrar produto</h3>


            <a href="/" data-link="">← Voltar</a>
            <form action="insert.php" method="POST">
                <label for="produto_id">ID:</label>
                <input type="text" id="produto_id" name="produto_id" required>

                <label for="referencia">Referência:</label>
                <input type="text" id="referencia" name="referencia" required>

                <label for="quantidade">Quantidade:</label>
                <input type="number" id="quantidade" name="quantidade" min="0" step="1" required>

                <label for="local">Local:</label>
                <input type="text" id="local" name="local" required>

                <button type="submit">Adicionar produto</button>
            </form>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 Magik Circus Management System</p>
    </footer>
</body>
</html>


