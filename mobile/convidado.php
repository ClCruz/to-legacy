<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
require_once('../settings/settings.php');
require_once('../settings/functions.php');
require_once('../settings/Log.class.php');
require_once('../settings/Template.class.php');

$mainConnection = mainConnection();

if($_GET['action'] == 'confirm') {
  $query = "UPDATE MW_CONVIDADO SET IN_CONFIRMADO = 1 WHERE id_convidado = ?";
  $param = array($_GET['id']);
  executeSQL($mainConnection, $query, $param);
  $tpl = new Template('templates/confirmConvite.html');
  $tpl->show();
  die();
}

if($_POST['action'] != 'del'){
    $query = "SELECT ISNULL(id_apresentacao, 0) AS id_apresentacao
                  FROM MW_APRESENTACAO A
                  INNER JOIN MW_EVENTO E ON E.CODPECA = ? AND E.ID_BASE = ?
                  WHERE A.DT_APRESENTACAO = CONVERT(DATETIME, ?, 112) AND A.HR_APRESENTACAO = ? 
                        AND A.ID_EVENTO = E.ID_EVENTO";

    $params = array($_POST["cboPeca"], $_POST["cboTeatro"], $_POST["cboApresentacao"], $_POST["cboHorario"]);
    $rs = executeSQL($mainConnection, $query, $params, true);
}

if ($_POST['action'] == 'add') {      

    if($rs["id_apresentacao"] != 0){

      //Busca Total Utilizado p/ Apresentacao
      $query = "SELECT SUM(qt_lugar) AS qt_lugar FROM MW_CONVIDADO WHERE id_apresentacao = ?";
      $param = array($rs['id_apresentacao']);
      $qt_utilizado = executeSQL($mainConnection, $query, $param, true);

      $query = "SELECT ISNULL(qt_ingresso, 0) AS qt_ingresso FROM MW_COTA_CONVITE WHERE id_apresentacao = ?";
      $param = array($rs['id_apresentacao']);
      $qt_total = executeSQL($mainConnection, $query, $param, true);

      if($qt_total['qt_ingresso'] != 0){

        $saldo = $qt_total['qt_ingresso'] - ($qt_utilizado['qt_lugar'] + $_POST['qtdeIngresso']);

        if($saldo >= 0){

          $query = 'INSERT INTO MW_CONVIDADO (id_apresentacao, nm_convidado, cd_cpf, cd_celular, cd_email, ds_convidado_por, ds_tipo_convite, qt_lugar, id_usuario, dt_atualizacao) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE()); SELECT SCOPE_IDENTITY() as ID;';
          $params = array($rs["id_apresentacao"], $_POST['convidado'], $_POST['cpf'], $_POST['celular'], $_POST['email'] ,utf8_encode2($_POST['convidadoPor']), $_POST['tipoConvite'], $_POST['qtdeIngresso'], $_POST['idUsuario']);

          $result = executeSQL($mainConnection, $query, $params);

          sqlsrv_next_result($result);
          $rsid = fetchResult($result);
          $id_convidado = $rsid['ID'];

          $subject = utf8_decode('Convite para Evento');
          $namefrom = utf8_decode(multiSite_getTitle());
          $from = multiSite_getEmail("compreingressos@gmail");

          $caminhoHtml = getwhitelabeltemplate("email:buyer");

          $tpl = new Template($caminhoHtml);
          $tpl->nome_cliente = utf8_encode2($_POST['convidado']);
          $tpl->link_convite = multiSite_getURICompra("/mobile/convidado.php?action=confirm&id=". $id_convidado);

          $query = "select e.ds_evento, a.dt_apresentacao, a.hr_apresentacao, b.ds_nome_teatro, m.ds_municipio, es.sg_estado
                    from mw_apresentacao a
                    inner join mw_evento e on a.id_evento = e.id_evento
                    inner join mw_base b on b.id_base = e.id_base
                    inner join mw_local_evento le on le.id_local_evento = e.id_local_evento
                    inner join mw_municipio m on m.id_municipio = le.id_municipio
                    inner join mw_estado es on es.id_estado = m.id_estado
                    where a.id_apresentacao = ?";
          $param = array($rs["id_apresentacao"]);
          $dados = executeSQL($mainConnection, $query, $param, true);

          $tpl->item_evento = utf8_encode2($dados['ds_evento']);
          $tpl->item_nome_teatro = utf8_encode2($dados['ds_nome_teatro']);
          $tpl->item_teatro_cidade = utf8_encode2($dados['ds_municipio']);
          $tpl->item_teatro_estado = $dados['sg_estado'];
          $tpl->item_tipo_bilhete = utf8_encode2($_POST['tipoConvite']);
          $tpl->item_data = $dados['dt_apresentacao']->format('d/M/y');
          $tpl->item_hora = $dados['hr_apresentacao'];
          $tpl->item_qtde = "Ingressos: ". $_POST['qtdeIngresso'];

          ob_start();
          $tpl->show();
          $message = ob_get_clean();
          $successMail = authSendEmail($from, $namefrom, $_POST['email'], $_POST['convidado'], $subject, utf8_decode($message), array(), array(), 'iso-8859-1', $barcodes);
      
          $json = array('retorno' => 'OK', 'mensagem' => 'Cadastro efetuado com sucesso.');        
        } else {
          $json = array('retorno' => 'falha', 'mensagem' => 'A Qtde. de Ingresso ultrapassa a cota disponível para a Apresentação.');  
        }
      } else{
        $json = array('retorno' => 'falha', 'mensagem' => 'Não existe cota de convites para a Apresentação.');
      }
    }else{
        $json = array('retorno' => 'falha', 'mensagem' => 'Apresentação Inválida.');
    }   
     
} else if($_POST['action'] == 'del'){

    $query = 'DELETE MW_CONVIDADO 
              WHERE id_convidado = ?';
    $params = array($_POST['id_convidado']);

    executeSQL($mainConnection, $query, $params);

    $json = array('retorno' => 'OK', 'mensagem' => 'Convite removido com sucesso.');        

} else if ($_POST["action"] == 'load') {
    $query = "SELECT id_convidado, nm_convidado, ISNULL(cd_cpf, 'N/I') as cd_cpf, cd_email, cd_celular, ds_convidado_por, ds_tipo_convite, qt_lugar, CASE in_confirmado WHEN 1 THEN 'Sim' ELSE 'Não' END AS in_confirmado
              FROM MW_CONVIDADO
              WHERE id_apresentacao = ?
              ORDER BY nm_convidado";
    $params = array($rs['id_apresentacao']);
    $rs = executeSQL($mainConnection, $query, $params);
    $json = array();
    if(hasRows($rs)){
        while($cota = fetchResult($rs)){
            $json[] = array( 'ID' => $cota['id_convidado'],
                           'NOME' => $cota['nm_convidado'],
                           'CPF' => $cota['cd_cpf'],
                           'EMAIL' => $cota['cd_email'],
                           'CELULAR' => $cota['cd_celular'],                           
                           'CONVIDADOPOR' => $cota['ds_convidado_por'],
                           'TIPOCONVITE' => $cota['ds_tipo_convite'],
                           'QTDELUGARES' => $cota['qt_lugar'],
                           'CONFIRMADO' => $cota['in_confirmado']);
        }
    }else{
        die();
    }
} 

echo json_encode($json);

?>