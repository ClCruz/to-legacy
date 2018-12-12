<?php
    header ('Content-type: text/plain');
    header ('Content-disposition: attachment; filename="comprovante.txt"');
    readfile('comprovante.txt');
?>
