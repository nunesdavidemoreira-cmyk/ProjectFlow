<?php
include('conexao.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = $_POST['descricao'];

    if (!empty($descricao)) {
        // Inserção simples na tabela todo_tarefas
        $stmt = $conn->prepare("INSERT INTO todo_tarefas (descricao) VALUES (?)");
        $stmt->bind_param("s", $descricao);

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $erro = "Erro ao guardar no banco de dados.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Novo Registo - ProjectFlow</title>
</head>
<body>
    <h2>Adicionar Registo ao ProjectFlow</h2>
    
    <?php if(isset($erro)) echo "<p style='color:red;'>$erro</p>"; ?>

    <form method="POST" action="">
        <label for="descricao">Descrição da Tarefa ou Atividade:</label><br><br>
        <textarea name="descricao" id="descricao" rows="5" cols="40" required></textarea><br><br>
        <button type="submit">Guardar Registo</button>
        <a href="index.php">Cancelar</a>
    </form>
</body>
</html>
