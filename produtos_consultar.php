<?php
require_once 'connection.php';
if (!isset($pdo) || !$pdo) {
    echo "<!DOCTYPE html><html><head><title>Erro de Conexão</title></head><body style='padding:0; margin:0;background:#18181b;color:#fff;font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;'><h2>Falha na conexão com o banco de dados.</h2></body></html>";
    exit;
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
            <h3>Produtos</h3>
            <a href="/" data-link="">← Voltar</a>
            <!-- Barra de pesquisa: pesquisar produto por ID -->
            <form class="search-bar" method="get" action="produtos_consultar.php" role="search" aria-label="Pesquisar produto por ID">
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
                            echo "</tbody></table>";
                        }
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


