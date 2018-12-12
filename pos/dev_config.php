<?php
if (isset($_GET['STS_ALTERA_SERVER']) or isset($_GET['ERROARQ'])) {
	dump_get();
	echo "<POST>";
	die();
}

echo "<CONSOLE>alterando data/hora...</CONSOLE>";

echo "<SET TYPE=TIME HOUR=".date('His')." DATE=".date('dmY')." HDSTS=STSSTT>";

echo "<CONSOLE>alterando pagamento...</CONSOLE>";

echo "<FILE NAME=CLSIT ADDR=/pos/CLSIT.txt ERR=ERROARQ>";

echo "<CONSOLE>alterando configs...</CONSOLE>";

// mudar a senha caso ainda tenha a config de fabrica
echo "<CONFIG_NAVS RETURN=STS_ALTERA_SERVER>";
echo "PASSWORD_CONFIG= ;";
echo "NEW_PASSWORD_CONFIG=159137;";
echo "</CONFIG_NAVS>";

// configuracoes gerais
echo "<CONFIG_NAVS RETURN=STS_ALTERA_SERVER>";
echo "PASSWORD_CONFIG=159137;";
// echo "NEW_PASSWORD_CONFIG=159137;";
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


// se estiver no ambiente de homologacao configura para producao
if ($_ENV['IS_TEST']) {
	echo "SERVER_IP=compra.compreingressos.com;";
	echo "SERVER_PORT=80;";
	echo "SERVER_RESOURCE=/pos/main.php;";
	echo "SERVER_HOST=compra.compreingressos.com;";
}
// se estiver no ambiente de producao configura para homologacao
else {
	echo "SERVER_IP=homolog.compreingressos.com;";
	echo "SERVER_PORT=8081;";
	echo "SERVER_RESOURCE=/pos/main.php;";
	echo "SERVER_HOST=homolog.compreingressos.com;";
}

// echo "SERVER_HTTPS_ACTIVE=0;";
// echo "SERVER_HTTPS_METHOD=1;";
// echo "POSITION_STATUS_LINE=B;";
// echo "SHOW_HOUR_AT_STATUS_LINE=1;";
// echo "SCROLL_UP=62;";
// echo "SCROLL_DOWN=63;";
// echo "PRINTER_CONTRAST=2;";
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

echo "<CONSOLE>obtendo resultado...</CONSOLE>";

echo "<POST>";