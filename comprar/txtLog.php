-----------------------------
POST: <?php print_r($_POST); ?>
-----------------------------
<?php
$log = ob_get_clean();

$filePath = 'c:\\inetpub\\wwwroot\\compreingressos\\';
$fileName = 'txtLog('.$_POST['codigo_pedido'].') - '.date('s').'.txt';

if ($file = fopen($filePath.$fileName, 'w')) {
	fwrite($file, $log);
	fclose($file);
}
?>