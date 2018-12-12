<?php
if ($_REQUEST['notificationType'] == 'transaction') {

    require_once('../settings/functions.php');
    require_once('../settings/multisite/unique.php');
    require_once('../settings/pagseguro_functions.php');

    $mainConnection = mainConnection();

    executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
        array(-999, json_encode(array('descricao' => '6. retorno pagseguro', 'post' => $_REQUEST)))
    );

    $response = getNotificationPagSeguro($_REQUEST['notificationCode']);

    if ($response['success']) {

        $id_pedido = $response['transaction']->getReference();

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
        $params = array($id_pedido, $response['transaction']->getStatus()->getValue(), base64_encode(serialize($response['transaction'])));
        executeSQL($mainConnection, $query, $params);

        switch ($rs['IN_SITUACAO']) {
            // pedido em processamento no sistema
            case 'P':
                switch ($response['transaction']->getStatus()->getValue()) {
                    // pago
                    case 3:
                    // disponivel
                    case 4:
                        $parametros['OrderData']['OrderId'] = $id_pedido;
                        $result->AuthorizeTransactionResult->OrderData->BraspagOrderId = 'PagSeguro';
                        $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->BraspagTransactionId = $response['transaction']->getCode();
                        $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AcquirerTransactionId = $response['transaction']->getPaymentMethod()->getType()->getValue();
                        $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AuthorizationCode = $response['transaction']->getPaymentMethod()->getCode()->getValue();
                        $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->PaymentMethod = $rs['CD_MEIO_PAGAMENTO'];

                        ob_start();
                        require_once "concretizarCompra.php";
                        // se necessario, replica os dados de assinatura e imprime url de redirecionamento
                        require "concretizarAssinatura.php";
                        $return = ob_get_clean();

                        $return = ($return == '' OR substr($return, 0, 12) == 'redirect.php' ? true : $return);
                    break;

                    // em disputa
                    case 5:
                    // devolvida
                    case 6:
                    // cancelada
                    case 7:
                    // debitado
                    case 8:
                    // retenção temporária
                    case 9:
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
                switch ($response['transaction']->getStatus()->getValue()) {
                    // em disputa
                    case 5:
                    // devolvida
                    case 6:
                    // cancelada
                    case 7:
                    // debitado
                    case 8:
                    // retenção temporária
                    case 9:
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
                switch ($response['transaction']->getStatus()->getValue()) {
                    // pago
                    case 3:
                    // disponivel
                    case 4:
                        $response = estonarPedidoPagseguro($response['transaction']->getCode());
                        $return = ($response['success'] ? true : $response['error']);
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
        array(-999, json_encode(array('descricao' => 'retorno pagseguro', 'post' => $_REQUEST, 'resultado' => $return)))
    );

    echo ($return === true ? 'ok' : $return);

}