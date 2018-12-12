<?php

$mainConnection = mainConnection();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 3, true)) {

    //Pegar listagem de testros com id do usuario a qual ele é responsável
    if ( $_GET['action'] == 'getTeatros' )
    {
        $query = 'SELECT A.id_base, A.ds_nome_teatro, B.id_usuario
                    FROM mw_base AS A 
                    LEFT JOIN mw_respons_teatro AS B ON B.id_base = A.id_base 
                    AND B.id_usuario = ?
                    WHERE A.in_ativo = 1
                    ORDER BY A.ds_nome_teatro';

        $paramns = array
        (
            (int)$_GET['userid']
        );
        $teatros = executeSQL($mainConnection, $query, $paramns);

        $html = '';
        while ( $rs = fetchResult($teatros) )
        {
            $checked = ( $rs['id_usuario'] != '' ) ? 'checked="checked"' : '';
            
            $html .= '<tr>';
                $html .= '<td>'.utf8_encode2($rs["ds_nome_teatro"]).'</td>';
                $html .= '<td><input class="check" type="checkbox" '.$checked.' value="'.$rs["id_base"].'"></td>';
            $html .= '</tr>';
        }

        echo $html;
    }

    elseif ( $_GET['action'] == 'cad' )
    {
        $userid     = $_GET['userid'];
        $teatroid   = $_GET['teatroid'];

        $paramns = array($teatroid, $userid);
        $query = 'INSERT INTO mw_respons_teatro (id_base, id_usuario)  VALUES (?, ?)';
        executeSQL( $mainConnection, $query, $paramns );
    }

    elseif ( $_GET['action'] == 'del' )
    {
        $userid     = $_GET['userid'];
        $teatroid   = $_GET['teatroid'];

        $paramns = array($teatroid, $userid);
        $query = 'DELETE FROM mw_respons_teatro WHERE id_base = ? AND id_usuario = ?';
        executeSQL( $mainConnection, $query, $paramns );
        //echo 'Remover permissão do usuário id: '.$userid.' no teatro id: '.$teatroid;
    }

}