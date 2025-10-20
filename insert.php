<?php
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto_id = intval($_POST['produto_id'] ?? 0);
    $referencia = trim($_POST['referencia'] ?? '');
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $local = trim($_POST['local'] ?? '');

    if ($produto_id <= 0 || $referencia === '' || $local === '') {
        echo 'Preencha todos os campos corretamente.<br>';
        exit;
    }

    // Ajuste o nome da tabela/colunas conforme seu esquema (aqui uso "produtos" e coluna "produto_id")
    $sql = 'INSERT INTO produtos (id, referencia, quantidade, `local`) 
            VALUES (:produto_id, :referencia, :quantidade, :local)';
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([
            ':produto_id' => $produto_id,
            ':referencia' => $referencia,
            ':quantidade' => $quantidade,
            ':local' => $local
        ]);
        header('Location: produtos_cadastrar.php');
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // violação de UNIQUE / chave primária
            echo 'Já existe um produto com este ID.<br>';
        } else {
            echo 'Erro ao inserir produto: ' . $e->getMessage() . '<br>';
        }
    }
}
?>