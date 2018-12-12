<?php
ob_start();

print_r(get_defined_vars());

$info = ob_get_clean();

$fileName = date('Y\_m\_d\_H\_i\_s\_u') . '.txt';
$logsPath = '../txtLogs/';

$handle = fopen($logsPath . $fileName, 'x');

fwrite($handle, $info);

fclose($handle);
?>