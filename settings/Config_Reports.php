<?php
/* 
 * Este arquivo contém a configuração utilizada nos relatórios
 */

$is_producao = true;

// Host do Reporting Services
define("REPORT_HOST", ($is_producao) ? "http://138.36.216.94" : "http://10.0.9.42");

?>
