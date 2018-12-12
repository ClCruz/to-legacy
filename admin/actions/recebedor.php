<?php

require_once('../settings/pagarme_functions.php');

if (acessoPermitido($mainConnection, $_SESSION['admin'], 660, true)) {

	if ($_GET['action'] != 'delete') {
		$ddd_celular = trim(substr($_POST['celular'], 0, 3));
        $celular = str_replace("-","",substr($_POST['celular'], 3, 10));
        $ddd_telefone = (trim($_POST['telefone']) != "") ? substr(str_replace("-","", trim($_POST['telefone'])), 0, 2) : "";
        $telefone = (trim($_POST['telefone']) != "") ? substr(str_replace("-","", trim($_POST['telefone'])), 2, 9) : "";
	}

	if ($_GET['action'] == 'add') {

		$query = "EXEC SP_REC_INS001 ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
		$dv_agencia = "";

		if (!isset($_POST["dv_agencia"])) {
			$dv_agencia = "";
		}
		else {
			$dv_agencia = trim($_POST["dv_agencia"]);
		}
		
		$params = array(strtoupper(utf8_encode2(trim($_POST["razao_social"]))), 
						$_POST["cpf_cnpj"],
						ucwords(utf8_encode2(trim($_POST["nome"]))), 
						trim(strtolower($_POST["email"])), 
						trim($ddd_telefone),
						trim($telefone),
						trim($ddd_celular),
						trim($celular),
						$_POST["banco"], 
						trim($_POST["agencia"]),
						$dv_agencia,
						trim($_POST["conta_bancaria"]), 
						trim($_POST["dv_conta_bancaria"]), 
						$_POST["tipo"],
						$_GET["produtor"],
						$_POST["status"],
						$_POST["transfer_day"],
						"monthly",
						0);

		$rs = executeSQL($mainConnection, $query, $params, true);

		$queryProdutor = 'SELECT id_gateway
				  FROM mw_produtor
				  WHERE id_produtor=?';
		
		$paramsProdutor = array($_GET['produtor']);

		$resultProdutor = executeSQL($mainConnection, $queryProdutor, $paramsProdutor, true);

		$recipient_id = $_POST["recipient_id"];
		
		switch ($resultProdutor["id_gateway"]){
			case 6:
			case "6":
				//pagarme
				$recipient = salvarRecebedorPagarme($_POST);
				$recipient_id = $recipient["id"];
			break;
			case 7:
			case "7":
				//tipagos
			break;
		}

		$query = "UPDATE mw_recebedor SET recipient_id = ? WHERE id_recebedor = ?";
		$param = array($recipient_id, $rs["id"]);
		executeSQL($mainConnection, $query, $param);

		$retorno = 'true?id=' . $rs["id"];

		if(sqlErrors()) {
			$retorno = sqlErrors();
		}
	} else if ($_GET['action'] == 'addtp') {
		$exist = false;
		$query = 'SELECT TOP 1 1
				  FROM mw_recebedor
				  WHERE in_ativo=1 AND id_produtor=? AND recipient_id=?';
		
		$params = array($_GET['produtor'], "re_cjp1quawk05y68f60nz9pdmyp");

		$result = executeSQL($mainConnection, $query, $params);
		
		$json = array();

        while ($rs = fetchResult($result)) {            
            $exist = true;
        }

		if ($exist)
		{
			$retorno ="false";
		}
		else {

			$query = "EXEC SP_RECEBEDOR_INTUITI ?";//"EXEC SP_RECEBEDOR_TICKETPAY ?";

			$params = array($_GET["produtor"]);

			$rs = executeSQL($mainConnection, $query, $params, true);

			$retorno = 'true?id=' . $rs["id"];

			if(sqlErrors()) {
				$retorno = sqlErrors();
			}
		}
	} else if ($_GET['action'] == 'update' and isset($_GET['id'])) {

		$query = "UPDATE mw_recebedor
				  SET ds_razao_social = ?,
				  	  cd_cpf_cnpj = ?,
				  	  ds_nome = ?,
				  	  cd_email = ?,
				  	  ds_ddd_telefone = ?,
				  	  ds_telefone = ?,
				  	  ds_ddd_celular = ?,
				  	  ds_celular = ?,
				  	  cd_banco = ?, 
					  cd_agencia = ?,
					  dv_agencia = ?,
					  cd_conta_bancaria = ?,
					  dv_conta_bancaria = ?,
					  cd_tipo_conta = ?,
					  id_produtor = ?,
					  in_ativo = ?,
					  transfer_enabled = ?,
					  transfer_interval = ?,
					  transfer_day = ?,
					  recipient_id = ?,
				  WHERE id_recebedor = ?";

		$params = array(strtoupper(utf8_encode2(trim($_POST["razao_social"]))), 
						$_POST["cpf_cnpj"],
						ucwords(utf8_encode2(trim($_POST["nome"]))), 
						trim(strtolower($_POST["email"])), 
						trim($ddd_telefone),
						trim($telefone),
						trim($ddd_celular),
						trim($celular),
						$_POST["banco"], 
						trim($_POST["agencia"]),
						trim($_POST["dv_agencia"]),
						trim($_POST["conta_bancaria"]), 
						trim($_POST["dv_conta_bancaria"]), 
						$_POST["tipo"],
						$_GET["produtor"],
						$_POST["status"],
						0,
						"monthly",
						$_POST["transfer_day"],
						$_POST["recipient_id"],
						$_GET['id']);

		if (executeSQL($mainConnection, $query, $params)) {
			$query = "SELECT recipient_id FROM mw_recebedor WHERE id_recebedor = ?";
			$param = array($_GET['id']);
			$rs = executeSQL($mainConnection, $query, $param, true);

			atualizarRecebedorPagarme($_POST, $rs['recipient_id']);

            $retorno = 'true?id=' . $_GET['id'];
        } else {
            $retorno = sqlErrors();
        }

	} else if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */

		$query = 'UPDATE mw_recebedor SET in_ativo = 0 WHERE id_recebedor = ?';
        $params = array($_GET['id']);
        
        if (executeSQL($mainConnection, $query, $params)) {
            $retorno = 'true';
        } else {
            $retorno = sqlErrors();
        }

	 } else if ($_GET['action'] == 'load' and isset($_GET['id'])){
		$query = 'SELECT
                   id_recebedor,
                   ds_razao_social,
                   cd_cpf_cnpj,
                   ds_nome,
                   cd_email,
                   ds_ddd_telefone,
                   ds_telefone,
                   ds_ddd_celular,
                   ds_celular,
                   cd_banco,
                   cd_agencia,
                   dv_agencia,
                   cd_conta_bancaria,
                   dv_conta_bancaria,
                   cd_tipo_conta,
                   id_produtor,
                   in_ativo,
				   transfer_day,
				   transfer_interval,
				   transfer_enabled,
				   recipient_id
                  FROM mw_recebedor WHERE id_recebedor = ?';
        $params = array($_GET['id']);
        $result = executeSQL($mainConnection, $query, $params);

        while ($rs = fetchResult($result)) {            
            $ret = array(
            	"id" => $rs["id_recebedor"],
            	"razao_social" => utf8_encode2($rs["ds_razao_social"]),
            	"cpf_cnpj" => $rs["cd_cpf_cnpj"],
            	"nome" => utf8_encode2($rs["ds_nome"]),
            	"email" => $rs["cd_email"],
            	"telefone" => $rs["ds_ddd_telefone"] ." ". substr($rs["ds_telefone"], 0, 4) ."-". substr($rs["ds_telefone"], 4, 5),
            	"celular" => $rs["ds_ddd_celular"] ." ". substr($rs["ds_celular"], 0, 4) ."-". substr($rs["ds_celular"], 4, 5),
            	"banco" => $rs["cd_banco"],         
            	"agencia" => $rs["cd_agencia"],
            	"dv_agencia" => $rs["dv_agencia"],
            	"conta_bancaria" => $rs["cd_conta_bancaria"],
            	"dv_conta_bancaria" => $rs["dv_conta_bancaria"],
            	"tipo" => $rs["cd_tipo_conta"],
				"status" => $rs["in_ativo"],
				"transfer_day" => $rs["transfer_day"],
				"transfer_interval" => $rs["transfer_interval"],
				"transfer_enabled" => $rs["transfer_enabled"],
				"recipient_id" => $rs["recipient_id"]
            );
        }
        $retorno = json_encode($ret);

    } else if ($_GET['action'] == 'getgateway'){
    	$query = "SELECT id_gateway FROM mw_produtor WHERE id_produtor = ?";
    	$param = array($_GET["produtor"]);
    	$stmt  = executeSQL($mainConnection, $query, $param, true);
    	$retorno = $stmt["id_gateway"];
	} else if ($_GET['action'] == 'check' and isset($_GET['produtor'])){
    	$query = "SELECT SUM(nr_percentual_split) AS split FROM mw_conta_bancaria WHERE id_produtor = ? AND in_ativo = 1 AND (id_conta_bancaria != ? OR ? = -1)";
    	$param = array($_GET["produtor"], $_GET["conta"], $_GET["conta"]);
    	$stmt  = executeSQL($mainConnection, $query, $param, true);
    	$retorno = (!isset($stmt["split"]) || $stmt["split"] == null) ? 0 : $stmt["split"];
	}  else if ($_GET['action'] == 'selectload'){
		$query = 'SELECT
                   id_recebedor,
                   ds_razao_social,
                   cd_cpf_cnpj
				  FROM mw_recebedor
				  WHERE in_ativo=1 AND id_produtor=?
				  ORDER BY ds_razao_social';
		
		$params = array($_GET['id_produtor']);

		$result = executeSQL($mainConnection, $query, $params);
		
		$json = array();

        while ($rs = fetchResult($result)) {            
            $json[] = array(
            	"id_recebedor" => $rs["id_recebedor"],
            	"ds_razao_social" => utf8_encode2($rs["ds_razao_social"]),
            	"cd_cpf_cnpj" => $rs["cd_cpf_cnpj"]
            );
        }
        $retorno = json_encode($json);

	} else {
		$retorno = "Nenhuma ação executada.";
	}

	if (is_array($retorno)) {
        echo $retorno[0]['message'];
    } else {
        echo $retorno;
    }
}

?>