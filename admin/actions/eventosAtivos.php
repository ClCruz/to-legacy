<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 19, true)) {
  if ($_GET['action'] == 'update' and isset($_GET['codevento'])) {
    /* ------------ UPDATE ------------ */
    $_POST["in_ativo"] = ($_POST["in_ativo"] == 'on') ? 1 : 0;

    $query = "UPDATE MW_APRESENTACAO SET IN_ATIVO = ? WHERE ID_APRESENTACAO = ?";
    $params = array($_POST['in_ativo'], $_POST['codevento']);

    if (executeSQL($mainConnection, $query, $params)) {
      $log = new Log($_SESSION['admin']);
      $log->__set('funcionalidade', 'Eventos Ativos e Inativos');
      $log->__set('parametros', $params);
      $log->__set('log', $query);
      $log->save($mainConnection);

      $queryApresentacao = "SELECT B.DS_NOME_BASE_SQL, I.CODAPRESENTACAO
	FROM MW_APRESENTACAO I
	INNER JOIN MW_EVENTO E ON E.ID_EVENTO = I.ID_EVENTO
	INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
        WHERE I.ID_APRESENTACAO = ?";
      $apresentacao = executeSQL($mainConnection, $queryApresentacao, array($_POST['codevento']), true);
      $queryTabApresentacao = "UPDATE " . $apresentacao["DS_NOME_BASE_SQL"] . "..TABAPRESENTACAO
			SET STAATIVOWEB = CASE " . $_POST["in_ativo"] . " WHEN 1 THEN 'S' ELSE 'N' END
			WHERE CODAPRESENTACAO = " . $apresentacao["CODAPRESENTACAO"];
      $result = executeSQL($mainConnection, $queryTabApresentacao, array());
      if($result == true){
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Eventos Ativos e Inativos');
        $log->__set('log', $queryTabApresentacao);
        $log->save($mainConnection);

        $retorno = 'true?codevento=' . $_GET['codevento'];
      }else{
        $retorno = "Houve um erro ao replicar a atualização na Bilheteria!";
      }
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
?>