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
// agora os modos são "movement" (Tipo de Movimentação) e "row_id" (ID do produto)
// padrão: Tipo de Movimentação
$q_by = isset($_GET['q_by']) && in_array($_GET['q_by'], ['movement','row_id']) ? $_GET['q_by'] : 'movement';

// movimento selecionado (quando q_by === 'movement') — usa select name="movement"
$movement = isset($_GET['movement']) ? trim($_GET['movement']) : '';

// nova lógica: aceita tz_offset do navegador e converte limites do dia para UTC (para consultas ao banco).
$date = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : date('Y-m-d');
$tzOffset = isset($_GET['tz_offset']) ? (int)$_GET['tz_offset'] : null;

if ($tzOffset !== null) {
    $sign = $tzOffset > 0 ? '-' : '+';
    $hours = floor(abs($tzOffset) / 60);
    $mins = abs($tzOffset) % 60;
    $tzOffsetStr = sprintf('%s%02d:%02d', $sign, $hours, $mins);
    try {
        $userTz = new DateTimeZone($tzOffsetStr);
    } catch (Exception $e) {
        $userTz = new DateTimeZone(date_default_timezone_get());
    }
} else {
    $userTz = new DateTimeZone(date_default_timezone_get());
}

// cria limites do dia no timezone do usuário e converte para UTC para usar nas queries
$startLocal = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' 00:00:00', $userTz);
$endLocal   = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' 23:59:59', $userTz);
if (! $startLocal) {
    $startLocal = new DateTimeImmutable('now', $userTz);
    $startLocal = $startLocal->setTime(0,0,0);
}
if (! $endLocal) {
    $endLocal = $startLocal->setTime(23,59,59);
}

$dateStart = $startLocal->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
$dateEnd   = $endLocal->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

// exibir apenas o dia ao lado do input
$displayDate = DateTimeImmutable::createFromFormat('Y-m-d', $date, $userTz);
$displayDateLabel = $displayDate ? $displayDate->format('d/m/Y') : $date;

// helper para label amigável (Hoje/ Ontem / DiaSemana dd/mm/yyyy)
function friendly_date_label(string $ymd, DateTimeZone $tz): string {
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $ymd, $tz);
    if (! $d) return $ymd;
    $today = new DateTimeImmutable('now', $tz);
    $yesterday = $today->sub(new DateInterval('P1D'));
    if ($today->format('Y-m-d') === $d->format('Y-m-d')) return $d->format('d/m/Y') . ' (Hoje)';
    if ($yesterday->format('Y-m-d') === $d->format('Y-m-d')) return $d->format('d/m/Y') . ' (Ontem)';
    return $d->format('d/m/Y') . ' (' . strftime('%A', $d->getTimestamp()) . ')';
}

// formatação do event_time para o timezone do usuário
function format_event_time_for_user(?string $utcTime, DateTimeZone $tz): string {
    if (!$utcTime) return '-';
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $utcTime, new DateTimeZone('UTC'));
    if (!$dt) return $utcTime;
    return $dt->setTimezone($tz)->format('d/m/Y H:i:s');
}

$rows = [];
$total = 0;
$totalPages = 1;
$searchError = '';

try {
    if ($q_by === 'row_id') {
        // ID do produto — exigir exatamente 6 dígitos
        if ($q === '') {
            $searchError = 'Informe o ID do produto (6 dígitos).';
        } elseif (!preg_match('/^\d{6}$/', $q)) {
            $searchError = 'ID do produto inválido. Deve ter exatamente 6 dígitos.';
        } else {
            $rid = (int)$q;
            $stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS id,event_time,actor,db_table,action,row_id,old_json,new_json,info
                                   FROM audit_log
                                   WHERE row_id = :rid
                                     AND event_time BETWEEN :ds AND :de
                                   ORDER BY event_time DESC
                                   LIMIT :limit OFFSET :offset");
            $offset = ($page - 1) * $perPage;
            $stmt->bindValue(':rid', $rid, PDO::PARAM_INT);
            $stmt->bindValue(':ds', $dateStart, PDO::PARAM_STR);
            $stmt->bindValue(':de', $dateEnd, PDO::PARAM_STR);
            $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total = (int)$pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
            $totalPages = max(1, (int)ceil($total / $perPage));
        }
    } else {
        // q_by === 'movement' (Tipo de Movimentação) — usa select name="movement"
        // mapear opções do seletor para condições SQL
        $movementMap = [
            'Venda' => ["(db_table = 'vendas')", "(action = 'INSERT')"],
            'Cadastro' => ["(db_table = 'produtos')", "(action = 'INSERT')"],
            'Atualização' => ["(db_table = 'produtos')", "(action = 'UPDATE')"],
            'Remoção' => ["(db_table = 'produtos')", "(action = 'DELETE')"],
            'Todos' => []
        ];

        $conds = [];
        if ($movement !== '' && isset($movementMap[$movement]) && count($movementMap[$movement]) > 0) {
            $conds = $movementMap[$movement];
        }
        // montar WHERE final
        $whereSql = '';
        if (!empty($conds)) {
            $whereSql = implode(' AND ', $conds) . ' AND ';
        }

        // contar
        $countSql = "SELECT COUNT(*) FROM audit_log WHERE {$whereSql} event_time BETWEEN :ds AND :de";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':ds' => $dateStart, ':de' => $dateEnd]);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        // seleção
        $sql = "SELECT id,event_time,actor,db_table,action,row_id,old_json,new_json,info
                FROM audit_log
                WHERE {$whereSql} event_time BETWEEN :ds AND :de
                ORDER BY event_time DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ds', $dateStart, PDO::PARAM_STR);
        $stmt->bindValue(':de', $dateEnd, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // FILTRO: remover updates em 'produtos' que são consequência imediata de uma venda
    $windowSeconds = 5;
    $filtered = [];
    foreach ($rows as $r) {
        $skip = false;
        if (isset($r['db_table'], $r['action']) && strtolower($r['db_table']) === 'produtos' && strtoupper($r['action']) === 'UPDATE') {
            $pid = null;
            if (!empty($r['new_json'])) {
                $nj = json_decode($r['new_json'], true);
                if (is_array($nj) && isset($nj['id'])) $pid = (int)$nj['id'];
            }
            if ($pid === null && !empty($r['row_id'])) {
                $pid = (int)$r['row_id'];
            }

            if ($pid !== null) {
                $start = date('Y-m-d H:i:s', strtotime($r['event_time']) - $windowSeconds);
                $end = date('Y-m-d H:i:s', strtotime($r['event_time']) + $windowSeconds);

                $qstmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log
                                        WHERE db_table = 'vendas'
                                          AND action = 'INSERT'
                                          AND event_time BETWEEN :start AND :end
                                          AND (JSON_UNQUOTE(JSON_EXTRACT(new_json,'$.produto_id')) = :pid OR info LIKE :info)");
                $qstmt->execute([
                    ':start' => $start,
                    ':end'   => $end,
                    ':pid'   => (string)$pid,
                    ':info'  => '%produto_id=' . $pid . '%'
                ]);
                $cnt = (int)$qstmt->fetchColumn();
                if ($cnt > 0) {
                    $skip = true;
                }
            }
        }

        if (!$skip) $filtered[] = $r;
    }
    $rows = $filtered;

} catch (PDOException $e) {
    $searchError = 'Erro ao consultar auditoria: ' . $e->getMessage();
}

/**
 * Extrai quantidades antigas/novas no formato requisitado pelo usuário.
 * Agora também retorna 'produto_local' (campo local na tabela produtos).
 */
function extract_business_change(PDO $pdo, array $row) {
    $tbl = strtolower($row['db_table'] ?? '');
    $action = strtoupper($row['action'] ?? '');
    $event_time = $row['event_time'] ?? null;
    $info = $row['info'] ?? null;

    $old_json = null; $new_json = null;
    if (!empty($row['old_json'])) $old_json = json_decode($row['old_json'], true);
    if (!empty($row['new_json'])) $new_json = json_decode($row['new_json'], true);

    $res = [
        'tipo' => '',
        'produto_id' => null,
        'produto_nome' => null,
        'produto_local' => '-',
        'old' => '-',
        'new' => '-'
    ];

    $fetch_product = function($id) use ($pdo) {
        if ($id === null) return [null, null, null];
        $s = $pdo->prepare("SELECT nome, quantidade, local FROM produtos WHERE id = :id LIMIT 1");
        $s->execute([':id' => (int)$id]);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        if (!$r) return [null, null, null];
        return [$r['nome'] ?? null, isset($r['quantidade']) ? (int)$r['quantidade'] : null, $r['local'] ?? null];
    };

    if ($tbl === 'vendas' && $action === 'INSERT') {
        $res['tipo'] = 'Venda';
        $prodId = $new_json['produto_id'] ?? null;
        if ($prodId === null && !empty($info)) {
            if (preg_match('/produto_id\s*=\s*(\d+)/', $info, $m)) $prodId = (int)$m[1];
        }
        $res['produto_id'] = $prodId !== null ? (int)$prodId : null;
        list($pname, $pqty, $plocal) = $fetch_product($res['produto_id']);
        $res['produto_nome'] = $pname;
        $res['produto_local'] = $plocal ?? '-';
        $sold = isset($new_json['quantidade']) ? (int)$new_json['quantidade'] : null;

        if ($res['produto_id'] !== null) {
            $q = $pdo->prepare("SELECT new_json, old_json FROM audit_log WHERE db_table = 'produtos' AND JSON_UNQUOTE(JSON_EXTRACT(new_json,'$.id')) = :pid AND event_time >= :et ORDER BY event_time ASC LIMIT 1");
            $q->execute([':pid' => (string)$res['produto_id'], ':et' => $event_time]);
            $found = $q->fetch(PDO::FETCH_ASSOC);
            if ($found) {
                $njson = json_decode($found['new_json'], true);
                $oj = json_decode($found['old_json'], true);
                $newQty = isset($njson['quantidade']) ? (int)$njson['quantidade'] : null;
                $oldQty = isset($oj['quantidade']) ? (int)$oj['quantidade'] : null;
                if ($newQty !== null) {
                    $res['new'] = (string)$newQty;
                    if ($oldQty !== null) $res['old'] = (string)$oldQty;
                    elseif ($sold !== null) $res['old'] = (string)($newQty + $sold);
                    else $res['old'] = '-';
                    return $res;
                }
            }
        }

        if ($res['produto_id'] !== null) {
            // fallback buscando quantidade atual e local
            list($pname2, $current, $plocal2) = $fetch_product($res['produto_id']);
            if ($plocal2) $res['produto_local'] = $plocal2;
            if ($current !== null) {
                $res['new'] = (string)$current;
                if ($sold !== null) $res['old'] = (string)($current + $sold);
                else $res['old'] = '-';
                return $res;
            }
        }

        $res['old'] = '-';
        $res['new'] = ($sold !== null) ? ('- após venda de '.$sold) : '-';
        return $res;
    }

    if ($tbl === 'produtos') {
        if ($action === 'INSERT') {
            $res['tipo'] = 'Cadastro';
            $prodId = $new_json['id'] ?? ($row['row_id'] ?? null);
            $res['produto_id'] = $prodId !== null ? (int)$prodId : null;
            list($pname, $pqty, $plocal) = $fetch_product($res['produto_id']);
            $res['produto_nome'] = $pname;
            $res['produto_local'] = $plocal ?? ($new_json['local'] ?? '-');
            $res['old'] = '-';
            $res['new'] = isset($new_json['quantidade']) ? (string)$new_json['quantidade'] : (isset($pqty) ? (string)$pqty : '-');
            return $res;
        } elseif ($action === 'UPDATE') {
            $res['tipo'] = 'Atualização';
            $prodId = $new_json['id'] ?? ($row['row_id'] ?? null);
            $res['produto_id'] = $prodId !== null ? (int)$prodId : null;
            list($pname, $pqty, $plocal) = $fetch_product($res['produto_id']);
            $res['produto_nome'] = $pname;
            $res['produto_local'] = $plocal ?? '-';
            $res['old'] = isset($old_json['quantidade']) ? (string)$old_json['quantidade'] : '-';
            $res['new'] = isset($new_json['quantidade']) ? (string)$new_json['quantidade'] : '-';
            return $res;
        } elseif ($action === 'DELETE') {
            $res['tipo'] = 'Remoção';
            $prodId = $old_json['id'] ?? ($row['row_id'] ?? null);
            $res['produto_id'] = $prodId !== null ? (int)$prodId : null;
            list($pname, $pqty, $plocal) = $fetch_product($res['produto_id']);
            $res['produto_nome'] = $pname;
            $res['produto_local'] = $plocal ?? '-';
            $res['old'] = isset($old_json['quantidade']) ? (string)$old_json['quantidade'] : '-';
            $res['new'] = '-';
            return $res;
        }
    }

    // fallback genérico
    $res['tipo'] = $action === 'INSERT' ? 'Cadastro' : ($action === 'UPDATE' ? 'Atualização' : 'Remoção');
    $res['produto_id'] = $row['row_id'] ?? null;
    list($pname, $pqty, $plocal) = $fetch_product($res['produto_id']);
    $res['produto_nome'] = $pname;
    $res['produto_local'] = $plocal ?? '-';
    $res['old'] = isset($old_json['quantidade']) ? (string)$old_json['quantidade'] : '-';
    $res['new'] = isset($new_json['quantidade']) ? (string)$new_json['quantidade'] : '-';
    return $res;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Auditoria — Gestor de Estoque</title>
<link rel="stylesheet" href="styles.css">
<!-- evitar flash: esconder até sabermos o tz_offset ou redirecionarmos -->
<style id="initial-hide">html{visibility:hidden;} </style>

<script>
/*
  Se tz_offset não estiver na URL, envia tz_offset (minutos) e date local (YYYY-MM-DD)
  na primeira carga para que o PHP gere a página já com timezone do navegador.
  Usa location.replace() para não poluir o histórico. Não redireciona em loop.
*/
(function(){
  try {
    var params = new URLSearchParams(window.location.search);
    if (!params.has('tz_offset')) {
      var offset = new Date().getTimezoneOffset(); // minutos
      var now = new Date();
      var y = now.getFullYear();
      var m = String(now.getMonth()+1).padStart(2,'0');
      var d = String(now.getDate()).padStart(2,'0');
      if (!params.has('date')) params.set('date', y + '-' + m + '-' + d);
      params.set('tz_offset', String(offset));
      // manter outros parâmetros (page, movement, q_by, etc.)
      var newUrl = window.location.pathname + '?' + params.toString();
      window.location.replace(newUrl);
      return;
    }
  } catch(e){}
  // já tem tz_offset: revelar conteúdo
  var s = document.getElementById('initial-hide');
  if (s) s.parentNode.removeChild(s);
  document.documentElement.style.visibility = 'visible';
})();
</script>
<style>
/* pequena estilização para exibir informação do dia ao lado do input */
.date-picker { display:inline-flex; flex-direction:row; gap:8px; align-items:center; }
.date-picker .date-info { font-size:0.95rem; color:var(--text-muted); margin-left:4px; white-space:nowrap; }
.date-picker input[type="date"] { padding:6px 8px; border-radius:6px; border:1px solid #ccc; background:var(--bg); }
.action-btn.small { padding:4px 8px; font-size:0.9rem; }
.hidden { display:none !important; }
</style>
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main>
    <h1>Auditoria</h1>
    <div>
        <h3>Histórico de movimentações</h3>
        <a href="index.php" class="btn-back action-btn secondary" aria-label="Voltar à página inicial">Voltar</a>

        <div class="search-tabs" role="tablist" aria-label="Modo de pesquisa">
            <button type="button" class="tab <?php echo $q_by === 'movement' ? 'active' : ''; ?>" data-qby="movement" aria-selected="<?php echo $q_by === 'movement' ? 'true' : 'false'; ?>">Tipo de Movimentação</button>
            <button type="button" class="tab <?php echo $q_by === 'row_id' ? 'active' : ''; ?>" data-qby="row_id" aria-selected="<?php echo $q_by === 'row_id' ? 'true' : 'false'; ?>">ID do produto</button>
        </div>

        <form id="search-form" class="search-bar" method="get" action="auditoria.php" role="search" aria-label="Pesquisar auditoria">
            <input type="hidden" id="q_by" name="q_by" value="<?php echo htmlspecialchars($q_by); ?>">
            <label for="q" style="display:none">Termo de busca</label>

            <div class="input-with-clear" style="width:100%;">
                <!-- campo de ID (aparece apenas no modo row_id) -->
                <input id="q" name="q" type="text" inputmode="numeric" maxlength="6" placeholder="000000" value="<?php echo htmlspecialchars($q); ?>" aria-hidden="<?php echo $q_by === 'row_id' ? 'false' : 'true'; ?>" <?php echo $q_by !== 'row_id' ? 'class="hidden"' : ''; ?>>
                <button type="button" id="clear-q" class="clear-input" aria-label="Limpar pesquisa" title="Limpar pesquisa" <?php echo $q === '' ? 'style="display:none;"' : ''; ?>>✕</button>

                <!-- seletor de tipo de movimentação (aparece apenas no modo movement) -->
                <select id="movement" name="movement" <?php echo $q_by !== 'movement' ? 'class="hidden"' : ''; ?> aria-hidden="<?php echo $q_by === 'movement' ? 'false' : 'true'; ?>">
                    <option value="">Todos</option>
                    <option value="Venda" <?php echo $movement === 'Venda' ? 'selected' : ''; ?>>Venda</option>
                    <option value="Cadastro" <?php echo $movement === 'Cadastro' ? 'selected' : ''; ?>>Cadastro</option>
                    <option value="Atualização" <?php echo $movement === 'Atualização' ? 'selected' : ''; ?>>Atualização</option>
                    <option value="Remoção" <?php echo $movement === 'Remoção' ? 'selected' : ''; ?>>Remoção</option>
                </select>
            </div>

            <div style="display:inline-flex; gap:8px; align-items:center;">
                <button type="button" id="prev-day" class="action-btn small" title="Dia anterior" aria-label="Dia anterior">◀</button>

                <div class="date-picker">
                    <input id="date" name="date" type="date" value="<?php echo htmlspecialchars($date); ?>">
                    <div id="date-info" class="date-info"><?php echo htmlspecialchars($displayDateLabel); ?></div>
                </div>

                <button type="button" id="next-day" class="action-btn small" title="Próximo dia" aria-label="Próximo dia">▶</button>

                <input type="hidden" id="tz_offset" name="tz_offset" value="<?php echo htmlspecialchars($tzOffset ?? ''); ?>">

                <button type="submit" class="action-btn edit">Pesquisar</button>
            </div>
        </form>

        <?php if ($searchError): ?>
            <div class="msg error"><?php echo htmlspecialchars($searchError); ?></div>
        <?php endif; ?>

        <?php if (empty($rows)): ?>
            <div class="msg">Nenhum registro encontrado.</div>
        <?php else: ?>
            <table class="styled-table" aria-label="Histórico de quantidade">
                <thead>
                    <tr>
                        <th>Data / Hora</th>
                        <th>Tipo</th>
                        <th>ID Produto</th>
                        <th>Produto</th>
                        <th>Local</th>
                        <th>Quant. antiga</th>
                        <th>Quant. nova</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r):
                        $b = extract_business_change($pdo, $r);
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars(format_event_time_for_user($r['event_time'], $userTz)); ?></td>
                            <td><?php echo htmlspecialchars($b['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($b['produto_id']); ?></td>
                            <td><?php echo htmlspecialchars($b['produto_nome'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($b['produto_local'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($b['old']); ?></td>
                            <td><?php echo htmlspecialchars($b['new']); ?></td>
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
    const movementSelect = document.getElementById('movement');

    function setMode(mode){
        qByInput.value = mode;
        tabs.forEach(t => {
            const active = t.dataset.qby === mode;
            t.classList.toggle('active', active);
            t.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        if (mode === 'movement') {
            qInput.classList.add('hidden');
            movementSelect.classList.remove('hidden');
            movementSelect.focus();
        } else {
            movementSelect.classList.add('hidden');
            qInput.classList.remove('hidden');
            qInput.focus();
        }
    }

    tabs.forEach(t => t.addEventListener('click', function(){ setMode(this.dataset.qby); }));
    // padrão para abrir a página: modo "movement"
    setMode(qByInput.value || 'movement');

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

    // validação no submit: se modo row_id, exigir 6 dígitos
    document.getElementById('search-form').addEventListener('submit', function(e){
        if (qByInput.value === 'row_id') {
            const v = (qInput.value || '').trim();
            if (!/^\d{6}$/.test(v)) {
                e.preventDefault();
                alert('ID do produto inválido. Informe exatamente 6 dígitos.');
                qInput.focus();
                return false;
            }
        }
        // caso de movement não precisa validar aqui
    });
})();
</script>

<script>
(function(){
    // pega elementos
    const dateInput = document.getElementById('date');
    const dateInfo = document.getElementById('date-info');
    const tzField = document.getElementById('tz_offset');
    const prevBtn = document.getElementById('prev-day');
    const nextBtn = document.getElementById('next-day');

    // setar tz_offset (minutos) do navegador se vazio
    try {
        const offset = new Date().getTimezoneOffset(); // minutos
        if (tzField && tzField.value === '') tzField.value = offset;
    } catch (e) {}

    function formatFriendly(ymd) {
        if (!ymd) return '';
        const d = new Date(ymd + 'T00:00:00');
        if (isNaN(d.getTime())) return ymd;
        const today = new Date(); today.setHours(0,0,0,0);
        const diffDays = Math.round((d - today) / (1000*60*60*24));
        if (diffDays === 0) return '(Hoje)';
        if (diffDays === -1) return '(Ontem)';
        const weekdays = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
        return '(' + weekdays[d.getDay()] + ')';
    }

    function updateInfo() {
        if (!dateInput || !dateInfo) return;
        dateInfo.textContent = formatFriendly(dateInput.value);
    }

    function shiftDay(delta) {
        if (!dateInput) return;
        const d = new Date(dateInput.value + 'T00:00:00');
        d.setDate(d.getDate() + delta);
        const y = d.getFullYear(), m = (d.getMonth()+1).toString().padStart(2,'0'), day = d.getDate().toString().padStart(2,'0');
        dateInput.value = `${y}-${m}-${day}`;
        updateInfo();
    }

    if (prevBtn) prevBtn.addEventListener('click', function(){ shiftDay(-1); });
    if (nextBtn) nextBtn.addEventListener('click', function(){ shiftDay(1); });
    if (dateInput) dateInput.addEventListener('change', updateInfo);

    // inicializa
    updateInfo();
})();
</script>
</body>
</html>