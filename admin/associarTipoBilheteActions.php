<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

// parametros para pesquisa dos dados
$Acao            = isset($_POST["Acao"]) ? $_POST['Acao'] : '';
$CodPeca         = isset($_POST["CodPeca"]) ? $_POST['CodPeca'] : '';


function carregarCombocodigoCategoriaIngresso(){
	$options = carregarCategoriaIngresso();

	$selected  = (isset($_POST['idSelected'])) ? $_POST['idSelected'] : '';


	echo createSelect($options,$selected,'cbocodigoCategoriaIngresso','cbocodigoCategoriaIngresso','cbohidden');

}

function carregarComboCodTipBilhete(){
	$options = carregarTipoBilhete();

	$selected  = (isset($_POST['idSelected'])) ? $_POST['idSelected'] : '';


	echo createSelect($options,$selected,'cboCodTipBilhete','cboCodTipBilhete','cbohidden');

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

function carregarTipoBilhete($all = false){
	
	$idBase = $_POST['idBase'];

	$conn = getConnection($idBase);

	$optionsCodTipBilhete = array('' =>'-');
	$condAll = $all ? '' : "AND NOT EXISTS(SELECT 1 
						 			  	   FROM CI_MIDDLEWAY..mw_ancine_de_para_tipobilhete anctb
										  WHERE anctb.CodTipBilhete = tb.CodTipBilhete
										  	AND anctb.id_base = $idBase
										  )";

	$querySub2 = " SELECT tb.CodTipBilhete, tb.TipBilhete 
					 FROM tabTipBilhete tb
					WHERE tb.StaTipBilhete = 'A'  
					$condAll  
					ORDER BY tb.TipBilhete ASC";			
	$rsGeralSub2 = executeSQL($conn,$querySub2);
	while($rs = fetchResult($rsGeralSub2)){
		$optionsCodTipBilhete[$rs['CodTipBilhete']] = utf8_encode2($rs['TipBilhete']);
	}


	return $optionsCodTipBilhete;	
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

	$conn = getConnection($IdBase);
	$query = "	SELECT 
					   anctb.codigoCategoriaIngresso,citb.ds_categoria_ingresso,
					   anctb.CodTipBilhete,tb.TipBilhete
				  FROM CI_MIDDLEWAY..mw_ancine_de_para_tipobilhete anctb
					   INNER JOIN tabTipBilhete tb 
							   ON tb.CodTipBilhete = anctb.CodTipBilhete
					   INNER JOIN CI_MIDDLEWAY..mw_ancine_tipo_bilhete citb
							   ON citb.codigoCategoriaIngresso = anctb.codigoCategoriaIngresso
					   
				 WHERE tb.StaTipBilhete = 'A'
				   AND anctb.id_base = ? 
				  ";
	$params = array($IdBase);			  

	$rsGeral = executeSQL($conn,$query,$params);

	if(!sqlErrors()){
		if(hasRows($rsGeral)){
			$html = "";
			$i = 1;


				$optionscodigoCategoriaIngresso = carregarCategoriaIngresso();

				$optionsCodTipBilhete = carregarTipoBilhete();				



			while($rs = fetchResult($rsGeral)){

				$nameSelectId = 'codigoCategoriaIngressoIn_'.$i;
				$codigoCategoriaIngresso = createSelect($optionscodigoCategoriaIngresso,$rs['codigoCategoriaIngresso'], $nameSelectId, $nameSelectId);

				$nameSelectId = 'CodTipBilheteIn_'.$i;
				$CodTipBilhete = createSelect($optionsCodTipBilhete,$rs['CodTipBilhete'], $nameSelectId, $nameSelectId);


			 $html .= '<tr>
	              <td style="text-align: center;"><label id="codigoCategoriaIngressoTx_'.$i.'" >'.utf8_encode2($rs['ds_categoria_ingresso']).'</label>
	                '.$codigoCategoriaIngresso.'
	              </td>

	             <td style="text-align: center;"><label id="CodTipBilheteTx_'.$i.'" >'.utf8_encode2($rs['TipBilhete']).'</label>
	                '.$CodTipBilhete.'
	              </td>
	
	              <td style="text-align: center;">
	                <input type="button" class="button btEditar" onclick="clickEdit(this);" data-item="'.$i.'" value="Editar"  data-codigoCategoriaIngressoold="'.$rs['codigoCategoriaIngresso'].'" 
	                	data-codtipbilheteold="'.$rs['CodTipBilhete'].'" />
	                 <input type="button" class="button btCancelar" style="display:none;" onclick="clickCancel(this);" data-item="'.$i.'" value="Cancelar" />
	                 <input type="button" class="button btApagar"  onclick="clickExcluir(this);" data-item="'.$i.'" data-CodTipBilhete="'.$rs['CodTipBilhete'].'" data-codigoCategoriaIngresso="'.$rs['codigoCategoriaIngresso'].'" value="Apagar" />
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

	if(isset($_POST['CodTipBilheteOld'])){
		$queryUpIn = " 
		DECLARE @CodTipBilhete INT, @codigoCategoriaIngresso INT,
		@CodTipBilheteOld INT, @codigoCategoriaIngressoOld INT,
		@idBase INT; 
	
		SET @CodTipBilhete = ?;
		SET @codigoCategoriaIngresso = ?;
		SET @CodTipBilheteOld = ?;
		SET @codigoCategoriaIngressoOld = ?;
		SET @idBase = ?;

	   UPDATE mw_ancine_de_para_tipobilhete SET CodTipBilhete = @CodTipBilhete,
	  		 codigoCategoriaIngresso = @codigoCategoriaIngresso

	   WHERE CodTipBilhete = @CodTipBilheteOld
	   	 AND codigoCategoriaIngresso = @codigoCategoriaIngressoOld
	   	 AND id_base = @idBase 
	      if @@rowcount = 0
	   begin
	      INSERT INTO mw_ancine_de_para_tipobilhete (CodTipBilhete, codigoCategoriaIngresso, id_base) VALUES(@CodTipBilhete, 
	      @codigoCategoriaIngresso, @idBase) 
	   end";
		$params = array($_POST['CodTipBilhete'],$_POST['codigoCategoriaIngresso'],$_POST['CodTipBilheteOld'],$_POST['codigoCategoriaIngressoOld'],$_POST['idBase']);

	}else{
	 $queryUpIn = " 
		DECLARE @CodTipBilhete INT, @codigoCategoriaIngresso INT, @idBase INT; 
	
		SET @CodTipBilhete = ?;
		SET @codigoCategoriaIngresso = ?;
		SET @idBase = ?;

         INSERT INTO mw_ancine_de_para_tipobilhete (CodTipBilhete, codigoCategoriaIngresso, id_base) 
         VALUES (@CodTipBilhete, @codigoCategoriaIngresso, @idBase) 
		";
		$params = array($_POST['CodTipBilhete'],$_POST['codigoCategoriaIngresso'], $_POST['idBase']);
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


function excluirAssociacaoCodTipBilhete(){


	$queryUpIn = " 
		DECLARE @CodTipBilhete INT, @codigoCategoriaIngresso INT, @idBase INT; 
	
		SET @CodTipBilhete = ?;
		SET @codigoCategoriaIngresso = ?;
		SET @idBase = ?;

	   DELETE mw_ancine_de_para_tipobilhete 
	   WHERE codigoCategoriaIngresso = @codigoCategoriaIngresso
	   AND CodTipBilhete = @CodTipBilhete
	   AND id_base = @idBase
	  
	  ";


	$conn = mainConnection();   
	$params = array($_POST['CodTipBilhete'],$_POST['codigoCategoriaIngresso'], $_POST['idBase']);
 	
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
		case "3":
			carregarCombocodigoCategoriaIngresso();
			break;	
		case "4":
			carregarComboCodTipBilhete();
			break;	
		case "5";
			excluirAssociacaoCodTipBilhete();
			break;

	}
}