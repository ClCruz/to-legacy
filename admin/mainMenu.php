<?php
require_once('../settings/functions.php');

$mainConnection = mainConnection();

function getChildren($conn, $id, $return = false) {
	$query = 'SELECT P.ID_PROGRAMA, P.DS_PROGRAMA, P.DS_URL
				 FROM MW_PROGRAMA P
				 INNER JOIN MW_USUARIO_PROGRAMA UP ON UP.ID_PROGRAMA = P.ID_PROGRAMA
				 INNER JOIN MW_USUARIO U ON U.ID_USUARIO = UP.ID_USUARIO
				 WHERE U.ID_USUARIO = ? AND P.ID_PARENT = ?
				 ORDER BY P.ID_ORDEM_EXIBICAO, P.DS_PROGRAMA';
	$result = executeSQL($conn, $query, array($_SESSION['admin'], $id));
	
	$hasRows = hasRows($result);
	
	if ($hasRows) {
		echo '<ul' . ($return ? " id='menu-items' class='ui-helper-hidden' style='text-transform: capitalize'" : '') . '>';
		while ($rs = fetchResult($result)) {
			echo '<li><a href="'.$rs['DS_URL'].'">'.utf8_encode2($rs['DS_PROGRAMA']).'</a>';
			getChildren($conn, $rs['ID_PROGRAMA']);
			echo '</li>';
		}
		echo '</ul>';
	}
	
	if ($return) return $hasRows;
}
?>
<a id='menu-bt'>Menu</a>
<?php
	if (!getChildren($mainConnection, 0, true)) { 
		echo '<ul><li><a href="login.php">Login</a></li></ul>';
	}
?>