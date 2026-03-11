<?php
require_once __DIR__ . '/includes/config.php';
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
session_start();
setFlash('success', 'Vous avez été déconnecté. À bientôt !');
redirect(BASE_URL . '/login.php');
