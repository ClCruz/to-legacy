<?php

require_once('../settings/settings.php');
require_once('../settings/functions.php');
require_once('../settings/Log.class.php');

$mainConnection = mainConnection();

$query = "SELECT ISNULL(id_apresentacao, 0) AS id_apresentacao
              FROM MW_APRESENTACAO A
              INNER JOIN MW_EVENTO E ON E.CODPECA = ? AND E.ID_BASE = ?
              WHERE A.DT_APRESENTACAO = CONVERT(DATETIME, ?, 112) AND A.HR_APRESENTACAO = ? 
                    AND A.ID_EVENTO = E.ID_EVENTO";

$params = array($_POST["cboEvento"], $_POST["cboLocal"], $_POST["cboApresentacao"], $_POST["cboHorario"]);
$rs = executeSQL($mainConnection, $query, $params, true);

if ($_POST['action'] == 'add') {      

    if($rs["id_apresentacao"] != 0){
        $query = 'INSERT INTO MW_COTA_CONVITE (id_apresentacao, nm_convidado_por, ds_tipo_convite, qt_ingresso, id_usuario, dt_atualizacao) VALUES(?, ?, ?, ?, ?, GETDATE())';
        $params = array($rs["id_apresentacao"], $_POST['convidadoPor'], $_POST['tipoConvite'], $_POST['qtdeIngresso'], $_POST['idUsuario']);

        executeSQL($mainConnection, $query, $params);
    
        $json = array('retorno' => 'OK', 'mensagem' => 'Cadastro efetuado com sucesso.');        
    }else{
        $json = array('retorno' => 'falha', 'mensagem' => 'Apresentação Inválida.');
    }   
     
} else if($_POST['action'] == 'edit'){

    if($rs["id_apresentacao"] != 0){
        $query = 'UPDATE MW_COTA_CONVITE SET id_apresentacao = ?, nm_convidado_por = ?, ds_tipo_convite = ?, 
                  qt_ingresso = ?, id_usuario = ?, dt_atualizacao = GETDATE()
                  WHERE id_cota_convite = ?';
        $params = array($rs["id_apresentacao"], $_POST['convidadoPor'], $_POST['tipoConvite'], $_POST['qtdeIngresso'], $_POST['idUsuario'], $_POST['id_cota_convite']);

        executeSQL($mainConnection, $query, $params);
    
        $json = array('retorno' => 'OK', 'mensagem' => 'Alteração efetuada com sucesso.');        
    }else{
        $json = array('retorno' => 'falha', 'mensagem' => 'Apresentação Inválida.');
    }

} else if ($_POST["action"] == 'load') {
    $query = "SELECT id_cota_convite, nm_convidado_por, ds_tipo_convite, qt_ingresso
              FROM MW_COTA_CONVITE
              WHERE id_apresentacao = ?";
    $params = array($rs['id_apresentacao']);
    $rs = executeSQL($mainConnection, $query, $params);
    $json = array();
    if(hasRows($rs)){
        while($cota = fetchResult($rs)){
            $json = array( 'id_cota_convite' => $cota['id_cota_convite'],
                           'nm_convidado_por' => $cota['nm_convidado_por'],
                           'ds_tipo_convite' => $cota['ds_tipo_convite'],
                           'qt_ingresso' => $cota['qt_ingresso']);
        }
    }else{
        die();
    }
}

echo json_encode($json);

?>