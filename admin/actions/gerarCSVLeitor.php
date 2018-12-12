<?php
require_once('../settings/functions.php');

$mainConnection = mainConnection();

session_start();

function getDadosPeca($fetch = false)
{
	$conn = getConnection($_GET['CodTeatro']);

	$query = "SELECT NOMPECA, CONVERT(VARCHAR(10), DATINIPECA, 120) DATINIPECA, CONVERT(VARCHAR(10), DATFINPECA, 120) DATFINPECA FROM TABPECA WHERE CODPECA = ?";
	$params = array($_GET['CodPeca']);
	$result = executeSQL($conn, $query, $params, true);

	if ($fetch)
	{
		$result = fetchAssoc( $result );
	}

	return $result;
}

function getTotal($fetch = false)
{
	$conn = getConnection($_GET['CodTeatro']);

	$query = "SELECT A.CODAPRESENTACAO, L.CODSALA, L.CODSETOR, COUNT(INDICE) AS TOTAL, S.NomSala, SE.NomSetor FROM TABSALDETALHE L
				  INNER JOIN TABAPRESENTACAO A ON L.CODSALA = A.CODSALA
				  INNER JOIN tabSala AS S ON S.CodSala = L.CodSala
				  INNER JOIN tabSetor AS SE ON SE.CodSetor = L.CodSetor AND SE.CodSala = L.CodSala
				  WHERE A.CODPECA = ? AND A.DATAPRESENTACAO = ? AND A.HORSESSAO = ? AND TIPOBJETO <> 'I'
				  GROUP BY A.CODAPRESENTACAO, L.CODSALA, L.CODSETOR, S.NomSala, SE.NomSetor";
	$params = array($_GET['CodPeca'], $_GET['DatApresentacao'], $_GET['HorSessao']);

	$result = executeSQL($conn, $query, $params);

	if ($fetch)
	{
		$result = fetchAssoc( $result );
	}

	return $result;
}

function getTiposBilhete($fetch = false)
{
	$conn = getConnection($_GET['CodTeatro']);
	$query = "SELECT V.CODTIPBILHETE, B.TIPBILHETE FROM TABVALBILHETE V
				  INNER JOIN TABTIPBILHETE B ON V.CODTIPBILHETE = B.CODTIPBILHETE
				  WHERE CODPECA = ?";
	$params = array($_GET['CodPeca']);
	$result = executeSQL($conn, $query, $params);

	if ($fetch)
	{
		$result = fetchAssoc( $result );
	}

	return $result;
}

function validaDados($str, $qtdeCaracteres)
{
	$len = strlen($str);
	if ( $len < $qtdeCaracteres )
	{
		$qtdeZeros = ($qtdeCaracteres - $len);

		$complementZeros = '';
		$i = 0;
		while ($i < $qtdeZeros)
		{
			$complementZeros .= '0';
			$i++;
		}

		$str = $complementZeros . $str;
	}

	return $str;
}

/*
 * Abrir arquivo na URL para disponibilizar download
 * */
function locationFile($fileName)
{
	header('Content-Description: File Transfer');
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename="' . $fileName . '"');
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($fileName));

	ob_clean();
	flush();

	readfile($fileName);
	unlink($fileName);
}

if (acessoPermitido($mainConnection, $_SESSION['admin'], 220, true)) {

	$conn = getConnection($_GET['CodTeatro']);

	if ($_GET['action'] == 'csv')
	{

		//Gerar nome do arquivo com base nos dados da peça
		$rs = getDadosPeca();
			$file_name = $_GET['DatApresentacao'] . str_replace(':', '', $_GET['HorSessao']) . normalize_string(substr(utf8_encode2($rs['NOMPECA']), 0, 15));
			$csv1_path = 'temp/EVE' . $file_name . '.csv';
			$csv2_path = 'temp/ING' . $file_name . '.csv';
			$zip_file = 'temp/' . $file_name . '.zip';

			$csv1 = fopen($csv1_path, 'wt');
			$data = substr($_GET['DatApresentacao'], 0, 4) . '-' . substr($_GET['DatApresentacao'], 4, 2) . '-' . substr($_GET['DatApresentacao'], -2);
			fwrite($csv1, "00;" . utf8_encode2($rs['NOMPECA']) . ";" . $data . ";00:00:00;" . $data . ";23:59:59;\n");

		$result = getTiposBilhete(true);
			$bilhetes = array();
			foreach ($result as $rs)
			{
				fwrite($csv1, "01;" . $rs['CODTIPBILHETE'] . ";" . utf8_encode2($rs['TIPBILHETE']) . "\n");
				$bilhetes[$rs['CODTIPBILHETE']] = utf8_encode2($rs['TIPBILHETE']);
			}

			fclose($csv1);

			$csv2 = fopen($csv2_path, 'wt');
			$result = getTotal(true);

			foreach ($result as $rs)
			{
				$cod_apresentacao	=	substr('00000' . $rs['CODAPRESENTACAO'], -5);
				$cod_setor			=	$rs['CODSETOR'];
				$data_apresentacao	=	substr($_GET['DatApresentacao'], -4);
				$hora_apresentacao	=	str_replace(':', '', $_GET['HorSessao']);

				foreach ($bilhetes as $id => $name)
				{
					$cod_bilhete = substr('000' . $id, -3);

					for ($i = 1; $i <= $rs['TOTAL'] * 1.2; $i++)
					{
						$sequencia_bilhete = substr('00000' . $i, -5);
						fwrite($csv2, "02;" . $cod_apresentacao . $cod_setor . $data_apresentacao . $hora_apresentacao . $cod_bilhete . $sequencia_bilhete . ";" . $id . ";;;;;I; \n");
					}
				}
			}

			fclose($csv2);

			$zip = new ZipArchive;
			$zip->open($zip_file, ZipArchive::CREATE);
			$zip->addFile($csv1_path);
			$zip->addFile($csv2_path);
			$zip->close();

		/*
		 * Abrir arquivo na URL para disponibilizar download
		 * */
		header('Content-Description: File Transfer');
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="' . $file_name . '.zip"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($zip_file));

		ob_clean();
		flush();

		readfile($zip_file);

			unlink($zip_file);
			unlink($csv1_path);
			unlink($csv2_path);

			exit();
	}
	/*
	 * Gerar todos os codigos 2D possives para este evento conforme apresentação e local da aprensetação
	 * */
	else if ($_GET['action'] == 'defaultcsv')
	{
		$dadosPeca = getDadosPeca();
		
		$fileName = 'ING';
		$fileName .= $_GET['DatApresentacao'];
		$fileName .= $hora = str_replace(':','',$_GET['HorSessao']);
		$fileName .= $nome = substr($dadosPeca['NOMPECA'], 0, 14);

		$FILE = fopen('temp/'.$fileName.'.csv', 'x');

		$Setores = getTotal(true);

		$totalFinalGerado = 0;
		$tipBilhete = getTiposBilhete(true);

		foreach ($tipBilhete as $tip)
		{
			foreach ($Setores as $setor)
			{
				$totalTipoSetor = (int)$setor['TOTAL'];
				$totalFinalGerado += $totalTipoSetor;

				//Gerar codigos com base no tipo de bilhete e setor
				$i = 1;
				while ($i <= $totalTipoSetor)
				{
					$Codigo = '';
					$Codigo .= validaDados($setor['CODAPRESENTACAO'], 5);
					$Codigo .= $setor['CODSETOR'];
					$Codigo .= substr($_GET['DatApresentacao'], 4, 7); //mês e dia da apresentação
					$Codigo .= $hora = str_replace(':','',$_GET['HorSessao']);
					$Codigo .= validaDados($tip['CODTIPBILHETE'], 3);
					$Codigo .= validaDados($i, 5);

					$filtro = $setor['NomSala'].';'.$setor['NomSetor'].';'.$setor['CODSALA'].';'.$setor['CODSETOR'].';';

					fputcsv($FILE, array($Codigo.';'.$filtro));
					//fputcsv($FILE, array($Codigo.';'));
					$i++;
				}
			}
		}

		fclose($FILE);

		$zip_file = 'temp/'.$fileName.'.zip';
		$zip = new ZipArchive;
		$zip->open($zip_file, ZipArchive::CREATE);
		$zip->addFile("temp/$fileName.csv");
		$zip->close();

		/*
		 * Abrir arquivo na URL para disponibilizar download
		 * */
		//locationFile($caminhoArquivo);
		header('Content-Description: File Transfer');
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="' . $fileName . '.zip"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($zip_file));

		ob_clean();
		flush();

		readfile($zip_file);

		unlink($zip_file);
		unlink("temp/$fileName.csv");

		exit();
	}
	/*
	 * Gerar todos os codigos vendidos para este evento conforme apresentação e local da aprensetação
	 * */
	else if ($_GET['action'] == 'vendidos')
	{
		$dadosPeca = getDadosPeca();
		
		$fileName = 'ING';
		$fileName .= $_GET['DatApresentacao'];
		$fileName .= $hora = str_replace(':','',$_GET['HorSessao']);
		$fileName .= $nome = substr($dadosPeca['NOMPECA'], 0, 14);

		$FILE = fopen('temp/'.$fileName.'.csv', 'x');

		$conn = getConnection($_GET['CodTeatro']);
		$query = "SELECT csv.codbar, L.CODSETOR, L.CODSALA, S.NomSala, SE.NomSetor
					FROM TABSALDETALHE L
					INNER JOIN TABAPRESENTACAO A ON L.CODSALA = A.CODSALA
					INNER JOIN tabSala AS S ON S.CodSala = L.CodSala
					INNER JOIN tabSetor AS SE ON SE.CodSetor = L.CodSetor AND SE.CodSala = L.CodSala
					inner join tabControleSeqVenda csv on csv.CodApresentacao = a.CodApresentacao and csv.Indice = l.Indice
					WHERE A.CODPECA = ? AND A.DATAPRESENTACAO = ? AND A.HORSESSAO = ? AND TIPOBJETO <> 'I'";
		$params = array($_GET['CodPeca'], $_GET['DatApresentacao'], $_GET['HorSessao']);

		$result = executeSQL($conn, $query, $params);

		while ($rs = fetchResult($result))
		{
			$codigo = $rs['codbar'];

			$filtro = $rs['NomSala'].';'.$rs['NomSetor'].';'.$rs['CODSALA'].';'.$rs['CODSETOR'].';';

			fputcsv($FILE, array($codigo.';'.$filtro));
		}

		fclose($FILE);

		$zip_file = 'temp/'.$fileName.'.zip';
		$zip = new ZipArchive;
		$zip->open($zip_file, ZipArchive::CREATE);
		$zip->addFile("temp/$fileName.csv");
		$zip->close();

		/*
		 * Abrir arquivo na URL para disponibilizar download
		 * */
		//locationFile($caminhoArquivo);
		header('Content-Description: File Transfer');
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="' . $fileName . '.zip"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($zip_file));

		ob_clean();
		flush();

		readfile($zip_file);

		unlink($zip_file);
		unlink("temp/$fileName.csv");

		exit();
	}
	else
	{
		echo 'Action '.$_GET['action'].' não existe!';
		exit();
	}

}