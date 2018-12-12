<?php
require_once('../settings/functions.php');

if (acessoPermitido($mainConnection, $_SESSION['admin'], 381, true)) {

    if ($_GET['action'] == 'efetivar') {        

        $connLocal = getConnection($_POST['local']);

        foreach ($_POST['pacote'] as $i => $pacote) {
            $query = "UPDATE MW_PACOTE_RESERVA SET IN_STATUS_RESERVA = 'R',
                  DT_HR_TRANSACAO = GETDATE() WHERE ID_PACOTE = ? AND
                  ID_CLIENTE = ? AND ID_CADEIRA = ?";
            $params = array($pacote, $_POST['cliente'][$i], $_POST['cadeira'][$i]);
            executeSQL($mainConnection, $query, $params);
            $errors = sqlErrors();
            if (empty($errors)) {
                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Efetivar a reserva nas apresentações dos pacotes');
                $log->__set('parametros', $params);
                $log->__set('log', $query);
                $log->save($mainConnection);
                $retorno = 'ok';
            } else {
                $retorno = $errors;
                break;
            }

            //Garantir lugar na tabLugSala
            $query = "SELECT TA.CODAPRESENTACAO, TSD.INDICE, 0, 255, 'M'
                    FROM CI_MIDDLEWAY..MW_PACOTE_APRESENTACAO PA
                    INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_APRESENTACAO = PA.ID_APRESENTACAO
                    INNER JOIN TABSALDETALHE TSD ON TSD.INDICE = ?
                    INNER JOIN TABAPRESENTACAO TA ON TA.CODAPRESENTACAO = A.CODAPRESENTACAO AND TA.CODSALA = TSD.CODSALA
                    WHERE PA.ID_PACOTE = ?";
            $params = array($_POST['cadeira'][$i], $pacote);
            $result = executeSQL($connLocal, $query, $params);
            //executar 2 vezes (solucao temporaria emergencial)
            $result2 = executeSQL($connLocal, $query, $params);
            $errors = sqlErrors();

            while($rs = fetchResult($result)){
                $query = "INSERT INTO TABLUGSALA (CodApresentacao,Indice,
                    CodTipBilhete, CodCaixa,StaCadeira) values(?, ?, 0, 255, 'M')";
                $params = array($rs["CODAPRESENTACAO"], $rs["INDICE"]);
                executeSQL($connLocal, $query, $params);
                $errors = sqlErrors();

                if (empty($errors)) {
                    $log = new Log($_SESSION['admin']);
                    $log->__set('funcionalidade', 'Efetivar a reserva nas apresentações dos pacotes');
                    $log->__set('parametros', $params);
                    $log->__set('log', $query);
                    $log->save($mainConnection);
                    $retorno = 'ok';
                } else {
                    $retorno = $errors;
                    break;
                }
            }            

            //Gerar código único da Reserva
            $codReserva = generateCodVenda($connLocal);
            
            //Gravar na tabResCliente
            $query = "SP_CLR_INS001 ".$_POST['codcliente'].",'". $codReserva ."', 255, '1'";
            executeSQL($connLocal, $query);
            $errors = sqlErrors();
            if (empty($errors)) {
                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Efetivar a reserva nas apresentações dos pacotes');
                $log->__set('parametros', $params);
                $log->__set('log', $query);
                $log->save($mainConnection);
                $retorno = 'ok';
            } else {
                $retorno = $errors;
                break;
            }

            while($rs = fetchResult($result2)){
                //Marcar lugar como reservado
                $query = "UPDATE tabLugSala 
                            SET StaCadeira = 'R', 
                                CodUsuario = ?,
                                CodReserva = ?
                           WHERE CodCaixa = 255 AND StaCadeira = 'M'
                                AND CodApresentacao = ?
                                AND Indice = ?";
                $params = array($_POST["usuario"], $codReserva, $rs["CODAPRESENTACAO"], $rs["INDICE"]);
                executeSQL($connLocal, $query, $params);
                $errors = sqlErrors();
                
                if (empty($errors)) {
                    $log = new Log($_SESSION['admin']);
                    $log->__set('funcionalidade', 'Efetivar a reserva nas apresentações dos pacotes');
                    $log->__set('parametros', $params);
                    $log->__set('log', $query);
                    $log->save($mainConnection);
                    $retorno = 'ok';
                } else {
                    $retorno = $errors;
                    break;
                }
            }
            
            usleep(1000000);
        }

    } else if ($_GET['action'] == 'load_pacotes') {
        $retorno = comboPacote('pacote_combo', $_SESSION['admin'],
                    $_POST['pacote_combo'], $_POST['local'], 3);
        
    } else if ($_GET['action'] == 'load_usuario') {
        $conn = getConnection($_POST['local']);
        $query = "SELECT CODUSUARIO, LTRIM(RTRIM(NOMUSUARIO)) AS NOMUSUARIO FROM TABUSUARIO
                  WHERE STAUSUARIO = 0 AND (CODUSUARIO > 0 AND
                  CODUSUARIO < 200) ORDER BY LTRIM(RTRIM(NOMUSUARIO))";
        $result = executeSQL($conn, $query);
        $html = "<select id=\"usuario\" name=\"usuario\">";
        $html .= "<option value=\"-1\">Selecione...</option>";
        while ($rs = fetchResult($result)) {
            $selected = ($_POST['usuario'] == $rs['CODUSUARIO']) ? "selected=\"selected\"" : "";
            $html .= "<option ". $selected ." value=" . $rs['CODUSUARIO'] . ">" . utf8_encode2($rs['NOMUSUARIO']) . "</option>";
        }
        $html .= "</select>";
        $retorno = $html;

    } else if ($_GET['action'] == 'load_cliente') {
        $conn = getConnection($_POST['local']);
        $cliente = getCliente($conn);
        if ($cliente == "") {
            setCliente($conn);
            $cliente = getCliente($conn);
        }
        $retorno = $cliente["NOME"].";".$cliente["CODIGO"];
    }

    if (is_array($retorno)) {
        echo $retorno[0]['message'];
    } else {
        echo $retorno;
    }
}
?>