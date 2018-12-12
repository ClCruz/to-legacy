<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 218, true)) {
    if(!empty($_POST["nome"])){
        $sql = 'EXEC prc_cons_comprovante ?';
        $params = array(utf8_encode2($_POST["nome"]));
        $result = executeSQL($mainConnection, $sql, $params);
        if(hasRows($result)){
            while($dados = fetchResult($result)){
                $html .= "<tr>";
                $html .= "<td>". utf8_encode2($dados['cliente']) ."</td>";
                $html .= "<td>". utf8_encode2($dados['ds_evento']) ."</td>";
                $html .= "<td>". $dados['apresentacao'] ."</td>";
                $html .= "<td>". $dados['id_pedido_venda'] ."</td>";
                $html .= "<td>". $dados['CodVenda'] ."</td>";
                $html .= "<td><a class=\"comprovanteUnico\" href=\"relComprovanteEntrega.php?codvenda=". $dados['CodVenda'] ."&dt_inicial=15/07/2011&dt_final=15/08/2011\">Imprimir</a>";
                $html .= "</tr>";
            }
            echo $html;
        }else{
            echo "<tr><td colspan=\"5\" class=\"vazio\">Nenhum registro encontrado.</td></tr>";
        }
    }
}
?>
