<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

// parametros para pesquisa dos dados
$Acao            = isset($_POST["Acao"]) ? $_POST['Acao'] : '';
$CodPeca         = isset($_POST["CodPeca"]) ? $_POST['CodPeca'] : '';


function carregarComboModalidadePagamento(){
	$options = carregarModalidadesPagamento();

	$selected  = (isset($_POST['idSelected'])) ? $_POST['idSelected'] : '';


	echo createSelect($options,$selected,'cboModalidadePagamento','cboModalidadePagamento','cbohidden');

}

function carregarComboFormaPagamento(){
	$options = carregarFormasPagamento();

	$selected  = (isset($_POST['idSelected'])) ? $_POST['idSelected'] : '';


	echo createSelect($options,$selected,'cboFormaPagamento','cboFormaPagamento','cbohidden');

}


function carregarModalidadesPagamento(){

	$mainConnection = mainConnection();

	$optionsModalidadePagamento = array('' =>'-');
	

	$querySub = "SELECT codigoModalidadePagamento,ds_forma_pagamento FROM mw_ancine_forma_pagamento";			
	$rsGeralSub = executeSQL($mainConnection,$querySub);
	while($rs = fetchResult($rsGeralSub)){
		$optionsModalidadePagamento[$rs['codigoModalidadePagamento']] = $rs['ds_forma_pagamento'];
	}


	return $optionsModalidadePagamento;	
}

function carregarFormasPagamento($all = false){
	
	$idBase = $_POST['idBase'];

	$conn = getConnection($idBase);

	$optionsFormaPagamento = array('' =>'-');
	$condAll = $all ? '' : "AND NOT EXISTS(SELECT 1 
						 			  	   FROM CI_MIDDLEWAY..mw_ancine_de_para_formapagamento ancfp
										  WHERE ancfp.CodTipForPagto = fp.CodTipForPagto
										    AND	ancfp.id_base = $idBase	
										  )";

	$querySub2 = "SELECT fp.CodTipForPagto, fp.TipForPagto 
	   				FROM tabTipForPagamento fp 
				   WHERE fp.StaTipForPagto = 'A' 
				   $condAll	
				   ORDER BY TipForPagto ASC";			
	$rsGeralSub2 = executeSQL($conn,$querySub2);
	while($rs = fetchResult($rsGeralSub2)){
		$optionsFormaPagamento[$rs['CodTipForPagto']] = $rs['TipForPagto'];
	}


	return $optionsFormaPagamento;	
}


function createSelect($optionsArray,$keySelected,$idSelect,$nameSelect,$class='inputhidden'){
		$options = '';
		foreach ($optionsArray as $key => $option) {
			$isSelected = '';
				if($key == $keySelected)
					$isSelected = 'selected="selected"';

				$options .= '<option '.$isSelected.' value="'.$key.'">'.utf8_encode2($option).'</option>';
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
					  fp.CodTipForPagto,fp.TipForPagto,ancfp.codigoModalidadePagamento,ancfp.ds_forma_pagamento
				  FROM CI_MIDDLEWAY..mw_ancine_de_para_formapagamento ancdpf
					  INNER JOIN CI_MIDDLEWAY..mw_ancine_forma_pagamento ancfp
							   ON ancfp.codigoModalidadePagamento = ancdpf.codigoModalidadePagamento 		   
					  LEFT JOIN tabTipForPagamento fp 
							   ON fp.CodTipForPagto = ancdpf.CodTipForPagto
				 WHERE fp.StaTipForPagto = 'A'
				   AND ancdpf.id_base = ?
				  ";
	$params = array($IdBase);			  

	$rsGeral = executeSQL($conn,$query,$params);

	if(!sqlErrors()){
		if(hasRows($rsGeral)){
			$html = "";
			$i = 1;


				$optionsModalidadePagamento = carregarModalidadesPagamento();
				$optionsFormaPagamento = carregarFormasPagamento();				



			while($rs = fetchResult($rsGeral)){
				

				$nameSelectId = 'modalidadePagamentoIn_'.$i;
				$modalidadePagamento = createSelect($optionsModalidadePagamento,$rs['codigoModalidadePagamento'], $nameSelectId, $nameSelectId);

				$nameSelectId = 'formaPagamentoIn_'.$i;
				$formaPagamento = createSelect($optionsFormaPagamento,$rs['CodTipForPagto'], $nameSelectId, $nameSelectId);


			 $html .= '<tr>
	              <td style="text-align: center;"><label id="modalidadePagamentoTx_'.$i.'" >'.utf8_encode2($rs['ds_forma_pagamento']).'</label>
	                '.$modalidadePagamento.'
	              </td>

	             <td style="text-align: center;"><label id="formaPagamentoTx_'.$i.'" >'.utf8_encode2($rs['TipForPagto']).'</label>
	                '.$formaPagamento.'
	              </td>
	
	              <td style="text-align: center;">
	                <input type="button" class="button btEditar" onclick="clickEdit(this);" data-item="'.$i.'" value="Editar" />
	                 <input type="button" class="button btCancelar" style="display:none;" onclick="clickCancel(this);" data-item="'.$i.'" value="Cancelar" />
	                 <input type="button" class="button btApagar"  onclick="clickExcluir(this);" data-item="'.$i.'" data-CodTipForPagto="'.$rs['CodTipForPagto'].'" data-codigoModalidadePagamento="'.$rs['codigoModalidadePagamento'].'" value="Apagar" />
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

	$queryUpIn = " 
		DECLARE @CodTipForPagto INT, @codigoModalidadePagamento INT, @idBase INT; 
	
		SET @CodTipForPagto = ?;
		SET @codigoModalidadePagamento = ?;
		SET @idBase = ?;


	   UPDATE mw_ancine_de_para_formapagamento SET CodTipForPagto = @CodTipForPagto,
	  		 codigoModalidadePagamento = @codigoModalidadePagamento

	   WHERE CodTipForPagto = @CodTipForPagto
	     AND id_base = @idBase
	      if @@rowcount = 0
	   begin
	      INSERT INTO mw_ancine_de_para_formapagamento (CodTipForPagto, codigoModalidadePagamento, id_base) VALUES(@CodTipForPagto, 
	      @codigoModalidadePagamento, @idBase) 
	   end";


	$conn = mainConnection();   
	$params = array($_POST['formaPagamento'],$_POST['modalidadePagamento'], $_POST['idBase']);
 	
	executeSQL($conn,$queryUpIn,$params);

	if(!sqlErrors()){
		echo "Salvo com sucesso!";
		

	}else{
		echo "<br>Erro #001:";
		print_r(sqlErrors());	
		echo "<br>".$queryUpIn;
	}	

}


function excluirAssociacaoFormaPagamento(){


	$queryUpIn = " 
		DECLARE @CodTipForPagto INT, @codigoModalidadePagamento INT, @idBase INT; 
	
		SET @CodTipForPagto = ?;
		SET @codigoModalidadePagamento = ?;
		SET @idBase = ?;

	   DELETE mw_ancine_de_para_formapagamento 
	   WHERE codigoModalidadePagamento = @codigoModalidadePagamento
	     AND CodTipForPagto = @CodTipForPagto
	     AND id_base = @idBase	
	  ";


	$conn = mainConnection();   
	$params = array($_POST['formaPagamento'],$_POST['modalidadePagamento'], $_POST['idBase']);
 	
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
			carregarComboModalidadePagamento();
			break;	
		case "4":
			carregarComboFormaPagamento();
			break;	
		case "5";
			excluirAssociacaoFormaPagamento();
			break;

	}
}