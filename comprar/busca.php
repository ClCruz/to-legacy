<?php
session_start();
if (isset($_SESSION['operador']) and is_numeric($_SESSION['operador'])) {
	if ($_POST['nomeBusca'] != '' or $_POST['sobrenomeBusca'] != '' or $_POST['telefoneBusca'] != '' or $_POST['cpfBusca'] != '') {
		
		foreach ($_POST as $key => $val) {
			if ($val != '') {
				$_POST[$key] = '%' . utf8_decode($val) . '%';
			}
		}

		// formatacao dos campos do layout 2.0 para o antigo (para manter compatibilidade)
		$_POST['cpfBusca'] = preg_replace("/[^0-9%]/", "", $_POST['cpfBusca']);

		if (preg_match('/\s/', $_POST['telefoneBusca'])) {
			$_POST['telefoneBusca'] =  explode(' ', $_POST['telefoneBusca']);
			$_POST['telefoneBusca'] = preg_replace("/[^0-9%]/", "", $_POST['telefoneBusca'][1]);
		}
		// -------------------------------------------------------------------------------
		
		require_once('../settings/functions.php');
		
		$mainConnection = mainConnection();
		
		$params = array();
		$query = 'SELECT top 50 ID_CLIENTE, DS_NOME, DS_SOBRENOME, DS_DDD_TELEFONE, DS_TELEFONE, CD_CPF
					 FROM MW_CLIENTE
					 WHERE 1=1 ';
		if ($_POST['nomeBusca'] != '') {
			$query .= 'AND DS_NOME LIKE ? ';
			$params[] = utf8_encode2($_POST['nomeBusca']);
		}
		if ($_POST['sobrenomeBusca'] != '') {
			$query .= 'AND DS_SOBRENOME LIKE ? ';
			$params[] = utf8_encode2($_POST['sobrenomeBusca']);
		}
		if ($_POST['telefoneBusca'] != '') {
			$query .= 'AND DS_TELEFONE LIKE ? ';
			$params[] = $_POST['telefoneBusca'];
		}
		if ($_POST['cpfBusca'] != '') {
			$query .= 'AND CD_CPF LIKE ? ';
			$params[] = $_POST['cpfBusca'];
		}
		$query .= ' ORDER BY DS_NOME, DS_SOBRENOME, CD_CPF';
		$result = executeSQL($mainConnection, $query, $params);
		
		if (hasRows($result)) {
			?>
				<p>Cliente(s) econtrado(s):</p>
				<ul>
			<?php
			while ($rs = fetchResult($result)) {

				$onclick 	= '';

				$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : ($_COOKIE['entrega'] ? 'etapa3_entrega.php' : 'etapa4.php');
				$href 		= "autenticacao.php?id=".$rs['ID_CLIENTE']."&redirect=".$redirect;

				if ($rs['DS_NOME'] == 'POS' && $rs['DS_SOBRENOME'] == 'POS')
				{
					$href = 'javascript:;';
					$onclick = 'onclick="finalizaCadastroPOS(this)"';
				}


			?>
				<a <?php echo $onclick; ?> href="<?php echo $href; ?>" class="cliente">
					<li>
						<p>
							Nome: <?php echo utf8_encode2($rs['DS_NOME'] . ' ' . $rs['DS_SOBRENOME']); ?><br>
							CPF: <?php echo $rs['CD_CPF']; ?><br>
							Telefone: <?php echo '(' . $rs['DS_DDD_TELEFONE'] . ') ' . $rs['DS_TELEFONE']; ?>
						</p>
					</li>
				</a>
			<?php
			}
			?>
				</ul>
			<?php
		} else {
		?>
			<p>Nenhum registro encontrado.</p>
		<?php
		}
		
	} else {
	?>
		<p>Preencha, pelo menos, um campo para efetuar a busca.</p>
	<?php
	}
}
?>