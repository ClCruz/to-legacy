<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

// parametros para pesquisa dos dados
$Acao            = isset($_POST["Acao"]) ? $_POST['Acao'] : '';
$CodPeca         = isset($_POST["CodPeca"]) ? $_POST['CodPeca'] : '';


// Entidades do Envio de Dados de Bilheteria
Class Bilheteria{
	public $registroANCINEExibidor;
	
	public $registroANCINESala;
	
	public $diaCinematografico;
	
	public $houveSessoes;

	public $retificador;
	
	public $sessoes;
	


	public  function jsonToArray($json){
		$objArray = json_decode($json);
		
		$this->registroANCINEExibidor = $objArray->registroANCINEExibidor;
		$this->registroANCINESala = $objArray->registroANCINESala;
		$this->diaCinematografico = $objArray->diaCinematografico;
		$this->houveSessoes = $objArray->houveSessoes;
		$this->sessoes =  isset($objArray->sessoes) ? Sessao::setObjectStatic($objArray->sessoes) : array() ;
		$this->retificador = $objArray->retificador;		
	}

	public function toJson(){
		return json_encode($this);
	}
}

Class Sessao{
	public $dataHoraInicio;
	
	// public $quantidadeAssentosDisponibilizados;// SAIU
	public $modalidade; //NOVO

	//public $vendedorRemoto; //NOVO

	public $obras; //NOVO class Obra

	public $totalizacoesTipoAssento; //NOVO
	// public $formaExibicao; //SAIU
	
	// public $formatoExibicao; //SAIU
	
	// public $negociacaoDistribuidor; //SAIU Class NegociacaoObraDistribuidor
	
	// public $totalizacoesCategoriaIngresso; // SAIU Class TotalizacaoCategoria


	public function setObject($objArray){
		$this->dataHoraInicio = $objArray['dataHoraInicio'];
		$this->quantidadeAssentosDisponibilizados = $objArray['quantidadeAssentosDisponibilizados'];
		$this->formaExibicao = $objArray['formaExibicao'];
		$this->formatoExibicao = $objArray['formatoExibicao'];
		$this->negociacaoDistribuidor = $objArray['negociacaoDistribuidor'];
		$this->totalizacoesCategoriaIngresso = $objArray['totalizacoesCategoriaIngresso'];
		return $this;
	}

	public static function setObjectStatic($objArray){
		$aSessao = [];
		foreach ($objArray as $session) {		
			$sessao = new Sessao();

			$sessao->dataHoraInicio = $session->dataHoraInicio;
			$sessao->quantidadeAssentosDisponibilizados = $session->quantidadeAssentosDisponibilizados;
			$sessao->formaExibicao = $session->formaExibicao;
			$sessao->formatoExibicao = $session->formatoExibicao;
			$sessao->negociacaoDistribuidor = $session->negociacaoDistribuidor;
			$sessao->totalizacoesCategoriaIngresso = $session->totalizacoesCategoriaIngresso;
			
			array_push($aSessao, $sessao);
		}
		
		return $aSessao;
	}	
}

Class Obra{
	public $numeroObra;
	public $tituloObra;
	public $tipoTela;
	public $digital;
	public $tipoProjecao;
	public $audio;
	public $legenda;
	public $libras;
	public $legendagemDescritiva;
	public $audioDescricao;
	public $distribuidor;
}

Class Distribuidor{
	public $cnpj;
	public $razaoSocial;
}

Class VendedorRemoto{
	public $cnpj;
	public $razaoSocial;
}
//Modificada e renomeada para Obra Separando o distribuidor
// Class NegociacaoObraDistribuidor{
// 	public $numeroCPBROEObra;
	
// 	public $tituloObra;
	
// 	public $registroANCINEDistribuidor;
	
// 	public $tipoNegociacaoDistribuidor;
	
// 	public $percentualParticipacaoDistribuidor;
	
// 	public $valorPrecoFixo;

// 	public $valorMinimoGarantido;

// 	public $valorRemuneracaoAoExibidor;

// 	public static function setObjectStatic($objArray){
// 		 $negociacaoObraDistribuidor = new NegociacaoObraDistribuidor();

// 		 $negociacaoObraDistribuidor->numeroCPBROEObra = $objArray['numeroCPBROEObra'];		
// 		 $negociacaoObraDistribuidor->tituloObra = $objArray['tituloObra'];
// 		 $negociacaoObraDistribuidor->registroANCINEDistribuidor = $objArray['registroANCINEDistribuidor'];
// 		 $negociacaoObraDistribuidor->tipoNegociacaoDistribuidor = $objArray['tipoNegociacaoDistribuidor'];
// 		 $negociacaoObraDistribuidor->percentualParticipacaoDistribuidor = $objArray['percentualParticipacaoDistribuidor'];
// 		 $negociacaoObraDistribuidor->valorPrecoFixo = $objArray['valorPrecoFixo'];
// 		 $negociacaoObraDistribuidor->valorMinimoGarantido = $objArray['valorMinimoGarantido'];
// 		 $negociacaoObraDistribuidor->valorRemuneracaoAoExibidor = $objArray['valorRemuneracaoAoExibidor'];
// 		 return $NegociacaoObraDistribuidor;
// 	}
// }

//NOVO
Class TotalizacaoTipoAssento{
	public $codigoTipoAssento;
	public $quantidadeDisponibilizada;
	public $totalizacoesCategoriaIngresso; //class TotalizacaoCategoriaIngresso
}

//Alterada de totalizacoesCategoriaIngresso para totalizacaoCategoriaIngresso
Class TotalizacaoCategoriaIngresso{
	public $codigoCategoriaIngresso;

	public $quantidadeEspectadores;

	public $totalizacoesModalidadePagamento;//NOVO class TotalizacaoModalidadePagamento

	//public $valorArrecadado;//SAIU

	//public $quantidadeIngressosValeCultura;//SAIU

	//public $valorArrecadadoValeCultura;//SAIU
}

//NOVO
Class TotalizacaoModalidadePagamento{
	public $codigoModalidadePagamento;
	public $valorArrecadado;
}
// Fim Entidades do Envio de Dados de Bilheteria

// Entidades da Consulta de Protocolo
Class StatusRelatorioBilheteria{
	public $registroANCINEExibidor;
	
	public $registroANCINESala;
	
	public $diaCinematografico;

	public $numeroProtocolo;

	public $statusProtocolo;

	public $mensagens; //Class Mensagem

	public  function jsonToArray($json){
		$objArray = json_decode($json);
		
		$this->registroANCINEExibidor = $objArray->registroANCINEExibidor;
		$this->registroANCINESala = $objArray->registroANCINESala;
		$this->diaCinematografico = $objArray->diaCinematografico;
		$this->numeroProtocolo = $objArray->numeroProtocolo;
		$this->statusProtocolo = $objArray->statusProtocolo;		
		$this->mensagens =  $objArray->mensagens;
		 // Mensagem::setObjectStatic($objArray->mensagens) : array() ;
	}

	public function toJson(){
		return json_encode($this);
	}
}

Class Mensagem{
	public $tipoMensagem;
	
	public $codigoMensagem;
	
	public $textoMensagem;

	public static function setObjectStatic($objArray){
		$aMensagens = [];
		foreach ($objArray as $mensagem) {
			$Mensagem = new Mensagem();
		
			$Mensagem->tipoMensagem = $mensagem->mensagem->tipoMensagem;
			$Mensagem->codigoMensagem = $mensagem->mensagem->codigoMensagem;
			$Mensagem->textoMensagem = $mensagem->mensagem->textoMensagem;	

			array_push($aMensagens, $Mensagem);
		}


		return $aMensagens;
	}
}

// Fim Entidades da Consulta de Protocolo

// Entidades de Consulta a Situa��o de Adimplencia

//NOVO
Class AdimplenciaExibidor{
	public $registroANCINEExibidor;
	public $diaCinematografico;
	public $adimplenciaSalas;//class AdimplenciaSala

	public function toJson(){
		return json_encode($this);
	}

	public  function jsonToArray($json){
		$objArray = json_decode($json);
		
		$this->registroANCINEExibidor = $objArray->registroANCINEExibidor;
		$this->diaCinematografico = $objArray->diaCinematografico;
		$this->adimplenciaSalas = $objArray->adimplenciaSalas;
	}
}


Class AdimplenciaSala{
	public $registroANCINESala;
	public $situacaoSala;
}

//Saiu
// Class SituacaoAdimplencia{
// 	public $registroANCINESala;

// 	public $diaCinematografico;

// 	public $situacaoSalaDiaCinematografico;

// 	public $statusProtocolo; //Verificar no retorno do WS se realmente vem esse

// 	public  function jsonToArray($json){
// 		$objArray = json_decode($json);
		
// 		$this->registroANCINESala = $objArray->registroANCINESala;
// 		$this->diaCinematografico = $objArray->diaCinematografico;
// 		$this->situacaoSalaDiaCinematografico = isset($objArray->situacaoSalaDiaCinematografico)? $objArray->situacaoSalaDiaCinematografico : ''; 
// 		$this->statusProtocolo = $objArray->statusProtocolo;


// 	}
	
// 	public function toJson(){
// 		return json_encode($this);
// 	}
// }

// Fim Entidades de Consulta a Situa��o de Adimplencia 



if(isset($_POST['NomeBase']) && $_POST["NomeBase"] != "" && $_POST["Proc"] != "" && !isset($_REQUEST["Acao"])){

	$strQuery = "SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = ".$_POST["NomeBase"];
	if( $stmt = executeSQL($mainConnection, $strQuery, array(), true) ){
		// $conn = getConnection($_POST["NomeBase"]);
		$conn = mainConnection();
		$query = " SELECT cis.NomSala AS NomSala, cis.CodSala AS CodSala
				     FROM mw_evento e 
			   INNER JOIN mw_apresentacao a ON a.id_evento = e.id_evento AND a.in_ativo = 1
			   INNER JOIN ".$stmt["DS_NOME_BASE_SQL"]."..tabApresentacao ciap ON ciap.CodApresentacao = a.CodApresentacao 
			   INNER JOIN ".$stmt["DS_NOME_BASE_SQL"]."..tabSala cis ON cis.CodSala = ciap.CodSala 
			   INNER JOIN mw_ancine_registro ancr ON ancr.CodSala = cis.CodSala AND ancr.id_base = e.id_base
				    WHERE e.id_base = ? AND e.in_ativo = 1
				  	  AND DATEDIFF(day,GETDATE(),a.dt_apresentacao) >= (-15)
				 GROUP BY cis.NomSala,cis.CodSala";

		$params = array($_POST['NomeBase']);		 

		// $query = "EXEC ". $stmt["DS_NOME_BASE_SQL"] ."..". $_POST['Proc'] ." ". $_SESSION['admin'] .", ". $_POST["NomeBase"];
		if(	$result = executeSQL($conn, $query, $params) ){
			// Cria sessao com nome da base utilizada
			$_SESSION["IdBase"] = $_POST["NomeBase"];
			$_SESSION["NomeBase"] = $stmt["DS_NOME_BASE_SQL"];
			$html = "<select name=\"cboSala\" id=\"cboSala\" onchange=\"\">\n";
			$html .= "<option value=\"todos\">Todos</option>";
			if(hasRows($result)){
				while($rs = fetchResult($result)){
					$html .= "<option value=\"". $rs["CodSala"] ."\">". utf8_encode2($rs["NomSala"]) ."</option>\n";	
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


function buscarSalasDiasCinematograficos(){

	$IdBase = $_POST['idBase'];
	$CodSala = $_POST['CodSala'];
	if($IdBase =='null' || $IdBase ==''){
		echo "Nenhum registro encontrado";	
		exit();
	}
	$conn = mainConnection();

	$condCodSala = $CodSala == 'todos' ? "" : " AND ancr.CodSala = ".$CodSala." ";

	$aDataIni = explode('/',$_POST['DatIni']);
	$strDataIni = $aDataIni[2].'-'.$aDataIni[1].'-'.$aDataIni[0];

	$aDataFim = explode('/',$_POST['DatFim']);
	$strDataFim = $aDataFim[2].'-'.$aDataFim[1].'-'.$aDataFim[0];


	$nomeBase = "";
	$strQuery = "SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = ".$_POST["idBase"];
	if( $stmt = executeSQL($conn, $strQuery, array(), true) ){
		$nomeBase = $stmt["DS_NOME_BASE_SQL"];
	}
	$conn = mainConnection();

	$query = 'SELECT cis.NomSala AS NomSala, cis.CodSala AS CodSala,
					 CONVERT(VARCHAR, a.dt_apresentacao,103) AS dt_apresentacao,
					 CONVERT(VARCHAR(10), a.dt_apresentacao, 120) AS dt_apresentacao_value,
 					 ISNULL(anca.nr_protoco_ancine,\'\') AS nr_protoco_ancine,
 					 ISNULL(anca.id_arquivo,0) AS id_arquivo,
 					 anca.statusProtocolo AS statusProtocolo,
 					 ISNULL(CONVERT(VARCHAR(MAX),anca.mensagens),\'\') AS mensagens 
		 	    FROM mw_evento e 
		  INNER JOIN mw_apresentacao a ON a.id_evento = e.id_evento AND a.in_ativo = 1
		  INNER JOIN '.$nomeBase.'..tabApresentacao ciap ON ciap.CodApresentacao = a.CodApresentacao	
		  INNER JOIN '.$nomeBase.'..tabSala cis ON cis.CodSala = ciap.CodSala 
		  INNER JOIN mw_ancine_registro ancr ON ancr.CodSala = cis.CodSala AND ancr.id_base = e.id_base
	 LEFT  JOIN mw_ancine_arquivos anca ON anca.CodSala = cis.CodSala AND anca.dt_apresentacao = a.dt_apresentacao
			   WHERE e.id_base = ? AND e.in_ativo = 1
			     AND DATEDIFF(day,GETDATE(),a.dt_apresentacao) >= (-15)
			     AND a.dt_apresentacao >= ? AND a.dt_apresentacao <= ? 

			    '.$condCodSala.'
		    GROUP BY cis.NomSala,cis.CodSala,a.dt_apresentacao,anca.nr_protoco_ancine,
		    anca.id_arquivo,anca.statusProtocolo,CONVERT( VARCHAR(MAX), anca.mensagens)';
	
	
	$rsGeral = executeSQL($conn,$query,array($IdBase,$strDataIni,$strDataFim));

	if(!sqlErrors()){
	if(hasRows($rsGeral)){
			$html = "";
			$i = 1;
			$statusProtocolo = array('N'=>'N�o acatado',
									 'A'=>'Em An�lise',
									 'E'=>'Com Erro',
									 'V'=>'Validado',
									 'R'=>'Recusado');
			 
			while($rs = fetchResult($rsGeral)){

					if(!is_null($rs['statusProtocolo'])){
						$mensagens = json_decode($rs['mensagens']);
						// print_r($mensagens);
						$mensagens_html = '<br>Retorno(s): ';	
							foreach ($mensagens as $mensagem) {
								$mensagens_html .= ' <a href="javascript:void(0);" 
															data-mensagem="'.utf8_decode($mensagem->textoMensagem).'" 
															onclick="exibirMensagem(this);" 
															>'.
															$mensagem->codigoMensagem.'</a> ';
							}

						$situacao	= '<label><a href="javascript:void(0);" class="situacaoSala" 
							 data-cod-protocolo="'.$rs['nr_protoco_ancine'].'"
							 data-cod-situacao="'.$rs['statusProtocolo'].'" onclick="situacaoSalaDiaCinematografico(this);">'.$statusProtocolo[$rs['statusProtocolo']].'</a>
							  	'.$mensagens_html.'
							 </label>';
					}else{
						$situacao = '';
					}

					if(!is_null($rs['nr_protoco_ancine']) && $rs['nr_protoco_ancine']!='' 
						&& !is_null($rs['statusProtocolo']) 
						&& ($rs['statusProtocolo'] != 'N' 
							&& $rs['statusProtocolo'] != 'E' 
							&& $rs['statusProtocolo'] != 'R'  )
						){
						//$objSituacaoAdimplencia  = consultarProtocolo($rs['nr_protoco_ancine']);

						$actionBt = '';
					}else{
						if(!is_null($rs['statusProtocolo'])){
							$retificarOn = 1;
							$actionBt = 'Re-Enviar para ANCINE';
						}else{
							$retificarOn = 0;
							$actionBt = 'Enviar para ANCINE';

						}

					}	

				if(!is_null($rs['nr_protoco_ancine']) && $rs['nr_protoco_ancine'] != '' ){
			 		$dataProtocolo = ' data-cod-protocolo="'.$rs['nr_protoco_ancine'].'"';
			 	}
			 	else{
			 		$dataProtocolo = '';
			 	}
			 			
			 if(empty($actionBt)){

			 	$button = '<input type="button" class="button btEditar" 
	  						data-id-arquivo="'.$rs['id_arquivo'].'" 
	  						data-codsala="'.$rs['CodSala'].'" 
	  						data-item="'.$i.'"  '.$dataProtocolo.'    
	  			 			data-dt-apresentacao="'.$rs['dt_apresentacao_value'].'" 
	  			 			value="'.utf8_encode2('Consultar Situa��o').'" 
	  			 			onclick="consultarProtocolo(this);"

	  			 />';
			 }else{
			 
	  			$button = '<input type="button" class="button btEditar" 
	  						data-id-arquivo="'.$rs['id_arquivo'].'" 
	  						data-codsala="'.$rs['CodSala'].'" 
	  						data-item="'.$i.'"  '.$dataProtocolo.'    
	  			 			data-dt-apresentacao="'.$rs['dt_apresentacao_value'].'" 
	  			 			value="'.$actionBt.'" 
	  			 			data-retificar="'.$retificarOn.'"
	  			 			onclick="enviarParaAncine(this);"

	  			 />';	
			 }

			 $html .= '
			 <tr>
	              <td style="text-align: center;">
	                <label>'.utf8_encode2($rs['NomSala']).'</label>
	              </td>
	              <td style="text-align: center;">
	                <label>'.$rs['dt_apresentacao'].'</label> 
	              </td>
	              <td style="text-align: center;">
	              		'.utf8_encode2($situacao).'
	              </td>
	              <td style="text-align: center;">
	                '.$button.'
	              </td>
            </tr>  
            ';
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



function carregarTotalizacaoTipoAssento($codSala, $dtApresentacao, $idBase,$tipoCadeira = 'P',$quantidadeDisponibilizada){
	$query = "WITH result AS(
				SELECT 
						fp.CodTipForPagto,
						l.CodForPagto,l.CodTipBilhete,l.ValPagto,l.QtdBilhete
				  FROM tabSala s
					INNER JOIN tabApresentacao a
							ON a.CodSala = s.CodSala
					INNER JOIN tabLancamento l
							ON l.CodApresentacao = a.CodApresentacao
					INNER JOIN CI_MIDDLEWAY..mw_evento e
							ON e.CodPeca = a.CodPeca
					INNER JOIN tabForPagamento fp
						ON fp.CodForPagto = l.CodForPagto				
				 WHERE 
				   --a.CodPeca in (364) AND 
				  s.CodSala IN( ? )
				  AND a.DatApresentacao = ?									
				  AND e.id_base = ?
				  AND not exists (Select bb.Indice from tabLancamento bb
									where bb.numlancamento = l.numlancamento
									  and bb.codtipbilhete = l.codtipbilhete
									  and bb.codtiplancamento = 2
									  and bb.codapresentacao = l.codapresentacao
									  and bb.indice          = l.indice)		
				)
				, X AS (
				SELECT 
				anctb.codigoCategoriaIngresso,
						 ISNULL(SUM(r.QtdBilhete),0) AS 'quantidadeEspectadores' ,		 
				 r.CodTipForPagto,
				 ancfp.codigoModalidadePagamento,
						 ISNULL(SUM(r.ValPagto),0) AS 'valorArrecadado'  
				 FROM result AS r
				RIGHT OUTER JOIN CI_MIDDLEWAY..mw_ancine_de_para_formapagamento fpdp
						ON fpdp.CodTipForPagto = r.CodTipForPagto
						
				CROSS JOIN CI_MIDDLEWAY..mw_ancine_forma_pagamento	ancfp
						--ON ancfp.codigoModalidadePagamento = fpdp.codigoModalidadePagamento

				INNER JOIN CI_MIDDLEWAY..mw_ancine_de_para_tipobilhete tipdp
						ON tipdp.CodTipBilhete = r.CodTipBilhete
				RIGHT OUTER JOIN CI_MIDDLEWAY..mw_ancine_tipo_bilhete	anctb
						ON anctb.codigoCategoriaIngresso = tipdp.codigoCategoriaIngresso

				WHERE ancfp.codigoModalidadePagamento = fpdp.codigoModalidadePagamento

				GROUP BY  r.CodForPagto,ancfp.codigoModalidadePagamento,r.CodTipForPagto,
						 r.CodTipBilhete,anctb.codigoCategoriaIngresso,r.QtdBilhete
				--ORDER BY anctb.codigoCategoriaIngresso ASC, ancfp.codigoModalidadePagamento ASC
				)	
				SELECT tb.codigoCategoriaIngresso,0 AS 'quantidadeEspectadores',0 AS 'CodTipoForPagto',
					   fp.codigoModalidadePagamento,0 AS 'valorArrecadado'
				  FROM CI_MIDDLEWAY..mw_ancine_tipo_bilhete tb 
					CROSS JOIN CI_MIDDLEWAY..mw_ancine_forma_pagamento fp
				WHERE tb.codigoCategoriaIngresso NOT IN (SELECT X.codigoCategoriaIngresso FROM X) 
				  OR fp.codigoModalidadePagamento NOT IN (SELECT X.codigoModalidadePagamento FROM X)
				UNION ALL
				SELECT * FROM X
				ORDER BY 1 ASC , 4 ASC


				";
	
	$params = array($codSala, $dtApresentacao, $idBase);	

	if($tipoCadeira == 'E'){			
		$query = "  SELECT tb.codigoCategoriaIngresso,0 AS 'quantidadeEspectadores',0 AS 'CodTipoForPagto',
						   fp.codigoModalidadePagamento,0 AS 'valorArrecadado'
					  FROM CI_MIDDLEWAY..mw_ancine_tipo_bilhete tb 
						CROSS JOIN CI_MIDDLEWAY..mw_ancine_forma_pagamento fp
					ORDER BY 1 ASC, 4 ASC";			
		$params = array();			
	}

	$conn = getConnection($idBase);		

	if(	$result = executeSQL($conn, $query, $params) ){
		
		if(hasRows($result)){
	

				//TotalizacaoCategoriaIngresso
				

			 
		
			$totalizacaoTipoAssento = null;	

			$totalizacaoCategoriaIngresso = null;

			$i = 1;

			while($rs = fetchResult($result)){
					
					// echo $i.'- codigoCategoriaIngresso:'.$rs['codigoCategoriaIngresso'].'  quantidadeEspectadores:'
					// 	.$rs['quantidadeEspectadores'].'  codigoModalidadePagamento:'.$rs['codigoModalidadePagamento'].'  valorArrecadado:'.
					// 	$rs['valorArrecadado'].'<br>'
					// 	;
						
					if(is_null($totalizacaoTipoAssento)){
						$totalizacaoTipoAssento = new TotalizacaoTipoAssento();	
				 		$totalizacaoTipoAssento->codigoTipoAssento = $tipoCadeira;
				 		$totalizacaoTipoAssento->quantidadeDisponibilizada = $quantidadeDisponibilizada;
				 		// echo "open TotalizacaoTipoAssento<br>";
					}
					

					if(is_null($totalizacaoCategoriaIngresso) 
						|| $totalizacaoCategoriaIngresso->codigoCategoriaIngresso != $rs['codigoCategoriaIngresso']){

						$totalizacaoCategoriaIngresso = new TotalizacaoCategoriaIngresso();

						$totalizacaoCategoriaIngresso->codigoCategoriaIngresso = $rs['codigoCategoriaIngresso'];
						$totalizacaoCategoriaIngresso->quantidadeEspectadores = $rs['quantidadeEspectadores'];

						// echo "open TotalizacaoCategoriaIngresso<br>";			
				    }


					    $totalizacaoModalidadePagamento = new TotalizacaoModalidadePagamento();

					    $totalizacaoModalidadePagamento->codigoModalidadePagamento = $rs['codigoModalidadePagamento'];
					    $totalizacaoModalidadePagamento->valorArrecadado = ($rs['valorArrecadado'] =='.00') ? '0.00' : $rs['valorArrecadado'];
						// echo  $i.' - '. $rs['valorArrecadado'].'<br>';
						//
					    $totalizacaoCategoriaIngresso->totalizacoesModalidadePagamento[] = $totalizacaoModalidadePagamento;

					     if($i == 3||$totalizacaoCategoriaIngresso->codigoCategoriaIngresso != $rs['codigoCategoriaIngresso']){
							$totalizacaoTipoAssento->totalizacoesCategoriaIngresso[] = $totalizacaoCategoriaIngresso;     
							// echo "totalizacoesCategoriaIngresso[] = totalizacaoCategoriaIngresso<br>";
							$i = 0;
						 }
					// }
				$i++;		

			}

			return $totalizacaoTipoAssento;
		}
		

	}else{
		echo print_r(sqlErrors());
		// $html .= "<br>".$query;	
	}

	return null;

}

function carregarBilheteriaSessoes($codSala,$dtApresentacao,$idBase,$retificarOn = false){
	// $codSala = '42';
	// $dtApresentacao = '2017-05-27';
	// $idBase = '139';
	// echo "$codSala,$dtApresentacao,$idBase<br>";

	$query = "SELECT DISTINCT
					--BILHETERIA
					ancr.nr_registro_ancine_exibidor,ancr.nr_registro_ancine_sala, 
					CONVERT(VARCHAR(10),a.DatApresentacao,120) AS 'diaCinematografico',
					CASE WHEN sum(l.QtdBilhete) > 0  THEN 'S' 
						 WHEN SUM(l.QtdBilhete) <= 0 THEN 'N'	
					END AS 'houveSessoes',	
					--SESSAO
					 CONVERT(VARCHAR(20),a.DatApresentacao + ' '+ a.HorSessao,120)  AS 'dataHoraInicio','A' AS 'modalidade',
					 --OBRA	
					 ancnod.numeroObra,e.ds_evento,	ancnod.tipoTela,ancnod.digital,
					 ancnod.tipoProjecao, ancnod.audio,
					 ancnod.legenda,ancnod.libras, ancnod.legendagemDescritiva,
					 ancnod.audioDescricao, ancd.cnpj, ancd.razao_social
					 --, l.DatVenda
					 ,ancr.qtd_assento_especial,ancr.qtd_assento_padrao	
				FROM tabSala s
					INNER JOIN tabApresentacao a
							ON a.CodSala = s.CodSala
					INNER JOIN tablancamento  l
					INNER JOIN tabTipBilhete tb 
							ON tb.CodTipBilhete = l.CodTipBilhete
							ON l.CodApresentacao = a.CodApresentacao	
					INNER JOIN tabTipLancamento tl
							ON l.CodTipLancamento = tl.CodTipLancamento		
					INNER JOIN CI_MIDDLEWAY..mw_evento e 
							ON e.CodPeca = a.CodPeca
					INNER JOIN CI_MIDDLEWAY..mw_ancine_negoc_obra_distribuidor	 ancnod
							ON ancnod.id_evento = e.id_evento
						   AND ancnod.id_base = e.id_base
					
					INNER JOIN CI_MIDDLEWAY..mw_ancine_distribuidor ancd
							ON ancd.id_distribuidor = ancnod.id_distribuidor	   		
					
					INNER JOIN CI_MIDDLEWAY..mw_ancine_registro ancr
							ON ancr.CodSala = a.CodSala
						   AND ancr.id_base = e.id_base		
				   	  
				WHERE 
				--a.CodPeca in (364) 
				  --AND 
				  s.CodSala IN( ? )
				  AND a.DatApresentacao = ?									
				  AND e.id_base = ?
				  AND not exists (Select bb.Indice from tabLancamento bb
									where bb.numlancamento = l.numlancamento
									  and bb.codtipbilhete = l.codtipbilhete
									  and bb.codtiplancamento = 2
									  and bb.codapresentacao = l.codapresentacao
									  and bb.indice          = l.indice)
  				GROUP BY ancr.nr_registro_ancine_exibidor,ancr.nr_registro_ancine_sala,
					a.DatApresentacao,a.HorSessao,ancnod.numeroObra,e.ds_evento,
					ancnod.tipoTela,ancnod.digital,ancnod.tipoProjecao,
					ancnod.audio,ancnod.legenda,ancnod.libras,ancnod.legendagemDescritiva,
					ancnod.audioDescricao,ancd.cnpj,ancd.razao_social,
					ancr.qtd_assento_especial,ancr.qtd_assento_padrao		  
					";

	$bilheteria = new Bilheteria();

	
	$params = array($codSala, $dtApresentacao, $idBase);
	$conn = getConnection($idBase);
 
	if(	$result = executeSQL($conn, $query, $params) ){
		
		if(hasRows($result)){
	
				// $stmt = fetchResult($result);	

		

				$sessao = null;
				$obra  = null;
				$qtdAssentoPadrao = 0;
				$qtdAssentoEspecial = 0;

			while($rs = fetchResult($result)){
		
					if(!isset($bilheteria->registroANCINEExibidor) 
						|| is_null($bilheteria->registroANCINEExibidor)
						|| $bilheteria->registroANCINEExibidor == ''
						){
						//Bilheteria
						$bilheteria->registroANCINEExibidor = $rs['nr_registro_ancine_exibidor'];
						$bilheteria->registroANCINESala = $rs['nr_registro_ancine_sala'];
						$bilheteria->diaCinematografico = $rs['diaCinematografico'];
						$bilheteria->houveSessoes = $rs['houveSessoes'];
						$bilheteria->retificador = $retificarOn ? 'S' :'N';			
					}

					if(is_null($sessao) || $sessao->dataHoraInicio != $rs['dataHoraInicio']){

						$sessao = new Sessao();
						//Sessoes
						$sessao->dataHoraInicio = $rs['dataHoraInicio'];
						$sessao->modalidade = $rs['modalidade'];
				    }
				    if(is_null($obra) || $obra->numeroObra != $rs['numeroObra']){
				    	$obra = new Obra();
				    	$obra->numeroObra = $rs['numeroObra'];
				    	$obra->tituloObra = $rs['ds_evento'];
				    	$obra->tipoTela = $rs['tipoTela'];
				    	$obra->digital = $rs['digital'];
				    	$obra->tipoProjecao = $rs['tipoProjecao'];
				    	$obra->audio = $rs['audio'];
				    	$obra->legenda = $rs['legenda'];
				    	$obra->libras = $rs['libras'];
				    	$obra->legendagemDescritiva = $rs['legendagemDescritiva'];
				    	$obra->audioDescricao = $rs['audioDescricao'];
				    	
				    	$distribuidor = new Distribuidor();
				    	$distribuidor->cnpj = $rs['cnpj'];
				    	$distribuidor->razaoSocial = $rs['razao_social'];

				    	$obra->distribuidor = $distribuidor;


				    }

				$sessao->obras[] = $obra;
				// echo '01-SESSAO<br>';
				$qtdAssentoPadrao = $rs['qtd_assento_padrao'];
				$qtdAssentoEspecial = $rs['qtd_assento_especial'];

			}

			$sessao->totalizacoesTipoAssento[] = carregarTotalizacaoTipoAssento($codSala, $dtApresentacao, $idBase,'P',$qtdAssentoPadrao);
			$sessao->totalizacoesTipoAssento[] = carregarTotalizacaoTipoAssento($codSala, $dtApresentacao, $idBase,'E',$qtdAssentoEspecial);

			$bilheteria->sessoes[] = $sessao;   

			return $bilheteria;	
		}
	   

	}else{
		echo print_r(sqlErrors());
	}
 
  return null;

}


function ToUl($input){
   $html = "<ul>";

   foreach($input as $value)
     if(is_array($value) || is_object($value)){
        $html .= "<li>";
        ToUl((array)$value);
        $html .= "</li>";
     }else
        $html .= "<li>" + $value + "</li>";

   $html .= "</ul>";
   return $html;
}


function confirmEnviar(){
	$html = '';

	$codSala = $_POST['CodSala'];    
 	$id_arquivo = $_POST['idArquivo'];
 	$idBase = $_POST['CodBase'];
 	$dtApresentacao = $_POST['dtApresentacao'];
 	$retificarOn = $_POST['retificar'];

	$bilheteria = carregarBilheteriaSessoes($codSala,$dtApresentacao,$idBase,$retificarOn);

	$dataSend = $bilheteria->toJson();
	$html .= '<style>
					.space{
						padding-right: 15px;
					}
					.title{
						font-size: 15px;
					}    
			</style>
			<script>
				  function showTipoAssento(id){
		              var qtdTipoAssento = $(\'#countTipoAssento_\'+id).val();
		              for(var i=0; i < qtdTipoAssento; i++){
		                if(i==id){
		                  $(\'#tipoAssento_\'+i).show();
		                }else{
		                  $(\'#tipoAssento_\'+i).hide();
		                }
		              }

		            }
			</script>
			';
	$html .= '<button class="button" onclick="showDiv(\'#divBilheteria\');">Bilheteria</button>';			
	$html .= '<button class="button" onclick="showDiv(\'#divSessao\');">Sessoes</button>';			
	$html .= '<button class="button" onclick="showDiv(\'#divObras\');">Obras</button>';			
	$html .= '<button class="button" onclick="showDiv(\'#divTotalizacao\');">'.utf8_encode2('Totaliza��es').'</button>';	


$contBilheteria = '<table>
						<tbody>
							<tr>
								<td style="text-align:right;"><strong>'.utf8_encode2('C�digo Registro Exibidor: ').'</strong></td>
								<td class="space"> '.$bilheteria->registroANCINEExibidor.'</td>
							
								<td style="text-align:right;"><strong>'.utf8_encode2('Houve Sess�es: ').'</strong></td>
								<td> '.$bilheteria->houveSessoes.'</td>
							</tr>
							<tr>
								<td style="text-align:right;"><strong>'.utf8_encode2('C�digo Registro Sala: ').'</strong></td>
								<td class="space"> '.$bilheteria->registroANCINESala.'</td>
								
								<td style="text-align:right;"><strong>Retificador: </strong></td>
								<td> '.$bilheteria->retificador.'</td>
							</tr>					
						</tbody>
					</table>';

	$contSessao = '';
	$contObra   = '';
	$contTipoAssento = '';
	if(isset($bilheteria->sessoes) && count($bilheteria->sessoes) >0 ){
		$contSessao .= '<table><tbody>';	
		
		
		foreach ($bilheteria->sessoes as $sessao) {
					$contSessao	.= '<tr>
										<td style="text-align:right;">   <strong>'.utf8_encode2('Data Sess�o: ').'</strong></td>
										<td class="space">  '.$sessao->dataHoraInicio.'</td>
										<td style="text-align:right;"><strong>   '.utf8_encode2('Modalidade: ').'</strong></td>
										<td> '.$sessao->modalidade.'</td>
									</tr>
								';


			 $i = 1;
			foreach ($sessao->obras as $obra) {
				if($i==1){
					$styleDisplay = 'block';
				}
				else{
					$styleDisplay = 'block';
				}
				$contObra .= '<table id="obra_'.$i.'" style="display: '.$styleDisplay.';"><tbody>';
				$contObra .= '<tr>
								<td style="text-align:right;">   <strong>'.utf8_encode2('Numero Obra: ').'</strong></td>
								<td>  '.$obra->numeroObra.'</td>
							 </tr>
							<tr>
								<td style="text-align:right;"><strong>   '.utf8_encode2('Titulo Obra: ').'</strong></td>
								<td> '.$obra->tituloObra.'</td>
							</tr>
							<tr>
								<td style="text-align:right;"><strong>   '.utf8_encode2('Tipo Tela: ').'</strong></td>
								<td> '.$obra->tipoTela.'</td>
							</tr>
							<tr>
								<td style="text-align:right;"><strong>   '.utf8_encode2('Digital: ').'</strong></td>
								<td> '.$obra->digital.'</td>
							</tr>
							<tr>
								<td style="text-align:right;"><strong>   '.utf8_encode2('Tipo Proje��o: ').'</strong></td>
								<td> '.$obra->tipoProjecao.'</td>
							</tr>
							<tr>
								<td style="text-align:right;"><strong>   '.utf8_encode2('�udio: ').'</strong></td>
								<td> '.$obra->audio.'</td>
							</tr>
							<tr>
								<td style="text-align:right;"><strong>   '.utf8_encode2('Legenda: ').'</strong></td>
								<td> '.$obra->legenda.'</td>
							</tr>
							<tr>
								<td style="text-align:right;"><strong>   '.utf8_encode2('Libras: ').'</strong></td>
								<td> '.$obra->libras.'</td>
							</tr>
							<tr>
								<td style="text-align:right;"><strong>   '.utf8_encode2('legendagem Descritiva: ').'</strong></td>
								<td> '.$obra->legendagemDescritiva.'</td>
							</tr>
							<tr>
								<td style="text-align:right;"><strong>   '.utf8_encode2('�udio descri��o: ').'</strong></td>
								<td> '.$obra->audioDescricao.'</td>
							</tr>
							<br>
							<tr>
								<td><br><h3 class="title">   '.utf8_encode2('Distribuidor ').'</h3>
								 
									<tr>
										<td style="text-align:right;"><strong>'.utf8_encode2('CNPJ: ').'</strong></td>
										<td>'.$obra->distribuidor->cnpj.'</td>
									</tr>
									<tr>	
										<td style="text-align:right;"><strong>'.utf8_encode2('Raz�o Social: ').'</strong></td>
										<td>'.$obra->distribuidor->razaoSocial.'</td>
									</tr>
								</td>
							</tr>
						';
				$contObra .= '</tbody></table> <div class="button" onclick="showObra('.$i.');" >'.$i.'</div>';
				$i++;
			}
			$contObra .= '<input type="hidden" id="countObras" value="'.$i.'" />';


			$iTipoAssento = 1;
			$contTipoAssento = '';
			foreach ($sessao->totalizacoesTipoAssento as $totalizacoesTipoAssento) {
				$contCategoriaIngresso = '';
				foreach ($totalizacoesTipoAssento->totalizacoesCategoriaIngresso as $totalizacaoCategoriaIngresso) {
						$contModalidadePagamento = '';
						foreach ($totalizacaoCategoriaIngresso->totalizacoesModalidadePagamento as $totalizacaoModalidadePagamento) {
								$contModalidadePagamento .= '
										 <tr>
										 	<td style="text-align:right;"><strong>'.utf8_encode2('Cod. Modalidade Pagamento: ').'</strong></td>
										 	<td>'.$totalizacaoModalidadePagamento->codigoModalidadePagamento.'</td>
										 	<td style="text-align:right;"><strong>'.utf8_encode2('Valor Arrecadado: ').'</strong></td>
										 	<td>'.$totalizacaoModalidadePagamento->valorArrecadado.'</td>
										 </tr>	
										';
						}

						$contCategoriaIngresso .= '<tr>
										<td style="text-align:right;"><strong>'.utf8_encode2('Cod. Categoria Ingresso: ').'</strong></td>
										<td>'.$totalizacaoCategoriaIngresso->codigoCategoriaIngresso.'</td>
										<td style="text-align:right;"><strong>'.utf8_encode2('Qtd. Espectadores: ').'</strong></td>
										<td>'.$totalizacaoCategoriaIngresso->quantidadeEspectadores.'</td>
										
										<td>
											<tr>

												<td style="text-align:right;"><br><strong class="title">'.utf8_encode2('Totaliza��es Modalidade Pagamento ').'</strong>
													'.$contModalidadePagamento.'
												</td>
											</tr>
										</td>
									</tr>'; 
				}

				if($iTipoAssento==1){
					$styleDisplay = 'block';
				}
				else{
					$styleDisplay = 'block';
				}
				$contTipoAssento .= '<table id="tipoAssento_'.$iTipoAssento.'" style="display: '.$styleDisplay.';"><tbody>';
				$contTipoAssento .= '
							<tr>
								<td style="text-align:right;">   <strong>'.utf8_encode2('Cod. Tipo Assento: ').'</strong></td>
								<td>  '.$totalizacoesTipoAssento->codigoTipoAssento.'</td>
							</tr>
							<tr>
								<td style="text-align:right;"><strong>   '.utf8_encode2('Qtd. Disponibilizada: ').'</strong></td>
								<td> '.$totalizacoesTipoAssento->quantidadeDisponibilizada.'</td>
							</tr>
							<tr>
								<td style="text-align:right;"><strong>   '.utf8_encode2('Totaliza��es Categoria Ingresso: ').'</strong>
								 '.$contCategoriaIngresso.'
								</td>
							</tr>
						';
				$contTipoAssento .= '</tbody></table> <div class="button" onclick="showTipoAssento('.$iTipoAssento.');" >'.$iTipoAssento.'</div>';
				
				$contTipoAssento .= '<input type="hidden" id="countTipoAssento" value="'.$iTipoAssento.'" />';
				$iTipoAssento++;
			}


		}				
		$contSessao .= '</tbody></table>';

	}

	$html .= '<div id="divBilheteria" style="display:block;"><h3 class="title">Bilheteria</h3><br>'.$contBilheteria.'<hr></div>';


	$html .= '<div id="divSessao" style="display:none;"><h3 class="title">'.utf8_encode2('Sess�es').'</h3><br> '.$contSessao.'<hr></div>';
	$html .= '<div id="divObras" style="display:none;"><h3 class="title">Obras</h3> '.$contObra.'<hr></div>';
	$html .= '<div id="divTotalizacao" style="display:none;"><h3 class="title">'.utf8_encode2('Totaliza��es').'</h3> '.$contTipoAssento.'</div>';
	
	

	echo $html;	

}


function getToken($idBase){
 	$mainConnection = mainConnection();

 	$query = "SELECT token 
 				FROM mw_ancine_token 
 			   WHERE ambiente = 'H'
 			     AND id_base = ?";

 	$token = null;		     
 	if($result = executeSQL($mainConnection, $query, array($idBase))){
	 	if(hasRows($result)){
	 		$stmt = fetchResult($result);
		 	$token = $stmt['token'];
		}
	}
 return $token;

}

//Opera��es WebService ANCINE
// $login = '03459043000128';
// $senha = 'cinesanta';

// $token = 'v91rh3c8504eudm1ei87j5d6am53n';


function registrarBilheteriaDeSalaDeExibicao(){

	//extract data from the post
	//set POST variables
	$url = 'https://scbcertificacao.ancine.gov.br/scb/v1.0/bilheterias';


 	$codSala = $_POST['CodSala'];    
 	$id_arquivo = $_POST['idArquivo'];
 	$idBase = $_POST['CodBase'];
 	$dtApresentacao = $_POST['dtApresentacao'];
 	$retificar = $_POST['retificar'];

	$bilheteria = carregarBilheteriaSessoes($codSala,$dtApresentacao,$idBase,$retificar);

	$dataSend = $bilheteria->toJson();


	$token = getToken($idBase);
	if(is_null($token) || $token == ''){
		echo "Nenhuma token associada, por favor realize a associa��o de uma token para integra��o com ANCINE!";
		exit;
	}
		
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => $url,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => $dataSend,
	  CURLOPT_SSL_VERIFYPEER => false,
	  CURLOPT_HTTPHEADER => array(
	    "authorization: $token",
	    "cache-control: no-cache",
	    "content-type: application/json"
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {
			$statusBilheteria = new StatusRelatorioBilheteria();

			$statusBilheteria->jsonToArray($response);

		  	// print_r($statusBilheteria);

		  	

		  	$query = "DECLARE
		  		  		@nr_protoco_ancine VARCHAR(25),
		  				@dt_apresentacao DATE,
		  				@CodSala INT,
		  				@mensagens VARCHAR(MAX),
		  				@statusProtocolo CHAR(1),
		  				@arquivo_enviado VARCHAR(MAX),
		  				@id_arquivo INT;

		  		SET @nr_protoco_ancine = ?;
		  		SET @dt_apresentacao = ?;
		  		SET @CodSala = ?;
		  		SET @mensagens = ?;
		  		SET @statusProtocolo = ?;
		  		SET @arquivo_enviado = ?;
		  		SET @id_arquivo = ?;

		  		UPDATE mw_ancine_arquivos SET nr_protoco_ancine = @nr_protoco_ancine ,
						dt_envio_para_ancine = GETDATE(),
						dt_apresentacao = @dt_apresentacao,
						CodSala = @CodSala,
						mensagens = @mensagens,
						statusProtocolo = @statusProtocolo,
						arquivo_enviado = @arquivo_enviado

				WHERE id_arquivo = @id_arquivo		

			 if @@rowcount = 0
			 BEGIN

		  		INSERT INTO mw_ancine_arquivos (
						nr_protoco_ancine,
						dt_envio_para_ancine,
						dt_apresentacao,
						CodSala,
						mensagens,
						statusProtocolo,
						arquivo_enviado)
						VALUES(@nr_protoco_ancine,GETDATE(),@dt_apresentacao,
						@CodSala,@mensagens,@statusProtocolo,@arquivo_enviado);

		     END";
		
		  $numeroProtocolo = !empty($statusBilheteria->numeroProtocolo) ? $statusBilheteria->numeroProtocolo : '0';
		 $params = array($numeroProtocolo, $statusBilheteria->diaCinematografico, $codSala,
		  json_encode($statusBilheteria->mensagens), $statusBilheteria->statusProtocolo, $dataSend, $id_arquivo); 
	
		$mainConnection = mainConnection();

		if( executeSQL($mainConnection,$query, $params)){
			echo "Salvo com sucesso!";
		}else{
			echo print_r(sqlErrors());
		}
	}


}

function consultarProtocolo(){
	// $token = 'v91rh3c8504eudm1ei87j5d6am53n';

	$codProtocolo = $_POST['CodProtocolo'];
	$idArquivo = $_POST['idArquivo'];
	$idBase = $_POST['CodBase'];
	
	$token = getToken($idBase);
	if(is_null($token) || $token == ''){
		echo utf8_encode2("Nenhuma token associada. Por favor realize a associa��o de uma token para realizar integra��o com a ANCINE!");
		exit;
	}
		

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://scbcertificacao.ancine.gov.br/scb/v1.0/protocolos/".$codProtocolo,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_SSL_VERIFYPEER => false,
	  CURLOPT_HTTPHEADER => array(
	    "authorization: $token",
	    "cache-control: no-cache",
	    "content-type: application/json"
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {
	  		$statusBilheteria = new StatusRelatorioBilheteria();

			$statusBilheteria->jsonToArray($response);

		  	// print_r($statusBilheteria);

		  	

		  	$query = "DECLARE
		  		  		@nr_protoco_ancine VARCHAR(25),
		  				@mensagens VARCHAR(MAX),
		  				@statusProtocolo CHAR(1),
		  				@id_arquivo INT;

		  		SET @nr_protoco_ancine = ?;
		  		SET @mensagens = ?;
		  		SET @statusProtocolo = ?;
		  		SET @id_arquivo = ?;

		  		UPDATE mw_ancine_arquivos SET nr_protoco_ancine = @nr_protoco_ancine ,
						dt_envio_para_ancine = GETDATE(),
						mensagens = @mensagens,
						statusProtocolo = @statusProtocolo
						
				WHERE id_arquivo = @id_arquivo ";
		
		  $numeroProtocolo = !empty($statusBilheteria->numeroProtocolo) ? $statusBilheteria->numeroProtocolo : '0';
		 $params = array($numeroProtocolo, json_encode($statusBilheteria->mensagens), $statusBilheteria->statusProtocolo, $idArquivo); 
	
		$mainConnection = mainConnection();

		if( executeSQL($mainConnection,$query, $params)){
			echo "Consulta realizada com sucesso!";
		}else{
			echo print_r(sqlErrors());
		}
	}

}

//Fim Opera��es WebService ANCINE





if(isset($_REQUEST["Acao"])){
	switch($_REQUEST["Acao"]){
		case "1":
			cadastrarNegociacao();
			break;	
		case "2":
			consultarProtocolo();
			break;
		case "3":
			buscarSalasDiasCinematograficos();
			break;	
		case "4":
			$bilheteria = carregarBilheteriaSessoes();		
			// echo "<pre>";
			echo($bilheteria->toJson());
			break;
		case "5":
			registrarBilheteriaDeSalaDeExibicao();
			break;
		case "6":
			confirmEnviar();
			break;	
		case "7":
			test();
			break;	
	}
}