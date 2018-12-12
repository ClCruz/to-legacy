<?php
require_once('../settings/functions.php');
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
try {
    $mainConnection = mainConnection();

    executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
        array(-999, json_encode(array('descricao' => 'pagarme receiver workaround', 'request' => $_REQUEST)))
    );

    if ($_REQUEST['object'] == 'transaction') {

        require_once('../settings/pagarme_functions.php');

        //$post_data = file_get_contents("php://input");

        // if (!PagarMe::validateRequestSignature($post_data, $_SERVER['HTTP_X_HUB_SIGNATURE'])) {
        //     executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
        //         array(-999, json_encode(array('descricao' => 'pagarme receiver not ok', 'request' => $_REQUEST)))
        //     );
            
        //     http_response_code(400);
        //     die('not ok');
        // }

        $response = getNotificationPagarme($_REQUEST['id']);

        executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
            array(-999, json_encode(array('descricao' => 'retorno consulta pagarme', 'post' => $_REQUEST, 'resultado' => $response)))
        );

        if ($response['transaction']['payment_method'] == 'credit_card' AND !in_array($response['transaction']['status'], array('chargedback'))) {
            die('credit_card');
        }

        if ($response['success']) {

            $id_pedido = $response['transaction']['metadata']['id_pedido_venda'];

            $rs = executeSQL(
                $mainConnection,
                'SELECT P.IN_SITUACAO, M.CD_MEIO_PAGAMENTO
                    FROM MW_PEDIDO_VENDA P
                    INNER JOIN MW_MEIO_PAGAMENTO M ON M.ID_MEIO_PAGAMENTO = P.ID_MEIO_PAGAMENTO
                    WHERE P.ID_PEDIDO_VENDA = ?',
                array($id_pedido),
                true
            );

            $query = 'INSERT INTO MW_PEDIDO_PAGSEGURO (ID_PEDIDO_VENDA, DT_STATUS, CD_STATUS, OBJ_PAGSEGURO) VALUES (?, GETDATE(), ?, ?)';
            $params = array($id_pedido, $response['transaction']['status'], base64_encode(serialize($response['transaction'])));
            executeSQL($mainConnection, $query, $params);

            switch ($rs['IN_SITUACAO']) {
                // pedido em processamento no sistema
                case 'P':
                    switch ($response['transaction']['status']) {
                        // pago
                        case 'paid':
                            $parametros['OrderData']['OrderId'] = $id_pedido;

                            $result = new stdClass();

                            $result->AuthorizeTransactionResult->OrderData->BraspagOrderId = 'Pagar.me';
                            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->BraspagTransactionId = $response['transaction']->id;
                            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AcquirerTransactionId = $response['transaction']->nsu;
                            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AuthorizationCode = $response['transaction']->authorization_code;
                            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->PaymentMethod = $rs['CD_MEIO_PAGAMENTO'];

                            ob_start();
                            require_once "concretizarCompra.php";
                            // se necessario, replica os dados de assinatura e imprime url de redirecionamento
                            require "concretizarAssinatura.php";
                            $return = ob_get_clean();

                            $return = ($return == '' OR substr($return, 0, 12) == 'redirect.php' ? true : $return);
                        break;

                        // em processo de estorno
                        case 'pending_refund':
                        // estornado
                        case 'refunded':
                        // recusado
                        case 'refused':
                        // chargedback
                        case 'chargedback':
                            $query = "UPDATE MW_RESERVA SET DT_VALIDADE = GETDATE()-1 WHERE ID_PEDIDO_VENDA = ?";
                            executeSQL($mainConnection, $query, array($id_pedido));

                            $query = "exec prc_limpa_reserva";
                            executeSQL($mainConnection, $query);

                            $return = true;
                        break;
                    }
                break;

                // pedido finalizado no sistema
                case 'F':
                    switch ($response['transaction']['status']) {
                        // estorno pendente
                        case 'pending_refund':
                        // estornado
                        case 'refunded':
                        // chargedback
                        case 'chargedback':
                            $post_data = http_build_query(array('pedido' => $_GET['pedido'], 'justificativa' => 'Estorno pela máquina POS', 'auth' => $auth_code));
                            $url = 'http'.($_SERVER["HTTPS"] == "on" ? 's' : '').'://'.($_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : multiSite_getDomainCompra()).'/admin/estorno.php';

                            $ch = curl_init(); 
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_COOKIE, $strCookie);
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            $response = curl_exec($ch);
                            $errno = curl_errno($ch);
                            curl_close($ch);

                            if ($response == 'ok') {
                                executeSQL($mainConnection, "UPDATE MW_PEDIDO_VENDA SET DS_ESTORNO_POS = ? WHERE ID_PEDIDO_VENDA = ?", array(json_encode($_GET), $_GET['pedido']));
                                $return = true;
                            } else {
                                $return = $response.($errno != 0 ? ' CURL'.$errno : '');
                            }
                        break;
                    }
                break;

                // pedido cancelado/estornado no sistema
                case 'C':
                case 'E':
                case 'S':
                    switch ($response['transaction']['status']) {
                        // pago
                        case 'paid':
                            $query = "SELECT TOP 1 PP.CD_STATUS, MP.NM_CARTAO_EXIBICAO_SITE
                                        FROM MW_PEDIDO_PAGSEGURO PP
                                        INNER JOIN MW_PEDIDO_VENDA PV ON PV.ID_PEDIDO_VENDA = PP.ID_PEDIDO_VENDA
                                        INNER JOIN MW_MEIO_PAGAMENTO MP ON MP.ID_MEIO_PAGAMENTO = PV.ID_MEIO_PAGAMENTO
                                        WHERE PP.ID_PEDIDO_VENDA = ? AND PP.CD_STATUS != 'paid'
                                        ORDER BY DT_STATUS DESC";
                            $params = array($id_pedido);
                            $rs = executeSQL($mainConnection, $query, $params, true);

                            if ($rs['CD_STATUS'] == 'pending_refund' OR preg_match('/boleto/', strtolower($rs['NM_CARTAO_EXIBICAO_SITE']))) {
                                $message = "O pedido $id_pedido foi pago por boleto e precisa ser estornado devidamente.";
                                sendErrorMail('Erro no Sistema - boleto', $message);
                            }

                            $return = 'Pedido cancelado.';
                        break;
                    }
                break;
            }
        }

        // se nao foi encontrada nenhuma transacao com o codigo informado
        else {

            $return = 'nenhuma transação encontrada com o código informado';

        }

        executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
            array(-999, json_encode(array('descricao' => 'retorno pagarme', 'post' => $_REQUEST, 'resultado' => $return)))
        );

        if ($return === true) {
            echo 'ok';
        } else {
            http_response_code(400);
            echo $return;
        }

    }
} catch (Exception $e) {
    $nova_msg = $error_obj->getMessage();
    $mainConnection = mainConnection();

    executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
        array(NULL, json_encode(array('descricao' => 'erro pagarme workaround pedido ' . $_REQUEST['id'], 'error' => $nova_msg)))
    );

}
?>