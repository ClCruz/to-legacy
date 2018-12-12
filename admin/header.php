<?php

require_once($_SERVER['DOCUMENT_ROOT']."/settings/functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
session_start();
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
    
    <title><?php echo $title; ?></title>
    
    <link rel='stylesheet' type='text/css' href='../stylesheets/reset.css' />
	
    <link rel="stylesheet" type="text/css" href="../stylesheets/customred/jquery-ui-1.10.3.custom.css"/>
	<!--<link rel="stylesheet" type="text/css" href="../stylesheets/customred/jquery-ui-1.7.3.custom.css"/>-->
	 
    <link rel='stylesheet' type='text/css' href='../stylesheets/admin.css' />
    <link rel='stylesheet' type='text/css' href='../stylesheets/ajustes.css' /> 
	 
	<!--[if IE]>
	<link rel="stylesheet" type="text/css" href="../stylesheets/ajustesIE.css"/>
	<![endif]-->
    
    <script type='text/javascript' src='../javascripts/jquery.js'></script>
    <script type='text/javascript' src='../javascripts/jquery-ui.js'></script>
    <script type='text/javascript' src='../javascripts/jquery.ui.datepicker-pt-BR.js'></script>
    <script type='text/javascript' src='../javascripts/jquery.utils.js'></script>
	 
	 <script>
		 $(function(){

		 	$('#menu-bt').button({
				text: true,
				icons: {
					secondary: "ui-icon-triangle-1-s"
				}
	        }).removeClass('ui-corner-all').on('click', function(){
	        	$('#menu-items').slideToggle();
	        });

	        $('#menu-items').hide().menu();
			
		 });
	 </script>
</head>

<body>
<div id='holder'>
	<div id='header'>
    	<p style='font-weight:bold' id="clock"><?php echo date('d/m/Y - H:i (T)'); ?></p>
		<?php
		if (isset($_SESSION['admin'])) {
			$mainConnection = mainConnection();
			$query = 'SELECT DS_NOME FROM MW_USUARIO WHERE ID_USUARIO = ?';
			$params = array($_SESSION['admin']);
			$rs = executeSQL($mainConnection, $query, $params, true);
		?>
        <p>Bem vindo, <?php echo $rs['DS_NOME']; ?>!<br />
		[<a href='./login.php?action=trocarSenha'>Trocar Senha</a>] [<a href='./login.php?action=logout'>Sair</a>]</p>
		
    	<?php
		}
		echo getSiteLogo();
		?>
    </div>
    
    <div id='mainMenu'>
    	<?php
		if (isset($_SESSION['admin'])) {
			require_once('mainMenu.php');
		}
		?>
    </div>