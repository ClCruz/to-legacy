<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
foreach($_POST as $key => $val) {
	$_POST[$key] = utf8_encode2($val);
}

$jsonPOST = json_encode($_POST);

$query = 'INSERT INTO MW_LOG_IPAGARE (DT_OCORRENCIA, ID_CLIENTE, DS_LOG)
			 VALUES (GETDATE(), ?, ?)';
$params = array($_SESSION['user'], $jsonPOST);

$resultiPagare = executeSQL($mainConnection, $query, $params);
$errorsiPagare = sqlErrors();
if (!$resultiPagare or !empty($errorsiPagare)) {
	$subject = 'Erro no LOG do iPagare'; 
	
	$namefrom = multiSite_getTitle();
	$from = '';
	
	//define the body of the message.
	ob_start(); //Turn on output buffering
	?>
	<p>&nbsp;</p>
	<p>Array de erros do LOG:</p>
	<p><pre><?php print_r(sqlErrors()); ?></pre></p><br>
	<p>Par&acirc;metros da query:</p>
	<p><pre><?php print_r($params); ?></pre></p>
	<p>&nbsp;</p>
	<?php
	//copy current buffer contents into $message variable and delete current output buffer
	$message = ob_get_clean();
	
	$cc = array('Emerson => emerson@intuiti.com.br', 'Jefferson => jefferson.ferreira@intuiti.com.br', 'Edicarlos => edicarlos.barbosa@intuiti.com.br');
	
	authSendEmail($from, $namefrom, 'gabriel.monteiro@intuiti.com.br', 'Gabriel', $subject, $message, $cc);
}
?>