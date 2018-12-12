<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 12, true)) {

	if ($_GET['js_enviar_email']) {

		$query = 'SELECT TOP 1 ID_PEDIDO_VENDA, CD_EMAIL_LOGIN FROM TEMP_EMAILS_PARA_ENVIAR WHERE IN_ENVIADO = 0 AND DS_ERRO IS NULL';
		$result = executeSQL($mainConnection, $query);

		// tabela temporaria nao existe?
		// if (sqlErrors('code') == 208) {

		// 	executeSQL($mainConnection, "SELECT DISTINCT PV.ID_PEDIDO_VENDA, C.CD_EMAIL_LOGIN, 0 AS IN_ENVIADO, CAST(NULL AS VARCHAR(MAX)) AS DS_ERRO

		// 								INTO TEMP_EMAILS_PARA_ENVIAR

		// 								FROM MW_PEDIDO_VENDA PV
		// 								INNER JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
		// 								INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = IPV.ID_APRESENTACAO
		// 								INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
		// 								INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE

		// 								WHERE CONVERT(DATETIME, CONVERT(VARCHAR(10), A.DT_APRESENTACAO, 120) + ' ' + REPLACE(A.HR_APRESENTACAO, 'H', ':')) >= GETDATE()
		// 								AND PV.DT_PEDIDO_VENDA <= '2015-07-01 14:12'
		// 								AND E.ID_BASE IN (143, 78, 139, 162, 138, 141, 13, 10, 155, 77, 27, 33, 41, 153, 72)");
		// 	$result = executeSQL($mainConnection, $query);

		// }

		$rs = fetchResult($result);

		if (!empty($rs)) {

			$_GET['action'] = 'reemail';
			$_GET['pedido'] = $rs['ID_PEDIDO_VENDA'];
			$_POST['emailInformado'] = $_GET['emailAtual'] = $rs['CD_EMAIL_LOGIN'];
			ob_start();
			require "actions/listaMovimentacao.php";
			$retorno = ob_get_clean();

			if ($retorno == 'ok') {

				$query = 'UPDATE TEMP_EMAILS_PARA_ENVIAR SET IN_ENVIADO = 1 WHERE ID_PEDIDO_VENDA = ? AND CD_EMAIL_LOGIN = ?';
				$result = executeSQL($mainConnection, $query, array($_GET['pedido'], $_POST['emailInformado']));

			} else {

				$query = 'UPDATE TEMP_EMAILS_PARA_ENVIAR SET DS_ERRO = ? WHERE ID_PEDIDO_VENDA = ? AND CD_EMAIL_LOGIN = ?';
				$result = executeSQL($mainConnection, $query, array($retorno, $_GET['pedido'], $_POST['emailInformado']));

			}

			echo "pedido: {$_GET['pedido']} \t e-mail: {$_POST['emailInformado']} \t resultado: $retorno \n";

		} else {

			echo "finalizado";

		}

	} else {
?>
		<style>
			#resultado {width: 100%; height: 90%; overflow: scroll;}
		</style>
		<script src="../javascripts/jquery.2.0.0.min.js" type="text/javascript"></script>
		<script>
		$(document).ready(function(){
			function countdown(s) {
				if (s == 0) {
					$('#status').text('enviando...');

					$.ajax({
						data: {js_enviar_email: 1}
					}).done(function(retorno){
						$('#resultado').html($('#resultado').html()+retorno);

						if (retorno == 'finalizado') {
							$('#status').text('finalizado');
						} else {
							$('#iniciar').trigger('click');
						}

						$('#resultado').animate({scrollTop: $('#resultado').get(0).scrollHeight});
					});
				} else {
					$('#status').text('novo e-mail em '+s+' segundo(s)');

					setTimeout(function(){countdown(s-1)}, 1000);
				}
			}

			$('#iniciar').on('click', function(){
				var intervalo = $('#intervalo').val();

				if (intervalo >= 1) {
					$(this).hide();
					countdown(intervalo);
				} else {
					$(this).show();
					$('#status').text('');
				}
			});
		});
		</script>
		<input type="number" id="intervalo" placeholder="intervalo" /> <input type="button" id="iniciar" value="iniciar" /> <span id="status"></span>
		<pre id="resultado"></pre>
<?php
	}
}
?>