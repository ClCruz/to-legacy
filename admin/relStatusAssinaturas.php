<?php
require_once('../settings/Utils.php');
require_once("../settings/functions.php");
$mainConnection = mainConnection();

function getDateTraco($date) {
  if ($date != "") {
    $data = explode("/", $date);
  } else {
    $data = explode("/", date('y/m/d'));
  }
  $retorno = $data[2] . "-" . $data[1] . "-" . $data[0];
  return $retorno;
}

// Host do Reporting Services
$report_host = "http://186.237.201.154";
// URL do ReportViewer
$report_server = "/ReportServer/Pages/ReportViewer.aspx?";
// Pasta onde esta o relatório no ReportServer
$report_folder = "%2fRSCompreingressos";
// Nome do arquivo do relatório
$report_name = "%2f04-REL_ASSINATURAS";
// URL completa para execução do relatório
$url_report = $report_host.$report_server.$report_folder.$report_name;
$params = array(
    1 => "PARAM_STATUS",
    2 => "PARAM_ANO",
	3 => "PARAM_DTTRAN",
    4 => "cboTeatro",
    5 => "rc:Parameters",
    6 => "rs:Command"    
);
foreach ($params as $key => $value) {
  if (!empty($_POST[$value])) {
    $param = $_POST[$value];
    if($key == 3){
      $param = getDateTraco($_POST[$value]);
   } 
//	}else if($key == 12){
//      $strQuery = "SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = " . $_POST[$value];
//      $stmt = executeSQL($mainConnection, $strQuery, array(), true);
//      $param = $stmt["DS_NOME_BASE_SQL"];
//    }
    $url_report .= "&". $value ."=". $param;
  }
}
header("Location: ". $url_report);
?>
