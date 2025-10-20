<?php
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $table = $_POST['table'];

    if (!empty($id) && !empty($table)) {
        $sql = "DELETE FROM $table WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            echo "Registro deletado com sucesso!" . '<br>';
        } else {
            echo "Erro ao deletar registro." . '<br>';
        }
    } else {
        echo "ID e tabela não podem estar vazios." . '<br>';
    }
}
?>