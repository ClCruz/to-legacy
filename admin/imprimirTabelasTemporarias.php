<?php
require_once('../settings/settings.php');
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 330, true)) {

	if(isset($_GET["dt_inicial"]) && isset($_GET["dt_final"])){

		$dt_inicial = explode('/', $_GET["dt_inicial"]);
		$dt_inicial = $dt_inicial[2].$dt_inicial[1].$dt_inicial[0];

		$dt_final = explode('/', $_GET["dt_final"]);
		$dt_final = $dt_final[2].$dt_final[1].$dt_final[0];
		
		$conn = ($_ENV['IS_TEST'] ? getConnection(137) : getConnection(139));
		$query = "SELECT
					CONVERT(VARCHAR(10), [Data do Evento], 103) AS 'Data do Evento'
			      ,CONVERT(VARCHAR(5), [Horario do Evento], 114) AS 'Horario do Evento'
			      ,[Local]
			      ,[Evento]
			      ,[Nome do cliente]
			      ,[CPF]
			      ,[E-mail]
			      ,[Telefone]
			      ,[Setor]
			      ,[Fila/Poltrona]
			      ,[".utf8_decode('Preço')."]
			      ,[Valor do ingresso]
			      ,'\"' + [CodigoBarras] AS CodigoBarras
			      ,CONVERT(VARCHAR(10), [DtHrEntrada], 103) + ' ' + CONVERT(VARCHAR(5), [DtHrEntrada], 114) AS DtHrEntrada
			     FROM [TabCodBarraTemp]
			     WHERE [Data do Evento] BETWEEN ? AND ?
			     AND [DtHrEntrada] IS NOT NULL";
		$params = array($dt_inicial, $dt_final);
		$result1 = executeSQL($conn, $query, $params);

		$query = "SELECT
					[CODIGO_BARRAS]
			      ,CONVERT(VARCHAR(10), [DATA_EVENTO], 103) AS DATA_EVENTO
			      ,CONVERT(VARCHAR(5), [HORA_EVENTO], 114) AS HORA_EVENTO
			      ,[LOCAL]
			      ,[NOME_EVENTO]
			      ,[SETOR]
			      ,[FILA]
			      ,[POLTRONA]
			      ,[NOME_CLIENTE]
			      ,[CPF_CLIENTE]
			      ,[RG_CLIENTE]
			      ,[EMAIL_CLIENTE]
			      ,[TIPO_INGRESSO]
			      ,[VALOR_INGRESSO]
			      ,[CANALVENDA]
			      ,[FORMA_PAGAMENTO]
			      ,[SENHA_CLIENTE]
			      ,[STATUS]
			      ,CONVERT(VARCHAR(10), [DtHrEntrada], 103) + ' ' + CONVERT(VARCHAR(5), [DtHrEntrada], 114) AS DtHrEntrada
			     FROM [TabCodBarraTempIngFac]
			     WHERE [DATA_EVENTO] BETWEEN ? AND ?
			     AND [DtHrEntrada] IS NOT NULL";
		$result2 = executeSQL($conn, $query, $params);

		header("Content-type: application/vnd.ms-excel");
		header("Content-type: application/force-download");
		header("Content-Disposition: attachment; filename=controleDeEntrada.xls");
	?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

	<h2>Ingresso Rapido</h2>

	<table>
		<tr>
			<td>Data Inicial</td>
			<td><?php echo $_GET["dt_inicial"]; ?></td>
			<td>Data Final</td>
			<td><?php echo $_GET["dt_final"]; ?></td>
		</tr>
	</table>

	<table>
		<?php
		$header_printed = false;
		while($rs = fetchResult($result1)) {
			if (!$header_printed) {
				$header_printed = true;
				echo "<thead><tr>";
				foreach ($rs as $key => $value) {
					echo is_numeric($key) ? '' : utf8_encode2("<td>$key</td>");
				}
				echo "</tr></thead>";
			}
			echo "<tbody><tr>";
			foreach ($rs as $key => $value) {
				echo is_numeric($key) ? '' : utf8_encode2("<td>$value</td>");
			}
			echo "</tr></tbody>";
		}
		?>
	</table>

	<h2>Ingresso Facil</h2>

	<table>
		<tr>
			<td>Data Inicial</td>
			<td><?php echo $_GET["dt_inicial"]; ?></td>
			<td>Data Final</td>
			<td><?php echo $_GET["dt_final"]; ?></td>
		</tr>
	</table>

	<table>
		<?php
		$header_printed = false;
		while($rs = fetchResult($result2)) {
			if (!$header_printed) {
				$header_printed = true;
				echo "<thead><tr>";
				foreach ($rs as $key => $value) {
					echo is_numeric($key) ? '' : utf8_encode2("<td>$key</td>");
				}
				echo "</tr></thead>";
			}
			echo "<tbody><tr>";
			foreach ($rs as $key => $value) {
				echo is_numeric($key) ? '' : utf8_encode2("<td>$value</td>");
			}
			echo "</tr></tbody>";
		}
		?>
	</table>
	<?php
	}
}
?>