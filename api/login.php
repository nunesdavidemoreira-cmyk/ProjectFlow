<?php
session_start();
require_once 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id_utilizador, nome, tipo, password FROM UtilizadorPF WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        // Verifica se a password coincide em hash (password_verify) ou em texto simples (para testes)
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['user_id'] = $user['id_utilizador'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_tipo'] = $user['tipo'];
            header("Location: index.php");
            exit;
        } else {
            $erro = "Palavra-passe incorreta.";
        }
    } else {
        $erro = "Utilizador não encontrado.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>ProjectFlow - Login</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body class="bg-login">
    <div class="card-login">
        <h2>ProjectFlow - Entrar</h2>
        <?php if ($erro): ?><p class="msg-erro"><?= htmlspecialchars($erro) ?></p><?php endif; ?>
        <form method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required placeholder="Insira o seu email">
            
            <label for="password">Palavra-passe:</label>
            <input type="password" id="password" name="password" required placeholder="Insira a sua palavra-passe">
            
            <button type="submit">Iniciar Sessão</button>
        </form>
    </div>
</body>
</html>
