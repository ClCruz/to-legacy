<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

// parametros para pesquisa dos dados
$Acao            = isset($_POST["Acao"]) ? $_POST['Acao'] : '';
$CodPeca         = isset($_POST["CodPeca"]) ? $_POST['CodPeca'] : '';
$DatApresentacao = isset($_POST["DatApresentacao"]) ? "'" . $_POST["DatApresentacao"] . "'" : '';
$Horario		 = isset($_POST["Horario"]) ? "'" . $_POST["Horario"] . "'" : '';

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

function buscarInformacaoSessao(){
	$CodPeca = ($_REQUEST["CodPeca"] == "") ?  "null" : $_REQUEST["CodPeca"];
	$DatIni = ($_REQUEST["DatIni"] == "") ? "null" :  $_REQUEST["DatIni"];
	$DatFim = ($_REQUEST["DatFim"] == "") ? "null" : $_REQUEST["DatFim"];
	$offset  = !isset($_REQUEST['Offset']) ? " " : " AND a.id_apresentacao > ".$_REQUEST['Offset'].' ';

	$condData = "";
	if($DatIni != 'null'){
		$aData = explode('/', $DatIni);
		$condData .= " AND a.dt_apresentacao >= '".$aData[2]."-".$aData[1]."-".$aData[0]."' ";
	}
	if($DatFim != 'null'){
		$aData = explode('/', $DatFim);
		$condData .= " AND a.dt_apresentacao <= '".$aData[2]."-".$aData[1]."-".$aData[0]."' ";
	}



	$conn = mainConnection();
    $query = 'SELECT 
    			cis.NomSala AS NomSala,
				cis.CodSala AS CodSala,
				e.ds_evento,
				ISNULL(ancs.modalidade,\'-\') AS modalidade
				,e.id_evento 
			  FROM mw_evento e
				INNER JOIN mw_apresentacao a
						ON a.id_evento = e.id_evento
					   AND a.in_ativo = 1  	
				INNER JOIN '.$_SESSION["NomeBase"].'..tabApresentacao ciap	 
						ON ciap.CodApresentacao = a.CodApresentacao		   
				INNER JOIN '.$_SESSION["NomeBase"].'..tabSala cis 
						ON cis.CodSala = ciap.CodSala
				LEFT OUTER JOIN mw_ancine_sessao ancs
						ON ancs.CodSala = cis.CodSala						
			 WHERE e.id_base = ?
			   AND e.in_ativo = 1  
			   AND e.id_evento = ?
			   '.$condData.'	
			   AND DATEDIFF(day,GETDATE(),a.dt_apresentacao) >= (-15)
			   GROUP BY cis.NomSala,cis.CodSala,e.ds_evento, 
 						ancs.modalidade,e.id_evento 
			';

	// echo $query;
	// echo '<br>'.$_SESSION['IdBase'].', '.$CodPeca;

    $params = array($_SESSION['IdBase'],$CodPeca);
    $rsGeral = executeSQL($conn, $query, $params);
    if(!sqlErrors()){
		if(sqlsrv_has_rows($rsGeral)){
			$html = "";
			$i = 1;
			while($rs = fetchResult($rsGeral)){

				$selects = array(
								'' =>'-',
								'A'=>'Sessão Regular',
								'B'=>'Pré-estreia',
								'C'=>'Sessão de Mostra ou Festival',
								'D'=>'Sessão Privada',
					);
			
				$options = '';
				foreach ($selects as $key => $option) {
					$isSelected = '';
						if($key == $rs['modalidade'])
							$isSelected = 'selected="selected"';

						$options .= '<option '.$isSelected.' value="'.$key.'">'.$option.'</option>';
				}

				$modalidade = '<select id="modalidadeIn_'.$i.'" name="modalidade" class="inputhidden">
									'.$options.'
								</select>';


				$html .= ' 
				<tr>
	              <td style="text-align: center;">'.$rs['NomSala'].'</td>
	              <td style="text-align: center;"><label id="modalidadeTx_'.$i.'">'.$selects[$rs['modalidade']].'</label>
	              	'.$modalidade.'		
	              </td>
	              <td style="text-align: center;">
	              <input type="button" class="button btEditar" onclick="clickEdit(this);" data-codsala="'.$rs['CodSala'].'" data-item="'.$i.'" value="Editar" />
	                 <input type="button" class="button btCancelar" style="display:none;" onclick="clickCancel(this);" data-codsala="'.$rs['CodSala'].'" data-item="'.$i.'" value="Cancelar" />

	              </td>
	            </tr>';

	            $i++;
			}
			echo $html;
		}else{
			$html = '<tr><td colspan="7">Nada encontrado!</td></tr>';
			echo $html;
		}
	}else{
		print_r(sqlErrors());
		echo "<br>".$query;	
	}

}

function alterarSessao(){

	//  print_r($_POST);
	// exit();
	// echo $_POST['qtdAssento'];
	// $objApresentacao = json_decode($_POST['Apresentacao']);
	// print_r($obj[0]->codsala);

	$queryUpIn = " 
		DECLARE @IdEvento INT, @CodSala INT, @modalidade VARCHAR(1); 
			
		SET @CodSala = ?;
		SET @IdEvento = ?;
		SET @modalidade = ?;


	   UPDATE mw_ancine_sessao SET modalidade = @modalidade
	   WHERE CodSala = @CodSala AND id_evento = @IdEvento	
	      if @@rowcount = 0
	   begin
	      INSERT INTO mw_ancine_sessao ( modalidade, CodSala, id_evento) 
	      						 VALUES( @modalidade, @CodSala, @IdEvento ) 
	   end";


	$conn = mainConnection();   


		 $params = array($_POST['codSala'], $_POST['idBase'], $_POST['modalidade']);  
		 if(executeSQL($conn,$queryUpIn,$params))
				echo "Salvo com sucesso!";
		else
			echo "Houve falha tente novamente!";
			

}

if (isset($_REQUEST['Acao']) && $_REQUEST["Acao"] == 'requestDates' and $CodPeca) {
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

if(isset($_POST['NomeBase']) && $_POST["NomeBase"] != "" && $_POST["Proc"] != "" && !isset($_REQUEST["Acao"])){

	$strQuery = "SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = ".$_POST["NomeBase"];
	if( $stmt = executeSQL($mainConnection, $strQuery, array(), true) ){
		// $conn = getConnection($_POST["NomeBase"]);
		$conn = mainConnection();
		$query = "SELECT DISTINCT e.CodPeca,e.id_evento,e.ds_evento FROM mw_evento e
			INNER JOIN mw_apresentacao a
					ON a.id_evento = e.id_evento
				   AND a.in_ativo = 1  	
		 WHERE e.id_base = ".$_POST["NomeBase"]."
		   AND e.in_ativo = 1  
		   AND DATEDIFF(day,GETDATE(),a.dt_apresentacao) >= (-15)";

		// $query = "EXEC ". $stmt["DS_NOME_BASE_SQL"] ."..". $_POST['Proc'] ." ". $_SESSION['admin'] .", ". $_POST["NomeBase"];
		if(	$result = executeSQL($conn, $query) ){
			// Cria sessao com nome da base utilizada
			$_SESSION["IdBase"] = $_POST["NomeBase"];
			$_SESSION["NomeBase"] = $stmt["DS_NOME_BASE_SQL"];
			$html = "<select name=\"cboPeca\" id=\"cboPeca\" onchange=\"CarregaApresentacao()\">\n";
			$html .= "<option value=\"null\">Selecione...</option>";
			if(hasRows($result)){
				while($rs = fetchResult($result)){
					$html .= "<option value=\"". $rs["id_evento"] ."\">". utf8_encode2($rs["ds_evento"]) ."</option>\n";	
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
		case "4":
			buscarInformacaoSessao();
			break;	
		case "5":
			alterarSessao();
			break;	
	}
}
?>