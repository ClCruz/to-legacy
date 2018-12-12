<?php
session_start();

require 'logado.php';

require_once('../settings/functions.php');
$mainConnection = mainConnection();

function gridIngressos($conn, $apresentacao) {
	$grid = '';

	$query = 'SELECT ID_APRESENTACAO_BILHETE, DS_TIPO_BILHETE, VL_LIQUIDO_INGRESSO
				FROM MW_APRESENTACAO_BILHETE
				WHERE ID_APRESENTACAO = ? AND IN_ATIVO = 1
				ORDER BY DS_TIPO_BILHETE';
	$result = executeSQL($conn, $query, array($apresentacao));
	$errors = sqlErrors();
	print_r($errors);
	while ($rs = fetchResult($result)) {
		$class = ($class != 'e' ? 'e' : 'c');
		$grid .= 	'<tr class="'.$class.'">
						<td><input type="hidden" name="bilhete[]" value="'.$rs['ID_APRESENTACAO_BILHETE'].'" />'.$rs['DS_TIPO_BILHETE'].'</td>
						<td class="c"><input type="text" class="qtd" name="qtd[]" maxlength="2" value="0" /></td>
						<td class="r"><span class="val">'.number_format($rs['VL_LIQUIDO_INGRESSO'], 2, ',', '').'</span></td>
					</tr>';
	}
	
	return $grid;
}

if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
	header('Content-type: application/json');
	$data = array();
	
	if ($_GET['action'] === 'cpf_search') {
		$query = 'SELECT CD_RG, DS_DDD_TELEFONE, DS_TELEFONE,  DS_NOME, DS_SOBRENOME, CD_EMAIL_LOGIN FROM MW_CLIENTE WHERE CD_CPF = ?';
		$params = array($_POST['cpf']);
		$result = executeSQL($mainConnection, $query, $params);
		
		if (hasRows($result)) {
			$rs = fetchResult($result);
			$data['cpf'] = $_POST['cpf'];
			$data['rg'] = $rs['CD_RG'];
			$data['ddd'] = $rs['DS_DDD_TELEFONE'];
			$data['telefone'] = $rs['DS_TELEFONE'];
			$data['ramal'] = null;
			$data['nome'] = $rs['DS_NOME'] ? $rs['DS_NOME'].' '.$rs['DS_SOBRENOME'] : null;
			$data['email'] = $rs['CD_EMAIL_LOGIN'];
			$data['ncartao'] = null;
		}
	} else if ($_GET['action'] === 'evento_combo') {
		$data['html'] = comboApresentacoesItau('', $_SESSION['userItau'], $_POST['evento']);
	} else if ($_GET['action'] === 'apresentacao_combo') {
		$data['html'] = gridIngressos($mainConnection, $_POST['apresentacao']);
	}
	//$data['error'] = 'teste de erro';
	exit(json_encode($data));
}

$userData = executeSQL($mainConnection, 'SELECT DS_NOME FROM MW_USUARIO_ITAU WHERE ID_USUARIO = ?', array($_SESSION['userItau']), true);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<?php require('header.php'); ?>
	</head>
	<body>
		<div id="sisbin">
			<div class="cabecalho">
				<div class="usuario">Bem Vindo, <b><?php echo $userData['DS_NOME']; ?></b></div>
				<div class="logo"><img src="../images/itau/logo_itaucard.jpg" alt="Itaucard" title="Itaucard" /></div>
			</div>
			<div class="bar_top">SISBIN - Sistema de Controle de BINs Promo&ccedil;&atilde;o Itaucard<div class="sair"><a href="login.php?logout">Sair</a></div></div>
			<div class="container">
				<form id='compra' action="confirmar.php" method="post">
					<div class="cont_sistema">
						<div class="top">
							<div class="descricao">
								<p>Evento</p>
								<div class="select">
									<div class="left"></div>
									<?php echo comboEventosItau('evento', $_SESSION['userItau'], $_GET['evento']); ?>
								</div>
							</div>
							<div class="descricao">
								<p>Apresentação</p>
								<div class="select">
									<div class="left"></div>
									<?php echo comboApresentacoesItau('apresentacao', $_SESSION['userItau'], $_GET['evento'], $_GET['apresentacao']); ?>
								</div>
							</div>
						</div>
						<div class="left">
							<div class="ing_qtd">
								<h1>Ingressos e Quantidades</h1>
								<table class="ing_qtd">
									<thead>
										<tr>
											<th width="350">Tipo de Bilhete</th>
											<th width="30">Qtd</th>
											<th width="60">Preço R$</th>
										</tr>
									</thead>
									<tbody>
										<?php echo gridIngressos($mainConnection, $_GET['apresentacao']); ?>
									</tbody>
								</table>
							</div>
							<div class="dados_cli">
								<h1>Dados do Cliente</h1>
								<div class="linha">
									<div class="campo">
										<p>CPF</p>
										<div class="cont_input dark">
											<div class="contorno_left"></div>
											<input type="text" name="cpf" maxlength="14" style="width:100px" />
											<div class="contorno_right"></div>
										</div>
									</div>
									
									<div class="campo">
										<p>RG</p>
										<div class="cont_input">
											<div class="contorno_left"></div>
											<input type="text" name="rg" maxlength="16" style="width:100px" />
											<div class="contorno_right"></div>
										</div>
									</div>
									
									<div class="campo">
										<p>DDD</p>
										<div class="cont_input">
											<div class="contorno_left"></div>
											<input type="text" name="ddd" maxlength="2" style="width:20px" />
											<div class="contorno_right"></div>
										</div>
									</div>
									
									<div class="campo">
										<p>Telefone</p>
										<div class="cont_input">
											<div class="contorno_left"></div>
											<input type="text" name="telefone" maxlength="9" style="width:70px" />
											<div class="contorno_right"></div>
										</div>
									</div>
									
									<div class="campo nomargin">
										<p>Ramal</p>
										<div class="cont_input">
											<div class="contorno_left"></div>
											<input type="text" name="ramal" maxlength="6" style="width:20px" />
											<div class="contorno_right"></div>
										</div>
									</div>
								</div>
								<div class="linha">
									<div class="campo nomargin">
										<p>Nome</p>
										<div class="cont_input dark">
											<div class="contorno_left"></div>
											<input type="text" name="nome" maxlength="250" style="width:470px" />
											<div class="contorno_right"></div>
										</div>
									</div>
								</div>
								<div class="linha">
									<div class="campo nomargin">
										<p>E-mail do Cliente</p>
										<div class="cont_input">
											<div class="contorno_left"></div>
											<input type="text" name="email" maxlength="250" style="width:470px" />
											<div class="contorno_right"></div>
										</div>
									</div>
								</div>
								<div class="linha nomargin">
									<div class="campo">
										<p>Nº do Cart&atilde;o Participante da Promo&ccedil;&atilde;o</p>
										<div class="cont_input dark">
											<div class="contorno_left"></div>
											<input type="text" name="ncartao" maxlength="16" style="width:270px" />
											<div class="contorno_right"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="right">
							<h1>Forma de Pagamento</h1>
							<div class="select">
								<div class="left"></div>
								<select name="pagamento">
									<option value="">Cr&eacute;dito/D&eacute;bito</option>
								</select>
							</div>
							<div class="resumo">
								<h2>Resumo do caixa</h2>
								<p class="descricao">Total à Pagar</p>
								<p class="valor" id="res_total">0,00</p>
								
								<p class="descricao">Valor recebido</p>
								<div class="cont_input">
									<div class="contorno_left"></div>
									<input type="text" name="usuario" id="res_recebido" maxlength="9" style="width:190px" value="0,00" />
									<div class="contorno_right"></div>
								</div>
								
								<p class="descricao">Troco</p>
								<p class="valor" id="res_troco">0,00</p>
							</div>
							<div class="container">
								<a href="#"><div class="bt confirmar disable"></div></a>
								<a href="#"><div class="bt estornar disable ui-helper-hidden"></div></a>
								<a href="#"><div class="bt reimprimir disable ui-helper-hidden"></div></a>
								<a href="#"><div class="bt reservar disable ui-helper-hidden"></div></a>
								<a href="#"><div class="bt reservas disable ui-helper-hidden"></div></a>
							</div>
						</div>
					</div>
				</form>
				<div id="dialog-confirm" title="Confirma&ccedil;&atilde;o..." class="ui-helper-hidden">
					<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Os dados informados est&atilde;o corretos?<br/><br/>Efetivar a venda?</p>
				</div>
			</div>
			<div class="bar_bottom"></div>
		</div>
		<?php
		//require 'mensagens.php';
		?>
	</body>
</html>
