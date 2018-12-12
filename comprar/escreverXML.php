<?php

require_once('../settings/functions.php');

function mainConnection2() {
    $host = '189.112.5.71';
    $port = '1433';
    $dbname = 'CI_MIDDLEWAY';
    $user = 'tsp';
    $pass = 'tsp';

    return sqlsrv_connect($host . ',' . $port, array("UID" => $user, "PWD" => $pass, "Database" => $dbname));
}

$conn = mainConnection2();

$dir = "C:\\Inetpub\\wwwroot\\CompreIngressos\\comprar\\campanhas\\";

$idcampanha = "";
$idevento = "";
$tag = "";

if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<campanha>\n";
        while (($file = readdir($dh)) !== false) {
            if ($file != "." && $file != "..") {
                $texto = file_get_contents($dir . $file);
                $idcampanha = substr(substr($texto, strpos($texto, "// Post-TAG "), 16), 12, 4);
                $tag = strstr(strstr($texto, ' - Comprar', true), "Post Tag:");
                $tamanho = strlen($tag);
                $pos = strpos($tag, "Post Tag:", 10);
                $tag = substr($tag, $pos + 10, $tamanho);
                $tag_modificado = strstr($file, "-TAG", true);
                $sql = "SELECT TOP 1 ID_EVENTO FROM MW_EVENTO WHERE ltrim(DS_EVENTO) LIKE '%" . $tag . "%'";
                $result = executeSQL($conn, $sql);
                if (hasRows($result)) {
                    while ($rs = fetchResult($result)) {
                        $xml .= "\t<item>\n";
                        $xml .= "\t\t<id>" . $rs["ID_EVENTO"] . "</id>\n";
                        $xml .= "\t\t<idcampanha>" . $idcampanha . "</idcampanha>\n";
                        $xml .= "\t\t<tag>" . utf8_encode2($tag_modificado) . "</tag>\n";
                        $xml .= "\t</item>\n";
                    }
                } else {
                    $xml .= "\t<item>\n";
                    $xml .= "\t\t<id>N/D<id>\n";
                    $xml .= "\t\t<idcampanha>" . $idcampanha . "</idcampanha>\n";
                    $xml .= "\t\t<tag>" . $tag_modificado . "</tag>\n";
                    $xml .= "\t</item>\n";
                }
            }
        }
        $xml .= "</campanha>";

        $arquivo = fopen($dir."campanha.xml", "a+b");
        if (!$arquivo) {
            echo "Erro na abertura do arquivo\r\n";
            exit;
        } else {
            $gravar = fputs($arquivo, $xml);
            if ($gravar === FALSE) {
                echo "Erro na abertura do arquivo";
                exit;
            }
            fclose($arquivo);
        }

        closedir($dh);
    }
}
?>