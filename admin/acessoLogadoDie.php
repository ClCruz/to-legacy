<?php
//ACESSO PERMITIDO APENAS PARA ADMINS LOGADOS
session_start();
if (!isset($_SESSION['admin'])) {
	die('Acesso negado.');
}
?>