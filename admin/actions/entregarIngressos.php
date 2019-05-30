<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/settings/functions.php');
session_start();
function tratarData($data){
    $array = explode("/",$data);
    $dia = $array[0];
    $mes = $array[1];
    $ano = $array[2];
    return $ano."/".$mes."/".$dia;
}
if($_POST["NomeBase"] != "" && $_POST["Proc"] != "" && !isset($_REQUEST["Acao"])){
    $mainConnection = mainConnection();
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
    die();
}
if (acessoPermitido($mainConnection, $_SESSION['admin'], 216, true)) {

    if ($_GET['action'] == 'update' and isset($_GET['id'])) { /* ------------ UPDATE ------------ */

        $query = "UPDATE MW_PEDIDO_VENDA SET
                                        DT_ENTREGA_INGRESSO = ?
                                      WHERE
                                        ID_PEDIDO_VENDA = ?";

        if(empty($_POST['dt_entrega'])){
            $data = null;
        }
        else
        {
            $data = tratarData($_POST['dt_entrega']) . " " .date("H:i:s");
        }

        $params = array($data , $_GET['id']);

        if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Entrega de Ingressos');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);
            
            $retorno = 'true?id=' . $_GET['id'];
        } else {
            $retorno = sqlErrors();
        }
    }

    if (is_array($retorno)) {
        echo $retorno[0]['message'];
    } else {
        echo $retorno;
    }
}
?>