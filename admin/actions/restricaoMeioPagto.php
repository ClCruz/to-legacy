<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 7, true)) {

    $_POST['dt_inicio'] = explode('/', $_POST['dt_inicio']);
    $_POST['dt_inicio'] = $_POST['dt_inicio'][2].$_POST['dt_inicio'][1].$_POST['dt_inicio'][0];

    $_POST['dt_fim'] = explode('/', $_POST['dt_fim']);
    $_POST['dt_fim'] = $_POST['dt_fim'][2].$_POST['dt_fim'][1].$_POST['dt_fim'][0];


     if ($_GET['action'] == 'add' and isset($_GET['idMeioPagamento']) and isset($_GET['idBase'])) { /*------------ UPDATE ------------*/
        $query ="IF NOT EXISTS(SELECT 1 FROM mw_base_meio_pagamento WHERE id_base = ? AND id_meio_pagamento = ?)
                    INSERT INTO MW_BASE_MEIO_PAGAMENTO
                (ID_BASE, ID_MEIO_PAGAMENTO, DT_INICIO, DT_FIM)
                    VALUES ( ?, ? , ?, ? )
                ELSE
                    UPDATE MW_BASE_MEIO_PAGAMENTO SET
                          DT_INICIO = ?
                         ,DT_FIM    = ?
                         WHERE ID_BASE = ? AND ID_MEIO_PAGAMENTO = ? ";

         $params = array($_GET['idBase'],
             $_GET['idMeioPagamento'],
             $_GET['idBase'],
             $_GET['idMeioPagamento'],
             $_POST['dt_inicio'],
             $_POST['dt_fim'],
             $_POST['dt_inicio'],
             $_POST['dt_fim'],
             $_GET['idBase'],
             $_GET['idMeioPagamento']
         );
         if (executeSQL($mainConnection, $query, $params)) {
             $log = new Log($_SESSION['admin']);
             $log->__set('funcionalidade', 'Restrição Meios de Pagamento');
             $log->__set('parametros', $params);
             $log->__set('log', $query);
             $log->save($mainConnection);

         } else {
             $retorno = sqlErrors();
         }

     } else if ($_GET['action'] == 'delete' and isset($_GET['idMeioPagamento']) and isset($_GET['idBase'])) {


        function deleteReg($mainConnection)
        {
            $query = 'DELETE FROM MW_BASE_MEIO_PAGAMENTO WHERE ID_BASE = ? AND ID_MEIO_PAGAMENTO = ?';
            $params = array($_GET['idBase'], $_GET['idMeioPagamento']);

            if (executeSQL($mainConnection, $query, $params)) {
                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Restrição Meios de Pagamento');
                $log->__set('parametros', $params);
                $log->__set('log', $query);
                $log->save($mainConnection);

                $retorno = 'true';
            } else {
                $retorno = sqlErrors();
            }

            return $retorno;
        }


         $retorno = deleteReg($mainConnection);

    }

    if (is_array($retorno)) {
        if ($retorno[0]['code'] == 2627) {
            echo 'Essa restrição já está cadastrada.';
        } else {
            echo $retorno[0]['message'];
        }
    } else {
        echo $retorno;
    }

}
?>