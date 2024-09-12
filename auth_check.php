<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    // Se não estiver autenticado, redireciona para a página de login
    header('Location: login.php');
    exit();
}
