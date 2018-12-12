<?php
$total_lines = 19;
$header_lines = 4;
$body_lines = $total_lines - $header_lines;

if ($_ENV['IS_TEST']) {
	$ip_tef = '184.172.45.130';
	$porta_tef = '4096';
	$codloja_tef = '00000000';
} else {
	$ip_tef = '192.168.102.1';
	$porta_tef = '10101';
	$codloja_tef = '11660113';
}

if ($_GET['config']) {

	if ($_GET['config'] == 2) {
		$query = "UPDATE MW_POS SET LAST_CONFIG = GETDATE() WHERE SERIAL = ?";
		$params = array($_GET['pos_serial']);
		executeSQL($mainConnection, $query, $params);
	}

	$_SESSION['is_pos_configured'] = 1;
}

if (!$_SESSION['is_pos_configured']) {

	if ($_GET['pos_serial']) {

		$_SESSION['pos_user']['serial'] = $_GET['pos_serial'];

		$mainConnection = mainConnection();

		$query = "SELECT ID, LAST_CONFIG FROM MW_POS WHERE SERIAL = ?";
		$params = array($_GET['pos_serial']);
		$rs = executeSQL($mainConnection, $query, $params, true);

		if ($rs['ID'] == null) {

			$query = "INSERT INTO MW_POS (SERIAL) VALUES (?)";
			$params = array($_GET['pos_serial']);
			executeSQL($mainConnection, $query, $params);

		}

		if ($rs['ID'] == null or $rs['LAST_CONFIG'] == null or $rs['LAST_CONFIG']->format('U') < filemtime(__FILE__)) {
			$imgs_dir = '/pos';

			// envia logos
			echo "<FILE NAME=logo_ci_colorida.bmp ADDR=$imgs_dir/logo_ci_colorida.bmp ERR=erroarq WRT=SIM>";
			echo "<FILE NAME=logo_ci_mono.bmp ADDR=$imgs_dir/logo_ci_mono.bmp ERR=erroarq WRT=SIM>";
			echo "<FILE NAME=logo_scroll.bmp ADDR=$imgs_dir/logo_scroll.bmp ERR=erroarq WRT=SIM>";

			// envia arquivo de configuracao do <PAGAMENTO>
			echo "<FILE NAME=CLSIT ADDR=/pos/CLSIT.txt ERR=ERROARQ WRT=SIM>";

			// altera a hora
			echo "<SET TYPE=TIME HOUR=".date('His')." DATE=".date('dmY')." HDSTS=STSSTT>";

			// configuracoes gerais
			echo "<CONFIG_NAVS RETURN=STS_ALTERA_SERVER>";
			// echo "PASSWORD_CONFIG=senha";
			// echo "NEW_PASSWORD_CONFIG=novasenha";
			// echo "CONECTION_TYPE=E;";
			// echo "WI_FI_SSID=skytefwifi;";
			// echo "WI_FI_PASSWORD=skytef;";
			// echo "LOCAL_IP=192.168.1.58;";
			// echo "LOCAL_MASK=255.255.0.0;";
			// echo "LOCAL_GATEWAY=192.168.0.1;";
			// echo "LOCAL_DNS_1=192.168.0.103;";
			// echo "LOCAL_DNS_2=192.168.0.103;";
			// echo "LOCAL_PING=15;";
			// echo "GPRS_CONFIG=1;";
			// echo "GPRS_APN=ZAP.VIVO.COM.BR;";
			// echo "GPRS_USER=VIVO;";
			// echo "GPRS_PASSWORD=VIVO;";
			// echo "SERVER_IP=200.160.80.90;";
			// echo "SERVER_PORT=6789;";
			// echo "SERVER_RESOURCE=/TESTE.PHP;";
			// echo "SERVER_HOST=200.160.80.90;";
			// echo "SERVER_HTTPS_ACTIVE=0;";
			// echo "SERVER_HTTPS_METHOD=1;";
			// echo "POSITION_STATUS_LINE=B;";
			// echo "SHOW_HOUR_AT_STATUS_LINE=1;";
			// echo "SCROLL_UP=62;";
			// echo "SCROLL_DOWN=63;";
			echo "PRINTER_CONTRAST=2;";
			// echo "KEEP_ALIVE_ATIVAR=S;";
			// echo "KEEP_ALIVE_TEMPO_DE_INTERVALO=20;";
			// echo "KEEP_ALIVE_IP_DESTINO=200.160.80.90;";
			// echo "KEEP_ALIVE_PORT=6789;";
			// echo "BAUDRATE_SERIAL=28800;";
			// echo "PARIDADE_SERIAL=PAR;";
			// echo "DATA_BITS_SERIAL=7;";
			// echo "STOP_BITS_SERIAL=1;";
			// echo "TIMEOUT_SERIAL=5;";
			echo "</CONFIG_NAVS>";

			$query = "UPDATE MW_POS SET LAST_CONFIG = GETDATE() WHERE SERIAL = ?";
			$params = array($_GET['pos_serial']);
			executeSQL($mainConnection, $query, $params);

			echo "<RESET>";

			$config = 2;
		} else {

			$config = 1;
		}

		// retorna fim da configuracao
		echo "<GET TYPE=HIDDEN NAME=config VALUE=$config>";

		echo "<CONSOLE> Iniciando aplicativo...</CONSOLE>";

	} else {

		echo "<INIT KEEP_COOKIES=1>";

		echo utf8_decode("<CONSOLE> Carregando configurações...</CONSOLE>");

		echo "<GET TYPE=SERIALNO NAME=pos_serial>";
	}

	echo "<POST>";

	die();
}