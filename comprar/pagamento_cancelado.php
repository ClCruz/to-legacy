<?php
session_start();

require_once('../settings/functions.php');
require_once('../settings/settings.php');

$mainConnection = mainConnection();

$json = json_encode(array('descricao' => '99. chamada pagamento_cancelado', 'erro' => isset($_COOKIE['ipagareError']), 'manualmente' => isset($_GET['manualmente']), 'expirado' => isset($_GET['tempoExpirado'])));
include('logiPagareChamada.php');

if (isset($_COOKIE['ipagareError'])) {
	foreach ($_COOKIE['ipagareError'] as $key => $val) {
		setcookie('ipagareError['.$key.']', '', -1);
	}
} else if (isset($_GET['manualmente']) or isset($_GET['tempoExpirado'])) {
	$query = 'SELECT DISTINCT E.ID_BASE
				 FROM
				 MW_EVENTO E
				 INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
				 INNER JOIN MW_RESERVA R ON R.ID_APRESENTACAO = A.ID_APRESENTACAO
				 WHERE R.ID_SESSION = ?';
	$params = array(session_id());
	$result = executeSQL($mainConnection, $query, $params);
	
	$conn = array();
	
	$noErrors = true;
	
	while ($rs = fetchResult($result)) {
		$conn[$rs['ID_BASE']] = getConnection($rs['ID_BASE']);
		beginTransaction($conn[$rs['ID_BASE']]);
		
		$query = 'DELETE FROM TABLUGSALA WHERE ID_SESSION = ? AND STACADEIRA = \'T\'';
		$params = array(session_id());
		executeSQL($conn[$rs['ID_BASE']], $query, $params);
		
		$sqlErrors = sqlErrors();
		$noErrors = (empty($sqlErrors) and $noErrors);
	}
	
	beginTransaction($mainConnection);
	
	if (isset($_COOKIE['pedido']) and is_numeric($_COOKIE['pedido'])) {
		$query = "UPDATE MW_PEDIDO_VENDA SET
					 IN_SITUACAO = CASE WHEN IN_SITUACAO <> 'N' THEN 'C' ELSE 'N' END,
					 CD_BIN_CARTAO = LEFT(CD_BIN_CARTAO, 6) + '******' + RIGHT(CD_BIN_CARTAO, 4)
					 WHERE ID_PEDIDO_VENDA = ? AND ID_CLIENTE = ? AND IN_SITUACAO <> 'F'";
		$params = array($_COOKIE['pedido'], $_SESSION['user']);
		executeSQL($mainConnection, $query, $params);
		
		$sqlErrors = sqlErrors();
		$noErrors = (empty($sqlErrors) and $noErrors);
	}
	
	$query = 'UPDATE MW_PROMOCAO SET ID_SESSION = NULL
				 WHERE ID_SESSION = ?';
	$params = array(session_id());
	executeSQL($mainConnection, $query, $params);
	
	$query = 'DELETE FROM MW_RESERVA
				 WHERE ID_SESSION = ?';
	$params = array(session_id());
	executeSQL($mainConnection, $query, $params);
	
	$sqlErrors = sqlErrors();
	$noErrors = (empty($sqlErrors) and $noErrors);
	
	$sqlErrors = sqlErrors();
	if ($noErrors and empty($sqlErrors)) {
		if (!isset($_SESSION['pos_user'])) {
			setcookie('pedido', '', -1);
			setcookie('id_braspag', '', -1);
		}
		commitTransaction($mainConnection);
		foreach ($conn as $connection) {
			commitTransaction($connection);
		}
	} else {
		rollbackTransaction($mainConnection);
		foreach ($conn as $connection) {
			rollbackTransaction($connection);
		}
	}
	
	if (!isset($_SESSION['pos_user']))
		setcookie('binItau', '', -1);

	unset($_SESSION['assinatura']);
	unset($_SESSION['origem']);
	
	if (isset($_SESSION['operador']) AND !isset($_GET['cielo'])) {
		unset($_SESSION['user']);
		setcookie('pedido', '', -1);
		setcookie('id_braspag', '', -1);
		header("Location: etapa0.php");
	}
}

$campanha = get_campanha_etapa('etapa5');

if ($_GET['cielo']) {
?>
<link rel='stylesheet' type='text/css' href='../stylesheets/reset.css' />

<link rel="stylesheet" type="text/css" href="../stylesheets/customred/jquery-ui-1.10.3.custom.css"/>

<link rel='stylesheet' type='text/css' href='../stylesheets/admin.css' />
<link rel='stylesheet' type='text/css' href='../stylesheets/ajustes.css' /> 

<link rel="stylesheet" href="../stylesheets/cicompra.css"/>
<?php require("desktopMobileVersion.php"); ?>
<link rel="stylesheet" href="../stylesheets/ajustes2.css"/>

<script src="../javascripts/jquery.2.0.0.min.js" type="text/javascript"></script>
<script src="../javascripts/jquery.placeholder.js" type="text/javascript"></script>
<script src="../javascripts/jquery.selectbox-0.2.min.js" type="text/javascript"></script>
<script src="../javascripts/jquery.mask.min.js" type="text/javascript"></script>
<script src="../javascripts/cicompra.js" type="text/javascript"></script>

<script src="../javascripts/jquery.utils2.js" type="text/javascript"></script>
<script src="../javascripts/common.js" type="text/javascript"></script>
<script type="text/javascript">
	$(function(){
		$.confirmDialog({
			text: '',
			detail: 'Pagamento recusado ou n&atilde;o autorizado<br>'+
					'Para continuar comprando escolha<br>'+
					'novamente seus ingressos.',
			uiOptions: {
				buttons: {
					'Ok, entendi': ['Leve-me de volta para a<br>p&aacute;gina inicial do site', null]
				}
			}
		});
		$('#resposta .opcao.unica').attr('href', '<?php echo $homeSite; ?>');
	});
</script>
<body id="pai"></body>
<?php
}
?>