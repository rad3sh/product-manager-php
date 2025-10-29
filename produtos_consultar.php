<?php
require_once 'connection.php';
if (!isset($pdo) || !$pdo) {
    echo "<!DOCTYPE html><html><head><title>Erro de Conexão</title></head><body style='padding:0; margin:0;background:#18181b;color:#fff;font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;'><h2>Falha na conexão com o banco de dados.</h2></body></html>";
    exit;
}

// ...existing code...

// parâmetros de paginação
$perPage = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// leitura dos parâmetros de busca (q / q_by)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$q_by = isset($_GET['q_by']) && in_array($_GET['q_by'], ['id','nome']) ? $_GET['q_by'] : 'id';

$produtos = [];
$total = 0;
$totalPages = 1;
$searchError = '';

if ($q !== '') {
    if ($q_by === 'id') {
        // busca exata por id (sem paginação)
        if (!is_numeric($q)) {
            $searchError = 'ID inválido.';
            $produtos = [];
            $total = 0;
            $totalPages = 1;
        } else {
            $stmt = $pdo->prepare("SELECT id, nome, referencia, quantidade, `local` FROM produtos WHERE id = :id LIMIT 1");
            if ($stmt->execute([':id' => (int)$q])) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row === false) $row = null;
                if ($row) {
                    $produtos = [$row];
                    $total = 1;
                } else {
                    $produtos = [];
                    $total = 0;
                }
                $totalPages = 1;
            } else {
                $searchError = 'Erro na consulta.';
            }
        }
    } else {
        // busca por nome com paginação
        $term = '%' . str_replace('%','\\%',$q) . '%';
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE nome LIKE :term");
        $countStmt->execute([':term' => $term]);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("SELECT id, nome, referencia, quantidade, `local` FROM produtos WHERE nome LIKE :term ORDER BY id ASC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':term', $term, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    // sem filtro: listagem normal paginada
    $countStmt = $pdo->query("SELECT COUNT(*) FROM produtos");
    $total = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("SELECT id, nome, referencia, quantidade, `local` FROM produtos ORDER BY id ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Produtos — Gestor De Estoque</title>
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
            <form id="search-form" class="search-bar" method="get" action="produtos_consultar.php" role="search" aria-label="Pesquisar produto">
                <input type="hidden" id="q_by" name="q_by" value="<?php echo htmlspecialchars($q_by); ?>">
                <label for="q" style="display:none">Termo de busca</label>

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

            <?php if (empty($produtos)): ?>
                <div class="msg">Nenhum produto encontrado.</div>
            <?php else: ?>
                <table class="styled-table" aria-label="Lista de produtos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Referência</th>
                            <th>Quantidade</th>
                            <th>Local</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['id']); ?></td>
                                <td><?php echo htmlspecialchars($p['nome']); ?></td>
                                <td><?php echo htmlspecialchars($p['referencia']); ?></td>
                                <td><?php echo htmlspecialchars($p['quantidade']); ?></td>
                                <td><?php echo htmlspecialchars($p['local']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($q_by !== 'id' && $totalPages > 1): ?>
                    <nav class="pagination" aria-label="Paginação de produtos">
                        <?php
                        $start = max(1, $page - 3);
                        $end = min($totalPages, $page + 3);
                        $baseUrl = 'produtos_consultar.php';
                        $qs = $_GET;
                        ?>
                        <?php if ($page > 1):
                            $qs['page'] = $page - 1;
                            $prevUrl = $baseUrl . '?' . http_build_query($qs);
                        ?>
                            <a class="page-link" href="<?php echo $prevUrl; ?>">&laquo; Prev</a>
                        <?php endif; ?>

                        <?php for ($i = $start; $i <= $end; $i++):
                            $qs['page'] = $i;
                            $url = $baseUrl . '?' . http_build_query($qs);
                        ?>
                            <a class="page-link <?php echo $i === $page ? 'current' : ''; ?>" href="<?php echo $url; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages):
                            $qs['page'] = $page + 1;
                            $nextUrl = $baseUrl . '?' . http_build_query($qs);
                        ?>
                            <a class="page-link" href="<?php echo $nextUrl; ?>">Next &raquo;</a>
                        <?php endif; ?>

                        <div style="margin-left:8px;color:var(--text-muted)">Página <?php echo $page; ?> de <?php echo $totalPages; ?> — <?php echo $total; ?> produtos</div>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
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