<?php
require_once 'connection.php';
if (!isset($pdo) || !$pdo) {
    echo "<!DOCTYPE html><html><head><title>Erro de Conexão</title></head><body style='padding:0; margin:0;background:#18181b;color:#fff;font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;'><h2>Falha na conexão com o banco de dados.</h2></body></html>";
    exit;
}

// ...existing code...

// leitura dos parâmetros de busca
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$q_by = isset($_GET['q_by']) && in_array($_GET['q_by'], ['id','nome']) ? $_GET['q_by'] : 'id';

// resultados
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Produto — Gestor de Estoque</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include __DIR__ . '/partials/nav.php'; ?>
    <main>
        <h1>Gestor de Estoque</h1>
        <div>
            <h3>Produtos</h3>
            <a href="index.php" class="btn-back action-btn secondary" aria-label="Voltar à página inicial">Voltar</a>

            <!-- abas (seletor) para escolher modo de pesquisa -->
            <div class="search-tabs" role="tablist" aria-label="Modo de pesquisa">
                <button type="button" class="tab <?php echo $q_by === 'id' ? 'active' : ''; ?>" data-qby="id" aria-selected="<?php echo $q_by === 'id' ? 'true' : 'false'; ?>">Pesquisar por ID</button>
                <button type="button" class="tab <?php echo $q_by === 'nome' ? 'active' : ''; ?>" data-qby="nome" aria-selected="<?php echo $q_by === 'nome' ? 'true' : 'false'; ?>">Pesquisar por Nome</button>
            </div>

            <!-- formulário único que envia q e q_by -->
            <form id="search-form" class="search-bar" method="get" action="produto_consultar.php" role="search" aria-label="Pesquisar produto">
                <input type="hidden" id="q_by" name="q_by" value="<?php echo htmlspecialchars($q_by); ?>">
                <label for="q" style="display:none">Termo de busca</label>

                <!-- input com botão "X" interno para limpar -->
                <div class="input-with-clear" style="width:100%;">
                    <input id="q" name="q" type="text" placeholder="<?php echo $q_by === 'nome' ? 'Digite o nome' : 'Digite o ID'; ?>" value="<?php echo htmlspecialchars($q); ?>">
                    <button type="button" id="clear-q" class="clear-input" aria-label="Limpar pesquisa" title="Limpar pesquisa" <?php echo $q === '' ? 'style="display:none;"' : ''; ?>>✕</button>
                </div>

                <div style="display:inline-flex; gap:8px;">
                    <button type="submit" class="action-btn edit">Pesquisar</button>
                </div>
            </form>

            <?php if ($searchError): ?>
                <div class="msg error"><?php echo htmlspecialchars($searchError); ?></div>
            <?php endif; ?>

            <?php
            if ($q !== '') {
                if ($q_by === 'id') {
 if ($searchError) {
                       echo "<div class='msg error'>".htmlspecialchars($searchError)."</div>";
                   } elseif ($produto) {
                       echo "<table class='styled-table' aria-label='Produto'>";
                       echo "<thead><tr><th>Campo</th><th>Valor</th></tr></thead><tbody>";
                       $fields = ['id','nome','referencia','quantidade','local'];
                        foreach ($fields as $col) {
                            $val = isset($produto[$col]) ? $produto[$col] : '';
                            echo "<tr><td>".htmlspecialchars($col)."</td><td>".htmlspecialchars((string)$val)."</td></tr>";
                        }
                        echo "</tbody></table>";
                    } else {
                        echo "<div class='msg'>Produto não encontrado.</div>";
                    }
                } else {
                    if (empty($rows)) {
                        echo "<div class='msg'>Nenhum produto encontrado para esse nome.</div>";
                    } else {
                        echo "<table class='styled-table' aria-label='Resultados por nome'>";
                        echo "<thead><tr><th>ID</th><th>Nome</th><th>Referência</th><th>Quantidade</th><th>Local</th></tr></thead><tbody>";
                        foreach ($rows as $r) {
                            echo "<tr>";
                            echo "<td>".htmlspecialchars($r['id'])."</td>";
                            echo "<td>".htmlspecialchars($r['nome'])."</td>";
                            echo "<td>".htmlspecialchars($r['referencia'])."</td>";
                            echo "<td>".htmlspecialchars($r['quantidade'])."</td>";
                            echo "<td>".htmlspecialchars($r['local'])."</td>";
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                    }
                }
            } else {
                echo "<div class='msg'>Escolha 'Pesquisar por ID' ou 'Pesquisar por Nome' e insira o termo. Para listar todos os produtos use a página de listagem.</div>";
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
    // inicializa placeholder correto
    setMode(qByInput.value || 'id');

    // --- novo: comportamento do botão limpar dentro do input ---
    const clearBtn = document.getElementById('clear-q');
    if (clearBtn) {
        // esconde/mostra X conforme valor do input
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
            // opcional: não submete; apenas limpa. se quiser submeter para atualizar resultados, descomente:
            // document.getElementById('search-form').submit();
        });
        // chamar uma vez para definir estado inicial
        toggleClear();
    }
})();
</script>
</body>
</html>