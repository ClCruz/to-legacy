<?php
require_once('../settings/Utils.php');
require_once("../settings/functions.php");
$mainConnection = mainConnection();

// Host do Reporting Services
$report_host = "http://138.36.216.94";
// URL do ReportViewer
$report_server = "/ReportServer/Pages/ReportViewer.aspx?";
// Pasta onde esta o relatório no ReportServer
$report_folder = "%2fRSCompreingressos";
// Nome do arquivo do relatório
$report_name = "%2f01-REL_LUGARES_VENDIDOS";
// URL completa para execução do relatório
$url_report = $report_host.$report_server.$report_folder.$report_name;
$params = array(
    1 => "PARAM_PECA",
    2 => "PARAM_SALA",
    3 => "PARAM_DATA_INI",
    4 => "PARAM_DATA_FIM",
    5 => "PARAM_CLIENTE",
    6 => "PARAM_CPF",
    7 => "PARAM_RG",
    8 => "rc:Parameters",
    9 => "rs:Command",
    10 => "PARAM_HR_INI",
    11 => "PARAM_HR_FIM",
    12 => "cboTeatro"
);
foreach ($params as $key => $value) {
  if (!empty($_POST[$value])) {
    $param = $_POST[$value];
    if($key == 3 || $key == 4){
      $param = getDateF($param);
    }else if($key == 6 || $key == 7){
      $param = cleanDocuments($param);
    }else if($key == 2){
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
