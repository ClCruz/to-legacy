<?php
echo '...';
include('../settings/functions.php');

echo 'gerando conexão...';
$mainConnection = mainConnection();

echo 'iniciando transação...';
beginTransaction($mainConnection);

echo 'executando query...';
executeSQL($mainConnection, 'INSERT INTO MW_USUARIO (CD_LOGIN, DS_NOME, DS_EMAIL, IN_ATIVO, IN_ADMIN, CD_PWW)
										VALUES (\'testeT\', \'testeT\', \'testeT@email.com\', 1, 1, \'testeT\')');

echo 'finalizando transação com rollback...';
rollbackTransaction($mainConnection);

echo 'OK!'
?>