<?php
require_once 'connection.php';
// ...existing code...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$produto_id_raw = trim($_POST['produto_id'] ?? '');

// se preenchido, exige exatamente 6 dígitos
if ($produto_id_raw !== '' && !preg_match('/^\d{6}$/', $produto_id_raw)) {
    echo 'ID inválido: se informado, deve conter exatamente 6 dígitos.';
    exit;
}
$hasId = ($produto_id_raw !== '');
$produto_id = $hasId ? (int)$produto_id_raw : null;

    // novo campo obrigatório
    $nome = trim($_POST['nome'] ?? '');

    $referencia = trim($_POST['referencia'] ?? '');
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $local = trim($_POST['local'] ?? '');

    // validação: agora nome, referencia e local são obrigatórios
    if ($nome === '' || $referencia === '' || $local === '') {
        echo 'Preencha todos os campos obrigatórios: nome, referência e local.<br>';
        exit;
    }

    // Monta INSERT condicional: com ou sem id (para permitir auto-increment)
    if ($hasId) {
        $sql = 'INSERT INTO produtos (id, nome, referencia, quantidade, `local`) VALUES (:id, :nome, :referencia, :quantidade, :local)';
    } else {
        $sql = 'INSERT INTO produtos (nome, referencia, quantidade, `local`) VALUES (:nome, :referencia, :quantidade, :local)';
    }

    $stmt = $pdo->prepare($sql);

    try {
        $params = [
            ':nome' => $nome,
            ':referencia' => $referencia,
            ':quantidade' => $quantidade,
            ':local' => $local
        ];
        if ($hasId) {
            $params[':id'] = $produto_id;
        }

        $stmt->execute($params);

        // ...existing code...
        header('Location: produtos_cadastrar.php');
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // violação de UNIQUE / chave primária
            echo 'Já existe um produto com este ID.<br>';
        } else {
            echo 'Erro ao inserir produto: ' . htmlspecialchars($e->getMessage()) . '<br>';
        }
    }
}
?>