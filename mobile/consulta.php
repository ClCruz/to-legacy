<?php

require_once('../settings/settings.php');
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if ($_POST['action'] == 'cboTeatro') {
    $query = "SELECT DISTINCT B.ID_BASE, B.DS_NOME_TEATRO
              FROM MW_BASE B
              INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = B.ID_BASE
              WHERE AC.ID_USUARIO = ? AND B.IN_ATIVO = '1'
              ORDER BY B.DS_NOME_TEATRO";
    $result = executeSQL($mainConnection, $query, array($_POST['admin']));

    $combo = array();
    while ($rs = fetchResult($result)) {
        $combo[] = array("id" => $rs['ID_BASE'], "value" => utf8_encode2($rs['DS_NOME_TEATRO']));
    }        
    echo json_encode($combo);
    die();
} elseif ($_POST['action'] == 'cboPeca' and isset($_POST['cboTeatro'])) {
    $conn = getConnection($_POST['cboTeatro']);
    $query = "EXEC SP_PEC_CON009;8 ?, ?";
    $params = array($_POST['admin'], $_POST['cboTeatro']);
    $result = executeSQL($conn, $query, $params);
    $json = array();
    while ($rs = fetchResult($result)) {
        $json[] = array("id" => $rs["CodPeca"], "value" => utf8_encode2($rs["nomPeca"]));
    }
    echo json_encode($json);
    die();
} elseif ($_POST['action'] == 'cboApresentacao' and isset($_POST['cboTeatro']) and isset($_POST['cboPeca'])) {
    $conn = getConnection($_POST['cboTeatro']);
    $query = "SELECT tbAp.DatApresentacao
              from tabApresentacao tbAp (nolock)
              inner join tabPeca tbPc (nolock)
                on tbPc.CodPeca = tbAp.CodPeca
              inner join ci_middleway..mw_acesso_concedido iac (nolock)
                on iac.id_base = ?
                and iac.id_usuario = ?
                and iac.CodPeca = tbAp.CodPeca
              where tbPc.CodPeca = ?
              -- comentar para homologacao
              --  AND CONVERT(DATETIME, CONVERT(VARCHAR(8), TBAP.DATAPRESENTACAO, 112) + ' ' + TBAP.HORSESSAO) >= CONVERT(DATETIME, CONVERT(VARCHAR(8), DATEADD(DAY, -1, GETDATE()), 112) + ' 22:00')
              --  AND TBAP.DATAPRESENTACAO <= GETDATE()
              ----------------------------
              group by tbAp.DatApresentacao
              order by tbAp.DatApresentacao";
    $params = array($_POST['cboTeatro'], $_POST['admin'], $_POST['cboPeca']);
    $result = executeSQL($conn, $query, $params);
    $json = array();
    while ($rs = fetchResult($result)) {
        $json[] = array('id' => $rs["DatApresentacao"]->format("Ymd"),
                        'value' => $rs["DatApresentacao"]->format("d/m/Y"));
    }
    echo json_encode($json);
    die();
} elseif ($_POST['action'] == 'cboHorario' and isset($_POST['cboTeatro']) and isset($_POST['cboPeca']) and isset($_POST['cboApresentacao'])) {
    $conn = getConnection($_POST['cboTeatro']);
    $query = "SELECT HorSessao
              from tabApresentacao tbAp (nolock)
              inner join tabPeca tbPc (nolock)
                on tbPc.CodPeca = tbAp.CodPeca
              inner join ci_middleway..mw_acesso_concedido iac (nolock)
                on iac.id_base = ?
                and iac.id_usuario = ?
                and iac.CodPeca = tbAp.CodPeca
              where tbPc.CodPeca = ?
              -- comentar para homologacao
              --  AND CONVERT(DATETIME, CONVERT(VARCHAR(8), TBAP.DATAPRESENTACAO, 112) + ' ' + TBAP.HORSESSAO) >= CONVERT(DATETIME, CONVERT(VARCHAR(8), DATEADD(DAY, -1, GETDATE()), 112) + ' 22:00')
              ----------------------------
                AND TBAP.DATAPRESENTACAO = CONVERT(DATETIME, ?, 112)
              group by tbAp.HorSessao
              order by tbAp.HorSessao";
    $params = array($_POST['cboTeatro'], $_POST['admin'], $_POST['cboPeca'], $_POST['cboApresentacao']);
    $result = executeSQL($conn, $query, $params);
    $json = array();
    while ($rs = fetchResult($result)) {
        $json[] = array('id' => $rs["HorSessao"], 'value' => $rs["HorSessao"]);
    }
    echo json_encode($json);
    die();
} elseif ($_POST['action'] == 'cboSetor' and isset($_POST['cboTeatro']) and isset($_POST['cboPeca']) and isset($_POST['cboApresentacao']) and isset($_POST['cboHorario'])) {  
  $conn = getConnection($_POST['cboTeatro']);
  $query = "SELECT DB_NAME() as BASE";
  $base = executeSQL($conn, $query, array(), true);
  $query = "SP_REL_BORDERO_VENDAS;7 ?, ?, ?, ?";
  $params = array($_POST['cboApresentacao'], $_POST['cboPeca'], $_POST['cboHorario'], $base['BASE']);
  $conn = getConnectionTsp();
  $result = executeSQL($conn, $query, $params);
  $json = array();
  while ($rs = fetchResult($result)) {
      $json[] = array('id' => $rs["codsala"], 'value' => utf8_encode2($rs["nomSala"]));
  }
  echo json_encode($json);
  die();
} elseif ($_POST['action'] == 'conDisponiveis' and isset($_POST['cboTeatro']) and isset($_POST['cboApresentacao']) and isset($_POST['cboHorario']) and isset($_POST['cboPeca'])) {
    $conn = getConnection($_POST['cboTeatro']);
    $query = "WITH RESULTADO AS (
                SELECT DISTINCT INDICE
                FROM TABCONTROLESEQVENDA C
                INNER JOIN TABAPRESENTACAO A ON A.CODAPRESENTACAO = C.CODAPRESENTACAO
                WHERE A.DATAPRESENTACAO = CONVERT(DATETIME, ?, 112) AND A.HORSESSAO = ? AND A.CODPECA = ?
                    AND C.STATUSINGRESSO <> 'E'
                )
                SELECT COUNT(1) AS QTDTOTAL FROM RESULTADO";
    $params = array($_POST['cboApresentacao'],$_POST['cboHorario'],$_POST['cboPeca']);
    $qtdTotal = executeSQL($conn, $query, $params, true);

    $query = "WITH RESULTADO AS (
                SELECT DISTINCT INDICE
                FROM TABCONTROLESEQVENDA C
                INNER JOIN TABAPRESENTACAO A ON A.CODAPRESENTACAO = C.CODAPRESENTACAO
                WHERE A.DATAPRESENTACAO = CONVERT(DATETIME, ?, 112) AND A.HORSESSAO = ? AND A.CODPECA = ?
                    AND STATUSINGRESSO = 'U'
              )
              SELECT COUNT(1) AS QTDUTILIZADO FROM RESULTADO";
    $params = array($_POST['cboApresentacao'],$_POST['cboHorario'],$_POST['cboPeca']);
    $qtdUtilizado = executeSQL($conn, $query, $params, true);
    
    $json = array('Total' => $qtdTotal["QTDTOTAL"], 'Utilizados' => $qtdUtilizado["QTDUTILIZADO"]);

    echo json_encode($json);
    die();
}
?>