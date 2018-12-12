<?php
require_once('../settings/functions.php');
session_start();

$mainConnection = mainConnection();


$selectInfoVB = 'SELECT E.ID_BASE, AB.CODTIPBILHETE, R.ID_APRESENTACAO, R.ID_APRESENTACAO_BILHETE, AB.DS_TIPO_BILHETE
                    FROM MW_APRESENTACAO_BILHETE AB
                    INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = AB.ID_APRESENTACAO
                    INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                    INNER JOIN MW_RESERVA R ON R.ID_APRESENTACAO_BILHETE = AB.ID_APRESENTACAO_BILHETE
                    WHERE R.ID_SESSION = ?
                    GROUP BY E.ID_BASE, AB.CODTIPBILHETE, R.ID_APRESENTACAO, R.ID_APRESENTACAO_BILHETE, AB.DS_TIPO_BILHETE';


$queryTipoBilhete = "SELECT STATIPBILHMEIAESTUDANTE, QTDVENDAPORLOTE FROM TABTIPBILHETE WHERE CODTIPBILHETE = ? AND STATIPBILHETE = 'A'";

$queryIsBilheteMeiaEstudante = "SELECT COUNT(1) AS BILHETE_MEIA FROM TABTIPBILHETE WHERE STATIPBILHMEIAESTUDANTE = 'S' AND STATIPBILHETE = 'A' AND CODTIPBILHETE = ?";

$queryIsLote = "SELECT COUNT(1) AS BILHETE_LOTE FROM TABTIPBILHETE WHERE STATIPBILHMEIAESTUDANTE = 'N' AND QTDVENDAPORLOTE > 0 AND STATIPBILHETE = 'A' AND CODTIPBILHETE = ?";

$result = executeSQL($mainConnection, $selectInfoVB, array(session_id()));

while ($rs = fetchResult($result)) {

    $conn = getConnection($rs['ID_BASE']);

    //identifica o bilhete como meia ou lote
    $rs1 = executeSQL($conn, $queryTipoBilhete, array($rs['CODTIPBILHETE']), true);

    //MEIA ESTUDANTE
    if ($rs1['STATIPBILHMEIAESTUDANTE'] == 'S') {
        //checar se o bilhete atual é meia estudante e está no carrinho
        $rs3 = executeSQL($conn, $queryIsBilheteMeiaEstudante, array($rs['CODTIPBILHETE']), true);

        //checar se o numero disponivel de meia estudante esta zerado, ou seja, se a pessoa selecionou um bilhete nao disponivel
        if ($rs3['BILHETE_MEIA'] == 1 and getTotalMeiaEntradaDisponivel($rs['ID_APRESENTACAO']) < 0) {
            
            $erro = 'Quantidade de meia entrada de estudante superou a cota disponível, altere um ou mais tipos de ingresso para efetuar a compra.';

        }

    //LOTE
    } else if ($rs1['STATIPBILHMEIAESTUDANTE'] == 'N' and $rs1['QTDVENDAPORLOTE'] > 0) {
        //checar se o bilhete atual é de lote e está no carrinho
        $rs3 = executeSQL($conn, $queryIsLote, array($rs['CODTIPBILHETE']), true);

        $disponiveis = getTotalLoteDisponivel($rs['ID_APRESENTACAO_BILHETE']);

        //checar se o numero disponivel de lote esta zerado, ou seja, se a pessoa selecionou um bilhete nao disponivel
        if ($rs3['BILHETE_LOTE'] == 1 and $disponiveis < 0) {

            $rs2 = executeSQL($conn, "SELECT COUNT(1) FROM TABLUGSALA WHERE CODTIPBILHETE = ? AND ID_SESSION = ?", array($rs['CODTIPBILHETE'], session_id()), true);

            $comprando = $rs2[0];

            $disponiveis = (($comprando + $disponiveis) < 0 ? 0 : ($comprando + $disponiveis));

            if ($_POST['pos']) {
                $erro = 'Neste momento existe(m) apenas a quantidade disponível de '.$disponiveis.' ingresso(s) "'.$rs['DS_TIPO_BILHETE'].'". Selecione outro tipo de ingresso para efetuar a compra.';
            } else {
                $erro = 'Neste momento existe(m) apenas a quantidade disponível de '.$disponiveis.' ingresso(s) "'.$rs['DS_TIPO_BILHETE'].'", retorne até a etapa "2. Tipo de Ingresso" e selecione outro tipo de ingresso para efetuar a compra.<br/><br/>
                        Importante: a quantidade disponível poderá ser alterada de acordo com a procura do evento.';
            }

        }
    }
}

if ($erro != '') {
    echo $erro;
    if (!$_POST['pos'])
        die();
}
?>