<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 9, true)) {

 if ($_GET['action'] == 'update' and isset($_GET['codestabelecimento'])) { /*------------ UPDATE ------------*/

    $_POST['ativo'] = $_POST['ativo'] == 'on' ? 1 : 0;

    if($_POST['ativo'] == 1)
    {
        $query = "UPDATE MW_CONTA_IPAGARE SET NM_CONTA_ESTABELECIMENTO = ?, IN_ATIVO = 1, CD_SEGURANCA = ? WHERE CD_ESTABELECIMENTO = ?";

        $query2 = "UPDATE MW_CONTA_IPAGARE SET IN_ATIVO = 0 WHERE CD_ESTABELECIMENTO <> ". $_GET['codestabelecimento'];

        $params = array(utf8_encode2($_POST['nome']), $_POST['cdSeguranca'], $_GET['codestabelecimento']);
        
        if (executeSQL($mainConnection, $query, $params)) {
                executeSQL($mainConnection, $query2, $params);
                
                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Conta IPAGARE');
                $log->__set('parametros', $params);
                $log->__set('log', $query);
                $log->save($mainConnection);

                $retorno = 'true?codestabelecimento='.$_GET['codestabelecimento'];
        } else {
                $retorno = sqlErrors();
        }
    }
    else
    {
        $query = "UPDATE MW_CONTA_IPAGARE SET NM_CONTA_ESTABELECIMENTO = ?, CD_SEGURANCA = ? WHERE CD_ESTABELECIMENTO = ?";

        $params2 = array(utf8_encode2($_POST['nome']), $_POST['cdSeguranca'], $_GET['codestabelecimento']);
        
        if (executeSQL($mainConnection, $query, $params2)) {

                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Conta IPAGARE');
                $log->__set('parametros', $params2);
                $log->__set('log', $query);
                $log->save($mainConnection);

                $retorno = 'true?codestabelecimento='.$_GET['codestabelecimento'];
        } else {
                $retorno = sqlErrors();
        }
    }

if (is_array($retorno)) {
	echo $retorno[0]['message'];
} else {
	echo $retorno;
}

}
}
?>