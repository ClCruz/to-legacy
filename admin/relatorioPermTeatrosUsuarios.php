<?php
if ($_GET['xls']) {
	header("Content-type: application/vnd.ms-excel");
	header("Content-type: application/force-download");
	header("Content-Disposition: attachment; filename=relatorioPermTeatrosUsuarios.xls");
	?><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><?php
}

require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 13, true)) {

$pagina = basename(__FILE__);

	$query = 'SELECT DISTINCT u.id_usuario, u.ds_nome, u.ds_email, u.cd_login
	FROM mw_acesso_concedido ac INNER JOIN mw_usuario u ON u.id_usuario = ac.id_usuario
	WHERE ac.id_base = ? AND u.in_ativo = 1
	ORDER BY u.ds_nome';

	$result = fetchAssoc($NewResult = executeSQL($mainConnection, $query, array($_GET['local']) ) );
	
?>
<html>
<head>
</head>
<link rel="stylesheet" type="text/css" href="../stylesheets/customred/jquery-ui-1.10.3.custom.css"/>
<body>
<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script><script>
$(function() {
	$(document).ready(function(){
    	$('.button').button();
	})
	var pagina = '<?php echo $pagina; ?>';
	var getParamns = {};
	getUrlParamns();

	var selectLocal = document.getElementById('local');
	selectLocal.onchange = function () {
		setParamns('local', this.value);
		goTo();
	};
	
	$('tr:not(.ui-widget-header)').hover(function() {
		$(this).addClass('ui-state-hover');
	}, function() {
		$(this).removeClass('ui-state-hover');
	});

	/*
	* Pega os parametros GET e envia para um objeto Javascript
	* */
	function getUrlParamns()
	{
		var paramns = document.location.search;
		paramns = paramns.replace('?','&');
		paramns = paramns.split('&');
		var obj = {};
		var i =0;
		for(x in paramns)
		{
			var item = paramns[x];
			if (item != '')
			{
				item = item.split('=');
				eval("obj['"+item[0]+"'] = {}");
				eval("obj['"+item[0]+"'].value = '"+item[1]+"';");
				i++;
			}
		}
		getParamns = obj;
	}

	/*
	* Cria ou altera novos parametros GET que serão utilizados depois em goTo()
	* */
	function setParamns(paramn, value)
	{
		if (eval('getParamns.'+paramn))
		{
			eval('getParamns.'+paramn+'.value = "'+value+'"');
		}
		else
		{
			eval("getParamns['"+paramn+"'] = {}");
			eval("getParamns['"+paramn+"'].value = '"+value+"';");
		}
	}

	function goTo()
	{
		var i 	= 0;
		var str = '';
		for(x in getParamns)
		{
			str += ( i == 0 ) ? '?' : '&';
			str += x+'='+getParamns[x].value;
			i++;
		}

		document.location = document.location.origin + document.location.pathname + str;
	}
	$('.excell').click(function(e) {
	    e.preventDefault();

	    document.location = '<?php echo $pagina; ?>?' + $.serializeUrlVars() + '&xls=1';
	});
});
</script>
<?php if ($_GET['xls']) { ?>
	<h2>Permissões de Teatros X Usuários</h2>
<?php } else { ?>
	<?php echo comboTeatroPorUsuario('local', $_SESSION['admin'], $_GET['local']); ?>
	<?php if (isset($NewResult) && hasRows($NewResult)) { ?>
	&nbsp;&nbsp;<a class="button excell" href="#">Exportar Excel</a>
	<?php } ?>
<?php } ?>
<br><br>
<table class="ui-widget ui-widget-content">
	<thead>
		<tr class="ui-widget-header">
			<th>ID</th>
			<th>Nome</th>
			<th>E-mail</th>
			<th>Login</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($result as $resp): ?>
			<tr>
				<td><?php echo $resp['id_usuario']; ?></td>
				<td><?php echo utf8_encode2($resp['ds_nome']); ?></td>
				<td><?php echo $resp['ds_email']; ?></td>
				<td><?php echo $resp['cd_login'] ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
</body>
</html>
<?php
}
?>