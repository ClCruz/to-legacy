<?php
require_once('../settings/Utils.php');
require_once("../settings/functions.php");
$mainConnection = mainConnection();

// Host do Reporting Services
$report_host = "http://186.237.201.154";
// URL do ReportViewer
$report_server = "/ReportServer/Pages/ReportViewer.aspx?";
// Pasta onde esta o relatório no ReportServer
$report_folder = "%2fRSCompreingressos";
// Nome do arquivo do relatório
$report_name = "%2f03-REL_CLIENTES";
// URL completa para execução do relatório
$url_report = $report_host.$report_server.$report_folder.$report_name;
$params = array(
    1 => "cboTeatro",
    2 => "PARAM_PECA",
    3 => "rc:Parameters",
    4 => "rs:Command"    
);
foreach ($params as $key => $value) {
  if (!empty($_POST[$value])) {
    $param = $_POST[$value];
    if($key == 2){
      $param = str_replace("TODOS", "-1", $param);
    }else if($key == 12){
      $strQuery = "SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = " . $_POST[$value];
      $stmt = executeSQL($mainConnection, $strQuery, array(), true);
      $param = $stmt["DS_NOME_BASE_SQL"];
    }
    $url_report .= "&". $value ."=". $param;
  }
}
header("Location: ". $url_report);
?>
