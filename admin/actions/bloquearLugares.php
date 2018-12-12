<?php

require_once('../settings/functions.php');

if (acessoPermitido($mainConnection, $_SESSION['admin'], 382, true)) {

    if ($_GET['action'] == 'load') {
        $conn = getConnection($_POST['teatro']);

        $query = 'SELECT CODSALA, CODAPRESENTACAO FROM TABAPRESENTACAO
                  WHERE CODAPRESENTACAO = (SELECT TOP 1 CODAPRESENTACAO
                                           FROM CI_MIDDLEWAY..MW_APRESENTACAO
                                           WHERE ID_APRESENTACAO = ?)';
        $params = array($_POST["sala"]);
        $rs = executeSQL($conn, $query, $params, true);
        $idSala = $rs["CODSALA"];
        $idApresentacao = $rs["CODAPRESENTACAO"];
        $idBase = $_POST['teatro'];

        $query = 'SELECT NOMEIMAGEMSITE, ALTURASITE, LARGURASITE, TAMANHOLUGAR
              FROM TABSALA WHERE CODSALA = ?';
        $params = array($idSala);
        $rs = executeSQL($conn, $query, $params, true);

        if ($rs[0] != '') {
            $imagem = $rs[0];
        }
        if ($rs[1] != '') {
            $yScale = $rs[1];
        }
        if ($rs[2] != '') {
            $xScale = $rs[2];
        }
        if ($rs[3] != '') {
            $size = $rs[3];
        }

        $query = "SELECT MAX(POSX) MAXX, MAX(POSY) MAXY, MAX(POSXSITE) MAXXSITE,
              MAX(POSYSITE) MAXYSITE FROM TABSALDETALHE
              WHERE CODSALA = ? AND TIPOBJETO = 'C'";
        $params = array($idSala);
        $rs = executeSQL($conn, $query, $params, true);

        $query = "WITH RESULTADO AS (
                    SELECT PR.ID_CADEIRA FROM CI_MIDDLEWAY..MW_PACOTE_RESERVA PR
                    INNER JOIN CI_MIDDLEWAY..MW_PACOTE_APRESENTACAO PA ON PA.ID_PACOTE = PR.ID_PACOTE
                    INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_APRESENTACAO = PA.ID_APRESENTACAO
                    INNER JOIN CI_MIDDLEWAY..MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                    WHERE E.ID_BASE = " . $idBase . " AND A.CODAPRESENTACAO = " . $idApresentacao . " AND PR.IN_STATUS_RESERVA IN ('A', 'S', 'R')

                    UNION ALL

                    SELECT PR.ID_CADEIRA FROM CI_MIDDLEWAY..MW_PACOTE_RESERVA PR
                    INNER JOIN CI_MIDDLEWAY..MW_PACOTE P ON P.ID_PACOTE = PR.ID_PACOTE
                    INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A2 ON A2.ID_APRESENTACAO = P.ID_APRESENTACAO
                    INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_EVENTO = A2.ID_EVENTO AND A.DT_APRESENTACAO = A2.DT_APRESENTACAO AND A.HR_APRESENTACAO = A2.HR_APRESENTACAO AND A2.IN_ATIVO = 1
                    INNER JOIN CI_MIDDLEWAY..MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = 1
                    WHERE E.ID_BASE = " . $idBase . " AND A.CODAPRESENTACAO = " . $idApresentacao . " AND PR.IN_STATUS_RESERVA IN ('A', 'S', 'R')
                )
            SELECT CASE WHEN (L.STACADEIRA IS NULL AND R.ID_CADEIRA IS NULL)
                            THEN 'O' ELSE 'C' END STATUS,
                         S.INDICE, S.NOMOBJETO, S.CODSETOR, SE.NOMSETOR, S.IMGVISAOLUGAR, L.ID_SESSION,";

        if ($rs['MAXXSITE'] == '' or $rs['MAXYSITE'] == '' or $_POST['reset']) {
            $query .= '(((S.POSX * ?) / ?) + ?) POSXSITE, (((S.POSY * ?) / ?) + ?) POSYSITE';
            $params = array(
                1 - $_POST['xmargin'],
                $rs['MAXX'],
                $_POST['xmargin'],
                1 - $_POST['ymargin'],
                $rs['MAXY'],
                $_POST['ymargin'],
                $idSala
            );
        } else {
            $query .= 'S.POSXSITE, S.POSYSITE';
            $params = array($idSala);
        }

        $query .= ' FROM TABSALDETALHE S
                LEFT JOIN TABLUGSALA L ON L.INDICE = S.INDICE AND L.CODAPRESENTACAO = ' . $idApresentacao
                . ' INNER JOIN TABSETOR SE ON SE.CODSALA = S.CODSALA
                AND SE.CODSETOR = S.CODSETOR
                LEFT JOIN RESULTADO R ON R.ID_CADEIRA = S.INDICE
                WHERE S.CODSALA = ? AND S.TIPOBJETO = \'C\'';

        $result = executeSQL($conn, $query, $params);
        $cadeiras = '[';

        while ($rs = fetchResult($result)) {
            $rs['STATUS'] = (session_id() == $rs['ID_SESSION']) ? 'S' : $rs['STATUS'];
            $cadeiras .= "{" .
                    "id:'" . $rs['INDICE'] . "'" .
                    ",name:'" . $rs['NOMOBJETO'] . "'" .
                    ",setor:'" . utf8_encode2($rs['NOMSETOR']) . "'" .
                    ",codSetor:'" . $rs['CODSETOR'] . "'" .
                    ",x:" . $rs['POSXSITE'] .
                    ",y:" . $rs['POSYSITE'] .
                    ($rs['IMGVISAOLUGAR'] ? ",img:'" . $rs['IMGVISAOLUGAR'] . "'" : '') .
                    ",status:'" . $rs["STATUS"] . "'" .
                    "},";
        }

        header("Content-type: text/txt");

        echo substr($cadeiras, 0, -1) . ']' . '||' . $imagem . '||' . $xScale . '||' . $yScale . '||' . $size;
    } else if ($_GET['action'] == 'load_pacotes') {
        $retorno = comboPacote('pacote_combo', $_SESSION['admin'],
                        $_POST['pacote_combo'], $_POST['local'], 3);
    } else if ($_GET['action'] == 'load_setor') {
        $query = "SELECT ID_APRESENTACAO FROM MW_PACOTE WHERE ID_PACOTE = " . $_POST["pacote"];
        $rs = executeSQL($mainConnection, $query, array(), true);
        if (!sqlErrors () && isset($rs["ID_APRESENTACAO"])) {
            $anoTemporada = $_POST['ano'];
            $idApresentacao = $rs["ID_APRESENTACAO"];
            $query = "SELECT ID_APRESENTACAO, DS_PISO FROM MW_APRESENTACAO
                      WHERE ID_EVENTO = (SELECT ID_EVENTO FROM MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = '1')
                      AND DT_APRESENTACAO = (SELECT DT_APRESENTACAO FROM MW_APRESENTACAO WHERE YEAR(DT_APRESENTACAO) = ? AND ID_APRESENTACAO = ? AND IN_ATIVO = '1')
                      AND HR_APRESENTACAO = (SELECT HR_APRESENTACAO FROM MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = '1')
                      AND IN_ATIVO = '1'
                      ORDER BY DS_PISO";
            $params = array($idApresentacao, $anoTemporada, $idApresentacao, $idApresentacao);
            print_r($query);
            $result = executeSQL($mainConnection, $query, $params);
            $combo = '<option value="">Selecione...</option>';
            while ($rs = fetchResult($result)) {
                $combo .= '<option value="' . $rs['ID_APRESENTACAO'] . '">' . utf8_encode2($rs['DS_PISO']) . '</option>';
            }
            $retorno = $combo;
        } else {
            $retorno = sqlErrors();
        }
    } else if ($_GET['action'] == 'add' and isset($_REQUEST['id'])) {
        // não existe na mw_reserva?
        $query = 'SELECT 1 FROM MW_RESERVA WHERE ID_APRESENTACAO = ? AND ID_CADEIRA = ?';
        $params = array($_POST['apresentacao'], $_REQUEST['id']);
        $rs = executeSQL($mainConnection, $query, $params, true);


        $id_usuario_teatro = executeSQL($mainConnection, "SELECT ID_CLIENTE FROM MW_BASE WHERE ID_BASE = ?", array($_POST['local']), true);
        $id_usuario_teatro = $id_usuario_teatro['ID_CLIENTE'];


        // não existe na mw_pacote_reserva?
        $query = "SELECT 1 FROM MW_PACOTE_RESERVA PR
                INNER JOIN MW_PACOTE_APRESENTACAO PA ON PA.ID_PACOTE = PR.ID_PACOTE
                WHERE PA.ID_APRESENTACAO = ? AND PR.ID_CADEIRA = ?
                AND PR.IN_STATUS_RESERVA IN ('A', 'S') AND PR.ID_CLIENTE <> ?";
        $params = array($_POST['apresentacao'], $_REQUEST['id'], $id_usuario_teatro);
        $rs2 = executeSQL($mainConnection, $query, $params, true);

        if (empty($rs) and empty($rs2)) {
            // não existe na tablugsala?
            $query = 'SELECT A.CODAPRESENTACAO, E.ID_BASE FROM MW_EVENTO E INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO AND A.IN_ATIVO = \'1\' WHERE A.ID_APRESENTACAO = ? AND E.IN_ATIVO = \'1\'';
            $params = array($_POST['apresentacao']);
            $rs = executeSQL($mainConnection, $query, $params, true);

            $codApresentacao = $rs['CODAPRESENTACAO'];
            $conn = getConnection($rs['ID_BASE']);

            $query = 'SELECT 1 FROM TABLUGSALA WHERE CODAPRESENTACAO = ? AND INDICE = ?';
            $params = array($codApresentacao, $_REQUEST['id']);
            $rs = executeSQL($conn, $query, $params, true);

            if (empty($rs)) {
                beginTransaction($mainConnection);
                beginTransaction($conn);
                $query = 'INSERT INTO MW_RESERVA (ID_APRESENTACAO,ID_CADEIRA,DS_CADEIRA,DS_SETOR,ID_SESSION,DT_VALIDADE) VALUES (?,?,?,?,?,DATEADD(MI, ?, GETDATE()))';
                $params = array($_POST['apresentacao'], $_REQUEST['id'], utf8_encode2($_POST['name']), utf8_encode2($_POST['setor']), session_id(), 150);
                $result = executeSQL($mainConnection, $query, $params);

                // gravou direito na mw_reserva?
                if ($result) {
                    $query = 'INSERT INTO TABLUGSALA
                              (CODAPRESENTACAO
                              ,INDICE
                              ,CODTIPBILHETE
                              ,CODCAIXA
                              ,CODVENDA
                              ,STAIMPRESSAO
                              ,STACADEIRA
                              ,CODUSUARIO
                              ,CODRESERVA
                              ,ID_SESSION)
                              VALUES
                              (?,?,?,?,?,?,?,?,?,?)';
                    $params = array($codApresentacao, $_REQUEST['id'], NULL, 255, NULL, 0, 'T', NULL, NULL, session_id());
                    $result = executeSQL($conn, $query, $params);
                }
            } else {
                $errors2[] = 'reservado';
            }
        } else {
            $errors2[] = 'reservado';
        }

        $errors = sqlErrors();
        if (empty($errors) and empty($errors2)) {// completou todas as operações com sucesso?
            commitTransaction($mainConnection);
            commitTransaction($conn);
            echo 'true?' . (isset($idsCadeiras) ? substr($idsCadeiras, 0, -1) : $_REQUEST['id']);
        } else {
            rollbackTransaction($mainConnection);
            rollbackTransaction($conn);

            $query = 'SELECT A.CODAPRESENTACAO, E.ID_BASE FROM MW_EVENTO E INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO AND A.IN_ATIVO = \'1\' WHERE A.ID_APRESENTACAO = ? AND E.IN_ATIVO = \'1\'';
            $params = array($_POST['apresentacao']);
            $rs = executeSQL($mainConnection, $query, $params, true);

            $codApresentacao = $rs['CODAPRESENTACAO'];
            $conn = getConnection($rs['ID_BASE']);


            echo (isset($idsCadeiras) ? substr($idsCadeiras, 0, -1) : $_REQUEST['id']) . '?Esta posição já foi ocupada.';
        }
    } else if ($_GET['action'] == 'delete' and isset($_REQUEST['apresentacao']) and isset($_REQUEST['id'])) {

        $query = 'SELECT A.CODAPRESENTACAO, E.ID_BASE FROM MW_EVENTO E INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO WHERE A.ID_APRESENTACAO = ?';
        $params = array($_REQUEST['apresentacao']);
        $rs = executeSQL($mainConnection, $query, $params, true);

        $codApresentacao = $rs['CODAPRESENTACAO'];
        $idBase = $rs['ID_BASE'];
        $conn = getConnection($idBase);

        beginTransaction($mainConnection);
        $query = 'DELETE FROM MW_RESERVA
                  WHERE ID_APRESENTACAO = ? AND ID_CADEIRA = ?
                  AND ID_SESSION = ?';
        $params = array($_REQUEST['apresentacao'], $_REQUEST['id'], session_id());
        $result = executeSQL($mainConnection, $query, $params);

        if ($result) {
            $query = 'SELECT A.CODAPRESENTACAO, E.ID_BASE FROM MW_EVENTO E INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO WHERE A.ID_APRESENTACAO = ?';
            $params = array($_REQUEST['apresentacao']);
            $rs = executeSQL($mainConnection, $query, $params, true);

            $codApresentacao = $rs['CODAPRESENTACAO'];
            $conn = getConnection($rs['ID_BASE']);

            beginTransaction($conn);

            $query ='DELETE FROM TABLUGSALA
                     WHERE CODAPRESENTACAO = ? AND INDICE = ? AND ID_SESSION = ?';
            $params = array($codApresentacao, $_REQUEST['id'], session_id());
            $result = executeSQL($conn, $query, $params);
        }

        $errors = sqlErrors();
        if (empty($errors)) {// completou todas as operações com sucesso?
            commitTransaction($mainConnection);
            commitTransaction($conn);
            echo 'true?' . (isset($idsCadeiras) ? substr($idsCadeiras, 0, -1) : $_REQUEST['id']);
        } else {
            rollbackTransaction($mainConnection);
            rollbackTransaction($conn);
            echo 'false';
        }
    } else if ($_GET['action'] == 'bloquear') {
        $query = "SELECT ID_CADEIRA, DS_CADEIRA FROM MW_RESERVA
                  WHERE ID_APRESENTACAO = ? AND ID_SESSION = ?";
        $params = array($_REQUEST['apresentacao'], session_id());
        $result = executeSQL($mainConnection, $query, $params);

        if(!hasRows($result)){
            echo 'true?Nenhum lugar selecionado.';
        }


        $id_usuario_teatro = executeSQL($mainConnection, "SELECT ID_CLIENTE FROM MW_BASE WHERE ID_BASE = ?", array($_POST['local']), true);
        $id_usuario_teatro = $id_usuario_teatro['ID_CLIENTE'];


        beginTransaction($mainConnection);

        while($reserva = fetchResult($result)){
            $query = "SELECT 1 FROM MW_PACOTE_RESERVA 
                      WHERE ID_CLIENTE = ? AND ID_PACOTE = ? AND ID_CADEIRA = ?
                      AND IN_STATUS_RESERVA NOT IN ('A','R')";
            $params = array($id_usuario_teatro, $_REQUEST['pacote'], $reserva['ID_CADEIRA']);
            $rs = executeSQL($mainConnection, $query, $params);
            if(hasRows($rs)){
                $query = "UPDATE MW_PACOTE_RESERVA SET IN_STATUS_RESERVA = 'A',
                         DT_HR_TRANSACAO = GETDATE()
                         WHERE ID_CLIENTE = ? AND ID_PACOTE = ? AND ID_CADEIRA = ?
                         AND IN_STATUS_RESERVA NOT IN ('A','R')";
                $params = array($id_usuario_teatro, $_REQUEST['pacote'], $reserva['ID_CADEIRA']);
                executeSQL($mainConnection, $query, $params);
            }else{
                $query = "INSERT INTO MW_PACOTE_RESERVA (ID_CLIENTE, ID_PACOTE, ID_CADEIRA,
                         IN_STATUS_RESERVA, IN_ANO_TEMPORADA, DS_LOCALIZACAO, DT_HR_TRANSACAO)
                         VALUES(?, ?, ?, 'A', ?, ?, GETDATE())";
                $params = array($id_usuario_teatro,
                                $_REQUEST['pacote'], $reserva['ID_CADEIRA'],
                                $_REQUEST['ano'], $reserva['DS_CADEIRA']);
                executeSQL($mainConnection, $query, $params);
            }

            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Bloquear lugares para a Gestão do Teatro');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

            //Remove da Reserva porque já foi efetivado
            $query = 'DELETE FROM MW_RESERVA WHERE ID_APRESENTACAO = ? AND ID_CADEIRA = ? AND ID_SESSION = ?';
            $params = array($_REQUEST['apresentacao'], $reserva['ID_CADEIRA'], session_id());
            executeSQL($mainConnection, $query, $params);

            
            $query = 'SELECT A.CODAPRESENTACAO, E.ID_BASE FROM MW_EVENTO E INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO WHERE A.ID_APRESENTACAO = ?';
            $params = array($_REQUEST['apresentacao']);
            $rs = executeSQL($mainConnection, $query, $params, true);

            $codApresentacao = $rs['CODAPRESENTACAO'];
            $conn = getConnection($rs['ID_BASE']);

            //Remove da tabLugSala
            $query ='DELETE FROM TABLUGSALA WHERE CODAPRESENTACAO = ? AND INDICE = ? AND ID_SESSION = ?';
            $params = array($codApresentacao, $reserva['ID_CADEIRA'], session_id());
            executeSQL($conn, $query, $params);

            $idsCadeiras .= $reserva['ID_CADEIRA'] . "|";

            $errors = sqlErrors();
        }      
        
        if(empty($errors)){
            commitTransaction($mainConnection);
            echo 'true?'.$idsCadeiras;
        }else{
            rollbackTransaction($mainConnection);
            echo 'false?'.$idsCadeiras;
        }

    }

    if (is_array($retorno)) {
        echo $retorno[0]['message'];
    } else {
        echo $retorno;
    }
}
?>