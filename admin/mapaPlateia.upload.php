<?php
  if ($_GET['action'] == 'upload') {
    require_once($_SERVER['DOCUMENT_ROOT']."/settings/settings.php");
    require_once($_SERVER['DOCUMENT_ROOT']."/settings/functions.php");
    
    $imagem = $_POST['file'];

    $conn = getConnection($_POST['teatroID']);

    $query = 'UPDATE TABSALA SET FOTOIMAGEMSITE = ?,
              LARGURASITE = ?,
              ALTURASITE = ?,
              TAMANHOLUGAR = ?
              WHERE CODSALA = ?';
    $params = array(
        $imagem,
        (isset($_POST['xScale'])) ? $_POST['xScale'] : '',
        (isset($_POST['yScale'])) ? $_POST['yScale'] : '',
        (isset($_POST['Size'])) ? $_POST['Size'] : '',
        $_POST['salaID']
    );
    executeSQL($conn, $query, $params);
    die("Imagem salva com sucesso.");
  }
?>