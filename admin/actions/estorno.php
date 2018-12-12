<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 250, true)) {

     if ($_GET['action'] == 'load_evento_combo') {

        $queryEvento = 'SELECT E.ID_EVENTO, E.DS_EVENTO FROM MW_EVENTO E WHERE IN_ATIVO = 1 ORDER BY DS_EVENTO ASC';
        $resultEventos = executeSQL($mainConnection, $queryEvento, null);
        
        $options = '<option value="">Selecione um evento...</option>';
        while ($rs = fetchResult($resultEventos)) {
            $options .= '<option value="' . $rs['ID_EVENTO'] . '"' .
                    (($_GET["nm_evento"] == $rs['ID_EVENTO']) ? ' selected' : '' ) .
                    '>' . utf8_encode2($rs['DS_EVENTO']) . '</option>';
        }

        $retorno = $options;

    } else if ($_POST['pedido'] != '' and isset($_POST['pedido'])) {

        $_POST['justificativa'] = substr(utf8_encode2($_POST['justificativa']), 0, 250);

        //RequestID
        $ri = md5(time());
        $ri = substr($ri, 0, 8) . '-' . substr($ri, 8, 4) . '-' . substr($ri, 12, 4) . '-' . substr($ri, 16, 4) . '-' . substr($ri, -12);

        // checa se o pedido é um filho de assinatura
        $query = "SELECT DISTINCT
                        CONVERT(VARCHAR(23), P.DT_PEDIDO_VENDA, 126) DATA,
                        P.VL_TOTAL_PEDIDO_VENDA VALOR,
                        P.ID_TRANSACTION_BRASPAG BRASPAG_ID,
                        P.ID_CLIENTE,
                        M.IN_TRANSACAO_PDV,
                        P.IN_PACOTE,
                        CASE WHEN P.ID_PEDIDO_PAI IS NOT NULL THEN 1 ELSE 0 END FILHO,
                        P.ID_PEDIDO_VENDA,
                        P.ID_PEDIDO_PAI,
                        (SELECT COUNT(1) FROM MW_PROMOCAO PROMO WHERE PROMO.ID_PEDIDO_VENDA = P.ID_PEDIDO_VENDA) AS INGRESSOS_PROMOCIONAIS,
                        P.ID_PEDIDO_IPAGARE
                FROM MW_PEDIDO_VENDA P
                INNER JOIN MW_MEIO_PAGAMENTO M ON M.ID_MEIO_PAGAMENTO = P.ID_MEIO_PAGAMENTO
                INNER JOIN MW_ITEM_PEDIDO_VENDA I ON P.ID_PEDIDO_VENDA = I.ID_PEDIDO_VENDA
                WHERE P.IN_SITUACAO = 'F' AND P.ID_PEDIDO_VENDA = ?";
        $result = executeSQL($mainConnection, $query, array($_POST['pedido']));
        $pedido_principal = fetchResult($result, SQLSRV_FETCH_ASSOC);

        if ($pedido_principal['ID_PEDIDO_VENDA'] == null) {
            echo "Pedido inexistente ou já estornado.";
            die();
        }

        if ($pedido_principal["FILHO"]) {
            echo "Este pedido pertence à uma assinatura.<br /> Não é possível o estorno individualmente.<br /><br /> Caso queira estornar este pedido, efetue o estorno utilizando o pedido principal da assinatura: ".$pedido_principal["ID_PEDIDO_PAI"].".<br /><br /> <b>Atenção</b>: efetuando o estorno do pedido principal todos os lugares e todas as apresentações serão estornados.";
            die();
        }

        if ($pedido_principal["BRASPAG_ID"] == 'POS' and !isset($_POST['pos_serial'])) {
            echo "Este pedido foi feito em um POS.<br /> Não é possível o estorno por esse meio.<br /><br /> Caso queira estornar este pedido, efetue o estorno utilizando um POS.";
            die();
        } elseif (isset($_POST['pos_serial']) and $pedido_principal["BRASPAG_ID"] != 'POS') {
            echo "Este pedido não foi feito em um POS.<br /> Não é possível o estorno por esse meio.<br /><br /> Caso queira estornar este pedido, efetue o estorno utilizando o sistema administrativo pelo site.";
            die();
        } else if ($pedido_principal["BRASPAG_ID"] == 'POS' and isset($_POST['pos_serial'])) {
            $retorno = 'ok';
            $is_estorno_brasbag = false;
        }

        // checa se alguma apresentacao do pedido já ocorreu
        $query = "SELECT TOP 1 1
                FROM MW_PEDIDO_VENDA P
                INNER JOIN MW_ITEM_PEDIDO_VENDA I ON P.ID_PEDIDO_VENDA = I.ID_PEDIDO_VENDA
                INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
                WHERE P.IN_SITUACAO = 'F'
                AND CONVERT(DATETIME, CONVERT(VARCHAR(10), A.DT_APRESENTACAO, 120) + ' ' + REPLACE(A.HR_APRESENTACAO, 'H', ':')) <= GETDATE()
                AND P.ID_PEDIDO_VENDA = ?";
        $pedido_ocorreu = executeSQL($mainConnection, $query, array($_POST['pedido']), true);

        if ($pedido_ocorreu[0]) {
            echo "Este pedido contém pelo menos uma apresentação que já ocorreu.<br /><br /> Não é possível o estorno.";
            die();
        }

        if ($pedido_principal['IN_PACOTE'] == 'S') {
            $pedidos = array($pedido_principal);

            $query = "SELECT DISTINCT
                                CONVERT(VARCHAR(23), P.DT_PEDIDO_VENDA, 126) DATA,
                                P.VL_TOTAL_PEDIDO_VENDA VALOR,
                                P.ID_TRANSACTION_BRASPAG BRASPAG_ID,
                                P.ID_CLIENTE,
                                M.IN_TRANSACAO_PDV,
                                P.IN_PACOTE,
                                CASE WHEN P.ID_PEDIDO_PAI IS NOT NULL THEN 1 ELSE 0 END FILHO,
                                P.ID_PEDIDO_VENDA,
                                (SELECT COUNT(1) FROM MW_PROMOCAO PROMO WHERE PROMO.ID_PEDIDO_VENDA = P.ID_PEDIDO_VENDA) AS INGRESSOS_PROMOCIONAIS,
                                P.ID_PEDIDO_IPAGARE
                        FROM MW_PEDIDO_VENDA P
                        INNER JOIN MW_MEIO_PAGAMENTO M ON M.ID_MEIO_PAGAMENTO = P.ID_MEIO_PAGAMENTO
                        INNER JOIN MW_ITEM_PEDIDO_VENDA I ON P.ID_PEDIDO_VENDA = I.ID_PEDIDO_VENDA
                        WHERE P.IN_SITUACAO = 'F' AND P.ID_PEDIDO_PAI = ?";
            $result = executeSQL($mainConnection, $query, array($_POST['pedido']));

            while ($rs = fetchResult($result, SQLSRV_FETCH_ASSOC)) $pedidos[] = $rs;
            
        } else {
            $pedidos = array($pedido_principal);
        }

        foreach ($pedidos as $pedido) {

            $parametros['RequestId'] = $ri;
            $parametros['Version'] = '1.0';
            $parametros['TransactionDataCollection']['TransactionDataRequest']['BraspagTransactionId'] = $pedido['BRASPAG_ID'];
            $parametros['TransactionDataCollection']['TransactionDataRequest']['Amount'] = 0; //$pedido['VALOR'];

            $is_cancelamento = date('d', strtotime($pedido['DATA'])) == date('d');

            // VENDAS PELO PDV, PEDIDOS FILHOS (DE ASSINATURAS), PEDIDOS COM INGRESSOS PROMOCIONAIS, VALOR 0 E FEITOS PELO POS NÃO SÃO ESTORNADAS DO BRASPAG
            $is_estorno_brasbag = ($pedido["IN_TRANSACAO_PDV"] == 0 and !$pedido["FILHO"] and ($pedido['INGRESSOS_PROMOCIONAIS'] == 0 and $pedido['VALOR'] != 0)
                                    and !($pedido_principal["BRASPAG_ID"] == 'POS' and isset($_POST['pos_serial'])) and $pedido_principal["BRASPAG_ID"] != 'Fastcash'
                                    and $pedido_principal["ID_PEDIDO_IPAGARE"] != 'PagSeguro' and $pedido_principal["ID_PEDIDO_IPAGARE"] != 'Pagar.me' and $pedido_principal["ID_PEDIDO_IPAGARE"] != 'Paypal'
                                    and $pedido_principal["ID_PEDIDO_IPAGARE"] != 'Cielo' and $pedido_principal["ID_PEDIDO_IPAGARE"] != 'TiPagos');

            $options = array(
                'local_cert' => file_get_contents('../settings/cert.pem'),
                //'passphrase' => file_get_contents('cert.key'),
                //'authentication' => SOAP_AUTHENTICATION_BASIC || SOAP_AUTHENTICATION_DIGEST

                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE
            );

            // tratamento para braspag
            if ($is_estorno_brasbag) {
                // $where = $is_teste == '1' ? ' WHERE IN_ATIVO = 1' : '';
                $result_gateway_pagamento = executeSQL($mainConnection, 'SELECT ID_GATEWAY_PAGAMENTO, DS_GATEWAY_PAGAMENTO, CD_GATEWAY_PAGAMENTO, DS_URL FROM MW_GATEWAY_PAGAMENTO'.$where);

                $conta = array();

                while ($rs_gateway_pagamento = fetchResult($result_gateway_pagamento)) {

                    $url_braspag = $rs_gateway_pagamento['DS_URL'];
                    $parametros['MerchantId'] = $rs_gateway_pagamento['CD_GATEWAY_PAGAMENTO'];
                    $conta[$rs_gateway_pagamento['ID_GATEWAY_PAGAMENTO']]['descricao'] = $rs_gateway_pagamento['DS_GATEWAY_PAGAMENTO'];

                    try {
                        $client = @new SoapClient($url_braspag, $options);

                        if ($is_cancelamento) {
                            $result = $client->VoidCreditCardTransaction(array('request' => $parametros));
                            $conta[$rs_gateway_pagamento['ID_GATEWAY_PAGAMENTO']]['response'] = $result->VoidCreditCardTransactionResult;

                        } else {
                            $result = $client->RefundCreditCardTransaction(array('request' => $parametros));
                            $conta[$rs_gateway_pagamento['ID_GATEWAY_PAGAMENTO']]['response'] = $result->RefundCreditCardTransactionResult;

                        }
                    } catch (SoapFault $e) {
                        $conta[$rs_gateway_pagamento['ID_GATEWAY_PAGAMENTO']]['descricao_erro'] = $e->getMessage();
                    }
                }

                $resposta_geral = '';
                foreach ($conta as $value) {

                    if ($value['response']->CorrelationId != $ri) {
                        $request_valido = false;
                        break;
                    } else {
                        $request_valido = valido;
                    }

                    if ($value['response']->TransactionDataCollection->TransactionDataResponse->Status == '0') {
                        $value['descricao_erro'] = "Pedido cancelado/estornado.";
                        $retorno = 'ok';
                        break;
                    }

                    // se o status for 3 fazer o estorno e avisar para efetuar o monitoramento manual pela braspag
                    /*
                    The status "3" is exclusive to Redecard transactions.
                    The reversal is processed by Redecard overnight following the Refund request.To request a Refund be
                    processed in the early hours you must submit the same before 18:00.The return is D +1 for requests
                    received by 18:00 or D +2 for requests received after 18:00.
                    After returning from Redecard the transaction can be marked as reversed in the case of Redecard accept
                    the request for cancelation, or continue to pay if REDECARD deny Reversal. 
                    */
                    elseif ($value['response']->TransactionDataCollection->TransactionDataResponse->Status == '3') {
                        $force_system_refund = true;
                        $value['descricao_erro'] = "A solicitação de estorno foi solicitada para o meio de pagamento, porém
                                                     não há confirmação que o mesmo será efetuado, sendo necessário o
                                                     acompanhamento nos próximos dias, diretamente no site do meio de
                                                     pagamento, com o intuito de certificar que o cancelamento ocorreu com
                                                     sucesso.<br/><br/>
                                                     O pedido no sistema foi cancelado com sucesso.";

                    }

                    // se der a msg de erro "Refund is not enabled for this merchant" fazer o estorno pelo sistema do mesmo jeito
                    elseif ($value['response']->ErrorReportDataCollection->ErrorReportDataResponse->ErrorCode === "139") {
                        $force_system_refund = true;
                        $value['descricao_erro'] = "<b>Não foi possível efetuar o estorno junto à Operadora (Braspag)</b>, 
                                                    por favor, efetue o procedimento de cancelamento junto a operadora manualmente.<br/><br/>
                                                    Os dados do sistema do Middleway foram atualizados com sucesso.";

                    } elseif ($value['response']->TransactionDataCollection->TransactionDataResponse->Status == '2') {
                        $value['descricao_erro'] = "Pedido inexistente ou já cancelado/estornado.";
                    }

                    $value['descricao'] = utf8_encode2($value['descricao']);

                    $resposta_geral .= "<b>{$value['descricao']}</b>:<br/>
                                            {$value['response']->ErrorReportDataCollection->ErrorReportDataResponse->ErrorMessage}<br/>
                                            {$value['response']->TransactionDataCollection->TransactionDataResponse->ReturnMessage}<br/>
                                            {$value['descricao_erro']}<br/><br/>";

                    $erros_resposta_braspag['count'] += count(get_object_vars($value['response']->ErrorReportDataCollection));
                    $erros_resposta_braspag['descr'] .= $value['response']->ErrorReportDataCollection->ErrorReportDataResponse->ErrorMessage;
                }
            }

            // tratamento para pagseguro
            elseif ($pedido_principal["ID_PEDIDO_IPAGARE"] == 'PagSeguro') {

                require_once('../settings/pagseguro_functions.php');

                $query = "SELECT OBJ_PAGSEGURO FROM MW_PEDIDO_PAGSEGURO WHERE ID_PEDIDO_VENDA = ? ORDER BY DT_STATUS DESC";
                $params = array($pedido['ID_PEDIDO_VENDA']);
                $rs2 = executeSQL($mainConnection, $query, $params, true);

                $transaction =  unserialize(base64_decode($rs2['OBJ_PAGSEGURO']));

                $response = estonarPedidoPagseguro($transaction->getCode());

                if ($response['success']) {
                    $resposta_geral = "Pedido cancelado/estornado.";
                    $retorno = 'ok';
                } else {
                    $resposta_geral = $response['error']."<br/><br/><b>Não foi possível efetuar o estorno junto à Operadora (PagSeguro)</b>, 
                                                por favor, efetue o procedimento de cancelamento junto a operadora manualmente.<br/><br/>
                                                Os dados do sistema do Middleway foram atualizados com sucesso.";
                    $force_system_refund = true;
                }
            }

            // tratamento para pagarme
            elseif ($pedido_principal["ID_PEDIDO_IPAGARE"] == 'Pagar.me') {

                require_once('../settings/pagarme_functions.php');

                if (!empty($_POST['banco'])) {
                    $bank_data = array(
                        'bank_account' => array(
                            'bank_code' => $_POST['banco'],
                            'agencia' => $_POST['nr_agencia'],
                            'agencia_dv' => $_POST['dv_agencia'],
                            'conta' => $_POST['nr_conta'],
                            'conta_dv' => $_POST['dv_conta'],
                            'document_number' => $_POST['cpf'],
                            'legal_name' => utf8_encode2($_POST['nome'])
                        )
                    );
                }

                $response = estonarPedidoPagarme($pedido['ID_PEDIDO_VENDA'], $bank_data);

                if ($response['success']) {
                    $resposta_geral = "Pedido cancelado/estornado.";
                    $retorno = 'ok';
                } elseif (empty($_POST['banco'])) {
                    $resposta_geral = $response['error']."<br/><br/><b>Não foi possível efetuar o estorno junto à Operadora (Pagar.me)</b>, 
                                                por favor, efetue o procedimento de cancelamento junto a operadora manualmente.<br/><br/>
                                                Os dados do sistema do Middleway foram atualizados com sucesso.";
                    $force_system_refund = true;
                } else {
                    echo $response['error'];
                    die();
                }
            }

            elseif ($pedido_principal["ID_PEDIDO_IPAGARE"] == 'Paypal') {

                require_once('../settings/paypal_functions.php');

                $response = paypalRefund($pedido['ID_PEDIDO_VENDA']);

                if ($response['success']) {
                    $resposta_geral = "Pedido cancelado/estornado.";
                    $retorno = 'ok';
                } else {
                    echo $response['error'];
                    die();
                }
            }

            // tratamento para Tipagos
            elseif ($pedido_principal["ID_PEDIDO_IPAGARE"] == 'TiPagos') {
                require_once('../settings/tipagos_functions.php');

                $response = estonarPedidoTiPagos($pedido['ID_PEDIDO_VENDA']);

                if ($response['success']) {
                    $resposta_geral = "Pedido cancelado/estornado.";
                    $retorno = 'ok';
                }  else {
                    $resposta_geral = $response['error']."<br/><br/><b>Não foi possível efetuar o estorno junto à Operadora (TiPagos)</b>, 
                                                por favor, efetue o procedimento de cancelamento junto a operadora manualmente.<br/><br/>
                                                Os dados do sistema do Middleway foram atualizados com sucesso.";
                    $force_system_refund = true;
                }
            }

            // tratamento para cielo
            elseif ($pedido_principal["ID_PEDIDO_IPAGARE"] == 'Cielo') {

                require_once('../settings/cielo_functions.php');

                $response = estonarPedidoCielo($pedido['BRASPAG_ID'], $pedido['ID_PEDIDO_VENDA']);

                if ($response['success']) {
                    $resposta_geral = "Pedido cancelado/estornado.";
                    $retorno = 'ok';
                } else {
                    $resposta_geral = $response['error']."<br/><br/><b>Não foi possível efetuar o estorno junto à Operadora (Cielo)</b>, 
                                                por favor, efetue o procedimento de cancelamento junto a operadora manualmente.<br/><br/>
                                                Os dados do sistema do Middleway foram atualizados com sucesso.";
                    $force_system_refund = true;
                }
            }

            // tratamento para outros meios de pagamento
            else {
                if ($pedido_principal["BRASPAG_ID"] == 'Fastcash') {
                    $force_system_refund = true;
                    $value['descricao_erro'] = "<b>Não foi possível efetuar o estorno junto à Operadora (Fastcash)</b>, 
                                                por favor, efetue o procedimento de cancelamento junto a operadora manualmente.<br/><br/>
                                                Os dados do sistema do Middleway foram atualizados com sucesso.";
                }

                $resposta_geral .= "{$value['descricao_erro']}<br/><br/>";
            }


            if (($request_valido) OR (!$is_estorno_brasbag) OR $force_system_refund) {

                if (($retorno = 'ok') OR (!$is_estorno_brasbag) OR $force_system_refund) {

                    //lista de eventos e codvenda
                    $query1 = "SELECT DISTINCT E.DS_EVENTO, A.CODAPRESENTACAO, B.ID_BASE, B.DS_NOME_BASE_SQL, I.CODVENDA
                            FROM MW_BASE B
                            INNER JOIN MW_EVENTO E ON E.ID_BASE = B.ID_BASE
                            INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
                            INNER JOIN MW_ITEM_PEDIDO_VENDA I ON I.ID_APRESENTACAO = A.ID_APRESENTACAO
                            INNER JOIN MW_PEDIDO_VENDA P ON P.ID_PEDIDO_VENDA = I.ID_PEDIDO_VENDA
                            WHERE P.ID_CLIENTE = ? AND P.ID_PEDIDO_VENDA = ? AND P.IN_SITUACAO = 'F'";
                    $params1 = array($pedido['ID_CLIENTE'], $pedido['ID_PEDIDO_VENDA']);
                    $bases = executeSQL($mainConnection, $query1, $params1);

                    //para cada evento/codvenda
                    while ($rs = fetchResult($bases)) {

                        // echo "lista de bases, eventos, codapresentacao e codvenda: \n"; print_r(array($query1, $params1)); echo "\n"; print_r($rs); echo "\n\n";
                        //lista todos os indices de um codvenda/codapresentacao
                        $query2 = "SELECT S.INDICE, L1.CODCAIXA, L1.DATMOVIMENTO, L1.CODMOVIMENTO
                                FROM " . strtoupper($rs['DS_NOME_BASE_SQL']) . "..tabLugSala S
                                        INNER JOIN " . strtoupper($rs['DS_NOME_BASE_SQL']) . "..tabTipBilhete B
                                                ON S.CodTipBilhete = B.CodTipBilhete
                                        INNER JOIN " . strtoupper($rs['DS_NOME_BASE_SQL']) . "..tabSalDetalhe D
                                                ON S.Indice = D.Indice
                                        INNER JOIN " . strtoupper($rs['DS_NOME_BASE_SQL']) . "..tabSetor E
                                                ON D.CodSala = E.CodSala
                                                AND D.CodSetor = E.CodSetor
                                        INNER JOIN " . strtoupper($rs['DS_NOME_BASE_SQL']) . "..tabApresentacao A
                                                ON S.CodApresentacao = A.CodApresentacao
                                                AND D.codsala = A.codsala
                                        INNER JOIN " . strtoupper($rs['DS_NOME_BASE_SQL']) . "..tabPeca P
                                                ON A.CodPeca = P.CodPeca
                                        INNER JOIN " . strtoupper($rs['DS_NOME_BASE_SQL']) . "..tabLancamento L1
                                                ON S.Indice = L1.Indice
                                        INNER JOIN " . strtoupper($rs['DS_NOME_BASE_SQL']) . "..tabForPagamento G
                                                ON G.CodForPagto = L1.CodForPagto
                                                AND S.CodApresentacao = L1.CodApresentacao
                                WHERE   (L1.CodTipLancamento = 1)
                                AND     (S.CodVenda = ?)
                                AND     (A.CodApresentacao = ?)
                                AND     (S.codvenda is not null)
                                AND     NOT EXISTS (SELECT 1 FROM " . strtoupper($rs['DS_NOME_BASE_SQL']) . "..TABLANCAMENTO L2 WHERE L2.NUMLANCAMENTO = L1.NUMLANCAMENTO AND L2.CODTIPLANCAMENTO = 2)
                                ORDER BY D.NomObjeto";
                        $params2 = array($rs['CODVENDA'], $rs['CODAPRESENTACAO']);
                        $indices = executeSQL($mainConnection, $query2, $params2);

                        //para cada codvenda/codapresentacao
                        $i = 0;
                        while ($rs2 = fetchResult($indices)) {

                            // echo "lista de indice, codcaixa, DatMovimento e CodMovimento: \n"; print_r(array($query2, $params2)); echo "\n"; print_r($rs2); echo "\n\n";
                            //executa apenas 1 vez para cada codvenda/codapresentacao
                            if ($i == 0) {

                                // SP_JUS_INS001
                                // @Justificativa      varchar(250),
                                // @Indice             int,
                                // @CodApresentacao    int
                                $query3 = 'EXEC ' . strtoupper($rs['DS_NOME_BASE_SQL']) . '..SP_JUS_INS001 ?,?,?';
                                $params3 = array(utf8_encode2($_POST['justificativa']), $rs2['INDICE'], $rs['CODAPRESENTACAO']);
                                $rsProc1 = executeSQL($mainConnection, $query3, $params3, true);

                                // echo "procedure 1: \n"; print_r(array($query3, $params3)); echo "\n"; print_r($rsProc1); echo "\n\n";
                                // SP_GLE_INS001
                                // @CodUsuario         int, (255 = WEB)
                                // @StrLog             varchar(50), --> nome do espetaculo
                                // @CodVenda           varchar(50)
                                $query4 = 'EXEC ' . strtoupper($rs['DS_NOME_BASE_SQL']) . '..SP_GLE_INS001 ?,?,?';
                                $params4 = array(255, $rs['DS_EVENTO'], $rs['CODVENDA']);
                                $rsLog = executeSQL($mainConnection, $query4, $params4, true);
                                $IdLogOperacao = $rsLog['IdLogOperacao'];

                                // echo "procedure 2: \n"; print_r(array($query4, $params4)); echo "\n"; print_r($rsLog); echo "\n\n";
                            }

                            // SP_LUG_DEL003
                            // @CodCaixa           tinyint,
                            // @DatMovimento       smalldatetime,
                            // @CodApresentacao    int,
                            // @Indice             int,
                            // @CodLog             int, --> resultado da gle_ins
                            // @CodMovimento       int
                            $query5 = 'EXEC ' . strtoupper($rs['DS_NOME_BASE_SQL']) . '..SP_LUG_DEL003 ?,?,?,?,?,?,?';
                            $params5 = array($rs2['CODCAIXA'], $rs2['DATMOVIMENTO'], $rs['CODAPRESENTACAO'], $rs2['INDICE'], $IdLogOperacao, $rs2['CODMOVIMENTO'], 255);
                            $rsProc3 = executeSQL($mainConnection, $query5, $params5, true);

                            // echo "procedure 3: \n"; print_r(array($query5, $params5)); echo "\n"; print_r($rsProc3); echo "\n\n";

                            $i++;
                        }
                    }

                    $query = "UPDATE MW_PROMOCAO SET
                                    ID_PEDIDO_VENDA = NULL
                            WHERE ID_PEDIDO_VENDA = ?";
                    $params = array($pedido['ID_PEDIDO_VENDA']);
                    executeSQL($mainConnection, $query, $params);

                    $query = "UPDATE MW_PEDIDO_VENDA SET
                                    IN_SITUACAO = 'S',
                                    ID_USUARIO_ESTORNO = ?,
                                    DS_MOTIVO_CANCELAMENTO = ?,
                                    DT_HORA_CANCELAMENTO = GETDATE()
                            WHERE ID_PEDIDO_VENDA = ?";
                    $params = array($_SESSION['admin'], utf8_encode2($_POST['justificativa']), $pedido['ID_PEDIDO_VENDA']);
                    executeSQL($mainConnection, $query, $params);

                    $sqlErrors = sqlErrors();

                    if (empty($sqlErrors)) {
                        executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
                                array($_SESSION['user'], json_encode(array('descricao' => 'estorno/cancelamento do pedido ' . $pedido['ID_PEDIDO_VENDA'], 'retorno' => $conta)))
                        );

                        if (!empty($bank_data)) {
                            $query .= ' ' . json_encode($bank_data);
                        }

                        $log = new Log($_SESSION['admin']);
                        $log->__set('funcionalidade', 'Estorno de Pedidos');
                        $log->__set('parametros', $params);
                        $log->__set('log', $query);
                        $log->save($mainConnection);

                        $retorno = 'ok';

                    } else {
                        $retorno = $sqlErrors;
                    }
                } else {
                    $retorno = 'O pedido não foi cancelado/estornado.';
                    $envia_error_mail = true;
                }

                if ($erros_resposta_braspag['count'] > 0 or $envia_error_mail) {
                    // include('../comprar/errorMail.php');

                    executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
                            array($_SESSION['user'], json_encode(array('descricao' => 'erro no estorno/cancelamento do pedido ' . $pedido['ID_PEDIDO_VENDA'], 'retorno' => $conta)))
                    );

                    // apenas para log
                    $query = "UPDATE MW_PEDIDO_VENDA SET
                                    IN_SITUACAO = 'S',
                                    ID_USUARIO_ESTORNO = ?,
                                    DS_MOTIVO_CANCELAMENTO = ?
                            WHERE ID_PEDIDO_VENDA = ?";
                    $params = array($_SESSION['admin'], utf8_encode2($_POST['justificativa']), $pedido['ID_PEDIDO_VENDA']);
                    // ----------------
                    $log = new Log($_SESSION['admin']);
                    $log->__set('funcionalidade', 'Estorno de Pedidos');
                    $log->__set('parametros', $params);
                    $log->__set('log', $query . '; Erro: ' . $erros_resposta_braspag['descr']);
                    $log->save($mainConnection);
                }
            } else {
                $retorno = "Requisição forçada!<br/><br/>O que você está tentando fazer?";
            }

            //parar estorno se ocorrer um erro
            if ($retorno != 'ok') break;
        }

        if ($pedido_principal['IN_PACOTE'] == 'S' and $retorno == 'ok') {
            $query = "UPDATE PR
                        SET PR.IN_STATUS_RESERVA = CASE WHEN CONVERT(VARCHAR, GETDATE(), 112) BETWEEN DT_INICIO_FASE1 AND DT_FIM_FASE1 THEN 'A' ELSE 'C' END
                        FROM MW_PEDIDO_VENDA PV
                        INNER JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                        INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = IPV.ID_APRESENTACAO
                        INNER JOIN MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
                        INNER JOIN MW_PACOTE P ON P.ID_APRESENTACAO = A2.ID_APRESENTACAO
                        INNER JOIN MW_PACOTE_RESERVA PR ON PR.ID_CLIENTE = PV.ID_CLIENTE AND PR.ID_PACOTE = P.ID_PACOTE AND PR.ID_CADEIRA = IPV.INDICE
                        WHERE PV.ID_PEDIDO_VENDA = ? AND PR.IN_STATUS_RESERVA = 'R'";
            $params = array($pedido_principal['ID_PEDIDO_VENDA']);
            executeSQL($mainConnection, $query, $params);
        }
    }

    if (is_array($retorno)) {
        echo $retorno[0]['message'];
    } else {
        
        if ($force_system_refund) {
            echo $resposta_geral;
        } else {
            if ($retorno == 'ok') {
                echo 'ok';
            } else {
                echo $retorno;
            }
        }
    }
}