<?php
require_once 'connection.php';
if (!isset($pdo) || !$pdo) {
    echo "<!DOCTYPE html><html><head><title>Erro de Conexão</title></head><body style='padding:0; margin:0;background:#18181b;color:#fff;font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;'><h2>Falha na conexão com o banco de dados.</h2></body></html>";
    exit;
}

// Processar exclusão por query string (delete_id)
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    if (!is_numeric($delete_id)) {
        $error = 'ID inválido.';
    } else {
        // Verifica existência e quantidade antes de deletar
        $check = $pdo->prepare('SELECT quantidade FROM produtos WHERE id = :id LIMIT 1');
        if ($check->execute([':id' => $delete_id])) {
            $row = $check->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $error = 'Produto não encontrado.';
            } elseif ((int)$row['quantidade'] !== 0) {
                $error = 'Não é possível deletar: quantidade diferente de 0.';
            } else {
                $del = $pdo->prepare('DELETE FROM produtos WHERE id = :id');
                if ($del->execute([':id' => $delete_id])) {
                    header('Location: produtos_deletar.php?deleted=1');
                    exit;
                } else {
                    $error = 'Erro ao deletar produto.';
                }
            }
        } else {
            $error = 'Erro ao consultar produto.';
        }
    }
}

// Carrega produtos
$produtos = [];
$stmt = $pdo->query("SELECT id, quantidade, referencia, local FROM produtos ORDER BY id ASC");
if ($stmt) {
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h3>Deletar produtos</h3>
            <a href="index.php">← Voltar</a>

            <?php if (!empty($error)): ?>
                <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="msg success">Produto deletado com sucesso.</div>
            <?php endif; ?>

            <!-- Barra de pesquisa: pesquisar produto por ID -->
            <form class="search-bar" method="get" action="produtos_deletar.php" role="search" aria-label="Pesquisar produto por ID">
                <label for="id" style="display:none">ID do produto</label>
                <input id="id" name="id" type="text" inputmode="numeric" pattern="\d*" placeholder="Digite o ID do produto" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">
                <button type="submit">Pesquisar</button>
            </form>

            <?php
            // Se um ID foi informado, buscar e exibir apenas esse produto
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $id = $_GET['id'];
                if (!is_numeric($id)) {
                    echo "<div class='msg error'>ID inválido.</div>";
                } else {
                    $sql = 'SELECT * FROM produtos WHERE id = :id LIMIT 1';
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute([':id' => $id])) {
                        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!$produto) {
                            echo "<div class='msg'>Produto não encontrado.</div>";
                        } else {
                            // Exibir produto encontrado com campos fixos
                            echo "<table class='styled-table'>";
                            echo "<thead><tr><th>Campo</th><th>Valor</th></tr></thead><tbody>";
                            // Campos fixos: id, quantidade, referencia, local
                            $fields = ['id', 'quantidade', 'referencia', 'local'];
                            foreach ($fields as $col) {
                                $val = isset($produto[$col]) ? $produto[$col] : '';
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($col) . "</td>";
                                echo "<td>" . htmlspecialchars((string)$val) . "</td>";
                                echo "</tr>";
                            }
                            echo "<tr><td>Ações</td><td>";
                            $quant = isset($produto['quantidade']) ? (int)$produto['quantidade'] : 0;
                            if ($quant === 0) {
                                echo "<a class='action-btn delete' href='produtos_deletar.php?delete_id=" . urlencode($produto['id']) . "' onclick=\"return confirm('Tem certeza que deseja deletar este produto?');\">🗑️ Deletar</a>";
                            } else {
                                echo "Não é possível deletar: quantidade diferente de 0.";
                            }
                            echo "</td></tr>";
                            echo "</tbody></table>";
                        }
                        echo "<div class='msg error'>Erro ao consultar produto.</div>";
                    }
                }
            } else {
                // Nenhum ID informado: instrução para o usuário
                echo "<div class='msg'>Digite o ID do produto acima e clique em Pesquisar para localizar um produto específico.</div>";
            }
            ?>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 Magik Circus Management System</p>
    </footer>
</body>
</html>


