<?php
require "Util.php";
//Este arquivo processa o retorno do I-PAGARE na notifica��o de altera��o de status de um pedido.
//Para tanto, deve ser configurado o campo URL DE NOTIFICACAO atrav�s do Painel de Controle, menu "Configura��es > Integra��o"

//Os par�metros abaixo s�o sempre enviados pelo I-PAGARE, n�o importando qual o status do pedido. 
	$uidPedido = $_REQUEST['uid_pedido'];
	$codigoPedido = $_REQUEST['codigo_pedido']; //Enviado apenas se o estabelecimento informou o c�digo do pedido na cria��o.
	$codigoStatus = $_REQUEST['codigo_status'];
	$dataStatus = $_REQUEST['data_status'];
	$horaStatus = $_REQUEST['hora_status'];
	
	//Os par�metros abaixo s� s�o enviados caso o status do pedido seja alterado para "Aguardando confirma��o de pagamento" ou "Pagamento confirmado".
	$codigoPagamento = $_REQUEST['codigo_pagamento']; //Meio de pagamento usado para o pagamento do pedido - ver Guia de Integra��o B�sica para identificar o meio de pagamento.
	$formaPagamento = $_REQUEST['forma_pagamento'];  // Forma de pagamento usada para o pagamento do pedido - ver Guia de Integra��o B�sica para identificar a forma de pagamento..
	$valorPagamento = $_REQUEST['valor_pagamento']; //Valor total pago pelo cliente.
	
	//Par�metros vers�o 1 da notifica��o
	//Dependendo do meio de pagamento utilizado, os par�metros abaixo tamb�m s�o retornados pelo I-PAGARE.
	
	//Visa e Visa Electron
	$tid_visa = $_REQUEST['tid_visa'];
	$lr_visa = $_REQUEST['lr_visa'];
	$arp_visa = $_REQUEST['arp_visa'];
	
	//MasterCard e Diners
	$numautor_redecard = $_REQUEST['numautor_redecard'];
	$numcv_redecard = $_REQUEST['numcv_redecard'];
	
	//American Express
	$merchtxnref_amex = $_REQUEST['merchtxnref_amex'];
	$receiptno_amex = $_REQUEST['receiptno_amex'];
	
	//Boletos banc�rios
	$vencimento_boleto = $_REQUEST['vencimento_boleto'];
	
	//Par�metros vers�o 2 da notifica��o
	//Dependendo do meio de pagamento utilizado, os par�metros abaixo tamb�m s�o retornados pelo I-PAGARE.
	$data_pagamento = $_REQUEST['data_pagamento'];
	$hora_pagamento = $_REQUEST['hora_pagamento'];
	$capturado = $_REQUEST['capturado'];
	$numero_autorizacao = $_REQUEST['numero_autorizacao'];
	$numero_transacao = $_REQUEST['numero_transacao'];
	$numero_cv = $_REQUEST['numero_cv'];
	$numero_cartao = $_REQUEST['numero_cartao'];
	$nacionalidade_emissor = $_REQUEST['nacionalidade_emissor'];
	$codigo_retorno = $_REQUEST['codigo_retorno'];
	$nosso_numero = $_REQUEST['nosso_numero'];
	$nsu_origem_banrisul = $_REQUEST['nsu_origem_banrisul'];
	$nsu_movimento_banrisul = $_REQUEST['nsu_movimento_banrisul'];
	
	//Para garantir que os par�metros enviados foram enviados pelo I-PAGARE, � sempre enviada a seguinte chave de autentica��o.
	$chave = $_REQUEST['chave'];

	//Para verificar se a chave est� correta, gerar a hash dos par�metros recebidos (menos a chave), usando o algoritmo MD5, da seguinte maneira:
	// chave = MD5(MD5(codigo_seguranca) + parametro1 + parametro2 + ... + parametron)
	// Usar a mesma ordem de par�metros usada no Guia de Integra��o B�sica.
	
	$parametros = $uidPedido;
	
	if(!Util::isEmpty($codigoPedido)){
		$parametros = $parametros . $codigoPedido;
	}
	
	$parametros = $parametros . $codigoStatus;
	$parametros = $parametros . $dataStatus;
	$parametros = $parametros . $horaStatus;
	
	if (!Util::isEmpty($codigoPagamento)){
	    $parametros = $parametros . $codigoPagamento;
	}
	if (!Util::isEmpty($formaPagamento)){
	    $parametros = $parametros . $formaPagamento;
	}
	if (!Util::isEmpty($valorPagamento)){
	    $parametros = $parametros . $valorPagamento;
	}
	if (!Util::isEmpty($tid_visa)){
	    $parametros = $parametros . $tid_visa;
	}
	if (!Util::isEmpty($lr_visa)){
	    $parametros = $parametros . $lr_visa;
	}
	if (!Util::isEmpty($arp_visa)){
	    $parametros = $parametros . $arp_visa;
	}
	if (!Util::isEmpty($numautor_redecard)){
	    $parametros = $parametros . $numautor_redecard;
	}
	if (!Util::isEmpty($numcv_redecard)){
	    $parametros = $parametros . $numcv_redecard;
	}
	if (!Util::isEmpty($merchtxnref_amex)){
	    $parametros = $parametros . $merchtxnref_amex;
	}
	if (!Util::isEmpty($receiptno_amex)){
	    $parametros = $parametros . $receiptno_amex;
	}
	
	//Par�metros vers�o 2 da notifica��o
	if (!Util::isEmpty($data_pagamento)) {
	  	$parametros = $parametros . $data_pagamento;
	}
	if (!Util::isEmpty($hora_pagamento)) {
		$parametros = $parametros . $hora_pagamento;
	}
	if (!Util::isEmpty($capturado)) {
		$parametros = $parametros . $capturado;
	}
	if (!Util::isEmpty($numero_autorizacao)) {
		$parametros = $parametros . $numero_autorizacao;
	}
	if (!Util::isEmpty($numero_transacao)) {
	    $parametros = $parametros . $numero_transacao;
	}
	if (!Util::isEmpty($numero_cv)) {
	    $parametros = $parametros . $numero_cv;
	}
	if (!Util::isEmpty($numero_cartao)) {
	   $parametros = $parametros . $numero_cartao;
	}
	if (!Util::isEmpty($nacionalidade_emissor)) {
	   $parametros = $parametros . $nacionalidade_emissor;
	}
	if (!Util::isEmpty($codigo_retorno)) {
	   $parametros = $parametros . $codigo_retorno;
	}
	if (!Util::isEmpty($nosso_numero)) {
	   $parametros = $parametros . $nosso_numero;
	}
	if (!Util::isEmpty($vencimento_boleto)){
		//Aten��o: este par�metro tamb�m � enviado na vers�o 1 da notifica��o. Ele foi colocado aqui para manter a ordena��o dos par�metros na gera��o da chave.
		$parametros = $parametros . $vencimento_boleto;
	}
	if (!Util::isEmpty($nsu_origem_banrisul)) {
	   $parametros = $parametros . $nsu_origem_banrisul;
	}
	if (!Util::isEmpty($nsu_movimento_banrisul)) {
	   $parametros = $parametros . $nsu_movimento_banrisul;
	}
	
	//Busca dados do estabelecimento.
        $result = executeSQL($mainConnection, 'SELECT CD_ESTABELECIMENTO, CD_SEGURANCA, IN_ATIVO FROM MW_CONTA_IPAGARE WHERE IN_ATIVO = 1');
        while ($rsResult = fetchResult($result)) {
              $codigoEstabelecimento = $rsResult['CD_ESTABELECIMENTO'];
              $codigoSeguranca = trim(md5($rsResult['CD_SEGURANCA'])); //Busca o código do segurança e gera a hash com o algoritmo MD5.
        }
        
	$chaveVerificacao = md5($codigoSeguranca . $parametros);
	
	if($chave === $chaveVerificacao){
		$validado = true;
		 //Dados foram autenticados com sucesso. Proceder com o processamento do pedido.
	/*
		$dataAlteracaoStatus= Util::convertStringToDate($dataStatus,$horaStatus);
		
		if("1" == $codigoStatus){
			 //Status do pedido � "Aguardando pagamento", pedido apenas foi criado no I-PAGARE, ainda n�o foi pago.
		}else if("2" == $codigoStatus || "3" == $codigoStatus){
			//Status do pedido � "Aguardando confirma��o de pagamento" (2) ou "Pagamento confirmado" (3)
			
			//Double valorPago = Util.convertStringToDouble(valorPagamento);
			$valorPago = Util::convertStringToDouble($valorPagamento);
			
			//Processar os outros par�metros aqui.
			if("7" == $codigoPagamento || "8" == $codigoPagamento || "27" == $codigoPagamento){
				 //Pagamento foi feito com Visa. Considerar os par�metros tid_visa, lr_visa e arp_visa.
				
			} else if("1" == $codigoPagamento || "2" == $codigoPagamento || "25" == $codigoPagamento || "26" == $codigoPagamento){
	    	  //Pagamento foi feito com MasterCard e Diners. Considerar os par�metros numautor_redecard e numcv_redecard.

		    	  
	    	}else if("11" == $codigoPagamento || "28" == $codigoPagamento){
	    	  //Pagamento foi feito com American Express. Considerar os par�metros merchtxnref_amex e receiptno_amex. 

		    	  
	    	}else if (!Util::isEmpty($vencimento_boleto)){
	    	  //Pagamento foi feito com boleto banc�rio. Considerar o par�metro vencimento_boleto. 
	    		$dataVencimentoBoleto= Util::convertStringToDate($vencimento_boleto);
	    	}
	    	
			if("3" == $codigoStatus){
			    //salvar no pedido que ele j� foi pago.
			}
		}else if("4" == $codigoStatus){
	        //Status do pedido � "Cancelado". N�o � mais poss�vel processar pagamentos para este pedido.
	        
	    }else if("5" == $codigoStatus){
	      //Status do pedido � "Pagamento expirado". N�o � mais poss�vel processar pagamentos para este pedido. 
	      // Status usado quando a data de vencimento de um boleto j� passou.
	      
	    }
	    
	     //Salvar altera��es no pedido aqui.
	//*/
	}else{
		$validado = false;
		//Falha na autentica��o.
	}
	
	//ATEN��O: sempre retornar apenas string "OK", qualquer que seja o status.

//ATEN��O: sempre retornar apenas string "OK", qualquer que seja o status.
//A impress�o do "OK", e SOMENTE do "OK" � obrigat�ria para confirmar ao i-PAGARE que a notifica��o foi processada,
//caso contr�rio o sistema tentar� enviar a notifica��o novamente por mais 5x
//caso n�o obtenha sucesso, ser� enviada uma notifica��o por e-mail
?>