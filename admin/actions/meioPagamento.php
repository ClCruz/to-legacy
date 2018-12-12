<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 260, true)) {

  $_POST['in_ativo'] = $_POST['in_ativo'] == 'on' ? 1 : 0;
  $_POST['in_ativo_assinatura'] = $_POST['in_ativo_assinatura'] == 'on' ? 1 : 0;
  $nomeCartaoSite = substr(trim(utf8_decode($_POST["nm_cartao_site"])), 0, 25);

  if ($_GET['action'] == 'update' and isset($_GET['idMeioPagamento'])) { /* ------------ UPDATE ------------ */

    if ($_POST['in_ativo_assinatura']) {
      $rs = executeSQL($mainConnection, 'SELECT 1 FROM MW_ASSINATURA_MEIO_PAGAMENTO WHERE ID_MEIO_PAGAMENTO = ?', array($_GET['idMeioPagamento']), true);

      if (empty($rs)) {
        die('Esse meio de pagamento não é permitido na compra de assinaturas.');
      }
    }

    $query = 'UPDATE MW_ASSINATURA_MEIO_PAGAMENTO SET IN_ATIVO = ? WHERE ID_MEIO_PAGAMENTO = ?';
    $params = array($_POST['in_ativo_assinatura'], $_GET['idMeioPagamento']);

    executeSQL($mainConnection, $query, $params);

    $log = new Log($_SESSION['admin']);
    $log->__set('funcionalidade', 'Habilitar meio de pagamento para WEB');
    $log->__set('parametros', $params);
    $log->__set('log', $query);
    $log->save($mainConnection);

    $_POST['hr_anteced'] = (($_POST['hr_anteced'] == 0 or $_POST['hr_anteced'] == '') ? null : $_POST['hr_anteced']);

    $query = "UPDATE MW_MEIO_PAGAMENTO SET IN_ATIVO = ?, NM_CARTAO_EXIBICAO_SITE = ?, QT_HR_ANTECED = ? WHERE ID_MEIO_PAGAMENTO = ?";
    $params = array($_POST['in_ativo'], $nomeCartaoSite, $_POST['hr_anteced'], $_GET['idMeioPagamento']);

    if (executeSQL($mainConnection, $query, $params)) {
      $log = new Log($_SESSION['admin']);
      $log->__set('funcionalidade', 'Habilitar meio de pagamento para WEB');
      $log->__set('parametros', $params);
      $log->__set('log', $query);
      $log->save($mainConnection);
      
      $retorno = 'true?idMeioPagamento=' . $_GET['idMeioPagamento'];
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