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

$tpl = new Template('relatorioLugaresVendidos.html');
$tpl->titulo = "Relatório de Lugares Vendidos";
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 272, true)) {
  $pagina = basename(__FILE__);
  $tpl->host = $_SERVER["HTTP_HOST"];
  // Carrega o combo de Local
  $rsLocal = executeSQL($mainConnection, 'SELECT DISTINCT B.ID_BASE, B.DS_NOME_TEATRO FROM MW_BASE B INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = B.ID_BASE WHERE AC.ID_USUARIO =' . $_SESSION['admin'] . '  AND B.IN_ATIVO = \'1\' ORDER BY B.DS_NOME_TEATRO');
  while ($locais = fetchResult($rsLocal)) {
    $tpl->idLocal = $locais["ID_BASE"];
    $tpl->dsLocal = strtoupper(utf8_encode2($locais['DS_NOME_TEATRO']));
    $tpl->parseBlock("BLOCK_LOCAL", true);
  }
  $tpl->show();
  if (sqlErrors ()) {
    echo sqlErros();
  }
}
?>