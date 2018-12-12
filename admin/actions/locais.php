<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 3, true)) {

    if ($_GET['action'] != 'delete') {
        $_POST['ativo'] = $_POST['ativo'] == 'on' ? 1 : 0;
        $ddd_celular = trim(substr($_POST['celular'], 0, 3));        
        $celular = str_replace("-","",substr($_POST['celular'], 3, 10));
        $ddd_telefone = substr(str_replace("-","", trim($_POST['telefone'])), 0, 2);
        $telefone = substr(str_replace("-","", trim($_POST['telefone'])), 2, 9);
        $cpf_cnpj = str_replace(array("-",".","/"), "", trim($_POST['cpf_cnpj']));
        $_POST["valor"] = str_replace(",", ".", $_POST["valor"]);
        $_POST["taxa_cc"] = str_replace(",", ".", $_POST["taxa_cc"]);
        $_POST["taxa_cd"] = str_replace(",", ".", $_POST["taxa_cd"]);
        $_POST["taxa_rp"] = str_replace(",", ".", $_POST["taxa_rp"]);
    }

    if ($_GET['action'] == 'add') { /* ------------ INSERT ------------ */

        $query = 'SELECT 1 FROM MW_BASE WHERE DS_NOME_TEATRO = ? OR DS_NOME_BASE_SQL = ?';
        $params = array($_POST['nome'], $_POST['nomeSql']);
        $result = executeSQL($mainConnection, $query, $params);
        if (hasRows($result)) {
            echo 'Já existe um registro cadastrado com esse nome/nome de base.';
            exit();
        }

        $query = "SELECT ID_CLIENTE FROM MW_CLIENTE WHERE DS_NOME = '". $_POST['nomeSql'] ."'";
        $result = executeSQL($mainConnection, $query, array());
        if(hasRows($result)){
            while($cliente = fetchResult($result)){
                $idCliente = $cliente["ID_CLIENTE"];
            }
        }else{
            $query = "SELECT MAX(id_cliente) + 1 AS ID_CLIENTE FROM mw_cliente";
            $result = executeSQL($mainConnection, $query, array(), true);
            $idCliente = $result["ID_CLIENTE"];

            beginTransaction($mainConnection);
            $query = "INSERT INTO mw_cliente ([id_cliente], [ds_nome],[ds_sobrenome],
                      [in_recebe_info],[in_recebe_sms],[in_concorda_termos],[dt_inclusao], in_assinante, cd_cpf)
                      VALUES (?, ?, ?, 1, 1, 1, GETDATE(), 'S', '-1')";
            $params = array($idCliente, utf8_decode($_POST['nomeSql']), utf8_decode($_POST['nomeSql']));
            $rsCliente = executeSQL($mainConnection, $query, $params);
            $retorno = sqlErrors();
            if($rsCliente){
                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Locais');
                $log->__set('parametros', $params);
                $log->__set('log', $query);
                $log->save($mainConnection);
                commitTransaction($mainConnection);
            }else{
                rollbackTransaction($mainConnection);
                echo $retorno[0]['message'];
                exit();
            }
        }        

        $query = "INSERT INTO MW_BASE (
                    DS_NOME_BASE_SQL,
                    DS_NOME_TEATRO,
                    IN_ATIVO,
                    DS_RZ_SOCIAL,
                    CD_CPF_CNPJ,
                    QT_PRAZO_REPASSE_EM_DIAS,
                    DS_NOME_BANCO,
                    DS_NR_BANCO,
                    DS_NR_AGENCIA,
                    DS_NR_CONTA,
                    IN_POUPANCA_CC,
                    DS_NOME_CONTATO,
                    DS_DDD_TEL_FIXO,
                    DS_TEL_FIXO,
                    DS_DDD_CEL,
                    DS_CEL,
                    DS_EMAIL,
                    VL_TAXA_CARTAO_CRED,
                    VL_TAXA_CARTAO_DEB,
                    VL_TAXA_REPASSE,
                    VL_INGRESSO,
                    ID_CLIENTE,
                    DS_MSG_DEPOIS_VENDA,
                    DS_URL_DEPOIS_VENDA
                    )
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array(utf8_decode($_POST['nomeSql']),
                        utf8_decode($_POST['nome']),
                        $_POST['ativo'],
                        utf8_encode2($_POST['razao_social']),
                        $cpf_cnpj,
                        $_POST['prazo'],
                        utf8_decode($_POST['banco']),
                        $_POST['numero_banco'],
                        $_POST['numero_agencia'],
                        $_POST['numero_conta'],
                        $_POST['tipo_conta'],
                        utf8_encode2($_POST['contato']),
                        $ddd_telefone,
                        trim($telefone),
                        $ddd_celular,
                        $celular,
                        $_POST['email'],
                        $_POST['taxa_cc'],
                        $_POST['taxa_cd'],
                        $_POST['taxa_rp'],
                        $_POST['valor'],
                        $idCliente,
                        utf8_encode2($_POST['msg_pos_venda']),
                        $_POST['url_msg']
                    );
        
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Locais');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

        if (executeSQL($mainConnection, $query, $params)) {
            $query = 'SELECT ID_BASE FROM MW_BASE WHERE DS_NOME_TEATRO = ?';
            $params = array(utf8_decode($_POST['nome']));
            $rs = executeSQL($mainConnection, $query, $params, true);
            $retorno = 'true?id=' . $rs['ID_BASE'];
        } else {
            $retorno = sqlErrors();
        }
    } else if ($_GET['action'] == 'update' and isset($_GET['id'])) { /* ------------ UPDATE ------------ */

        $query = "SELECT ID_CLIENTE FROM MW_CLIENTE WHERE DS_NOME = '". $_POST['nomeSql'] ."'";
        $result = executeSQL($mainConnection, $query, array());
        if(hasRows($result)){
            while($cliente = fetchResult($result)){
                $idCliente = $cliente["ID_CLIENTE"];
            }
        }else{
            $query = "SELECT MAX(id_cliente) + 1 AS ID_CLIENTE FROM mw_cliente";
            $result = executeSQL($mainConnection, $query, array(), true);
            $idCliente = $result["ID_CLIENTE"];

            beginTransaction($mainConnection);
            $query = "INSERT INTO mw_cliente ([id_cliente], [ds_nome],[ds_sobrenome],
                      [in_recebe_info],[in_recebe_sms],[in_concorda_termos],[dt_inclusao], in_assinante, cd_cpf)
                      VALUES (?, ?, ?, 1, 1, 1, GETDATE(), 'S', '-1')";
            $params = array($idCliente, utf8_decode($_POST['nomeSql']), utf8_decode($_POST['nomeSql']));
            $rsCliente = executeSQL($mainConnection, $query, $params);
            $retorno = sqlErrors();
            if($rsCliente){
                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Locais');
                $log->__set('parametros', $params);
                $log->__set('log', $query);
                $log->save($mainConnection);
                commitTransaction($mainConnection);
            }else{
                rollbackTransaction($mainConnection);
                echo $retorno[0]['message'];
                exit();
            }
        }

        $query = "UPDATE MW_BASE SET
                    DS_NOME_BASE_SQL = ?,
                    DS_NOME_TEATRO = ?,
                    IN_ATIVO = ?,
                    DS_RZ_SOCIAL = ?,
                    CD_CPF_CNPJ = ?,
                    QT_PRAZO_REPASSE_EM_DIAS = ?,
                    DS_NOME_BANCO = ?,
                    DS_NR_BANCO = ?,
                    DS_NR_AGENCIA = ?,
                    DS_NR_CONTA = ?,
                    IN_POUPANCA_CC = ?,
                    DS_NOME_CONTATO = ?,
                    DS_DDD_TEL_FIXO = ?,
                    DS_TEL_FIXO = ?,
                    DS_DDD_CEL = ?,
                    DS_CEL = ?,
                    DS_EMAIL = ?,
                    VL_TAXA_CARTAO_CRED = ?,
                    VL_TAXA_CARTAO_DEB = ?,
                    VL_TAXA_REPASSE = ?,
                    VL_INGRESSO = ?,
                    ID_CLIENTE = ?,
                    DS_MSG_DEPOIS_VENDA = ?,
                    DS_URL_DEPOIS_VENDA = ?
                 WHERE
                    ID_BASE = ?";
        $params = array(utf8_decode($_POST['nomeSql']),
                        utf8_decode($_POST['nome']),
                        $_POST['ativo'],
                        utf8_encode2($_POST['razao_social']),
                        $cpf_cnpj,
                        $_POST['prazo'],
                        utf8_decode($_POST['banco']),
                        $_POST['numero_banco'],
                        $_POST['numero_agencia'],
                        $_POST['numero_conta'],
                        $_POST['tipo_conta'],
                        utf8_encode2($_POST['contato']),
                        $ddd_telefone,
                        trim($telefone),
                        $ddd_celular,
                        $celular,
                        $_POST['email'],
                        $_POST['taxa_cc'],
                        $_POST['taxa_cd'],
                        $_POST['taxa_rp'],
                        $_POST['valor'],
                        $idCliente,
                        utf8_encode2($_POST['msg_pos_venda']),
                        $_POST['url_msg'],
                        $_GET['id']);

        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Locais');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

        if (executeSQL($mainConnection, $query, $params)) {
            $retorno = 'true?id=' . $_GET['id'];
        } else {
            $retorno = sqlErrors();
        }
    } else if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */

        $query = 'DELETE FROM MW_BASE WHERE ID_BASE = ?';
        $params = array($_GET['id']);

        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Locais');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

        if (executeSQL($mainConnection, $query, $params)) {
            $retorno = 'true';
        } else {
            $retorno = sqlErrors();
        }
    } else if ($_GET['action'] == 'load' and isset($_GET['id'])){
        $query = 'SELECT
                   [ID_BASE]
                  ,[DS_NOME_BASE_SQL]
                  ,[DS_NOME_TEATRO]
                  ,[IN_ATIVO]
                  ,[DS_RZ_SOCIAL]
                  ,[CD_CPF_CNPJ]
                  ,[QT_PRAZO_REPASSE_EM_DIAS]
                  ,[DS_NOME_BANCO]
                  ,[DS_NR_BANCO]
                  ,[DS_NR_AGENCIA]
                  ,[DS_NR_CONTA]
                  ,[IN_POUPANCA_CC]
                  ,[DS_NOME_CONTATO]
                  ,[DS_DDD_TEL_FIXO]
                  ,[DS_TEL_FIXO]
                  ,[DS_DDD_CEL]
                  ,[DS_CEL]
                  ,[DS_EMAIL]
                  ,[VL_TAXA_CARTAO_CRED]
                  ,[VL_TAXA_CARTAO_DEB]
                  ,[VL_TAXA_REPASSE]
                  ,[VL_INGRESSO]
                  ,[DS_MSG_DEPOIS_VENDA]
                  ,[DS_URL_DEPOIS_VENDA]
                  FROM MW_BASE WHERE ID_BASE = ?';
        $params = array($_GET['id']);
        $result = executeSQL($mainConnection, $query, $params);

        while ($rs = fetchResult($result)) {            
            $ret = array(
                "id_local" => $rs["ID_BASE"],
                "nome" => utf8_encode2($rs["DS_NOME_TEATRO"]),
                "nomeSql" => $rs["DS_NOME_BASE_SQL"],
                "ativo" => $rs["IN_ATIVO"],
                "razao_social" => utf8_encode2($rs["DS_RZ_SOCIAL"]),
                "cpf_cnpj" => $rs["CD_CPF_CNPJ"],
                "prazo" => $rs["QT_PRAZO_REPASSE_EM_DIAS"],
                "banco" => utf8_encode2($rs["DS_NOME_BANCO"]),
                "numero_banco" => $rs["DS_NR_BANCO"],
                "numero_agencia" => $rs["DS_NR_AGENCIA"],
                "numero_conta" => $rs["DS_NR_CONTA"],
                "tipo_conta" => $rs["IN_POUPANCA_CC"],
                "contato" => utf8_encode2($rs["DS_NOME_CONTATO"]),
                "telefone" => $rs["DS_DDD_TEL_FIXO"] ." ". substr($rs["DS_TEL_FIXO"], 0, 4) ."-". substr($rs["DS_TEL_FIXO"], 4, 5),
                "celular" => $rs["DS_DDD_CEL"] ." ". substr($rs["DS_CEL"], 0, 4) ."-". substr($rs["DS_CEL"], 4, 5),
                "email" => $rs["DS_EMAIL"],
                "taxa_cc" => number_format($rs["VL_TAXA_CARTAO_CRED"], 2, ",", "."),
                "taxa_cd" => number_format($rs["VL_TAXA_CARTAO_DEB"], 2, ",", "."),
                "taxa_rp" => number_format($rs["VL_TAXA_REPASSE"], 2, ",", "."),
                "valor" => number_format($rs["VL_INGRESSO"], 2, ",", "."),
                "msg_pos_venda" => $rs["DS_MSG_DEPOIS_VENDA"],
                "url_msg" => $rs["DS_URL_DEPOIS_VENDA"]
            );
        }
        $retorno = json_encode($ret);
    }

    if (is_array($retorno)) {
        echo $retorno[0]['message'];
    } else {
        echo $retorno;
    }
}
?>