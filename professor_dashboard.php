<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: login.php");
    exit;
}

$id_professor = $_SESSION['user_id'];

// Consultar o projeto do aluno associado
$proj_stmt = $conn->prepare("
    SELECT p.*, u.nome AS aluno_nome 
    FROM ProjetoPF p 
    JOIN UtilizadorPF u ON p.id_aluno = u.id_utilizador 
    WHERE p.id_professor = ? LIMIT 1
");
$proj_stmt->bind_param("i", $id_professor);
$proj_stmt->execute();
$projeto = $proj_stmt->get_result()->fetch_assoc();
$id_projeto = $projeto['id_projeto'] ?? 0;

// OPERAÇÕES CRUD DO PROFESSOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'add_feedback') {
        $comentario = trim($_POST['comentario']);
        if (!empty($comentario)) {
            $stmt = $conn->prepare("INSERT INTO FeedbackPF (comentario, id_projeto, id_professor) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $comentario, $id_projeto, $id_professor);
            $stmt->execute();
        }
    } elseif ($_POST['acao'] === 'delete_feedback') {
        $stmt = $conn->prepare("DELETE FROM FeedbackPF WHERE id_feedback = ? AND id_professor = ?");
        $stmt->bind_param("ii", $_POST['id_feedback'], $id_professor);
        $stmt->execute();
    }
    header("Location: professor_dashboard.php");
    exit;
}

// READ
$tarefas = $conn->query("SELECT * FROM TarefasPF WHERE id_projeto = $id_projeto ORDER BY id_tarefa DESC");
$diarios = $conn->query("SELECT * FROM RegistoDiarioPF WHERE id_projeto = $id_projeto ORDER BY data DESC");
$recursos = $conn->query("SELECT * FROM RecursoPF WHERE id_projeto = $id_projeto ORDER BY data DESC");
$feedbacks = $conn->query("SELECT * FROM FeedbackPF WHERE id_projeto = $id_projeto ORDER BY data DESC");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel do Professor - ProjectFlow</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="topbar prof">
        <span>Painel do Orientador: <strong><?= htmlspecialchars($_SESSION['user_nome']) ?></strong></span>
        <a href="logout.php" class="btn-logout">Sair</a>
    </div>

    <div class="container">
        <h1>Acompanhamento da PAP: <?= htmlspecialchars($projeto['aluno_nome'] ?? 'Aluno') ?></h1>
        <p><strong>Tema:</strong> <?= htmlspecialchars($projeto['tema'] ?? '') ?></p>

        <!-- CREATE: NOVO FEEDBACK -->
        <div class="box feedback-box">
            <h2>✍️ Enviar Novo Feedback ao Aluno</h2>
            <form method="POST">
                <input type="hidden" name="acao" value="add_feedback">
                <textarea name="comentario" rows="4" placeholder="Escreva aqui as observações, correções ou orientações para o David Freitas..." required></textarea><br>
                <button type="submit" class="btn-prof">Publicar Feedback</button>
            </form>
        </div>

        <!-- READ & DELETE: HISTÓRICO DE FEEDBACKS -->
        <div class="box">
            <h3>Histórico de Feedbacks Enviados</h3>
            <?php if ($feedbacks && $feedbacks->num_rows > 0): ?>
                <?php while ($f = $feedbacks->fetch_assoc()): ?>
                    <div class="item-feedback item-lista">
                        <div>
                            <small>Enviado em: <?= $f['data'] ?></small>
                            <p><?= nl2br(htmlspecialchars($f['comentario'])) ?></p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Eliminar este feedback?');">
                            <input type="hidden" name="acao" value="delete_feedback">
                            <input type="hidden" name="id_feedback" value="<?= $f['id_feedback'] ?>">
                            <button type="submit" class="btn-danger btn-sm">🗑️ Eliminar</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Nenhum feedback enviado até ao momento.</p>
            <?php endif; ?>
        </div>

        <!-- READ: REGISTOS DO ALUNO -->
        <div class="grid-2">
            <div class="box">
                <h3>Tarefas Criadas pelo Aluno</h3>
                <table>
                    <thead>
                        <tr><th>Tarefa</th><th>Estado</th><th>Data Limite</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($t = $tarefas->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t['titulo']) ?></strong><br><small><?= htmlspecialchars($t['descricao']) ?></small></td>
                                <td><span class="badge <?= strtolower(str_replace(' ', '-', $t['estado'])) ?>"><?= $t['estado'] ?></span></td>
                                <td><?= $t['data_final'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="box">
                <h3>Diário de Bordo do Aluno</h3>
                <ul>
                    <?php while ($d = $diarios->fetch_assoc()): ?>
                        <li><small><?= $d['data'] ?></small> - <?= htmlspecialchars($d['descricao']) ?></li>
                    <?php endwhile; ?>
                </ul>

                <h3>Recursos e Links Partilhados</h3>
                <ul>
                    <?php while ($r = $recursos->fetch_assoc()): ?>
                        <li><a href="<?= htmlspecialchars($r['url_link']) ?>" target="_blank"><?= htmlspecialchars($r['titulo']) ?></a></li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>

    </div>
</body>
</html>