<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");

function echo_header($scroll = true) {
	// limpa tela
	echo "<CONSOLE></CONSOLE>";

	// banner / logo
	if ($scroll) {
		echo "<CONLOGO NOCLS=0 NAME=logo_scroll.bmp X=0 Y=0>";
	} else {
		echo "<CONLOGO NOCLS=0 NAME=logo_ci_colorida.bmp X=80 Y=0>";
	}
}

function remove_especial_chars($string) {

	// porcentagem
	$string = preg_replace('/\%/i', '%25', $string);
	
	// espaco
	$string = preg_replace('/\+/i', '%20', $string);

	// maior
	$string = preg_replace('/\>/i', '%3E', $string);

	// menor
	$string = preg_replace('/\</i', '%3C', $string);

	// virgula
	$string = preg_replace('/\,/i', '%2C', $string);

	return $string;
}

function echo_select($name, $list, $line, $select_lines = null){
	global $total_lines, $body_lines, $header_lines;

	while (strlen(implode(",", array_keys($list))) > 149) {
		$list = array_slice($list, 0, -1, true);	
	}

	$index = '';
	$items = '';

	foreach ($list as $key => $value) {
		$temp  = explode('_', $key);

		$index .= $temp[0].',';
		$items .= remove_especial_chars(substr($value, 0, 29)).',';
	}

	$line = $header_lines + $line + 1;

	$items = substr($items, 0, -1);
	$select_lines = $select_lines ? $select_lines : $total_lines - $line;
	echo "<SELECT LIN=$line COL=2 SIZE=29 QTD=$select_lines UP=B1 DOWN=B4 LEFT=34 RIGHT=36 NAME=$name TYPE_RETURN=3 INDEX=$index>";
	echo utf8_decode($items);
	echo "</SELECT>";

	echo "<WRITE_AT LINE=$line COLUMN=0> Aguarde...</WRITE_AT>";
}

function echo_list($name, $list, $line){
	global $body_lines, $header_lines;

	$line = $header_lines + $line + 1;

	echo "<WRITE_AT LINE=$line COLUMN=0>";
	foreach ($list as $key => $value) {
		echo utf8_decode(" $key - $value\n");
	}
	echo "</WRITE_AT>";

	$last_line = $line + count($list) + 1;
	$size = count($list) > 9 ? 2 : 1;

	echo "<GET TYPE=FIELD NAME=$name NOENTER=1 SIZE=$size COL=5 LIN=$last_line>";
}

function limpa_navegacao($reset) {

	// cancelamento da reserva
	$_GET['manualmente'] = 1;
	ob_start();
	require_once '../comprar/pagamento_cancelado.php';
	$response = ob_get_clean();

	// limpa usuario (cliente)
	unset($_SESSION['user']);

	// limpeza da navegacao
	$_SESSION['history'] = array();
	unset($_SESSION['screen']);
}

function display_error($text, $title = null) {

	echo_header(false);

	/*$string = preg_replace('/<(\s*)?br(\s*)?\/?>/i', '', $text);*/
	$string = strip_tags($text);

	$string = unblock_words($string);

	$string = preg_replace('/ {2,}/i', ' ', $string);

	$string = wordwrap($string, 29, "><><");

	$strings = explode('><><', $string);

	$title = ($title ? $title : "Erro");

	echo "<WRITE_AT LINE=5 COLUMN=0> $title</WRITE_AT>";

	$start_line = 7;

	foreach ($strings as $key => $value) {
		$line = $start_line + $key;
		echo utf8_decode("<WRITE_AT LINE=$line COLUMN=0> $value</WRITE_AT>");
	}
	
	echo "<GET TYPE=ANYKEY>";
}

function create_user($data) {

	require_once "../settings/functions.php";

	$mainConnection = mainConnection();

	$id = false;

	for ($i = 0; $i < 3; $i++) { 

		$newID = executeSQL($mainConnection, 'SELECT ISNULL(MAX(ID_CLIENTE), 0) + 1 FROM MW_CLIENTE', array(), true);
		$newID = $newID[0];

		$query = "INSERT INTO MW_CLIENTE (
								ID_CLIENTE,
								DS_NOME,
								DS_SOBRENOME,
								IN_RECEBE_INFO,
								IN_RECEBE_SMS,
								IN_CONCORDA_TERMOS,
								DT_INCLUSAO,
								CD_CPF,
								DS_DDD_CELULAR,
								DS_CELULAR
							)
							VALUES (?,'POS','POS','N','N','N',GETDATE(),?,?,?)";
		$params = array($newID, $data['cpf'], $data['ddd_celular'], $data['celular']);
			
		executeSQL($mainConnection, $query, $params);

		$rs = executeSQL($mainConnection, "SELECT ID_CLIENTE FROM MW_CLIENTE WHERE CD_CPF = ?", array($data['cpf']), true);

		if (isset($rs['ID_CLIENTE'])) {
			$id = $rs['ID_CLIENTE'];
			break;
		}

	}

	return $id;
}

function print_qrcode($code) {

	// small = 1 / medium = 2 / big = 3
	$qr_size = 1;

	$config = array(
		1 => array('size' => 7, 'spaces' => 18),
		2 => array('size' => 10, 'spaces' => 13),
		3 => array('size' => 15, 'spaces' => 0)
	);

	/*Os valores de status retornados são:
	 0: Ok;
	-4: Falha;
	-5: Pouco papel;
	-10: erro de RAM;
	-20: Falha na impressora;
	-21: Sem papel;
	-23: Sequência de Escape Code não encontrada;
	-24: Impressora não inicializada;
	-27: Firmware corrompido.*/

	// echo "<PRNLOGO NAME=logo_ci_mono.bmp SPACES=0>";

	// echo "<PRINTER><BR>Code: $code<BR></PRINTER>";

	echo "<GENERATE_QR_CODE SIZE={$config[$qr_size]['size']} QR_ECLEVEL=3 KEEP_FILE=0 SPACES={$config[$qr_size]['spaces']} ERR_QR=QR_SUCCESS>$code</GENERATE_QR_CODE>";
}

function get_codbar($apresentacao, $indice, $base){
	$conn = getConnection($base);
	$query = "SELECT C.CODBAR FROM TABCONTROLESEQVENDA C WHERE C.CODAPRESENTACAO = ? AND C.INDICE = ?";
	$rs = executeSQL($conn, $query, array($apresentacao, $indice), true);
	return $rs['CODBAR'];
}

function get_lugar($indice, $base){
	$conn = getConnection($base);
	$query = "SELECT NomObjeto FROM TABSALDETALHE WHERE INDICE = ?";
	$rs = executeSQL($conn, $query, array($indice), true);
	return $rs['NomObjeto'];
}

function print_order($pedido, $reprint = false){
	require_once "../settings/functions.php";
	$mainConnection = mainConnection();

	$query = 'SELECT COUNT(1)
				FROM MW_ITEM_PEDIDO_VENDA I
				INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
				INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
				WHERE I.ID_PEDIDO_VENDA = ? AND E.IN_IMPRIMI_CANHOTO_POS = 1';
	$rs = executeSQL($mainConnection, $query, array($pedido), true);
	$imprimir_canhoto = $rs[0] > 0;

	$query = "SELECT
				UPPER(E.DS_EVENTO) DS_EVENTO,
				A.DT_APRESENTACAO,
				A.HR_APRESENTACAO,
				A.DS_PISO,
				AB.DS_TIPO_BILHETE,
				AB.VL_LIQUIDO_INGRESSO,
				UPPER(C.DS_NOME + ' ' + C.DS_SOBRENOME) AS DS_NOME,
				C.CD_CPF,
				C.DS_DDD_CELULAR +' '+ C.DS_CELULAR AS DS_CELULAR,
				B.ID_BASE,
				ISNULL(LE.DS_LOCAL_EVENTO, B.DS_NOME_TEATRO) DS_NOME_TEATRO,
				R.INDICE,
				A.CODAPRESENTACAO,
				P.DT_PEDIDO_VENDA,
				UPPER(U.CD_LOGIN) CD_LOGIN,
				ISNULL(P.ID_PEDIDO_IPAGARE, 255) AS ID_PEDIDO_IPAGARE,
				R.CODVENDA,
				UPPER(MP.NM_CARTAO_EXIBICAO_SITE) NM_CARTAO_EXIBICAO_SITE,
				P.VL_TOTAL_PEDIDO_VENDA,
				P.CD_NUMERO_AUTORIZACAO,
				GETDATE() AS DT_ATUAL
			FROM MW_ITEM_PEDIDO_VENDA R
			INNER JOIN MW_PEDIDO_VENDA P ON P.ID_PEDIDO_VENDA = R.ID_PEDIDO_VENDA
			INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
			INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
			INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
			INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE
			INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = P.ID_CLIENTE
			LEFT JOIN MW_USUARIO U ON U.ID_USUARIO = P.ID_USUARIO_CALLCENTER
			INNER JOIN MW_MEIO_PAGAMENTO MP ON MP.ID_MEIO_PAGAMENTO = P.ID_MEIO_PAGAMENTO
			LEFT JOIN MW_LOCAL_EVENTO LE ON E.ID_LOCAL_EVENTO = LE.ID_LOCAL_EVENTO
			WHERE R.ID_PEDIDO_VENDA = ?
			ORDER BY E.DS_EVENTO, A.DT_APRESENTACAO, AB.DS_TIPO_BILHETE";
	$result = executeSQL($mainConnection, $query, array($pedido));

	$qtde = 1;
	$total = numRows($mainConnection, $query, array($pedido));

	$query = 'SELECT SE.NOMSETOR
				FROM TABSALDETALHE S
				INNER JOIN TABSETOR SE ON SE.CODSALA = S.CODSALA AND SE.CODSETOR = S.CODSETOR
				INNER JOIN TABAPRESENTACAO A ON A.CODSALA = S.CODSALA
				WHERE A.CODAPRESENTACAO = ? AND S.INDICE = ?';

	$space = " ";

	ob_start();

	while ($rs = fetchResult($result)) {

		$conn = getConnection($rs['ID_BASE']);
		$rsAux = executeSQL($conn, $query, array($rs['CODAPRESENTACAO'], $rs['INDICE']), true);

		echo "<CHGPRNFNT SIZE=4 FACE=FONTE1>";

		echo "<PRINTER>";

		echo str_pad(multiSite_getName(), 42, " ", STR_PAD_BOTH) ."<BR>";
		echo substr($space ."Local: ". remove_accents($rs['DS_NOME_TEATRO'], false), 0, 42) ."<BR>";
		
		echo $space ."Forma Pgto: ". remove_accents($rs['NM_CARTAO_EXIBICAO_SITE'], false) ."<BR>";

		echo substr($space ."Emitido Para: ". remove_accents(utf8_decode($rs['DS_NOME']), false), 0, 42) ."<BR>";
		echo $space ."CPF: ". $rs['CD_CPF'] ."  TEL: ". $rs['DS_CELULAR'] ."<BR>";

		echo $space ."Qtde: $qtde de $total" . "<BR>";
		echo utf8_decode($space ."Pedido: ". $pedido ." Cod: ". $rs['CODVENDA'] ."<BR>");
		echo $space ."Operador: ". remove_accents($rs['CD_LOGIN'], false) ." Serial POS: ". $rs['ID_PEDIDO_IPAGARE'] ."<BR>";
		
		echo $space ."Nr. DOC: ". $rs['CD_NUMERO_AUTORIZACAO'] ."<BR>";
		echo $space ."Vl. Total Pedido: R$ ". number_format($rs['VL_TOTAL_PEDIDO_VENDA'], 2, ',', '') ."<BR>";
		echo $space ."V:". $rs['DT_PEDIDO_VENDA']->format('d/m/y H:i:s') ." I:". $rs['DT_ATUAL']->format('d/m/y H:i:s') ."<BR><BR>";

		echo "</PRINTER>";

		if ($reprint) {
			echo "<CHGPRNFNT SIZE=2 FACE=FONTE1 DBL_HEIGHT>";
			echo "<PRINTER>";
			echo str_pad("REIMPRESSAO", 24, " ", STR_PAD_BOTH);
			echo "</PRINTER>";
			echo "<CHGPRNFNT SIZE=4 FACE=FONTE1>";
			echo "<PRINTER>";
			echo "<BR>";
			echo "</PRINTER>";
		}
		
		$codbar = get_codbar($rs['CODAPRESENTACAO'], $rs['INDICE'], $rs['ID_BASE']);
		print_qrcode($codbar);

		echo "<CHGPRNFNT SIZE=2 FACE=FONTE1 DBL_HEIGHT>";
		echo "<PRINTER><BR>";

		echo str_pad(substr(remove_accents($rs['DS_EVENTO'], false), 0, 24), 24, " ", STR_PAD_BOTH) ."<BR>";
		echo str_pad($rs['DT_APRESENTACAO']->format('d/m/Y') ." ". $rs['HR_APRESENTACAO'], 24, " ", STR_PAD_BOTH) ."<BR>";
		echo str_pad(utf8_decode(remove_accents(substr($rsAux['NOMSETOR'], 0, 24), false)), 24, " ", STR_PAD_BOTH) ."<BR>";
		echo str_pad(utf8_decode(get_lugar($rs['INDICE'], $rs['ID_BASE'])), 24, " ", STR_PAD_BOTH) ."<BR>";		
		echo str_pad(substr(remove_accents($rs['DS_TIPO_BILHETE'],false) . " - R$ ". number_format($rs['VL_LIQUIDO_INGRESSO'], 2, ',', ''), 0, 24), 24, " ", STR_PAD_BOTH) ."<BR>";
		
		echo "</PRINTER>";
		
		echo "<PRINTER><BR><BR><BR><BR><BR><BR></PRINTER>";
		
		if ($qtde != $total) {
			echo "<CONSOLE><BR> Pressione uma tecla<BR> para imprimir o ingresso.</CONSOLE>";
			echo "<GET TYPE=ANYKEY>";
			echo "<CONSOLE><BR> Aguarde...</CONSOLE>";
		} else {
			echo "<CONSOLE><BR> Finalizado!</CONSOLE>";
		}
		$qtde++;
	}

	echo "<CHGPRNFNT SIZE=4 FACE=FONTE1>";

	$impressao = ob_get_clean();

	if ($imprimir_canhoto) {
		echo "<CONSOLE><BR> VIA DO ESTABELECIMENTO<BR> Pressione uma tecla<BR> para imprimir o ingresso.</CONSOLE>";
		echo "<GET TYPE=ANYKEY>";

		echo "<CHGPRNFNT SIZE=4 FACE=FONTE3 BOLD>";
		echo "<PRINTER>";
		echo str_pad(" VIA ESTABELECIMENTO-URNA-CANHOTO ", 42, "-", STR_PAD_BOTH) ."<BR><BR>";
		echo "</PRINTER>";
	}

	echo $impressao;

	if ($imprimir_canhoto) {
		echo "<CONSOLE><BR> VIA DO CLIENTE<BR> Pressione uma tecla<BR> para imprimir o ingresso.</CONSOLE>";
		echo "<GET TYPE=ANYKEY>";

		echo "<CHGPRNFNT SIZE=4 FACE=FONTE1>";
		echo "<PRINTER>";
		echo str_pad(" VIA CLIENTE ", 42, "-", STR_PAD_BOTH) ."<BR><BR>";
		echo "</PRINTER>";

		echo $impressao;
	}
}

function unblock_words($string) {
	$words = array(
		"/(SENH)(A)/i" 			=> "$1ª",
		"/(CH)(A)(VE)/i" 		=> "$1ª$3",
		"/(ACESS)(O)/i" 		=> "$1º",
		"/(P)(A)(SS)/i" 		=> "$1ª$3",
		"/(P)(A)(SSWORD)/i" 	=> "$1ª$3",
		"/(ACCESS)(O)/i" 		=> "$1º",
		"/(CL)(A)(VE)/i" 		=> "$1ª$3",
		"/(SEÑ)(A)/i" 			=> "$1ª",
		"/(CONTRASEÑ)(A)/i" 	=> "$1ª",
		"/(CONTRASEN)(A)/i" 	=> "$1ª",
		"/(P)(I)(N)/i" 			=> "$1ī$3"
	);

	return preg_replace(array_keys($words), array_values($words), $string);
}

function getIDPOS($serial) {

	require_once "../settings/functions.php";

	$mainConnection = mainConnection();

	$rs = executeSQL($mainConnection, "SELECT ID FROM MW_POS WHERE SERIAL = ?", array($serial), true);

	return 'ID'.str_pad($rs['ID'], 6, '0', STR_PAD_LEFT);
}











// ------- DEBUG --------
function dump_get() {
	echo "<CONSOLE>";
	foreach ($_GET as $key => $value) {
		echo "$key<BR>$value<BR>";
	}
	echo "</CONSOLE>";
	echo "<GET TYPE=ANYKEY>";
}
// ------- DEBUG --------