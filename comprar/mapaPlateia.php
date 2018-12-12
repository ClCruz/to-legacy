<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');

$mainConnection = mainConnection();
$query = 'SELECT A.CODAPRESENTACAO, E.ID_BASE
         FROM MW_APRESENTACAO A
         INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = \'1\'
         WHERE A.ID_APRESENTACAO = ? AND A.IN_ATIVO = \'1\'';
$params = array($_GET['apresentacao']);
$rs = executeSQL($mainConnection, $query, $params, true);

$conn = getConnection($rs['ID_BASE']);
$query = 'SELECT S.FOTOIMAGEMSITE, S.ALTURASITE, S.LARGURASITE, S.INGRESSONUMERADO
         FROM TABAPRESENTACAO A
         INNER JOIN TABSALA S ON S.CODSALA = A.CODSALA
         WHERE A.CODAPRESENTACAO = ?';
$params = array($rs['CODAPRESENTACAO']);

$rs = executeSQL($conn, $query, $params, true);
?>
<div <?php echo ($rs['INGRESSONUMERADO'] ? 'id="mapa_de_plateia"' : ''); ?> class="mapa_de_plateia" style="width:<?php echo $rs['LARGURASITE'] == '' ? '660' : $rs['LARGURASITE']; ?>px;">
  <img src="<?php echo $rs['FOTOIMAGEMSITE'] == '' ? '../images/palco.png' : $rs['FOTOIMAGEMSITE']; ?>" width="<?php echo $rs['LARGURASITE'] == '' ? '660' : $rs['LARGURASITE']; ?>" height="<?php echo $rs['ALTURASITE'] == '' ? '610' : $rs['ALTURASITE']; ?>" />
</div>