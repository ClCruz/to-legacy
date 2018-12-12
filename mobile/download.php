<?php

require_once('../settings/settings.php');
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

$conn = getConnection($_POST['cboTeatro']);

$query ="SELECT B.NUMSEQ, B.CODAPRESENTACAO, B.INDICE, B.CODBAR, B.STATUSINGRESSO,
            B.DATHRENTRADA
         FROM TABCONTROLESEQVENDA A
         INNER JOIN TABCONTROLESEQVENDA B ON B.CODAPRESENTACAO = A.CODAPRESENTACAO AND
            B.INDICE = A.INDICE AND B.STATUSINGRESSO = A.STATUSINGRESSO
         INNER JOIN TABAPRESENTACAO AP ON AP.CODAPRESENTACAO = A.CODAPRESENTACAO
         WHERE AP.DATAPRESENTACAO = CONVERT(DATETIME, ?, 112) AND AP.HORSESSAO = ? AND AP.CODPECA = ?";
$params = array($_POST['cboApresentacao'], $_POST['cboHorario'], $_POST['cboPeca']);
$result = executeSQL($conn, $query, $params);

if (hasRows($result)) {
    // pode retornar 2 linhas no caso de complemento de ingressos, mas como sao o mesmo ingresso podem ser tratados como 1 so
    $retorno = array();
    while ($rs = fetchResult($result)) {
        $retorno[] = array('numseq' => $rs['NUMSEQ'],
                           'CodApresentacao' => $rs['CODAPRESENTACAO'],
                           'Indice' => $rs['INDICE'],
                           'codbar' => $rs['CODBAR'],
                           'statusingresso' => $rs['STATUSINGRESSO'],
                           'DatHrEntrada' => $rs['DATHRENTRADA']);
    }
} else {
    $retorno = array('class' => 'falha', 'mensagem' => "Código do ingresso não existe.\nAcesso não permitido.");
}
echo json_encode($retorno);

?>