<?php
require_once 'connection.php';
$required_action = 'produtos_deletar';
require_once __DIR__ . '/auth_check.php';
if (!isset($pdo) || !$pdo) {
    echo "<!DOCTYPE html><html><head><title>Erro de Conexão</title></head><body style='padding:0; margin:0;background:#18181b;color:#fff;font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;'><h2>Falha na conexão com o banco de dados.</h2></body></html>";
    exit;
}

$error = '';
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

// leitura dos parâmetros de busca (q / q_by) — agora com tabs similar a produto_consultar
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$q_by = isset($_GET['q_by']) && in_array($_GET['q_by'], ['id','nome']) ? $_GET['q_by'] : 'id';

$produto = null;
$rows = [];
$searchError = '';

if ($q !== '') {
    if ($q_by === 'id') {
        if (!is_numeric($q)) {
            $searchError = 'ID inválido.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id LIMIT 1');
            if ($stmt->execute([':id' => (int)$q])) {
                $produto = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($produto === false) $produto = null;
            } else {
                $searchError = 'Erro na consulta.';
            }
        }
    } else {
        $term = '%' . str_replace('%','\\%',$q) . '%';
        $stmt = $pdo->prepare('SELECT id,nome,referencia,quantidade,`local` FROM produtos WHERE nome LIKE :term ORDER BY id ASC LIMIT 200');
        if ($stmt->execute([':term' => $term])) {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $searchError = 'Erro na consulta.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Deletar Produtos — Gestor de Estoque</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main>
    <h1>Gestor de Estoque</h1>
    <div>
        <h3>Deletar produtos</h3>
        <a href="index.php" class="btn-back action-btn secondary" aria-label="Voltar à página inicial">Voltar</a>

        <?php if (!empty($error)): ?>
            <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="msg success">Produto deletado com sucesso.</div>
        <?php endif; ?>

        <!-- abas (seletor) para escolher modo de pesquisa -->
        <div class="search-tabs" role="tablist" aria-label="Modo de pesquisa">
            <button type="button" class="tab <?php echo $q_by === 'id' ? 'active' : ''; ?>" data-qby="id" aria-selected="<?php echo $q_by === 'id' ? 'true' : 'false'; ?>">Pesquisar por ID</button>
            <button type="button" class="tab <?php echo $q_by === 'nome' ? 'active' : ''; ?>" data-qby="nome" aria-selected="<?php echo $q_by === 'nome' ? 'true' : 'false'; ?>">Pesquisar por Nome</button>
        </div>

        <!-- formulário único que envia q e q_by -->
        <form id="search-form" class="search-bar" method="get" action="produtos_deletar.php" role="search" aria-label="Pesquisar produto">
            <input type="hidden" id="q_by" name="q_by" value="<?php echo htmlspecialchars($q_by); ?>">
            <label for="q" style="display:none">Termo de busca</label>

            <div class="input-with-clear" style="width:100%;">
                <input id="q" name="q" type="text" placeholder="<?php echo $q_by === 'nome' ? 'Digite o nome' : 'Digite o ID'; ?>" value="<?php echo htmlspecialchars($q); ?>">
                <button type="button" id="clear-q" class="clear-input" aria-label="Limpar pesquisa" title="Limpar pesquisa" <?php echo $q === '' ? 'style="display:none;"' : ''; ?>>✕</button>
            </div>

            <div style="display:inline-flex; gap:8px;">
                <button type="submit" class="action-btn delete">Pesquisar</button>
            </div>
        </form>

        <?php if ($searchError): ?>
            <div class="msg error"><?php echo htmlspecialchars($searchError); ?></div>
        <?php endif; ?>

        <?php
        if ($q !== '') {
            if ($q_by === 'id') {
                if ($produto === null && $searchError === '') {
                    echo "<div class='msg'>Produto não encontrado.</div>";
                } elseif ($produto) {
                    echo "<table class='styled-table' aria-label='Produto'>";
                    echo "<thead><tr><th>Campo</th><th>Valor</th></tr></thead><tbody>";
                    $fields = ['id','nome','referencia','quantidade','local'];
                    foreach ($fields as $col) {
                        $val = isset($produto[$col]) ? $produto[$col] : '';
                        echo "<tr><td>".htmlspecialchars($col)."</td><td>".htmlspecialchars((string)$val)."</td></tr>";
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
            } else {
                // resultados por nome (lista com botão de deletar por linha)
                if (empty($rows)) {
                    echo "<div class='msg'>Nenhum produto encontrado para esse nome.</div>";
                } else {
                    echo "<table class='styled-table' aria-label='Resultados por nome'>";
                    echo "<thead><tr><th>ID</th><th>Nome</th><th>Referência</th><th>Quantidade</th><th>Local</th><th>Ações</th></tr></thead><tbody>";
                    foreach ($rows as $r) {
                        $canDelete = ((int)$r['quantidade'] === 0);
                        echo "<tr>";
                        echo "<td>".htmlspecialchars($r['id'])."</td>";
                        echo "<td>".htmlspecialchars($r['nome'])."</td>";
                        echo "<td>".htmlspecialchars($r['referencia'])."</td>";
                        echo "<td>".htmlspecialchars($r['quantidade'])."</td>";
                        echo "<td>".htmlspecialchars($r['local'])."</td>";
                        echo "<td>";
                        if ($canDelete) {
                            echo "<a class='action-btn delete' href='produtos_deletar.php?delete_id=" . urlencode($r['id']) . "' onclick=\"return confirm('Tem certeza que deseja deletar o produto ID ".htmlspecialchars($r['id'])."?');\">🗑️ Deletar</a>";
                        } else {
                            echo "Não é possível deletar";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody></table>";
                }
            }
        } else {
            echo "<div class='msg'>Digite um ID ou Nome e clique em Pesquisar. Para listar todos use a página de listagem.</div>";
        }
        ?>
    </div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
(function(){
    const tabs = document.querySelectorAll('.search-tabs .tab');
    const qByInput = document.getElementById('q_by');
    const qInput = document.getElementById('q');

    function setMode(mode){
        qByInput.value = mode;
        tabs.forEach(t => {
            const active = t.dataset.qby === mode;
            t.classList.toggle('active', active);
            t.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        qInput.placeholder = mode === 'nome' ? 'Digite o nome' : 'Digite o ID';
        qInput.focus();
    }

    tabs.forEach(t => t.addEventListener('click', function(){ setMode(this.dataset.qby); }));
    setMode(qByInput.value || 'id');

    const clearBtn = document.getElementById('clear-q');
    if (clearBtn) {
        function toggleClear() {
            if (qInput.value && qInput.value.trim() !== '') {
                clearBtn.style.display = 'inline-flex';
            } else {
                clearBtn.style.display = 'none';
            }
        }
        qInput.addEventListener('input', toggleClear);
        clearBtn.addEventListener('click', function(){
            qInput.value = '';
            toggleClear();
            qInput.focus();
        });
        toggleClear();
    }
})();
</script>
</body>
</html>