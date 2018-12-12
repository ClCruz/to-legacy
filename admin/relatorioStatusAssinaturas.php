<?php

/**
 * Consulta para Relatório de Lugares Vendidos
 *
 * Esta página implementa a consulta de dados para utilizar na passagem de
 * paramêtros para o relatório de lugares vendidos.
 *
 * @author Edicarlos Barbosa <edicarlos.barbosa@cc.com.br>
 * @version 1.0
 */
require_once('../settings/Template.class.php');
require_once('../settings/functions.php');

$tpl = new Template('relatorioStatusAssinaturas.html');
$tpl->titulo = "Status das Assinaturas";
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 352, true)) {
  $pagina = basename(__FILE__);
  $tpl->host = $_SERVER["HTTP_HOST"];
  
  // Carrega o combo de Local
  $rsLocal = executeSQL($mainConnection, 'SELECT DISTINCT B.DS_NOME_BASE_SQL, B.DS_NOME_TEATRO FROM MW_BASE B INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = B.ID_BASE WHERE AC.ID_USUARIO =' . $_SESSION['admin'] . '  AND B.IN_ATIVO = \'1\' ORDER BY B.DS_NOME_TEATRO');
  while ($locais = fetchResult($rsLocal)) {
    $tpl->idLocal = $locais["DS_NOME_BASE_SQL"];
    $tpl->dsLocal = strtoupper(utf8_encode2($locais['DS_NOME_TEATRO']));
    $tpl->parseBlock("BLOCK_LOCAL", true);
  }
  
  // Carrega o combo de Status
  $rsStatus = executeSQL($mainConnection,"SELECT DISTINCT
                                             in_status_reserva
                                            ,CASE in_status_reserva
                                                WHEN 'A' THEN 'Aguardando ação do Assinante'
                                                WHEN 'C' THEN 'Assinatura cancelada'
                                                WHEN 'R' THEN 'Assinatura renovada'
                                                WHEN 'S' THEN 'Solicitação de troca efetuada'
                                                WHEN 'T' THEN 'Troca efetuada'
                                            END AS ds_status_reserva
                                        FROM mw_pacote_reserva");
  while ($status = fetchResult($rsStatus)) {
    $tpl->idStatus = $status["in_status_reserva"];
    $tpl->dsStatus = $status['ds_status_reserva'];
    $tpl->parseBlock("BLOCK_STATUS", true);
  }
  $tpl->show();
  if (sqlErrors ()) {
    echo sqlErros();
  }
}
?>