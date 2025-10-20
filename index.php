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
        <h1>Gestor de Estoque</h1>
         <div>
            <h3>Gestão de Produtos e Auditoria</h3>
            <div class="options">
                <ul class="actions" role="menu" aria-label="Opções de produtos e auditoria">
                    <li role="none"><a role="menuitem" href="produtos_consultar.php">🔍 Consultar Produtos</a></li>
                    <li role="none"><a role="menuitem" href="produtos_cadastrar.php" class="secondary">➕ Cadastrar Produto</a></li>
                    <li role="none"><a role="menuitem" href="produtos_deletar.php" class="danger">🗑️ Deletar Produto</a></li>
                    <li role="none"><a role="menuitem" href="auditoria.php" class="audit">📋 Auditoria</a></li>
                </ul>
            </div>
        </div>

    </main>
    <footer>
        <p>&copy; 2025 Magik Circus Management System</p>
    </footer>
</body>
</html>