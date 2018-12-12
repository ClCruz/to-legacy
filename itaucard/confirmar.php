<?php
session_start();

require 'logado.php';

if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') header('Content-type: application/json');

require_once('../settings/functions.php');
require_once('../settings/settings.php');
$mainConnection = mainConnection();




//--------------------------------------------------
//verificar se os dados enviados s�o v�lidos
//--------------------------------------------------
if ($_POST['evento'] == '' or $_POST['apresentacao'] == '') {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Favor informar o evento e a apresenta��o.'))));
}
if (!is_numeric($_POST['cpf'])) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Favor informar apenas n�meros no campo CPF.'))));
}
if (!verificaCPF($_POST['cpf'])) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('CPF inv�lido.'))));
}
/*if (!is_numeric($_POST['ddd']) or !is_numeric($_POST['telefone'])) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Favor informar apenas n�meros nos campos DDD e telefone.'))));
}*/
$nome_completo = explode(' ', $_POST['nome'], 2);
if (count($nome_completo) > 1) {
	$nome = $nome_completo[0];
	$sobrenome = $nome_completo[1];
} else {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Favor informar o nome completo.'))));
}
/*if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Favor informar um e-mail v�lido.'))));
}*/
if (!is_numeric($_POST['ncartao'])) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Favor informar apenas n�meros no campo N� do Cart�o.'))));
}
if (strlen($_POST['ncartao']) != 16) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Favor informar os 16 n�meros no campo N� do Cart�o.'))));
}

$rs = executeSQL($mainConnection, 'SELECT ID_BASE FROM MW_EVENTO WHERE ID_EVENTO = ?', array($_POST['evento']), true);
$id_base = $rs['ID_BASE'];
$conn = getConnection($id_base);




//--------------------------------------------------
//o cart�o participa da promo��o neste evento?
//--------------------------------------------------
$query = 'SELECT TOP 1 1
			FROM 
			CI_MIDDLEWAY..MW_APRESENTACAO A
			INNER JOIN CI_MIDDLEWAY..MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
			INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO = A.ID_APRESENTACAO
				AND AB.IN_ATIVO = 1
			INNER JOIN TABPECA P ON P.CODPECA = E.CODPECA
				AND P.CODTIPBILHETEBIN = AB.CODTIPBILHETE 
			INNER JOIN CI_MIDDLEWAY..MW_EVENTO_PATROCINADO EP ON EP.CODPECA = E.CODPECA
				AND EP.ID_BASE = E.ID_BASE
				AND A.DT_APRESENTACAO BETWEEN EP.DT_INICIO AND EP.DT_FIM
			INNER JOIN CI_MIDDLEWAY..MW_CARTAO_PATROCINADO CP ON CP.ID_CARTAO_PATROCINADO = EP.ID_CARTAO_PATROCINADO
				AND CP.CD_BIN = ?
			WHERE A.ID_APRESENTACAO = ?
			AND P.IN_BIN_ITAU = 1';
$params = array(substr($_POST['ncartao'], 0, 6), $_POST['apresentacao']);
$result = executeSQL($conn, $query, $params);
if (!hasRows($result)) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Este N� de Cart�o n�o � participante da promo��o.'))));
}




//--------------------------------------------------
//usu�rio j� cadastrado no sistema? se n�o, incluir
//--------------------------------------------------
$result = executeSQL($mainConnection, 'SELECT ID_CLIENTE FROM MW_CLIENTE WHERE CD_CPF = ?', array($_POST['cpf']));
if (hasRows($result)) {
	$rs = fetchResult($result);
	$id_cliente = $rs['ID_CLIENTE'];
} else {
	$result = executeSQL($mainConnection, 'SELECT ID_CLIENTE FROM MW_CLIENTE WHERE CD_EMAIL_LOGIN = ?', array($_POST['email']));
	if (hasRows($result)) {
		exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('N�o foi poss�vel cadastrar o cliente.<br><br>Este e-mail j� existe no sistema.'))));
	}
	
	$query = "INSERT INTO MW_CLIENTE (DS_NOME,DS_SOBRENOME,DS_DDD_TELEFONE,DS_TELEFONE,CD_RG,CD_CPF,
				CD_EMAIL_LOGIN,IN_RECEBE_INFO,IN_RECEBE_SMS,IN_CONCORDA_TERMOS,DT_INCLUSAO)
				VALUES (?, ?, ?, ?, ?, ?, ?, 'S', 'N', 'N', GETDATE())";
	$params = array($nome, $sobrenome, $_POST['ddd'], $_POST['telefone'], $_POST['rg'], $_POST['cpf'], $_POST['email']);
	
	$result = executeSQL($mainConnection, 'SELECT ID_CLIENTE FROM MW_CLIENTE WHERE CD_CPF = ?', array($_POST['cpf']));
	if (hasRows($result)) {
		$rs = fetchResult($result);
		$id_cliente = $rs['ID_CLIENTE'];
	} else {
		exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('N�o foi poss�vel cadastrar o cliente.<br><br>Houve um erro no sistema, favor procurar o suporte t�cnico.'))));
	}
}




//--------------------------------------------------
//processo de reserva
//--------------------------------------------------
$bilhete = array();
foreach ($_POST['bilhete'] as $key => $val) {
	$rs = executeSQL($mainConnection, 'SELECT CODTIPBILHETE FROM MW_APRESENTACAO_BILHETE WHERE ID_APRESENTACAO_BILHETE = ?', array($val), true);
	for ($i = 0; $i < $_POST['qtd'][$key]; $i++) {
		$bilhete[] = array('id_bilhete_apresentacao' => $val, 'cod_tip_bilhete' => $rs['CODTIPBILHETE']);
	}
}

$rs = executeSQL($mainConnection, 'SELECT CODAPRESENTACAO FROM MW_APRESENTACAO WHERE ID_APRESENTACAO = ?', array($_POST['apresentacao']), true);
$cod_apresentacao = $rs['CODAPRESENTACAO'];

$num_ingressos = array_sum($_POST['qtd']);
$limite = ((count($_POST['bilhete']) - 1) * 10) + 1;
if ($num_ingressos <= $limite) {
	$return = verificarLimitePorCPF($conn, $_POST['codapresentacao'], $id_cliente);
	($return != NULL) ? exit(json_encode(array('id'=>session_id(), 'error'=>$return))) : '';
		
	//ainda existe o numero selecionado de ingressos disponiveis?
	$query = "SELECT SUM(1) FROM TABSALDETALHE D
				INNER JOIN TABAPRESENTACAO A ON A.CODSALA = D.CODSALA
				WHERE D.TIPOBJETO = 'C' AND A.CODAPRESENTACAO = ?
				AND NOT EXISTS (SELECT 1 FROM TABLUGSALA L
									WHERE L.INDICE = D.INDICE
									AND L.CODAPRESENTACAO = A.CODAPRESENTACAO)";
	$params = array($cod_apresentacao);
	$ingressosDisponiveis = executeSQL($conn, $query, $params, true);
	$ingressosDisponiveis = $ingressosDisponiveis[0];
	
	if ($ingressosDisponiveis >= $num_ingressos) {
		beginTransaction($mainConnection);
		beginTransaction($conn);
		//$errors = false (ocorreu um erro)
		$errors = true;
		
		$query = 'DELETE FROM MW_RESERVA WHERE ID_SESSION = ?';
		$params = array(session_id());
		$result = executeSQL($mainConnection, $query, $params);
		
		$errors = $result and $errors;
		
		$query = 'DELETE FROM TABLUGSALA WHERE ID_SESSION = ?';
		$params = array(session_id());
		$result = executeSQL($conn, $query, $params);
		
		$errors = $result and $errors;
		
		$query = 'SELECT TOP ' . $num_ingressos . ' D.INDICE, D.NOMOBJETO, S.NOMSETOR FROM TABSALDETALHE D
					INNER JOIN TABAPRESENTACAO A ON A.CODSALA = D.CODSALA
					INNER JOIN TABSETOR S ON S.CODSALA = D.CODSALA AND S.CODSETOR = D.CODSETOR
					WHERE D.TIPOBJETO = \'C\' AND A.CODAPRESENTACAO = ?
					AND NOT EXISTS (SELECT 1 FROM TABLUGSALA L
										WHERE L.INDICE = D.INDICE
										AND L.CODAPRESENTACAO = A.CODAPRESENTACAO)';
		$params = array($cod_apresentacao);
		$result = executeSQL($conn, $query, $params);
		
		$errors = $result and $errors;
		$i = 0;
		while ($rs = fetchResult($result)) {
			$query = 'INSERT INTO MW_RESERVA (ID_APRESENTACAO,ID_CADEIRA,DS_CADEIRA,DS_SETOR,ID_SESSION,DT_VALIDADE,ID_APRESENTACAO_BILHETE) VALUES (?,?,?,?,?,DATEADD(MI, ?, GETDATE()),?)';
			$params = array($_POST['apresentacao'], $rs['INDICE'], $rs['NOMOBJETO'], $rs['NOMSETOR'], session_id(), $compraExpireTime, $bilhete[$i]['id_bilhete_apresentacao']);
			$errors = executeSQL($mainConnection, $query, $params) and $errors;
			
			$query = 'INSERT INTO TABLUGSALA
								  (CODAPRESENTACAO
								  ,INDICE
								  ,CODTIPBILHETE
								  ,CODCAIXA
								  ,CODVENDA
								  ,STAIMPRESSAO
								  ,STACADEIRA
								  ,CODUSUARIO
								  ,CODRESERVA
								  ,ID_SESSION)
						  VALUES
								  (?,?,?,?,?,?,?,?,?,?)';
			$params = array($cod_apresentacao, $rs['INDICE'], $bilhete[$i]['cod_tip_bilhete'], 255, NULL, 0, 'T', NULL, NULL, session_id());
			$errors = executeSQL($conn, $query, $params) and $errors;
			$i++;
		}
		
		if ($errors) {
			commitTransaction($mainConnection);
			commitTransaction($conn);
		} else {
			rollbackTransaction($mainConnection);
			rollbackTransaction($conn);
			exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('N�o foi poss�vel selecionar o(s) ingresso(s) desejado(s).<br><br>Por favor, tente novamente.<br><br>Se o erro persistir, favor informar o suporte.'))));
		}
	} else {
		exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Neste momento esta(�o) dispon�vel(is) apenas ' . $ingressosDisponiveis . ' ingresso(s)!'))));
	}
} else {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Voc� selecionou o m�ximo de ingressos permitidos para compras pelo site.<br><br>Para selecionar mais ingressos finalize essa compra.'))));
}




//--------------------------------------------------
//retorna limite e quantidade de bilhetes que participam da promo da compra atual
//--------------------------------------------------
$query = 'SELECT P.QT_BIN_POR_CPF, COUNT(R.ID_RESERVA) AS COMPRANDO
			FROM CI_MIDDLEWAY..MW_RESERVA R
			INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A2 ON A2.ID_APRESENTACAO = R.ID_APRESENTACAO
			INNER JOIN CI_MIDDLEWAY..MW_EVENTO E ON E.ID_EVENTO = A2.ID_EVENTO
			INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE
			INNER JOIN TABPECA P ON P.CODPECA = E.CODPECA AND P.CODTIPBILHETEBIN = AB.CODTIPBILHETE
			WHERE A2.ID_APRESENTACAO = ? AND P.IN_BIN_ITAU = 1 AND R.ID_SESSION = ?
			GROUP BY P.QT_BIN_POR_CPF';
$params = array($_POST['apresentacao'], session_id());
$result = executeSQL($conn, $query, $params);
if (!hasRows($result)) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Pelo menos 1 ingresso promocional deve ser selecionado para participar da promo��o.'))));
} else {
	$rs = fetchResult($result);
	$limite = $rs['QT_BIN_POR_CPF'];
	$compra_atual = $rs['COMPRANDO'];
}




//--------------------------------------------------
//retorna quantos ingressos promocionais foram comprados com o BIN
//--------------------------------------------------
$query = 'SELECT ISNULL(SUM(CASE H.CODTIPLANCAMENTO WHEN 1 THEN 1 ELSE -1 END), 0) AS TOTAL
		  FROM TABCLIENTE C
		  INNER JOIN TABHISCLIENTE H ON C.CODIGO = H.CODIGO
		  INNER JOIN TABCOMPROVANTE CR ON CR.CODCLIENTE = H.CODIGO AND CR.CODAPRESENTACAO = H.CODAPRESENTACAO
		  INNER JOIN TABINGRESSO I ON I.CODVENDA = CR.CODVENDA AND LEFT(I.INDICE, 6) = H.INDICE
		  WHERE C.CPF = ? AND H.CODAPRESENTACAO = ? AND LEFT(I.BINCARTAO, 6) = ?';
$params = array($_POST['cpf'], $cod_apresentacao, substr($_POST['ncartao'], 0, 6));
$result = executeSQL($conn, $query, $params);
if (!hasRows($result)) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Este N� de Cart�o n�o � participante da promo��o.'))));
} else {
	$rs = fetchResult($result);
	$total_comprado = $rs['TOTAL'];
}

if ($total_comprado >= $limite) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Este N� de Cart�o j� atingiu o limite de '.$limite.' ingresso(s) promocional(is) para esta apresenta��o.'))));
} else if ($total_comprado + $compra_atual > $limite) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('Este N� de Cart�o pode comprar apenas '.($limite - $total_comprado).' ingresso(s) promocional(is) para esta apresenta��o.'))));
}




//--------------------------------------------------
//processo de venda
//--------------------------------------------------
$query = "SELECT ID_USUARIO FROM MW_USUARIO WHERE DS_NOME = 'hotsite_itau'";
$rs = executeSQL($mainConnection, $query, array(), true);
$user_id = $rs['ID_USUARIO'];

$query = "SELECT SUM(AB.VL_LIQUIDO_INGRESSO) TOTAL_INGRESSOS
			FROM MW_RESERVA R
			INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO AND A.IN_ATIVO = '1'
			INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_VENDE_ITAU = '1'
			INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
			INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE AND AB.IN_ATIVO = '1'
			WHERE R.ID_SESSION = ? AND R.DT_VALIDADE >= GETDATE()";
$rs = executeSQL($mainConnection, $query, array(session_id()), true);
$total_ingressos = $rs['TOTAL_INGRESSOS'];

$query = 'SELECT MAX(ID_PEDIDO_VENDA)+1 ID_PEDIDO_VENDA FROM MW_PEDIDO_VENDA';
$rs = executeSQL($mainConnection, $query, array(), true);
$id_pedido = $rs['ID_PEDIDO_VENDA'];

$query = 'INSERT INTO MW_PEDIDO_VENDA
			(ID_PEDIDO_VENDA
			,ID_CLIENTE
			,ID_USUARIO_CALLCENTER
			,ID_USUARIO_ITAU
			,DT_PEDIDO_VENDA
			,VL_TOTAL_PEDIDO_VENDA
			,IN_SITUACAO
			,IN_RETIRA_ENTREGA
			,VL_TOTAL_INGRESSOS
			,VL_FRETE
			,VL_TOTAL_TAXA_CONVENIENCIA
			,IN_SITUACAO_DESPACHO
			,CD_BIN_CARTAO)
			VALUES
			(?, ?, ?, ?, GETDATE(), ?, ?, ?, ?, ?, ?, ?, ?)';
$params = array($id_pedido, $id_cliente, $user_id, $_SESSION['userItau'], $total_ingressos, 'F',
				'R', $total_ingressos, 0, 0, 'N', $_POST['ncartao']);
executeSQL($mainConnection, $query, $params);

$errors = sqlErrors();
if (!empty($errors)) {
	exit(json_encode(array(
		'id'=>session_id(),
		'error'=>utf8_encode2('N�o foi poss�vel cadastrar o pedido.<br><br>Houve um erro no sistema, favor procurar o suporte t�cnico.'),
		'db'=>$errors[0][2]
	)));
}

$query = "INSERT INTO MW_ITEM_PEDIDO_VENDA
			(ID_PEDIDO_VENDA,ID_RESERVA,ID_APRESENTACAO,ID_APRESENTACAO_BILHETE,
			DS_LOCALIZACAO,DS_SETOR,QT_INGRESSOS,VL_UNITARIO,VL_TAXA_CONVENIENCIA,CODVENDA)
		SELECT ".$id_pedido.", R.ID_RESERVA, R.ID_APRESENTACAO, R.ID_APRESENTACAO_BILHETE, R.DS_CADEIRA, R.DS_SETOR, 1, AB.VL_LIQUIDO_INGRESSO, 0, 'XXXXXXXXXX'
			FROM MW_RESERVA R
			INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO AND A.IN_ATIVO = '1'
			INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_VENDE_ITAU = '1'
			INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
			INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE AND AB.IN_ATIVO = '1'
			WHERE R.ID_SESSION = ? AND R.DT_VALIDADE >= GETDATE()
			ORDER BY E.DS_EVENTO, R.ID_APRESENTACAO, R.DS_CADEIRA";
executeSQL($mainConnection, $query, array(session_id()));

$errors = sqlErrors();
if (!empty($errors)) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('N�o foi poss�vel cadastrar os itens do pedido.<br><br>Houve um erro no sistema, favor procurar o suporte t�cnico.'))));
}

$query = 'EXEC SP_VEN_INS001_WEB ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?';
$params = array(session_id(), $id_base, null, $cod_apresentacao,
					 $_POST['ddd'], $_POST['telefone'], ($_POST['nome']),
					 $_POST['cpf'], $_POST['rg'], null, null,
					 null, null, $_POST['ncartao'], 251);
$retornoProcedure = executeSQL($conn, $query, $params, true);

$errors = sqlErrors();
if (!empty($errors) or $retornoProcedure[0] != 1) {
	exit(json_encode(array('id'=>session_id(), 'error'=>utf8_encode2('N�o foi poss�vel efetivar a venda.<br><br>Houve um erro no sistema, favor procurar o suporte t�cnico.'))));
}

exit(json_encode(array('id'=>session_id(), 'success'=>true)));