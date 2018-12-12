<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

// parametros para pesquisa dos dados
$Acao            = $_POST["Acao"];
$CodPeca         = $_POST["CodPeca"];
$DatApresentacao = "'" . $_POST["DatApresentacao"] . "'";
$Horario		 = "'" . $_POST["Horario"] . "'";

function mostraDataGeral($vData){
	return $vData->format("d/m/Y");
}

function mostraDataSimple($vData){
	return $vData->format("Ymd");
}

function buscarDatas(){
	$CodPeca = ($_REQUEST["CodPeca"] == "") ? "null" : $_REQUEST["CodPeca"];
	$gSQL =	$_SESSION["NomeBase"]."..SP_PEC_CON009;6 ".$_SESSION["admin"].", ".$_SESSION["IdBase"].", ".$CodPeca;
	$conn = getConnection($_SESSION["IdBase"]);
	$rsGeral = executeSQL($conn, $gSQL);
	if(!sqlErrors()){
		if(hasRows($rsGeral)){
			$html .= "<option value=\"\">Selecione...</option>";
			while($rs = fetchResult($rsGeral)){
				$html .= "<option value=\"". mostraDataSimple($rs["DatApresentacao"]) ."\">". mostraDataGeral($rs["DatApresentacao"]) ."</option>\n";
			}
			echo $html;
		}else{
			echo "Nenhum registro encontrado";	
		}
	}else{
		echo "<br>Erro #001:";
		print_r(sqlErrors());	
		echo "<br>".$gSQL;
	}	
}

function buscarHorarios(){
	$CodPeca = ($_REQUEST["CodPeca"] == "") ? "null" :  $_REQUEST["CodPeca"];
	$DatApresentacao = ($_REQUEST["DatApresentacao"] == "") ? "null" : $_REQUEST["DatApresentacao"];
	$gSQL = $_SESSION["NomeBase"]."..SP_PEC_CON009;7 ".$_SESSION["admin"].", ".$_SESSION["IdBase"].", ". $CodPeca .", ". $DatApresentacao;
	$conn = getConnection($_SESSION["IdBase"]);	
	$rsGeral = executeSQL($conn, $gSQL);
	if(!sqlErrors()){
		if(hasRows($rsGeral)){
			echo $gSQL;
			$html .= "<option value=\"\">Selecione...</option>\n";
			while($rs = fetchResult($rsGeral)){
				$html .= "<option value=\"". $rs["HorSessao"] ."\">". $rs["HorSessao"] ."</option>\n";
			}
			echo $html;
		}
	}else{
		print_r(sqlErrors());
		echo "<br>".$gSQL;	
	}
}

function buscarSala(){
	$CodPeca = ($_REQUEST["CodPeca"] == "") ?  "null" : $_REQUEST["CodPeca"];
	$DatApresentacao = ($_REQUEST["DatApresentacao"] == "") ? "null" : $_REQUEST["DatApresentacao"];
	$Horario = ($_REQUEST["Horario"] == "") ? "null" : $_REQUEST["Horario"];
	
	$gSQL = "SP_REL_BORDERO_VENDAS;7 '" . $DatApresentacao ."',". $CodPeca .",'". $Horario ."','".$_SESSION["NomeBase"]."'";
	$conn = getConnectionTsp();
	$rsGeral = executeSQL($conn, $gSQL);
	if(!sqlErrors()){
		if(hasRows($rsGeral)){
			$html .= "<option value=''>Selecione...</option>
				    <option value='TODOS'>&lt; TODOS &gt;</option>";
			while($rs = fetchResult($rsGeral)){
				$html .= "<option value=\"". $rs["codsala"] ."\">". utf8_encode2($rs["nomSala"]) ."</option>\n";
			}
			echo $html;
		}
	}else{
		print_r(sqlErrors());
		echo "<br>".$gSQL;	
	}
}

if ($_REQUEST["Acao"] == 'requestDates' and $CodPeca) {
    $conn = getConnection($_SESSION["IdBase"]);
    $query = 'SELECT CONVERT(VARCHAR(10), MIN(DATAPRESENTACAO), 103) INICIAL,
		CONVERT(VARCHAR(10), MAX(DATAPRESENTACAO), 103) FINAL
		FROM TABAPRESENTACAO WHERE CODPECA = ?';
    $params = array($CodPeca);
    $rs = executeSQL($conn, $query, $params, true);

    if (empty($rs)) {
	die(json_encode(array('inicial'=>'01/01/2005', 'final'=>'')));
    }

    die(json_encode(array('inicial'=>$rs['INICIAL'], 'final'=>$rs['FINAL'])));
}

if($_POST["NomeBase"] != "" && $_POST["Proc"] != "" && !isset($_REQUEST["Acao"])){
	$strQuery = "SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = ".$_POST["NomeBase"];
	if( $stmt = executeSQL($mainConnection, $strQuery, array(), true) ){
		$conn = getConnection($_POST["NomeBase"]);
		$query = "EXEC ". $stmt["DS_NOME_BASE_SQL"] ."..". $_POST['Proc'] ." ". $_SESSION['admin'] .", ". $_POST["NomeBase"];
		if(	$result = executeSQL($conn, $query) ){
			// Cria sessao com nome da base utilizada
			$_SESSION["IdBase"] = $_POST["NomeBase"];
			$_SESSION["NomeBase"] = $stmt["DS_NOME_BASE_SQL"];
			$html = "<select name=\"cboPeca\" id=\"cboPeca\" onchange=\"CarregaApresentacao()\">\n";
			$html .= "<option value=\"null\">Selecione...</option>";
			if(hasRows($result)){
				while($rs = fetchResult($result)){
					$html .= "<option value=\"". $rs["CodPeca"] ."\">". utf8_encode2($rs["nomPeca"]) ."</option>\n";	
				}
			}
			$html .= '</select>';
		}else{
			$html = print_r(sqlErrors());
			$html .= "<br>".$query;	
		}
	}else{
		$html = print_r(sqlErrors());
		$html .= "<br>".$strQuery;
	}
	echo $html;
}

if(isset($_REQUEST["Acao"])){
	switch($_REQUEST["Acao"]){
		case 1:
			buscarDatas();
			break;
		case "2":
			buscarHorarios();
			break;
		case "3":
			buscarSala();
			break;
	}
}
?>