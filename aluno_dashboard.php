<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'aluno') {
    header("Location: login.php");
    exit;
}

$id_aluno = $_SESSION['user_id'];

// Obter dados do projeto na tabela ProjetoPF
$proj_stmt = $conn->prepare("SELECT * FROM ProjetoPF WHERE id_aluno = ? LIMIT 1");
$proj_stmt->bind_param("i", $id_aluno);
$proj_stmt->execute();
$projeto = $proj_stmt->get_result()->fetch_assoc();
$id_projeto = $projeto['id_projeto'] ?? 0;

// --- PROCESSAR OPERAÇÕES CRUD (CREATE, UPDATE, DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    
    // 1. CREATE (Inserir)
    if ($_POST['acao'] === 'add_tarefa') {
        $stmt = $conn->prepare("INSERT INTO TarefasPF (titulo, descricao, estado, data_final, id_projeto) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $_POST['titulo'], $_POST['descricao'], $_POST['estado'], $_POST['data_final'], $id_projeto);
        $stmt->execute();
    } elseif ($_POST['acao'] === 'add_diario') {
        $stmt = $conn->prepare("INSERT INTO RegistoDiarioPF (descricao, id_projeto) VALUES (?, ?)");
        $stmt->bind_param("si", $_POST['descricao'], $id_projeto);
        $stmt->execute();
    } elseif ($_POST['acao'] === 'add_recurso') {
        $stmt = $conn->prepare("INSERT INTO RecursoPF (titulo, descricao, url_link, id_projeto) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $_POST['titulo'], $_POST['descricao'], $_POST['url_link'], $id_projeto);
        $stmt->execute();
    } 
    
    // 2. UPDATE (Atualizar Estado da Tarefa)
    elseif ($_POST['acao'] === 'update_estado_tarefa') {
        $stmt = $conn->prepare("UPDATE TarefasPF SET estado = ? WHERE id_tarefa = ? AND id_projeto = ?");
        $stmt->bind_param("sii", $_POST['novo_estado'], $_POST['id_tarefa'], $id_projeto);
        $stmt->execute();
    }
    
    // 3. DELETE (Eliminar Registos)
    elseif ($_POST['acao'] === 'delete_tarefa') {
        $stmt = $conn->prepare("DELETE FROM TarefasPF WHERE id_tarefa = ? AND id_projeto = ?");
        $stmt->bind_param("ii", $_POST['id_tarefa'], $id_projeto);
        $stmt->execute();
    } elseif ($_POST['acao'] === 'delete_diario') {
        $stmt = $conn->prepare("DELETE FROM RegistoDiarioPF WHERE id_registo = ? AND id_projeto = ?");
        $stmt->bind_param("ii", $_POST['id_registo'], $id_projeto);
        $stmt->execute();
    } elseif ($_POST['acao'] === 'delete_recurso') {
        $stmt = $conn->prepare("DELETE FROM RecursoPF WHERE id_recurso = ? AND id_projeto = ?");
        $stmt->bind_param("ii", $_POST['id_recurso'], $id_projeto);
        $stmt->execute();
    }

    header("Location: aluno_dashboard.php");
    exit;
}

// --- READ (Consultar dados das tabelas PF) ---
$tarefas = $conn->query("SELECT * FROM TarefasPF WHERE id_projeto = $id_projeto ORDER BY id_tarefa DESC");
$diarios = $conn->query("SELECT * FROM RegistoDiarioPF WHERE id_projeto = $id_projeto ORDER BY data DESC");
$recursos = $conn->query("SELECT * FROM RecursoPF WHERE id_projeto = $id_projeto ORDER BY data DESC");
$feedbacks = $conn->query("SELECT f.*, u.nome as prof_nome FROM FeedbackPF f JOIN UtilizadorPF u ON f.id_professor = u.id_utilizador WHERE f.id_projeto = $id_projeto ORDER BY f.data DESC");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel do Aluno - ProjectFlow</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="topbar">
        <span>Bem-vindo, <strong><?= htmlspecialchars($_SESSION['user_nome']) ?></strong> (Aluno)</span>
        <a href="logout.php" class="btn-logout">Sair</a>
    </div>

    <div class="container">
        <h1>Projeto PAP: <?= htmlspecialchars($projeto['tema'] ?? 'Sem Projeto Assignado') ?></h1>
        <p><strong>Área:</strong> <?= htmlspecialchars($projeto['area'] ?? '') ?> | <strong>Data de Entrega:</strong> <?= htmlspecialchars($projeto['data_fim'] ?? '') ?></p>

        <!-- SECÇÃO DE FEEDBACKS DO PROFESSOR (READ) -->
        <div class="box feedback-box">
            <h2>💬 Feedback do Professor</h2>
            <?php if ($feedbacks && $feedbacks->num_rows > 0): ?>
                <?php while ($f = $feedbacks->fetch_assoc()): ?>
                    <div class="item-feedback">
                        <small><strong><?= htmlspecialchars($f['prof_nome']) ?></strong> em <?= $f['data'] ?></small>
                        <p><?= nl2br(htmlspecialchars($f['comentario'])) ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Ainda não recebeu feedback do professor.</p>
            <?php endif; ?>
        </div>

        <div class="grid-2">
            <!-- CREATE: ADICIONAR TAREFA -->
            <div class="box">
                <h3>📌 Nova Tarefa</h3>
                <form method="POST">
                    <input type="hidden" name="acao" value="add_tarefa">
                    <input type="text" name="titulo" placeholder="Título da tarefa" required>
                    <textarea name="descricao" placeholder="Descrição"></textarea>
                    <select name="estado">
                        <option value="Pendente">Pendente</option>
                        <option value="Em Progresso">Em Progresso</option>
                        <option value="Concluído">Concluído</option>
                    </select>
                    <label>Data limite:</label>
                    <input type="date" name="data_final" required>
                    <button type="submit">Adicionar Tarefa</button>
                </form>
            </div>

            <!-- CREATE: REGISTO DIÁRIO -->
            <div class="box">
                <h3>📓 Novo Registo Diário</h3>
                <form method="POST">
                    <input type="hidden" name="acao" value="add_diario">
                    <textarea name="descricao" placeholder="O que realizou hoje no seu trabalho da PAP?" required></textarea>
                    <button type="submit">Guardar Registo</button>
                </form>
            </div>
        </div>

        <!-- CREATE: RECURSOS E LINKS -->
        <div class="box">
            <h3>🔗 Adicionar Recurso / Link</h3>
            <form method="POST">
                <input type="hidden" name="acao" value="add_recurso">
                <input type="text" name="titulo" placeholder="Título do Recurso" required>
                <input type="url" name="url_link" placeholder="https://exemplo.com" required>
                <input type="text" name="descricao" placeholder="Pequena descrição">
                <button type="submit">Adicionar Link</button>
            </form>
        </div>

        <!-- READ, UPDATE & DELETE: LISTA DE TAREFAS -->
        <div class="box">
            <h3>Lista de Tarefas</h3>
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Descrição</th>
                        <th>Estado</th>
                        <th>Data Limite</th>
                        <th>Ações (Update / Delete)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tarefas && $tarefas->num_rows > 0): ?>
                        <?php while ($t = $tarefas->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t['titulo']) ?></strong></td>
                                <td><?= htmlspecialchars($t['descricao']) ?></td>
                                <td><span class="badge <?= strtolower(str_replace(' ', '-', $t['estado'])) ?>"><?= $t['estado'] ?></span></td>
                                <td><?= $t['data_final'] ?></td>
                                <td>
                                    <!-- UPDATE ESTADO -->
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="acao" value="update_estado_tarefa">
                                        <input type="hidden" name="id_tarefa" value="<?= $t['id_tarefa'] ?>">
                                        <select name="novo_estado" onchange="this.form.submit()" class="select-sm">
                                            <option value="Pendente" <?= $t['estado'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                                            <option value="Em Progresso" <?= $t['estado'] === 'Em Progresso' ? 'selected' : '' ?>>Em Progresso</option>
                                            <option value="Concluído" <?= $t['estado'] === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                                        </select>
                                    </form>
                                    
                                    <!-- DELETE TAREFA -->
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Tem a certeza que pretende eliminar esta tarefa?');">
                                        <input type="hidden" name="acao" value="delete_tarefa">
                                        <input type="hidden" name="id_tarefa" value="<?= $t['id_tarefa'] ?>">
                                        <button type="submit" class="btn-danger btn-sm">🗑️ Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">Nenhuma tarefa registada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- READ & DELETE: REGISTOS E RECURSOS -->
        <div class="grid-2">
            <div class="box">
                <h3>Diário de Bordo</h3>
                <ul>
                    <?php if ($diarios && $diarios->num_rows > 0): ?>
                        <?php while ($d = $diarios->fetch_assoc()): ?>
                            <li class="item-lista">
                                <div>
                                    <small><?= $d['data'] ?></small><br>
                                    <span><?= htmlspecialchars($d['descricao']) ?></span>
                                </div>
                                <form method="POST" onsubmit="return confirm('Eliminar este registo?');">
                                    <input type="hidden" name="acao" value="delete_diario">
                                    <input type="hidden" name="id_registo" value="<?= $d['id_registo'] ?>">
                                    <button type="submit" class="btn-danger btn-sm">🗑️</button>
                                </form>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li>Nenhum registo no diário.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="box">
                <h3>Recursos Registados</h3>
                <ul>
                    <?php if ($recursos && $recursos->num_rows > 0): ?>
                        <?php while ($r = $recursos->fetch_assoc()): ?>
                            <li class="item-lista">
                                <div>
                                    <a href="<?= htmlspecialchars($r['url_link']) ?>" target="_blank"><strong><?= htmlspecialchars($r['titulo']) ?></strong></a>
                                    <br><small><?= htmlspecialchars($r['descricao']) ?></small>
                                </div>
                                <form method="POST" onsubmit="return confirm('Eliminar este recurso?');">
                                    <input type="hidden" name="acao" value="delete_recurso">
                                    <input type="hidden" name="id_recurso" value="<?= $r['id_recurso'] ?>">
                                    <button type="submit" class="btn-danger btn-sm">🗑️</button>
                                </form>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li>Nenhum recurso registado.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

    </div>
</body>
</html>