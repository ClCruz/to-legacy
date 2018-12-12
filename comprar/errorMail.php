<?php
require_once('../settings/functions.php');
$subject = 'Erro no Sistema';

//define the body of the message.
ob_start(); //Turn on output buffering
?>
<p>&nbsp;</p>
<p>Último erro no banco de dados:</p>
<p><pre><?php print_r(sqlErrors()); ?></pre></p>
<p>&nbsp;</p>
<p>Dump de variaveis:</p>
<p><pre><?php print_r(get_defined_vars()); ?></pre></p>
<p>&nbsp;</p>
<?php
//copy current buffer contents into $message variable and delete current output buffer
$message = ob_get_clean();

sendErrorMail('Erro no Sistema', $message);
?>