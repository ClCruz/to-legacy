<?php
if ($_REQUEST["dev"]=="1") {
    putenv("IS_TEST=1");
    echo "Mudando para ambiente de homologação";
}
else {
    putenv("IS_TEST=");
    echo "Mudando para ambiente de produção";
}

?>