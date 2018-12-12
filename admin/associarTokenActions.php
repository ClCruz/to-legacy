<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

// parametros para pesquisa dos dados
$Acao            = isset($_POST["Acao"]) ? $_POST['Acao'] : '';
$CodPeca         = isset($_POST["CodPeca"]) ? $_POST['CodPeca'] : '';



function carregarComboAmbiente(){
	$options = carregarAmbiente();

	$selected  = (isset($_POST['idSelected'])) ? $_POST['idSelected'] : '';


	echo createSelect($options,$selected,'cboAmbiente','cboAmbiente','cbohidden');

}


function carregarCategoriaIngresso(){

	$mainConnection = mainConnection();

	$optionscodigoCategoriaIngresso = array('' =>'-');
	

	$querySub = "SELECT codigoCategoriaIngresso,ds_categoria_ingresso FROM mw_ancine_tipo_bilhete";			
	$rsGeralSub = executeSQL($mainConnection,$querySub);
	while($rs = fetchResult($rsGeralSub)){
		$optionscodigoCategoriaIngresso[$rs['codigoCategoriaIngresso']] = $rs['ds_categoria_ingresso'];
	}


	return $optionscodigoCategoriaIngresso;	
}

function carregarAmbiente(){

	$optionsAmbiente = array(''=>'Selecione o ambiente','P'=>'Produção','H'=>'Homologação');


	return $optionsAmbiente;	
}


function createSelect($optionsArray,$keySelected,$idSelect,$nameSelect,$class='inputhidden'){
		$options = '';
		foreach ($optionsArray as $key => $option) {
			$isSelected = '';
				if($key == $keySelected)
					$isSelected = 'selected="selected"';

				$options .= '<option '.$isSelected.' value="'.$key.'">'.$option.'</option>';
		}

		$selectHtml = '<select id="'.$idSelect.'" name="'.$nameSelect.'" class="'.$class.'">
							'.$options.'
						</select>';
	return $selectHtml;					
}


function buscarRegistros(){


	$IdBase = $_POST['idBase'];
	if($IdBase =='null' || $IdBase ==''){
		echo "Nenhum registro encontrado";	
		exit();
	}

	$conn = mainConnection();
	$query = "	SELECT 
					   token,
					   id_base,
					   ambiente
				  FROM mw_ancine_token		   
				 WHERE id_base = ? 
				  ";
	$params = array($IdBase);			  

	$rsGeral = executeSQL($conn,$query,$params);

	if(!sqlErrors()){
		if(hasRows($rsGeral)){
			$html = "";
			$i = 1;


				$optionsAmbiente = carregarAmbiente();				



			while($rs = fetchResult($rsGeral)){

				$nameSelectId = 'ambienteIn_'.$i;
				$ambienteSelect = createSelect($optionsAmbiente,$rs['ambiente'], $nameSelectId, $nameSelectId);


			 $html .= '<tr>
	              <td style="text-align: center;"><label id="tokenTx_'.$i.'" data-codsala="'.$rs['token'].'" >'.utf8_encode2($rs['token']).'</label>
	                <input type="text" name="token" class="inputhidden" id="tokenIn_'.$i.'"  value="'.utf8_encode2($rs['token']).'">
	              </td>

	             <td style="text-align: center;"><label id="ambienteTx_'.$i.'" >'.utf8_encode2($rs['ambiente']).'</label>
	                '.$ambienteSelect.'
	              </td>
	
	              <td style="text-align: center;">
	                <input type="button" class="button btEditar" onclick="clickEdit(this);" data-item="'.$i.'" value="Editar"  data-tokenold="'.$rs['token'].'" 
	                	data-ambienteold="'.$rs['ambiente'].'" />
	                 <input type="button" class="button btCancelar" style="display:none;" onclick="clickCancel(this);" data-item="'.$i.'" value="Cancelar" />
	                 <input type="button" class="button btApagar"  onclick="clickExcluir(this);" data-item="'.$i.'" data-token="'.$rs['token'].'" data-ambiente="'.$rs['ambiente'].'" value="Apagar" />
	              </td>
	            </tr>';
	          $i++;  
			}
			echo $html;
		}else{
			echo "Nenhum registro encontrado";	
		}
	}else{
		echo "<br>Erro #001:";
		print_r(sqlErrors());	
		echo "<br>".$query;
	}	


}

function salvarRegistros(){


	//  print_r($_POST);
	//  echo $_POST['nrRegExib'];
	// exit();
	// echo $_POST['qtdAssento'];;
	// print_r($obj[0]->codsala);

	if(isset($_POST['tokenOld'])){
		$queryUpIn = " 
		DECLARE @token VARCHAR(100), @ambiente VARCHAR(1),
		@tokenOld VARCHAR(100), @ambienteOld VARCHAR(100),
		@idBase INT; 
	
		SET @token = ?;
		SET @ambiente = ?;
		SET @tokenOld = ?;
		SET @ambienteOld = ?;
		SET @idBase = ?;

	   UPDATE mw_ancine_token SET token = @token,
	  		 ambiente = @ambiente

	   WHERE token = @tokenOld
	   	 AND ambiente = @ambienteOld
	   	 AND id_base = @idBase 
	      if @@rowcount = 0
	   begin
	      INSERT INTO mw_ancine_token (token, ambiente, id_base) VALUES(@token, 
	      @ambiente, @idBase) 
	   end";
		$params = array($_POST['token'],$_POST['ambiente'],$_POST['tokenOld'],$_POST['ambienteOld'],$_POST['idBase']);

	}else{
	 $queryUpIn = " 
		DECLARE @token VARCHAR(100), @ambiente VARCHAR(1), @idBase INT; 
	
		SET @token = ?;
		SET @ambiente = ?;
		SET @idBase = ?;

         INSERT INTO mw_ancine_token (token, ambiente, id_base) 
         VALUES (@token, @ambiente, @idBase) 
		";
		$params = array($_POST['token'],$_POST['ambiente'], $_POST['idBase']);
	}


	$conn = mainConnection();   
 	
	executeSQL($conn,$queryUpIn,$params);

	if(!sqlErrors()){
		echo "Salvo com sucesso!";
		

	}else{
		echo "<br>Erro #001:";
		print_r(sqlErrors());	
		echo "<br>".$queryUpIn;
	}	

}


function excluirAssociacaoToken(){


	$queryUpIn = " 
		DECLARE @token VARCHAR(100), @ambiente VARCHAR(1), @idBase INT; 
	
		SET @token = ?;
		SET @ambiente= ?;
		SET @idBase = ?;

	   DELETE mw_ancine_token 
	   WHERE token = @token
	   AND ambiente = @ambiente
	   AND id_base = @idBase
	  
	  ";


	$conn = mainConnection();   
	$params = array($_POST['token'],$_POST['ambiente'], $_POST['idBase']);
 	
	executeSQL($conn,$queryUpIn,$params);

	if(!sqlErrors()){
		echo "Apagado com sucesso!";
		
	}else{
		echo "<br>Erro #001:";
		print_r(sqlErrors());	
		echo "<br>".$queryUpIn;
	}	
}


if(isset($_REQUEST["Acao"])){
	switch($_REQUEST["Acao"]){
		case "1":
			buscarRegistros();
			break;
		case "2":
			salvarRegistros();
			break;	
		case "4":
			carregarComboAmbiente();
			break;	
		case "5";
			excluirAssociacaoToken();
			break;

	}
}