<?php
final class XMLUtil {

	/**
	 * Faz o parse do XML retornado com o serviço 5 - Consultando opções de pagamento. As chaves colocadas no array $parametros
	 * são apenas sugestões.
	 *
	 * @param $xml
	 * @return array
	 */
	public function parseXmlMeiosPagamento($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);


		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5

		if($apps->opcoes_pagamento == null || !$apps->opcoes_pagamento ){
			return self::parseXmlErro($xml);
		}


		$countOpcao= 1;

		foreach($apps->opcoes_pagamento->opcao_pagamento as $opcao){

			// Meio de pagamento
			$parametros['codigoPagamento'.$countOpcao] = $opcao->codigo;

			$countForma = 1;

			foreach($opcao->formas->forma as $forma){
				// Código da forma de pagamento associada ao meio de pagamento acima.
				$parametros['codigoPagamento'.$countOpcao . '_codigoForma'.$countForma] = $forma->codigo;

				// Valor total do pedido se usar esta forma de pagamento e este meio de pagamento.
				$valorTotal = Util::convertStringToDouble($forma->valor_total);
				$parametros['codigoPagamento'.$countOpcao . '_valorTotal'.$countForma] = $forma->valor_total;

				// Valor de cada parcela do pedido se usar esta forma de pagamento e este meio de pagamento (apenas em formas parceladas).
				$valorParcela = Util::convertStringToDouble($forma->valor_parcela);
				$parametros['codigoPagamento'.$countOpcao . '_valorParcela'.$countForma] = $forma->valor_parcela;

				// Valor do juro se usar esta forma de pagamento e este meio de pagamento (apenas em formas parceladas com juros).
				$valorJuros = Util::convertStringToDouble($forma->juros);
				$parametros['codigoPagamento'.$countOpcao . '_juros'.$countForma] = $forma->juros;


				$countForma = $countForma + 1;
			}

			$countOpcao= $countOpcao + 1;
		}

		return $parametros;
	}

	/**
	 * Faz o parse do XML retornado com o serviço 3 - Consultando pedidos. As chaves colocadas no array são apenas
	 * sugestões.
	 *
	 * @param $xml
	 * @return array
	 */
	public function parseXmlPedido($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5

		if($apps->uid == null || !$apps->uid ){
			return self::parseXmlErro($xml);
		}

		$parametros['uid_pedido'] = $apps->uid;
		$parametros['codigo_pedido'] = $apps->codigo;

		// Valor enviado pelo I-PAGARE convertido para o formato decimal.
		$valorTotal = Util::convertStringToDouble($apps->total);
		$parametros['valor_total'] = $apps->total;

		$parametros['status'] = $apps->status;
		$parametros['data_status'] = $apps->data_status;
		$parametros['hora_status'] = $apps->hora_status;

		// Data da última alteração de status do pedido, convertida das Strings enviados pelo I-PAGARE para o uma data
		$dataHoraStatus = Util::convertStringToDate($apps->data_status, $apps->hora_status);

		//Dados do cliente do pedido
		if($apps->cliente != null && $apps->cliente){
			$parametros['tipo_cliente'] = $apps->cliente->tipo;
			$parametros['codigo_cliente'] = $apps->cliente->codigo; // Código ou chave única do cliente no Site
			$parametros['nome_cliente'] = $apps->cliente->nome;
			$parametros['email_cliente'] = $apps->cliente->email;
			$parametros['cpf_cnpj_cliente'] = $apps->cliente->cpf_cnpj; // CPF ou CNPJ do cliente sem formatação
		}

		// Dados do endereço de cobrança
		if($apps->endereco_cobranca != null && $apps->endereco_cobranca){
			$parametros['logradouro_cobranca'] = $apps->endereco_cobranca->logradouro;
			$parametros['numero_cobranca'] = $apps->endereco_cobranca->numero;
			$parametros['complemento_cobranca'] = $apps->endereco_cobranca->complemento;
			$parametros['bairro_cobranca'] = $apps->endereco_cobranca->bairro;
			$parametros['cep_cobranca'] = $apps->endereco_cobranca->cep;
			$parametros['cidade_cobranca'] = $apps->endereco_cobranca->cidade;
			$parametros['uf_cobranca'] = $apps->endereco_cobranca->uf;
			$parametros['pais_cobranca'] = $apps->endereco_cobranca->pais;
		}

		// Dados do endereço de entrega
		if($apps->endereco_entrega != null && $apps->endereco_entrega){
			$parametros['logradouro_entrega'] = $apps->endereco_entrega->logradouro;
			$parametros['numero_entrega'] = $apps->endereco_entrega->numero;
			$parametros['complemento_entrega'] = $apps->endereco_entrega->complemento;
			$parametros['bairro_entrega'] = $apps->endereco_entrega->bairro;
			$parametros['cep_entrega'] = $apps->endereco_entrega->cep;
			$parametros['cidade_entrega'] = $apps->endereco_entrega->cidade;
			$parametros['uf_entrega'] = $apps->endereco_entrega->uf;
			$parametros['pais_entrega'] = $apps->endereco_entrega->pais;
		}

		//Itens do pedido
		if($apps->itens_pedido != null && $apps->itens_pedido){
			$i = 1;
			foreach($apps->itens_pedido->item_pedido as $item){
				$parametros['codigo_item_'.$i] = $item->codigo;
				$parametros['descricao_item_'.$i] = $item->descricao;

				// Valor enviado pelo I-PAGARE convertido para o formato decimal.
				$quantidadeItem = Util::convertStringToDouble($item->quantidade);
				$parametros['quantidade_item_'.$i] = $item->quantidade;

				// Valor enviado pelo I-PAGARE convertido para o formato decimal.
				$valorItem = Util::convertStringToDouble($item->valor);
				$parametros['valor_item_'.$i] = $item->valor;

				$i = $i + 1;
			}
		}

		//Dados do pagamento do pedido, se existir
		if($apps->pagamento != null && $apps->pagamento){
			$parametros['codigo_pagamento'] = $apps->pagamento->codigo;
			$parametros['forma_pagamento'] = $apps->pagamento->forma;
			$parametros['data_pagamento'] = $apps->pagamento->data;
			$parametros['hora_pagamento'] = $apps->pagamento->hora;

			// Data do pagamento do pedido, convertida das Strings enviados pelo I-PAGARE para o uma data
			$dataHoraPagamento = Util::convertStringToDate($apps->pagamento->data, $apps->pagamento->hora);


			// Valor enviado pelo I-PAGARE convertido para o formato decimal.
			$valorPagamento = Util::convertStringToDouble( $apps->pagamento->total);
			$parametros['total_pagamento'] = $apps->pagamento->total;
			$parametros['capturado_pagamento'] = $apps->pagamento->capturado;

			// Parâmetros adicionais enviados, dependendo do meio de pagamento (apenas Visa, Master e American Express).
			if($apps->pagamento->parametros != null && $apps->pagamento->parametros){

				// Os parâmetros abaixo só são retornados se o pagamento foi realizado usando Visa.
				if($apps->pagamento->parametros->tid_visa != null){
					$parametros['tid_visa'] = $apps->pagamento->parametros->tid_visa;
					$parametros['lr_visa'] = $apps->pagamento->parametros-lr_visa;
					$parametros['arp_visa'] = $apps->pagamento->parametros->arp_visa;
				}

				// Os parâmetros abaixo só são retornados se o pagamento foi realizado usando Mastercard ou Diners.
				if($apps->pagamento->parametros->numautor_redecard != null){
					$parametros['numautor_redecard'] = $apps->pagamento->parametros->numautor_redecard;
					$parametros['numcv_redecard'] = $apps->pagamento->parametros->numcv_redecard;
				}

				// Os parâmetros abaixo só são retornados se o pagamento foi realizado usando American Express.
				if($apps->pagamento->parametros->merchtxnref_amex != null){
					$parametros['merchtxnref_amex'] = $apps->pagamento->parametros->merchtxnref_amex;
					$parametros['receiptno_amex'] = $apps->pagamento->parametros->receiptno_amex;
					$parametros['authorizeid_amex'] = $apps->pagamento->parametros->authorizeid_amex;
				}
			}

			// Apenas para boleto bancário
			if($apps->pagamento->boleto != null && $apps->pagamento->boleto){
				$parametros['valor_boleto'] = $apps->pagamento->boleto->valor;

				// Data do pagamento do pedido, convertida do valor enviado pelo I-PAGARE data.
				$vencimentoBoleto = Util::convertStringToDate($apps->pagamento->boleto->vencimento, null);
				$parametros['vencimento_boleto'] = $apps->pagamento->boleto->vencimento;

				$parametros['pago_boleto'] = $apps->pagamento->boleto->pago;
			}
		}

		return $parametros;
	}

	/**
	 * Faz o parse do XML retornado com o serviço 12 - Cancelando pagamentos por cartão de crédito. As chaves colocadas
	 * no array são apenas sugestões.
	 *
	 * @param $xml
	 * @return array
	 */
	public function parseXmlCancelamentoPagamento($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5

		if($apps->cancelado == null || !$apps->cancelado ){
			return self::parseXmlErro($xml);
		}

		$parametros['codigo_pedido'] = $apps->codigo;  // Código ou chave única do pedido no Site
		$parametros['cancelado'] = $apps->cancelado;  // Valor "1" confirmando o sucesso no cancelamento

		return $parametros;
	}

	/**
	 *  Faz o parse do XML retornado com o serviço 2 - Criação de pedido via integração webservicr. As chaves colocadas
	 * no array são apenas sugestões.
	 * @param $xml
	 * @return array
	 */
	public function parseXmlPedidoWebservices($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5

		if($apps->uid == null || !$apps->uid){
			return self::parseXmlErro($xml);
		}

		if($apps->teste !=null && $apps->teste){
			$parametros['teste'] = $apps->teste;
		}

		$parametros['uid_pedido'] = $apps->uid;
		$parametros['codigo_pedido'] = $apps->codigo;

		// Valor enviado pelo I-PAGARE convertido para o formato decimal.
		$valorTotal = Util::convertStringToDouble($apps->total);
		$parametros['valor_total'] = $apps->total;

		$parametros['status'] = $apps->status;
		$parametros['data_status'] = $apps->data_status;
		$parametros['hora_status'] = $apps->hora_status;

		// Data da última alteração de status do pedido, convertida das Strings enviados pelo I-PAGARE para o uma data
		$dataHoraStatus = Util::convertStringToDate($apps->data_status, $apps->hora_status);


		//Dados do pagamento do pedido, se existir
		if($apps->pagamento != null && $apps->pagamento){
			$parametros['codigo_pagamento'] = $apps->pagamento->codigo;
			$parametros['forma_pagamento'] = $apps->pagamento->forma;
			$parametros['data_pagamento'] = $apps->pagamento->data;
			$parametros['hora_pagamento'] = $apps->pagamento->hora;

			// Data do pagamento do pedido, convertida das Strings enviados pelo I-PAGARE para o uma data
			$dataHoraPagamento = Util::convertStringToDate($apps->pagamento->data, $apps->pagamento->hora);

			// Valor enviado pelo I-PAGARE convertido para o formato decimal.
			$valorPagamento = Util::convertStringToDouble( $apps->pagamento->total);
			$parametros['total_pagamento'] = $apps->pagamento->total;

			$parametros['capturado_pagamento'] = $apps->pagamento->capturado;

			// Parâmetros adicionais enviados, dependendo do meio de pagamento (apenas Visa, Master e American Express).
			if($apps->pagamento->parametros != null && $apps->pagamento->parametros){

				// Os parâmetros abaixo só são retornados se o pagamento foi realizado usando Visa.
				if($apps->pagamento->parametros->tid_visa != null){
					$parametros['tid_visa'] = $apps->pagamento->parametros->tid_visa;
					$parametros['lr_visa'] = $apps->pagamento->parametros-lr_visa;
					$parametros['arp_visa'] = $apps->pagamento->parametros->arp_visa;
				}

				// Os parâmetros abaixo só são retornados se o pagamento foi realizado usando Mastercard ou Diners.
				if($apps->pagamento->parametros->numautor_redecard != null){
					$parametros['numautor_redecard'] = $apps->pagamento->parametros->numautor_redecard;
					$parametros['numcv_redecard'] = $apps->pagamento->parametros->numcv_redecard;
				}

				// Os parâmetros abaixo só são retornados se o pagamento foi realizado usando American Express.
				if($apps->pagamento->parametros->merchtxnref_amex != null){
					$parametros['merchtxnref_amex'] = $apps->pagamento->parametros->merchtxnref_amex;
					$parametros['receiptno_amex'] = $apps->pagamento->parametros->receiptno_amex;
					$parametros['authorizeid_amex'] = $apps->pagamento->parametros->authorizeid_amex;
				}
			}

			// Apenas para boleto bancário
			if($apps->pagamento->boleto != null && $apps->pagamento->boleto){
				$parametros['valor_boleto'] = $apps->pagamento->boleto->valor;

				// Data do pagamento do pedido, convertida do valor enviado pelo I-PAGARE data.
				$vencimentoBoleto = Util::convertStringToDate($apps->pagamento->boleto->vencimento, null);
				$parametros['vencimento_boleto'] = $apps->pagamento->boleto->vencimento;

				$parametros['pago_boleto'] = $apps->pagamento->boleto->pago;
			}
		}

		// TODO: salvar pedido com os dados recebido do I-PAGARE aqui.


		return $parametros;
	}

	/**
	 * Faz o parse do xml de retorno dos serviços 7 - Processar recorrência integração webservice e 10 - Consultando
	 * dados da recorrência. As chaves colocadas no array são apenas sugestões.
	 *
	 * @param $xml
	 * @return array
	 */
	public function parseXmlRecorrencia($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5

		if($apps->ocorrencias == null || !$apps->ocorrencias){
			return self::parseXmlErro($xml);
		}

		if($apps->teste !=null && $apps->teste){
			$parametros['teste'] = $apps->teste;
		}

		$parametros['codigo'] = $apps->codigo;

		// Valor enviado pelo I-PAGARE convertido para o formato decimal.
		$totalRecorrencia = Util::convertStringToDouble( $apps->total);
		$parametros['total'] = $apps->total;

		$i = 1;
		foreach($apps->ocorrencias->ocorrencia as $ocorrencia){
			// Data da última alteração de status do pedido, convertida das Strings enviados pelo I-PAGARE para o uma data
			$dataOcorrencia = Util::convertStringToDate($ocorrencia->data, null);
			$parametros['data_'.$i] = $ocorrencia->data;

			// Valor enviado pelo I-PAGARE convertido para o formato decimal.
			$valorOcorrencia = Util::convertStringToDouble( $ocorrencia->valor);
			$parametros['valor_'.$i] = $ocorrencia->valor;

			if($ocorrencia->pedido !=null && $ocorrencia->pedido){
				$parametros['uid_pedido_'.$i] = ' ' . $ocorrencia->pedido->uid;
				$parametros['codigo_pedido'] = $ocorrencia->pedido->codigo;

				// Valor enviado pelo I-PAGARE convertido para o formato decimal.
				$valorTotal = Util::convertStringToDouble($ocorrencia->pedido->total);
				$parametros['valor_total_'.$i] = $ocorrencia->pedido->total;

				$parametros['status_'.$i] = $ocorrencia->pedido->status;
				$parametros['data_status_'.$i] = $ocorrencia->pedido->data_status;
				$parametros['hora_status_'.$i] = $ocorrencia->pedido->hora_status;

				// Data da última alteração de status do pedido, convertida das Strings enviados pelo I-PAGARE para o uma data
				$dataHoraStatus = Util::convertStringToDate($ocorrencia->pedido->data_status, $ocorrencia->pedido->hora_status);

				//Dados do cliente do pedido
				if($ocorrencia->pedido->cliente != null && $ocorrencia->pedido->cliente){
					$parametros['tipo_cliente_'.$i] = $ocorrencia->pedido->cliente->tipo;
					$parametros['codigo_cliente_'.$i] = $ocorrencia->pedido->cliente->codigo; // Código ou chave única do cliente no Site
					$parametros['nome_cliente_'.$i] = $ocorrencia->pedido->cliente->nome;
					$parametros['email_cliente_'.$i] = $ocorrencia->pedido->cliente->email;
					$parametros['cpf_cnpj_cliente_'.$i] = $ocorrencia->pedido->cliente->cpf_cnpj; // CPF ou CNPJ do cliente sem formatação
				}

				// Dados do endereço de cobrança
				if($ocorrencia->pedido->endereco_cobranca != null && $ocorrencia->pedido->endereco_cobranca){
					$parametros['logradouro_cobranca_'.$i] = $ocorrencia->pedido->endereco_cobranca->logradouro;
					$parametros['numero_cobranca_'.$i] = $ocorrencia->pedido->endereco_cobranca->numero;
					$parametros['complemento_cobranca_'.$i] = $ocorrencia->pedido->endereco_cobranca->complemento;
					$parametros['bairro_cobranca_'.$i] = $ocorrencia->pedido->endereco_cobranca->bairro;
					$parametros['cep_cobranca_'.$i] = $ocorrencia->pedido->endereco_cobranca->cep;
					$parametros['cidade_cobranca_'.$i] = $ocorrencia->pedido->endereco_cobranca->cidade;
					$parametros['uf_cobranca_'.$i] = $ocorrencia->pedido->endereco_cobranca->uf;
					$parametros['pais_cobranca_'.$i] = $ocorrencia->pedido->endereco_cobranca->pais;
				}

				// Dados do endereço de entrega
				if($ocorrencia->pedido->endereco_entrega != null && $ocorrencia->pedido->endereco_entrega){
					$parametros['logradouro_entrega_'.$i] = $ocorrencia->pedido->endereco_entrega->logradouro;
					$parametros['numero_entrega_'.$i] = $ocorrencia->pedido->endereco_entrega->numero;
					$parametros['complemento_entrega_'.$i] = $ocorrencia->pedido->endereco_entrega->complemento;
					$parametros['bairro_entrega_'.$i] = $ocorrencia->pedido->endereco_entrega->bairro;
					$parametros['cep_entrega_'.$i] = $ocorrencia->pedido->endereco_entrega->cep;
					$parametros['cidade_entrega_'.$i] = $ocorrencia->pedido->endereco_entrega->cidade;
					$parametros['uf_entrega_'.$i] = $ocorrencia->pedido->endereco_entrega->uf;
					$parametros['pais_entrega_'.$i] = $ocorrencia->pedido->endereco_entrega->pais;
				}

				//Itens do pedido
				if($ocorrencia->pedido->itens_pedido != null && $ocorrencia->pedido->itens_pedido){
					$j = 1;
					foreach($ocorrencia->pedido->itens_pedido->item_pedido as $item){
						$parametros['codigo_item__'.$i.'_'.$j] = $item->codigo;
						$parametros['descricao_item_'.$i.'_'.$j] = $item->descricao;

						// Valor enviado pelo I-PAGARE convertido para o formato decimal.
						$quantidadeItem = Util::convertStringToDouble($item->quantidade);
						$parametros['quantidade_item_'.$i.'_'.$j] = $item->quantidade;

						// Valor enviado pelo I-PAGARE convertido para o formato decimal.
						$valorItem = Util::convertStringToDouble($item->valor);
						$parametros['valor_item_'.$i.'_'.$j] = $item->valor;

						$j = $j + 1;
					}
				}

				//Dados do pagamento do pedido, se existir
				if($ocorrencia->pedido->pagamento != null && $ocorrencia->pedido->pagamento){
					$parametros['codigo_pagamento_'.$i] = $ocorrencia->pedido->pagamento->codigo;
					$parametros['forma_pagamento'.$i] = $ocorrencia->pedido->pagamento->forma;
					$parametros['data_pagamento'.$i] = $ocorrencia->pedido->pagamento->data;
					$parametros['hora_pagamento'.$i] = $ocorrencia->pedido->pagamento->hora;

					// Data do pagamento do pedido, convertida das Strings enviados pelo I-PAGARE para o uma data
					$dataHoraPagamento = Util::convertStringToDate($ocorrencia->pedido->pagamento->data, $ocorrencia->pedido->pagamento->hora);


					// Valor enviado pelo I-PAGARE convertido para o formato decimal.
					$valorPagamento = Util::convertStringToDouble( $ocorrencia->pedido->pagamento->total);
					$parametros['total_pagamento'.$i] = $ocorrencia->pedido->pagamento->total;
					$parametros['capturado_pagamento'.$i] = $ocorrencia->pedido->pagamento->capturado;

					// Parâmetros adicionais enviados, dependendo do meio de pagamento (apenas Visa, Master e American Express).
					if($ocorrencia->pedido->pagamento->parametros != null && $ocorrencia->pedido->pagamento->parametros){

						// Os parâmetros abaixo só são retornados se o pagamento foi realizado usando Visa.
						if($ocorrencia->pedido->pagamento->parametros->tid_visa != null){
							$parametros['tid_visa'.$i] = $ocorrencia->pedido->pagamento->parametros->tid_visa;
							$parametros['lr_visa'.$i] = $ocorrencia->pedido->pagamento->parametros-lr_visa;
							$parametros['arp_visa'.$i] = $ocorrencia->pedido->pagamento->parametros->arp_visa;
						}

						// Os parâmetros abaixo só são retornados se o pagamento foi realizado usando Mastercard ou Diners.
						if($ocorrencia->pedido->pagamento->parametros->numautor_redecard != null){
							$parametros['numautor_redecard'.$i] = $ocorrencia->pedido->pagamento->parametros->numautor_redecard;
							$parametros['numcv_redecard'.$i] = $ocorrencia->pedido->pagamento->parametros->numcv_redecard;
						}

						// Os parâmetros abaixo só são retornados se o pagamento foi realizado usando American Express.
						if($ocorrencia->pedido->pagamento->parametros->merchtxnref_amex != null){
							$parametros['merchtxnref_amex'.$i] = $ocorrencia->pedido->pagamento->parametros->merchtxnref_amex;
							$parametros['receiptno_amex'.$i] = $ocorrencia->pedido->pagamento->parametros->receiptno_amex;
							$parametros['authorizeid_amex'.$i] = $ocorrencia->pedido->pagamento->parametros->authorizeid_amex;
						}
					}

					// Apenas para boleto bancário
					if($ocorrencia->pedido->pagamento->boleto != null && $ocorrencia->pedido->pagamento->boleto){
						$parametros['valor_boleto'.$i] = $ocorrencia->pedido->pagamento->boleto->valor;

						// Data do pagamento do pedido, convertida do valor enviado pelo I-PAGARE data.
						$vencimentoBoleto = Util::convertStringToDate($ocorrencia->pedido->pagamento->boleto->vencimento, null);
						$parametros['vencimento_boleto'.$i] = $ocorrencia->pedido->pagamento->boleto->vencimento;

						$parametros['pago_boleto'.$i] = $ocorrencia->pedido->pagamento->boleto->pago;
					}
				}
			}
				
			$i = $i+1;
		}


		// TODO: salvar recorrência com os dados recebido do I-PAGARE aqui.

		return $parametros;
	}
	
	/**
	 *  Faz o parse do XML retornado com o serviço 8 - Cancelamento de recorrências. As chaves colocadas no array são
     * apenas sugestões.
     * 
	 * @param $xml
	 * @return array
	 */
	public function parseXmlCancelamentoRecorrencia($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5
		
		if($apps->cancelado == null || !$apps->cancelado ){
			return self::parseXmlErro($xml);
		}

		$parametros['codigo_recorrencia'] = $apps->codigo;  //Código ou chave única da recorrência no Site
		$parametros['cancelado'] = $apps->cancelado;  // Valor "1" confirmando o sucesso no cancelamento
		
		return $parametros;
	}
	
	/**
	 *  Faz o parse do XML retornado com o serviço 9 - Alterando o valor de uma ocorrência e 11 - Alterando dados do
     * cartão de crédito. As chaves colocadas no array são apenas sugestões.
     * 
	 * @param $xml
	 * @return array
	 */
	public function parseXmlAlteracaoRecorrencia($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5
		
		if($apps->alterado == null || !$apps->alterado ){
			return self::parseXmlErro($xml);
		}

		$parametros['codigo_recorrencia'] = $apps->codigo;  //Código ou chave única da recorrência no Site
		$parametros['alterado'] = $apps->alterado;  // Valor "1" confirmando o sucesso na alteração
		
		return $parametros;
	}
	
	/**
	 * Faz o parse do XML retornado com o serviço 13 - Capturando pagamentos por cartão de crédito. As chaves colocadas
	 * no array são apenas sugestões.
	 *
	 * @param $xml
	 * @return array
	 */
	public function parseXmlCapturaPagamento($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5

		if($apps->capturado == null || !$apps->capturado ){
			return self::parseXmlErro($xml);
		}

		$parametros['codigo_pedido'] = $apps->codigo;  // Código ou chave única do pedido no Site
		$parametros['capturado'] = $apps->capturado;  // Valor "1" confirmando o sucesso na captura

		return $parametros;
	}
	
	/**
	 * Faz o parse do XML retornado com o serviço 15 - Armazenando um novo cartão de crédito - Integração webservice. As chaves colocadas
	 * no array são apenas sugestões.
	 *
	 * @param $xml
	 * @return array
	 */
	public function parseXmlArmazenamentoCartaoWebservice($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5

		if($apps->token == null || !$apps->token){
			return self::parseXmlErro($xml);
		}

		$parametros['token'] = $apps->token;  //Token gerado no armazenamento do cartão de crédito.
		$parametros['data_expiracao'] = $apps->data_expiracao;  // Data em que o token será expirado e removido, no formato ddmmaaaa.

		// Data de expiração formatada.
		$dataExpiracao = Util::convertStringToDate($apps->data_expiracao, null);
				
		return $parametros;
	}
	
	/**
	 *Faz o parse do XML retornado com o serviço 16 - Excluindo um cartão de crédito. As chaves colocadas
	 * no array são apenas sugestões.
	 *
	 * @param $xml
	 * @return array
	 */
	public function parseXmlExclusaoCartao($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5

		if($apps->token == null || !$apps->token){
			return self::parseXmlErro($xml);
		}

		$parametros['token'] = $apps->token;  //Token gerado no armazenamento do cartão de crédito.
		$parametros['excluido'] = $apps->excluido;  // Com o valor “1” indicando que o token foi excluído com sucesso.
					
		return $parametros;
	}

	/**
	 * Faz o parse do XML retornado com o serviço 17 - Consultando cartão de crédito armazenado. As chaves colocadas
	 * no array são apenas sugestões.
	 *
	 * @param $xml
	 * @return array
	 */
	public function parseXmlConsultaCartao($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5

		if($apps->numero_cartao == null || !$apps->numero_cartao){
			return self::parseXmlErro($xml);
		}

		$parametros['numero_cartao'] = $apps->numero_cartao;  // Número do cartão de crédito mascarado.
					
		return $parametros;
	}
	
	/**
	 * Faz o parse do XML retornado com o serviço 18 - Renovando token de cartão de crédito. As chaves colocadas
	 * no array são apenas sugestões.
	 *
	 * @param $xml
	 * @return array
	 */
	public function parseXmlRenovacaoToken($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5

		if($apps->token == null || !$apps->token){
			return self::parseXmlErro($xml);
		}

		$parametros['token'] = $apps->token;  //Token gerado no armazenamento do cartão de crédito.
		$parametros['data_expiracao'] = $apps->data_expiracao;  // Data em que o token será expirado e removido, no formato ddmmaaaa.

		// Data de expiração formatada.
		$dataExpiracao = Util::convertStringToDate($apps->data_expiracao, null);
					
		return $parametros;
	}
	
	/**
	 * Faz o parse do XML retornado com o serviço 14 - Armazenar token, integração HTML passo 1. As chaves colocadas
	 * no array são apenas sugestões.
	 *
	 * @param $xml
	 * @return array
	 */
	public function parseXmlArmazenarTokenPasso1($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5

		if($apps->token == null || !$apps->token){
			return self::parseXmlErro($xml);
		}

		$parametros['token'] = $apps->token;  //Token gerado no armazenamento do cartão de crédito.
		$parametros['data_expiracao'] = $apps->data_expiracao;  // Data em que o token será expirado e removido, no formato ddmmaaaa.
		$parametros['hora_expiracao'] = $apps->hora_expiracao;  // Hora em que o token será expirado e removido, no formato ddmmaaaa.

		// Data de expiração formatada.
		$dataHoraExpiracaoToken = Util::convertStringToDate($apps->data_expiracao, $apps->hora_expiracao);
				
		return $parametros;
	}

	private function parseXmlErro($xml){
		$parametros =  array();

		$xml = self::limpaXML($xml);

		$apps = simplexml_load_string($xml); // Realiza o parsing do XML utilizando o simplexml do php 5

		$parametros['codigo_erro'] = $apps->codigo;
		$parametros['descricao_erro'] = $apps->descricao;

		if($apps->parametros !=null && $apps->parametros ){
			$i = 1;
			foreach($apps->parametros->parametro as $parametro){
				$parametros['parametro_'.$i] = $parametro;
				$i = $i+1;
			}
		}

		if($apps->tentativa_pagamento !=null && $apps->tentativa_pagamento){
			$parametros['uid-pedido'] = $apps->tentativa_pagamento->uid_pedido;
			$parametros['codigo-pedido'] = $apps->tentativa_pagamento->codigo_pedido;
			$parametros['uid-financeira'] = $apps->tentativa_pagamento->codigo_financeira;
			$parametros['mensagem-financeira'] = $apps->tentativa_pagamento->mensagem_financeira;
		}


		return $parametros;
	}

	private function limpaXML($xml){
		$xmlHeader = substr($xml,0,44);
		$xmlAux = substr($xml,44);
		$xmlAux = str_ireplace("-", "_", $xmlAux);
		$xml = $xmlHeader . $xmlAux;

		return $xml;
	}
}
?>