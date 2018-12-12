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


function cadastrarNegociacao(){
	// print_r($_POST);

	//  print_r($_POST);
	// exit();
	// echo $_POST['qtdAssento'];
	
	// print_r($obj[0]->codsala);
	$id_negoc_obra_distribuidor = ($_POST['id_negociacao'] == '') ? 0 : $_POST['id_negociacao'] ;

	$queryUpIn = " 
	
		  DECLARE @numeroObra VARCHAR(14),
		 @id_distribuidor INT,
		 @tipoTela VARCHAR(1),
		 @digital VARCHAR(1),
		 @tipoProjecao VARCHAR(1),
		 @audio VARCHAR(1),
		 @legenda VARCHAR(1),
		 @libras VARCHAR(1),
		 @legendagemDescritiva VARCHAR(1),
		 @audioDescricao VARCHAR(1),
		 @id_evento INT,
		 @id_base INT,
		 @id_negoc_obra_distribuidor INT;
		 
		 SET @numeroObra = ?;
		 SET @id_distribuidor = ?;
		 SET @tipoTela = ?;
		 SET @digital = ?;
		 SET @tipoProjecao = ?;
		 SET @audio = ?;
		 SET @legenda = ?;
		 SET @libras = ?;
		 SET @legendagemDescritiva = ?;
		 SET @audioDescricao = ?;
		 SET @id_evento = ?;
		 SET @id_base = ?;
		 SET @id_negoc_obra_distribuidor = ?;

		 
		 
		 UPDATE mw_ancine_negoc_obra_distribuidor SET numeroObra = @numeroObra,
		 id_distribuidor = @id_distribuidor, tipoTela = @tipoTela,
		 digital = @digital, tipoProjecao = @tipoProjecao,
		 audio = @audio, legenda = @legenda, libras = @libras,
		 legendagemDescritiva = @legendagemDescritiva,
		 audioDescricao = @audioDescricao
		 
		 
		 WHERE id_negoc_obra_distribuidor = @id_negoc_obra_distribuidor
		 
		 if @@rowcount = 0
		 BEGIN
		 INSERT INTO mw_ancine_negoc_obra_distribuidor ( numeroObra,
														 id_distribuidor,
														 tipoTela,
														 digital,
														 tipoProjecao,
														 audio,
														 legenda,
														 libras,
														 legendagemDescritiva,
														 audioDescricao,
														 id_evento,
														 id_base) 
		 VALUES( @numeroObra,
				 @id_distribuidor,
				 @tipoTela,
				 @digital,
				 @tipoProjecao,
				 @audio,
				 @legenda,
				 @libras,
				 @legendagemDescritiva,
				 @audioDescricao,
				 @id_evento,
				 @id_base
				  ) 
		 END";


	$conn = mainConnection();   


    $params = array($_POST['numeroObra'],$_POST['id_distribuidor'],$_POST['tipoTela'],$_POST['digital'],$_POST['tipoProjecao'],$_POST['audio'],$_POST['legenda'],$_POST['libras'], $_POST['legendagemDescritiva'],
    	$_POST['audioDescricao'], $_POST['CodPeca'], $_POST['idBase'] ,$id_negoc_obra_distribuidor);  
		 
	if( executeSQL($conn,$queryUpIn,$params)){
		echo "Salvo com sucesso!";
	}else{
		$html = print_r(sqlErrors());
		$html .= "<br>".$queryUpIn;
	}
		// echo $queryUpIn;

}

function buscarDistribuidores(){

	$conn = mainConnection();   

	$query = "
			SELECT * FROM mw_ancine_distribuidor WHERE id_base = ?	
		";

	if(	$rsGeral = executeSQL($conn, $query,array($_POST['idBase'])) ){
			
		if(hasRows($rsGeral)){
			$html = "";
			$i = 1;
			$html .='<option>Selecione...</option>';
			while($rs = fetchResult($rsGeral)){

			 $html .= '
			 <option value="'.$rs['id_distribuidor'].'">'.$rs['razao_social'].'</option>
              ';
	          $i++;  
			}
			echo $html;
		}else{
			echo "<option>Cadastre Algum Distribuidor</option>";	
		}

	}else{
		$html = print_r(sqlErrors());
		$html .= "<br>".$query;	
	}
}


function buscarNegociacoes(){

	$conn = mainConnection();   

	$query = "
			SELECT e.ds_evento,
			 	   ancnod.numeroObra,ancd.razao_social,
			 	   ancnod.id_negoc_obra_distribuidor 
	 		  FROM mw_ancine_negoc_obra_distribuidor ancnod
	    INNER JOIN mw_evento e ON e.id_evento = ancnod.id_evento
	    INNER JOIN mw_ancine_distribuidor ancd 
	    		ON ancd.id_distribuidor = ancnod.id_distribuidor
		 	 WHERE ancnod.id_evento = ?	
		";

	if(	$rsGeral = executeSQL($conn, $query,array($_POST['CodPeca'])) ){
			
		if(hasRows($rsGeral)){
			$html = "";
			$i = 1;
			while($rs = fetchResult($rsGeral)){

			 $html .= '
			 <tr>
              <td style="text-align: center;">
                <label>'.$rs['numeroObra'].'</label>
              </td>
              <td style="text-align: center;">
                <label>'.$rs['razao_social'].'</label>
              </td>
              <td style="text-align: center;">
                <input type="button" class="button btEditar" onclick="getValNegocToForm('.$rs['id_negoc_obra_distribuidor'].')" data-id-negociacao="'.$rs['id_negoc_obra_distribuidor'].'" data-item="'.$i.'" value="Alterar" />
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

function carregarNegociacao(){
		$conn = mainConnection();   

	$query = "
			SELECT *
	 		  FROM mw_ancine_negoc_obra_distribuidor
	     	 WHERE id_negoc_obra_distribuidor = ? 
		";

	if(	$rsGeral = executeSQL($conn, $query,array($_POST['IdNegociacao'])) ){
			
		if(hasRows($rsGeral)){
			$json = "";
			$json .= json_encode(fetchResult($rsGeral));

			while($rs = fetchResult($rsGeral)){

			 	
			}
			echo $json;
		}else{
			echo $query;
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
			cadastrarNegociacao();
			break;	
		case "2":
			buscarNegociacoes();
			break;	
		case "3":
			carregarNegociacao();
			break;	
		case "4":
			buscarDistribuidores();
			break;	
	}
}