<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

// parametros para pesquisa dos dados
$Acao            = isset($_POST["Acao"]) ? $_POST['Acao'] : '';
$CodPeca         = isset($_POST["CodPeca"]) ? $_POST['CodPeca'] : '';




function buscarRegistros(){


	$IdBase = $_POST['idBase'];
	if($IdBase =='null' || $IdBase ==''){
		echo "Nenhum registro encontrado";	
		exit();
	}

	$conn = getConnection($IdBase);
	$query = " SELECT s.CodSala, s.NomSala, ancr.nr_registro_ancine_exibidor, ancr.nr_registro_ancine_sala,
					  ancr.qtd_assento_padrao,ancr.qtd_assento_especial

			     FROM tabSala s
					  LEFT JOIN CI_MIDDLEWAY..mw_ancine_registro ancr 
							 ON ancr.CodSala = s.CodSala
				WHERE s.StaSala = 'A' ";
	$params = array();			  

	$rsGeral = executeSQL($conn,$query,$params);

	if(!sqlErrors()){
		if(hasRows($rsGeral)){
			$html = "";
			$i = 1;
			while($rs = fetchResult($rsGeral)){

			 $html .= '<tr>
	              <td style="text-align: center;"><label id="nomeSalaTx_'.$i.'" data-codsala="'.$rs['CodSala'].'" >'.utf8_encode2($rs['NomSala']).'</label>
	                <input type="text" name="nomeSala" class="inputhidden" data-codsala="'.$rs['CodSala'].'" id="nomeSalaIn_'.$i.'"  value="'.utf8_encode2($rs['NomSala']).'">
	              </td>
	              <td style="text-align: center;"><label id="nrRegExibTx_'.$i.'" data-codsala="'.$rs['CodSala'].'" >'.$rs['nr_registro_ancine_exibidor'].'</label> 
	                <input type="text" name="nrRegExib" class="inputhidden" data-codsala="'.$rs['CodSala'].'" id="nrRegExibIn_'.$i.'" value="'.$rs['nr_registro_ancine_exibidor'].'">
	              </td>
	              <td style="text-align: center;"><label id="nrRegSalaTx_'.$i.'" data-codsala="'.$rs['CodSala'].'" >'.$rs['nr_registro_ancine_sala'].'</label>
	                <input type="text" name="nrRegSala" class="inputhidden" data-codsala="'.$rs['CodSala'].'" id="nrRegSalaIn_'.$i.'" value="'.$rs['nr_registro_ancine_sala'].'">
	              </td>
	              <td style="text-align: center;"><label id="qtdAssentoPadraoTx_'.$i.'" data-codsala="'.$rs['qtd_assento_padrao'].'" >'.$rs['qtd_assento_padrao'].'</label>
	                <input type="text" name="qtdAssentoPadrao" class="inputhidden" data-codsala="'.$rs['qtd_assento_padrao'].'" id="qtdAssentoPadraoIn_'.$i.'" value="'.$rs['qtd_assento_padrao'].'">
	              </td>
	              <td style="text-align: center;"><label id="qtdAssentoEspecialTx_'.$i.'" data-codsala="'.$rs['qtd_assento_especial'].'" >'.$rs['qtd_assento_especial'].'</label>
	                <input type="text" name="qtdAssentoEspecial" class="inputhidden" data-codsala="'.$rs['qtd_assento_especial'].'" id="qtdAssentoEspecialIn_'.$i.'" value="'.$rs['qtd_assento_especial'].'">
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
			echo $query;
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
		DECLARE @IdBase INT, @CodSala SMALLINT,
			    @RegExib INT, @RegSala INT,
			    @QtdAssentoPadrao INT,@QtdAssentoEspecial INT; 
	
		SET @IdBase = ?;
		SET @CodSala = ?;
		SET @RegExib = ?;
		SET @RegSala = ?;
		SET @QtdAssentoPadrao = ?;
		SET @QtdAssentoEspecial = ?;


	   UPDATE mw_ancine_registro SET nr_registro_ancine_exibidor = @RegExib  ,nr_registro_ancine_sala= @RegSala,
	   		  qtd_assento_padrao = @QtdAssentoPadrao, qtd_assento_especial = @QtdAssentoEspecial
	   WHERE id_base = @IdBase AND CodSala = @CodSala	
	      if @@rowcount = 0
	   begin
	      INSERT INTO mw_ancine_registro ( nr_registro_ancine_exibidor, nr_registro_ancine_sala, id_base,
	 	  CodSala, qtd_assento_padrao, qtd_assento_especial) VALUES( @RegExib, @RegSala, @IdBase, @CodSala, @QtdAssentoPadrao, @QtdAssentoEspecial ) 
	   end";


	$conn = mainConnection();   
	$params = array($_POST['idBase'],$_POST['codSala'],$_POST['nrRegExib'],$_POST['nrRegSala'],$_POST['qtdAssentoPadrao'],$_POST['qtdAssentoEspecial']);
 	
	executeSQL($conn,$queryUpIn,$params);

	if(!sqlErrors()){
		echo "Salvo com sucesso!";
		

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
	}
}