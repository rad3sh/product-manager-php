<?php
require_once 'connection.php';
if (!isset($pdo) || !$pdo) {
    echo "<!DOCTYPE html><html><head><title>Erro de Conexão</title></head><body style='padding:0; margin:0;background:#18181b;color:#fff;font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;'><h2>Falha na conexão com o banco de dados.</h2></body></html>";
    exit;
}

// parâmetros de paginação
$perPage = 25;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// leitura dos parâmetros de busca
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$q_by = isset($_GET['q_by']) && in_array($_GET['q_by'], ['table','actor','row_id']) ? $_GET['q_by'] : 'table';

$rows = [];
$total = 0;
$totalPages = 1;
$searchError = '';

try {
    if ($q !== '') {
        if ($q_by === 'row_id') {
            if (!is_numeric($q)) {
                $searchError = 'Row ID inválido.';
            } else {
                $stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM audit_log WHERE row_id = :rid ORDER BY event_time DESC LIMIT :limit OFFSET :offset");
                $offset = ($page - 1) * $perPage;
                $stmt->bindValue(':rid', (int)$q, PDO::PARAM_INT);
                $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $total = (int)$pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
                $totalPages = max(1, (int)ceil($total / $perPage));
            }
        } else {
            // pesquisa por texto (table ou actor) usando LIKE
            $term = '%' . str_replace('%','\\%',$q) . '%';
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE " . ($q_by === 'actor' ? "actor" : "db_table") . " LIKE :term");
            $countStmt->execute([':term' => $term]);
            $total = (int)$countStmt->fetchColumn();
            $totalPages = max(1, (int)ceil($total / $perPage));
            if ($page > $totalPages) $page = $totalPages;
            $offset = ($page - 1) * $perPage;

            $stmt = $pdo->prepare("SELECT id,event_time,actor,actor_ip,db_table,action,row_id,old_json,new_json,info FROM audit_log WHERE " . ($q_by === 'actor' ? "actor" : "db_table") . " LIKE :term ORDER BY event_time DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':term', $term, PDO::PARAM_STR);
            $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        // sem filtro: listagem paginada
        $countStmt = $pdo->query("SELECT COUNT(*) FROM audit_log");
        $total = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("SELECT id,event_time,actor,actor_ip,db_table,action,row_id,old_json,new_json,info FROM audit_log ORDER BY event_time DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $searchError = 'Erro ao consultar auditoria: ' . $e->getMessage();
}

// helper para mostrar JSON/strings curtas
function short_json_html($json, $max = 300) {
    if ($json === null) return '';
    // se já for JSON válido, pretty print
    $decoded = json_decode($json, true);
    if ($decoded !== null) {
        $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        $pretty = $json;
    }
    if (mb_strlen($pretty, 'UTF-8') > $max) {
        $short = mb_substr($pretty, 0, $max - 3, 'UTF-8') . '...';
    } else {
        $short = $pretty;
    }
    return '<pre style="white-space:pre-wrap;margin:0;font-size:0.85rem;">' . htmlspecialchars($short) . '</pre>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Auditoria — Gestor de Estoque</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main>
    <h1>Auditoria</h1>
    <div>
        <h3>Logs de auditoria</h3>
        <a href="index.php" class="btn-back action-btn secondary" aria-label="Voltar à página inicial">Voltar</a>

        <div class="search-tabs" role="tablist" aria-label="Modo de pesquisa">
            <button type="button" class="tab <?php echo $q_by === 'table' ? 'active' : ''; ?>" data-qby="table" aria-selected="<?php echo $q_by === 'table' ? 'true' : 'false'; ?>">Pesquisar por Tabela</button>
            <button type="button" class="tab <?php echo $q_by === 'actor' ? 'active' : ''; ?>" data-qby="actor" aria-selected="<?php echo $q_by === 'actor' ? 'true' : 'false'; ?>">Pesquisar por Usuário</button>
            <button type="button" class="tab <?php echo $q_by === 'row_id' ? 'active' : ''; ?>" data-qby="row_id" aria-selected="<?php echo $q_by === 'row_id' ? 'true' : 'false'; ?>">Pesquisar por Row ID</button>
        </div>

        <form id="search-form" class="search-bar" method="get" action="auditoria.php" role="search" aria-label="Pesquisar auditoria">
            <input type="hidden" id="q_by" name="q_by" value="<?php echo htmlspecialchars($q_by); ?>">
            <label for="q" style="display:none">Termo de busca</label>

            <div class="input-with-clear" style="width:100%;">
                <input id="q" name="q" type="text" placeholder="<?php
                    echo ($q_by === 'actor') ? 'Digite usuário (actor)' : (($q_by === 'row_id') ? 'Digite row id (ex: 123)' : 'Digite nome da tabela (ex: produtos, vendas)');
                ?>" value="<?php echo htmlspecialchars($q); ?>">
                <button type="button" id="clear-q" class="clear-input" aria-label="Limpar pesquisa" title="Limpar pesquisa" <?php echo $q === '' ? 'style="display:none;"' : ''; ?>>✕</button>
            </div>

            <div style="display:inline-flex; gap:8px;">
                <button type="submit" class="action-btn edit">Pesquisar</button>
            </div>
        </form>

        <?php if ($searchError): ?>
            <div class="msg error"><?php echo htmlspecialchars($searchError); ?></div>
        <?php endif; ?>

        <?php if (empty($rows)): ?>
            <div class="msg">Nenhum registro encontrado.</div>
        <?php else: ?>
            <table class="styled-table" aria-label="Logs de auditoria">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tempo</th>
                        <th>Usuário</th>
                        <th>IP</th>
                        <th>Tabela</th>
                        <th>Ação</th>
                        <th>Row ID</th>
                        <th>Old</th>
                        <th>New</th>
                        <th>Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['id']); ?></td>
                            <td><?php echo htmlspecialchars($r['event_time']); ?></td>
                            <td><?php echo htmlspecialchars($r['actor']); ?></td>
                            <td><?php echo htmlspecialchars($r['actor_ip']); ?></td>
                            <td><?php echo htmlspecialchars($r['db_table']); ?></td>
                            <td><?php echo htmlspecialchars($r['action']); ?></td>
                            <td><?php echo htmlspecialchars($r['row_id']); ?></td>
                            <td style="max-width:220px;"><?php echo short_json_html($r['old_json']); ?></td>
                            <td style="max-width:220px;"><?php echo short_json_html($r['new_json']); ?></td>
                            <td><?php echo htmlspecialchars($r['info']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Paginação de auditoria">
                    <?php
                    $start = max(1, $page - 3);
                    $end = min($totalPages, $page + 3);
                    $baseUrl = 'auditoria.php';
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

                    <div style="margin-left:8px;color:var(--text-muted)">Página <?php echo $page; ?> de <?php echo $totalPages; ?> — <?php echo $total; ?> registros</div>
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
        qInput.placeholder = mode === 'actor' ? 'Digite usuário (actor)' : (mode === 'row_id' ? 'Digite row id (ex: 123)' : 'Digite nome da tabela (ex: produtos)');
        qInput.focus();
    }

    tabs.forEach(t => t.addEventListener('click', function(){ setMode(this.dataset.qby); }));
    setMode(qByInput.value || 'table');

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