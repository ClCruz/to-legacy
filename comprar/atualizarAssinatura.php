<?php

session_start();

if (!isset($_SESSION['user']))
    die();

if (isset($_GET['action'])) {
    require_once('../settings/functions.php');
    require_once('../settings/settings.php');
    $mainConnection = mainConnection();


    if ($_GET["action"] == "load" and isset($_GET["local"])) {
        // Conjunto de situaçãos da assinatura.
        $situacao = array(
            'A' => "Aguardando ação do Assinante",
            'S' => "Solicitado troca",
            'T' => "Troca efetuada",
            'C' => "Assinatura cancelada",
            'R' => "Assinatura renovada"
        );

        $conn = getConnection($_GET["local"]);

        $rsHist = executeSQL($conn,
                                "SELECT 
                                    PR.ID_PACOTE AS ID_HISTORICO
                                    ,E.DS_EVENTO COLLATE SQL_Latin1_General_CP1_CI_AS AS DS_PACOTE
                                    ,ISNULL(PR.IN_ANO_TEMPORADA,0) AS ID_ANO_TEMPORADA
                                    ,PR.ID_CADEIRA
                                    ,ISNULL(PR.DS_LOCALIZACAO,'') COLLATE SQL_Latin1_General_CP1_CI_AS AS DS_CADEIRA
                                    ,TA.VALPECA AS VL_PACOTE
                                    ,I.VL_UNITARIO AS VL_PAGO
                                    ,TS.NOMSETOR COLLATE SQL_Latin1_General_CP1_CI_AS AS DS_SETOR
                                    ,ISNULL((SELECT TOP 1 '' FROM CI_MIDDLEWAY..MW_PACOTE_APRESENTACAO PA WHERE PA.ID_PACOTE = P.ID_PACOTE), 'R') + PR.IN_STATUS_RESERVA AS IN_STATUS_RESERVA_ATTR
                                    ,PR.IN_STATUS_RESERVA
                                    ,'PACOTE' AS IN_ORIGEM
                                FROM CI_MIDDLEWAY..MW_PACOTE_RESERVA PR
                                INNER JOIN CI_MIDDLEWAY..MW_PACOTE P ON P.ID_PACOTE = PR.ID_PACOTE
                                INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                                INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
                                INNER JOIN CI_MIDDLEWAY..MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                                INNER JOIN CI_MIDDLEWAY..MW_BASE B ON B.ID_BASE  = E.ID_BASE
                                INNER JOIN TABSALDETALHE TSD ON TSD.INDICE = PR.ID_CADEIRA
                                INNER JOIN TABSETOR TS ON TS.CODSALA = TSD.CODSALA AND TS.CODSETOR = TSD.CODSETOR
                                INNER JOIN TABAPRESENTACAO TA ON TA.CODAPRESENTACAO = A2.CODAPRESENTACAO AND TA.CODSALA= TS.CODSALA
                                LEFT JOIN CI_MIDDLEWAY..MW_ITEM_PEDIDO_VENDA I ON I.ID_APRESENTACAO = A2.ID_APRESENTACAO AND I.INDICE = TSD.INDICE
                                    AND I.ID_PEDIDO_VENDA IN (SELECT PV.ID_PEDIDO_VENDA FROM CI_MIDDLEWAY..MW_PEDIDO_VENDA PV WHERE PV.ID_PEDIDO_VENDA = I.ID_PEDIDO_VENDA AND PV.ID_CLIENTE = PR.ID_CLIENTE AND PV.IN_SITUACAO = 'F')
                                WHERE PR.ID_CLIENTE = ?
                                ORDER BY E.DS_EVENTO, TS.NOMSETOR, PR.DS_LOCALIZACAO",
                            array($_SESSION['user']));
        $table = "";
        while ($rs = fetchResult($rsHist)) {
            //$link = ($rs["IN_ORIGEM"] == 'PACOTE') ? "detalhes_historico.php?historico=" . $rs['ID_HISTORICO'] : "#";
            $link = "detalhes_historico.php?historico=" . $rs['ID_HISTORICO'] ."&origem=".$rs["IN_ORIGEM"] ;
            $table .= "<tr>";
            $table .= "<td width='32'>";
            if ($rs["IN_ORIGEM"] == "PACOTE" && !in_array($rs["IN_STATUS_RESERVA"], array('C','R','T'))) {
                $table .="<input type='checkbox' name='pacote[]' id='cb_" . $rs["ID_HISTORICO"] . $rs["ID_CADEIRA"] . "'  status='" . $rs["IN_STATUS_RESERVA_ATTR"] . "' class='checkbox independente' value='" . $rs["ID_HISTORICO"] . "' />";
                $table .='<label class="checkbox" for="cb_' . $rs["ID_HISTORICO"] . $rs["ID_CADEIRA"] . '"></label>';
                $table .="<input type='checkbox' name='cadeira[]' status='" . $rs["IN_STATUS_RESERVA_ATTR"] . "' class='checkbox-normal hidden' value='" . $rs["ID_CADEIRA"] . "' />";
                
            }
            $table .="</td>";
            $table .="<td class='npedido'><a href='" . $link . "'>" . utf8_encode2($rs['DS_PACOTE']) . "</a></td>";
            $table .="<td>" . $rs['ID_ANO_TEMPORADA'] . "</td>";
            $table .="<td>" . utf8_encode2($rs['DS_SETOR']) . "</td>";
            $table .="<td>" . utf8_encode2($rs['DS_CADEIRA']) . "</td>";
            $table .="<td>R$ " . number_format($rs['VL_PACOTE'], 2, ',', '') . "</td>";
            $table .= $rs['VL_PAGO'] ? "<td>R$ " . number_format($rs['VL_PAGO'], 2, ',', '') . "</td>" : "<td></td>";
            $table .="<td>" . $situacao[$rs["IN_STATUS_RESERVA"]] . "</td>";
            $table .="</tr>";
        }
        echo $table;
    }

    if ($_GET['action'] == 'renovar' or $_GET['action'] == 'efetuarTroca') {

        // checar se o usuario tem algum registro na mw_reserva
        $query = "SELECT 1 FROM MW_RESERVA WHERE ID_SESSION = ?";
        $result = executeSQL($mainConnection, $query, array(session_id()));
        if (hasRows($result))
            die("Já existe uma reserva em andamento.<br />Você deseja continuar com a seleção existente<br />ou iniciar uma nova reserva?");

        // remove variavel de sessao anterior
        unset($_SESSION['assinatura']);
    }

    foreach ($_REQUEST['pacote'] as $i => $pacote) {
        // checar se o usuario realmente tem a reserva informada
        $query = "SELECT 1 FROM MW_PACOTE_RESERVA WHERE ID_PACOTE = ? AND ID_CLIENTE = ? AND ID_CADEIRA = ? AND IN_STATUS_RESERVA IN ('A', 'S')";
        $result = executeSQL($mainConnection, $query, array($pacote, $_SESSION['user'], $_REQUEST['cadeira'][$i]));
        if (!hasRows($result))
            die("Nenhuma reserva encontrada.");

        // checar se os pacotes informados estao sendo alterados dentro das datas
        $query = "SELECT 1 FROM MW_PACOTE WHERE ID_PACOTE = ?
					AND (CONVERT(VARCHAR(8), GETDATE(), 112) BETWEEN DT_INICIO_FASE1 AND DT_FIM_FASE1 OR 
					CONVERT(VARCHAR(8), GETDATE(), 112) BETWEEN DT_INICIO_FASE2 AND DT_FIM_FASE2 OR 
					CONVERT(VARCHAR(8), GETDATE(), 112) BETWEEN DT_INICIO_FASE3 AND DT_FIM_FASE3)";
        $result = executeSQL($mainConnection, $query, array($pacote));
        if (!hasRows($result))
            die("Fora do período de ação.");
    }


    if ($_GET['action'] == 'renovar' and isset($_REQUEST['pacote'])) {

        $dados_renovacao = array();

        foreach ($_REQUEST['pacote'] as $i => $pacote) {
            // obtem id_base
            $query = "SELECT ID_BASE, DS_EVENTO
						FROM MW_EVENTO E
						INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
						INNER JOIN MW_PACOTE P ON P.ID_APRESENTACAO = A.ID_APRESENTACAO
						WHERE ID_PACOTE = ?";
            $rs = executeSQL($mainConnection, $query, array($pacote), true);
            $conn = getConnection($rs['ID_BASE']);
            $ds_evento = $rs['DS_EVENTO'];

            // obtem id_apresentacao
            $query = "SELECT A.ID_APRESENTACAO, TSD.NOMOBJETO, SE.NOMSETOR
						FROM TABSALDETALHE TSD
						INNER JOIN TABSALA TS ON TS.CODSALA = TSD.CODSALA
						INNER JOIN TABSETOR SE ON SE.CODSALA = TSD.CODSALA AND SE.CODSETOR = TSD.CODSETOR
						INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.DS_PISO = TS.NOMSALA COLLATE SQL_Latin1_General_CP1_CI_AS
						INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO B ON B.ID_EVENTO = A.ID_EVENTO AND B.DT_APRESENTACAO = A.DT_APRESENTACAO AND B.HR_APRESENTACAO = A.HR_APRESENTACAO
						INNER JOIN CI_MIDDLEWAY..MW_PACOTE P ON P.ID_APRESENTACAO = B.ID_APRESENTACAO
						WHERE INDICE = ? AND ID_PACOTE = ?";
            $rs = executeSQL($conn, $query, array($_REQUEST['cadeira'][$i], $pacote), true);

            $dados_renovacao[] = array(
                'pacote' => $pacote,
                'apresentacao' => $rs['ID_APRESENTACAO'],
                'cadeira' => $_REQUEST['cadeira'][$i]
            );

            // simula as variaveis de uma adicao normal no carrinho

            $_GET['action'] = 'add';

            $_POST['id'] = $_REQUEST['cadeira'][$i];
            $_POST['apresentacao'] = $rs['ID_APRESENTACAO'];
            $_POST['name'] = $rs['NOMOBJETO'];
            $_POST['setor'] = $rs['NOMSETOR'];

            $_REQUEST['id'] = $_REQUEST['cadeira'][$i];
            $_REQUEST['apresentacao'] = $rs['ID_APRESENTACAO'];
            $_REQUEST['name'] = $rs['NOMOBJETO'];
            $_REQUEST['setor'] = $rs['NOMSETOR'];

            ob_start();
            require('atualizarPedido.php');
            $result = ob_get_clean();

            if (substr($result, 0, 4) != 'true') {
                die($result);
            }

        }

        // se passou por tudo esta ok

        $_SESSION['assinatura']['tipo'] = 'renovacao';
        $_SESSION['assinatura']['evento'] = $ds_evento;
        $_SESSION['assinatura']['lugares'] = $dados_renovacao;

        echo 'redirect.php?redirect=' . urlencode('etapa2.php?eventoDS=' . $ds_evento);
    } else if ($_GET['action'] == 'solicitarTroca' and isset($_REQUEST['pacote'])) {
        $mensagem = "";
        foreach ($_REQUEST['pacote'] as $i => $pacote) {
            // obtem id_base
            $query = "SELECT ID_BASE, DS_EVENTO
                        FROM MW_EVENTO E
                        INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
                        INNER JOIN MW_PACOTE P ON P.ID_APRESENTACAO = A.ID_APRESENTACAO
                        WHERE ID_PACOTE = ?";
            $rs = executeSQL($mainConnection, $query, array($pacote), true);
            $conn = getConnection($rs['ID_BASE']);
            $ds_evento = $rs['DS_EVENTO'];

            $retorno = true;
            $query = "SELECT E.DS_EVENTO AS DS_PACOTE, ISNULL(PR.DS_LOCALIZACAO,'') AS DS_CADEIRA,
                            TS.NOMSETOR AS DS_SETOR, PR.IN_STATUS_RESERVA
                        FROM CI_MIDDLEWAY..MW_PACOTE_RESERVA PR
                        INNER JOIN CI_MIDDLEWAY..MW_PACOTE P ON P.ID_PACOTE = PR.ID_PACOTE
                        INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                        INNER JOIN CI_MIDDLEWAY..MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                        INNER JOIN CI_MIDDLEWAY..MW_BASE B ON B.ID_BASE  = E.ID_BASE
                        INNER JOIN TABSALDETALHE TSD ON TSD.INDICE = PR.ID_CADEIRA
                        INNER JOIN TABSETOR TS ON TS.CODSALA = TSD.CODSALA AND TS.CODSETOR = TSD.CODSETOR
                        INNER JOIN TABAPRESENTACAO TA ON TA.CODAPRESENTACAO = A.CODAPRESENTACAO
                        WHERE PR.ID_PACOTE = ? AND PR.ID_CLIENTE = ? AND PR.ID_CADEIRA = ?";
            $rs = executeSQL($conn, $query, array($pacote, $_SESSION['user'], $_REQUEST['cadeira'][$i]), true);
            if ($rs["IN_STATUS_RESERVA"] !== 'A') {
                $retorno = false;
                $mensagem .= "Não é possível solicitar a troca para a Assinatura " . $rs["DS_PACOTE"] . " do Setor " . $rs["DS_SETOR"] . " do lugar " . $rs["DS_CADEIRA"] . "<br/>";
            }

            if ($retorno) {
                $query = "UPDATE
                            MW_PACOTE_RESERVA
                        SET IN_STATUS_RESERVA = 'S',
                            DT_HR_TRANSACAO = GETDATE()
                        WHERE
                            ID_PACOTE = ? AND ID_CLIENTE = ? AND ID_CADEIRA = ?";
                $result = executeSQL($mainConnection, $query, array($pacote, $_SESSION['user'], $_REQUEST['cadeira'][$i]));

                if ($result == false) {
                    print_r(sqlErrors());
                }
            }
        }
        echo ($mensagem == "") ? "true" : $mensagem;
    } else if ($_GET['action'] == 'efetuarTroca' and isset($_REQUEST['pacote'])) {

        foreach ($_REQUEST['pacote'] as $i => $pacote) {
            $query = "SELECT 1 FROM MW_PACOTE_RESERVA WHERE ID_PACOTE = ? AND ID_CLIENTE = ? AND ID_CADEIRA = ? AND IN_STATUS_RESERVA = 'S'";
            $result = executeSQL($mainConnection, $query, array($pacote, $_SESSION['user'], $_REQUEST['cadeira'][$i]));

            if (!hasRows($result)) {
                echo "Não é possível efetuar a troca de uma assinatura que não foi solicitada dentro do prazo estipulado.";
                die();
            }
        }

        $_SESSION['assinatura']['tipo'] = 'troca';
        $_SESSION['assinatura']['pacote'] = $_REQUEST['pacote'];
        $_SESSION['assinatura']['cadeira'] = $_REQUEST['cadeira'];

        echo 'redirect.php?redirect='.urlencode('selecionarTroca.php');

    } else if ($_GET['action'] == 'cancelar' and isset($_REQUEST['pacote'])) {
        $mensagem = "";
        foreach ($_REQUEST['pacote'] as $i => $pacote) {
            // obtem id_base
            $query = "SELECT ID_BASE, DS_EVENTO
                        FROM MW_EVENTO E
                        INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
                        INNER JOIN MW_PACOTE P ON P.ID_APRESENTACAO = A.ID_APRESENTACAO
                        WHERE ID_PACOTE = ?";
            $rs = executeSQL($mainConnection, $query, array($pacote), true);
            $conn = getConnection($rs['ID_BASE']);
            $ds_evento = $rs['DS_EVENTO'];

            $retorno = true;
            $query = "SELECT E.DS_EVENTO AS DS_PACOTE, ISNULL(PR.DS_LOCALIZACAO,'') AS DS_CADEIRA,
                            TS.NOMSETOR AS DS_SETOR, PR.IN_STATUS_RESERVA
                        FROM CI_MIDDLEWAY..MW_PACOTE_RESERVA PR
                        INNER JOIN CI_MIDDLEWAY..MW_PACOTE P ON P.ID_PACOTE = PR.ID_PACOTE
                        INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                        INNER JOIN CI_MIDDLEWAY..MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                        INNER JOIN CI_MIDDLEWAY..MW_BASE B ON B.ID_BASE  = E.ID_BASE
                        INNER JOIN TABSALDETALHE TSD ON TSD.INDICE = PR.ID_CADEIRA
                        INNER JOIN TABSETOR TS ON TS.CODSALA = TSD.CODSALA AND TS.CODSETOR = TSD.CODSETOR
                        INNER JOIN TABAPRESENTACAO TA ON TA.CODAPRESENTACAO = A.CODAPRESENTACAO
                        WHERE PR.ID_PACOTE = ? AND PR.ID_CLIENTE = ? AND PR.ID_CADEIRA = ?";
            $rs = executeSQL($conn, $query, array($pacote, $_SESSION['user'], $_REQUEST['cadeira'][$i]), true);
            if ($rs["IN_STATUS_RESERVA"] !== 'A' && $rs["IN_STATUS_RESERVA"] !== 'S') {
                $retorno = false;
                $mensagem .= "Não é possível cancelar a Assinatura " . $rs["DS_PACOTE"] . " do Setor " . $rs["DS_SETOR"] . " do lugar " . $rs["DS_CADEIRA"] . "<br/>";
            }

            if ($retorno) {
                $query = "UPDATE
                            MW_PACOTE_RESERVA
                        SET IN_STATUS_RESERVA = 'C',
                            DT_HR_TRANSACAO = GETDATE()
                        WHERE
                            ID_PACOTE = ? AND ID_CLIENTE = ? AND ID_CADEIRA = ?";
                $result = executeSQL($mainConnection, $query, array($pacote, $_SESSION['user'], $_REQUEST['cadeira'][$i]));
                if ($result == false) {
                    print_r(sqlErrors());
                }

                $id_usuario_teatro = executeSQL($mainConnection,
                                                "SELECT B.ID_CLIENTE
                                                FROM MW_PACOTE P
                                                INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                                                INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                                                INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
                                                WHERE P.ID_PACOTE = ?",
                                                array($pacote), true);
                $id_usuario_teatro = $id_usuario_teatro['ID_CLIENTE'];

                $query = "INSERT INTO MW_PACOTE_RESERVA (ID_CLIENTE, ID_PACOTE, ID_CADEIRA, IN_STATUS_RESERVA, DT_HR_TRANSACAO, IN_ANO_TEMPORADA, DS_LOCALIZACAO)
                        SELECT ?, ID_PACOTE, ID_CADEIRA, 'A', GETDATE(), IN_ANO_TEMPORADA, DS_LOCALIZACAO
                        FROM MW_PACOTE_RESERVA
                        WHERE ID_PACOTE = ? AND ID_CLIENTE = ? AND ID_CADEIRA = ?";
                $result = executeSQL($mainConnection, $query, array($id_usuario_teatro, $pacote, $_SESSION['user'], $_REQUEST['cadeira'][$i]));
                if ($result == false) {
                    print_r(sqlErrors());
                }
            }
        }
        echo ($mensagem == "") ? "true" : $mensagem;
    }
}