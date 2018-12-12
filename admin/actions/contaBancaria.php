<?php

require_once('../settings/pagarme_functions.php');

if (acessoPermitido($mainConnection, $_SESSION['admin'], 630, true)) {

	if ($_GET['action'] != 'delete') {
		
	}

	if ($_GET['action'] == 'add') {

		$query = "BEGIN TRANSACTION
				  SET NOCOUNT ON

				  INSERT INTO mw_conta_bancaria (cd_banco, cd_agencia, cd_conta_bancaria, dv_conta_bancaria, cd_tipo_conta, id_produtor, nr_percentual_split, in_ativo)
				  VALUES(?, ?, ?, ?, ?, ?, ?, ?)

				  SELECT @@IDENTITY AS id

				  SET NOCOUNT OFF
				  COMMIT TRANSACTION";

		$params = array($_POST["banco"], 
						trim($_POST["agencia"]), 
						trim($_POST["conta_bancaria"]), 
						trim($_POST["dv_conta_bancaria"]), 
						$_POST["tipo"],
						$_GET["produtor"],
						trim($_POST["split"]),
						$_POST["status"]);

		$rs = executeSQL($mainConnection, $query, $params, true);
		
		$recipient = salvarContaBancariaPagarme($_POST, $_GET["produtor"]);

		$query = "UPDATE mw_conta_bancaria SET recipient_id = ? WHERE id_conta_bancaria = ?";
		$param = array($recipient["id"], $rs["id"]);
		executeSQL($mainConnection, $query, $param);

		$retorno = 'true?id=' . $rs["id"];

		if(sqlErrors()) {
			$retorno = sqlErrors();
		}
	} else if ($_GET['action'] == 'update' and isset($_GET['id'])) {

		$query = "UPDATE mw_conta_bancaria
				  SET cd_banco = ?, 
					  cd_agencia = ?,
					  cd_conta_bancaria = ?,
					  dv_conta_bancaria = ?,
					  cd_tipo_conta = ?,
					  id_produtor = ?,
					  nr_percentual_split = ?,
					  in_ativo = ?
				  WHERE id_conta_bancaria = ?";

		$params = array($_POST["banco"], 
						trim($_POST["agencia"]), 
						trim($_POST["conta_bancaria"]),
						trim($_POST["dv_conta_bancaria"]),
						$_POST["tipo"], 
						$_GET["produtor"],
						trim($_POST["split"]),
						$_POST["status"],
						$_GET['id']);

		if (executeSQL($mainConnection, $query, $params)) {
            $retorno = 'true?id=' . $_GET['id'];
        } else {
            $retorno = sqlErrors();
        }

	} else if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */

		$query = 'UPDATE mw_conta_bancaria SET in_ativo = 0 WHERE id_conta_bancaria = ?';
        $params = array($_GET['id']);
        
        if (executeSQL($mainConnection, $query, $params)) {
            $retorno = 'true';
        } else {
            $retorno = sqlErrors();
        }

	 } else if ($_GET['action'] == 'load' and isset($_GET['id'])){
		$query = 'SELECT
                   id_conta_bancaria,
                   cd_banco,
                   cd_agencia,
                   cd_conta_bancaria,
                   dv_conta_bancaria,
                   cd_tipo_conta,
                   id_produtor,
                   nr_percentual_split,
                   in_ativo
                  FROM mw_conta_bancaria WHERE id_conta_bancaria = ?';
        $params = array($_GET['id']);
        $result = executeSQL($mainConnection, $query, $params);

        while ($rs = fetchResult($result)) {            
            $ret = array(
            	"id" => $rs["id_conta_bancaria"],
            	"banco" => $rs["cd_banco"],
            	"agencia" => $rs["cd_agencia"],
            	"conta_bancaria" => $rs["cd_conta_bancaria"],
            	"dv_conta_bancaria" => $rs["dv_conta_bancaria"],
            	"tipo" => $rs["cd_tipo_conta"],
            	"split" => $rs["nr_percentual_split"],
            	"status" => $rs["in_ativo"]
            );
        }
        $retorno = json_encode($ret);

    } else if ($_GET['action'] == 'check' and isset($_GET['produtor'])){
    	$query = "SELECT SUM(nr_percentual_split) AS split FROM mw_conta_bancaria WHERE id_produtor = ? AND in_ativo = 1 AND (id_conta_bancaria != ? OR ? = -1)";
    	$param = array($_GET["produtor"], $_GET["conta"], $_GET["conta"]);
    	$stmt  = executeSQL($mainConnection, $query, $param, true);
    	$retorno = (!isset($stmt["split"]) || $stmt["split"] == null) ? 0 : $stmt["split"];
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