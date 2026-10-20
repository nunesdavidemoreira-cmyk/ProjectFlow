<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user_tipo'] === 'aluno') {
    header("Location: aluno_dashboard.php");
} else {
    header("Location: professor_dashboard.php");
}
exit;
?>