<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

// parametros para pesquisa dos dados
$Acao            = isset($_POST["Acao"]) ? $_POST['Acao'] : '';
$CodPeca         = isset($_POST["CodPeca"]) ? $_POST['CodPeca'] : '';



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
			$html = "<select name=\"cboPeca\" id=\"cboPeca\" onchange=\"exibeOpcao();\">\n";
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


function cadastrarDistribuidor(){
	// print_r($_POST);

	//  print_r($_POST);
	// exit();
	// echo $_POST['qtdAssento'];
	
	// print_r($obj[0]->codsala);
	$id_distribuidor = ($_POST['id_distribuidor'] == '') ? 0 : $_POST['id_distribuidor'] ;

	$queryUpIn = " 
	
		  DECLARE @razaoSocial VARCHAR(100),
		 		  @cnpj	VARCHAR(14),
		 		  @id_base INT,
		 		  @id_distribuidor INT;
		 
		 SET @id_base = ?;
		 SET @razaoSocial = ?;
		 SET @cnpj = ?;
		 SET @id_distribuidor = ?;
		 
		 
		 UPDATE mw_ancine_distribuidor SET razao_social = @razaoSocial,
		 		cnpj = @cnpj
		 
		 WHERE id_distribuidor = @id_distribuidor
		 
		 if @@rowcount = 0
		 BEGIN
		 INSERT INTO mw_ancine_distribuidor ( razao_social,cnpj,id_base) 
		 VALUES( @razaoSocial, @cnpj, @id_base ) 
		 END";


	$conn = mainConnection();   


    $params = array($_POST['idBase'],$_POST['razaoSocial'],$_POST['cnpj'],$id_distribuidor);  
		 
	if( executeSQL($conn,$queryUpIn,$params)){
		echo "Salvo com sucesso!";
	}else{
		$html = print_r(sqlErrors());
		$html .= "<br>".$queryUpIn;
	}
		// echo $queryUpIn;

}



function buscarDistribuidor(){

	$conn = mainConnection();   

	$idBase = $_POST['idBase'];

	$query = "
			SELECT 
				   * 
	 		  FROM mw_ancine_distribuidor
	 		 WHERE id_base = ?
		";

	if(	$rsGeral = executeSQL($conn, $query,array($idBase)) ){
			
		if(hasRows($rsGeral)){
			$html = "";
			$i = 1;
			while($rs = fetchResult($rsGeral)){

			 $html .= '
			 <tr>
              <td style="text-align: center;">
                <label>'.$rs['cnpj'].'</label>
              </td>
              <td style="text-align: center;">
                <label>'.$rs['razao_social'].'</label> 
              </td>
              <td style="text-align: center;">
                <input type="button" class="button btEditar" onclick="getDistribuidorToForm('.$rs['id_distribuidor'].')"  data-item="'.$i.'" value="Alterar" />
              </td>
            </tr>       ';
	          $i++;  
			}
			echo $html;
		}else{
			echo "Nenhum registro encontrado";	
		}

	}else{
		$html = print_r(sqlErrors());
		$html .= "<br>".$query;	
	}
}

function carregarDistribuidor(){
		$conn = mainConnection();   

	$query = "
			SELECT *
	 		  FROM mw_ancine_distribuidor
	     	 WHERE id_distribuidor = ? 
		";

	if(	$rsGeral = executeSQL($conn, $query,array($_POST['IdDistribuidor'])) ){
			
		if(hasRows($rsGeral)){
			$json = "";
			$json .= json_encode(fetchResult($rsGeral));

			while($rs = fetchResult($rsGeral)){

			 	
			}
			echo $json;
		}else{
			echo "Nenhum registro encontrado";	
		}

	}else{
		$json = print_r(sqlErrors());
		$json .= "<br>".$query;	
	}
}



if(isset($_REQUEST["Acao"])){
	switch($_REQUEST["Acao"]){
		case "1":
			cadastrarDistribuidor();
			break;	
		case "2":
			buscarDistribuidor();
			break;	
		case "3":
			carregarDistribuidor();
			break;	
	}
}