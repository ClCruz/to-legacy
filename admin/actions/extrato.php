<?php

require_once('../settings/pagarme_functions.php');
require_once('../log4php/log.php');

if (acessoPermitido($mainConnection, $_SESSION['admin'], 640, true)) {

	if ($_GET['action'] != 'delete') {
		$ddd_celular = trim(substr($_POST['celular'], 0, 3));
        $celular = str_replace("-","",substr($_POST['celular'], 3, 10));
        $ddd_telefone = (trim($_POST['telefone']) != "") ? substr(str_replace("-","", trim($_POST['telefone'])), 0, 2) : "";
        $telefone = (trim($_POST['telefone']) != "") ? substr(str_replace("-","", trim($_POST['telefone'])), 2, 9) : "";
	}

	if ($_GET['action'] == 'add') {

		if (!isset($_POST["razao_social"]) || empty($_POST["razao_social"])) {
			echo "O campo Razão Social é Obrigatório!";
			die();
		}

		if (!isset($_POST["cpf_cnpj"]) || empty($_POST["cpf_cnpj"])) {
			echo "O campo CPF / CNPJ é Obrigatório!";
			die();
		}

		if (!isset($_POST["nome"]) || empty($_POST["nome"])) {
			echo "O campo Nome é Obrigatório!";
			die();
		}

		$query = "INSERT INTO mw_produtor VALUES(?, ?, ?, ?, ?, ?, ?, ?, 1);";
		$params = array(strtoupper(utf8_encode2(trim($_POST["razao_social"]))), 
						trim($_POST["cpf_cnpj"]), 
						ucwords(utf8_encode2(trim($_POST["nome"]))), 
						trim(strtolower($_POST["email"])), 
						trim($ddd_telefone),
						trim($telefone), 
						$ddd_celular, 
						$celular);

		$rs = executeSQL($mainConnection, $query, $params);
		$retorno = 'true?id=' . $rs["ID"];
		if(sqlErrors()) {
			$retorno = sqlErrors();
		}
	} else if ($_GET['action'] == 'update' and isset($_GET['id'])) {

		$query = "UPDATE mw_produtor 
				  SET ds_razao_social = ?, 
					  ds_nome_contato = ?,
					  cd_cpf_cnpj = ?,
					  cd_email = ?,
					  ds_ddd_telefone = ?,
					  ds_telefone = ?,
					  ds_ddd_celular = ?,
					  ds_celular = ?,
					  in_ativo = 1
				 WHERE id_produtor = ?";

		$params = array(strtoupper(utf8_encode2(trim($_POST["razao_social"]))), 						
						ucwords(utf8_encode2(trim($_POST["nome"]))), 
						trim($_POST["cpf_cnpj"]), 
						trim(strtolower($_POST["email"])), 
						$ddd_telefone,
						trim($telefone), 
						$ddd_celular, 
						trim($celular),
						$_GET['id']);

		if (executeSQL($mainConnection, $query, $params)) {
            $retorno = 'true?id=' . $_GET['id'];
        } else {
            $retorno = sqlErrors();
        }

	} else if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */

		$query = 'UPDATE mw_produtor SET in_ativo = 0 WHERE id_produtor = ?';
        $params = array($_GET['id']);
        
        if (executeSQL($mainConnection, $query, $params)) {
            $retorno = 'true';
        } else {
            $retorno = sqlErrors();
        }

	} else if ($_GET['action'] == 'load'){
		$aux = consultarExtratoRecebedorPagarme($_POST["recebedor"]
		, $_POST["status"]
		, $_POST["start_date"]
		, $_POST["end_date"]
		, $_POST["count"]
		, $_POST["evento"]);

		$retorno = json_encode($aux);;
	} else if ($_GET['action'] == 'listpayables'){
		log_trace("Call of action... " . $_GET['action']);
		$aux = listPayables($_POST["recebedor"]
		, "waiting_funds"
		, $_POST["evento"]
		, $_POST["count"]
		, 1);

		$retorno = json_encode($aux);;
	} else if ($_GET['action'] == 'load_saldo'){
		$aux = consultarSaldoRecebedorPagarme($_POST["recebedor"]);
		$retorno = $aux->__toJSON(true);
	} else if ($_GET["action"] == 'load_recebedor') {
		$query = "
	SELECT 
		id_recebedor
		,ds_razao_social
		,cd_cpf_cnpj
		,recipient_id
		,HasPermission
		,id_gateway
	FROM (
	SELECT 
		r.id_recebedor
		,r.ds_razao_social
		,r.cd_cpf_cnpj
		,r.recipient_id
		,ISNULL((SELECT 1 FROM mw_permissao_split sub WHERE sub.id_usuario=?
			AND (sub.id_produtor=r.id_produtor OR sub.id_recebedor IS NULL)
			AND (sub.id_recebedor=r.id_recebedor OR sub.id_recebedor IS NULL)),0) HasPermission
		,p.id_gateway
	FROM mw_recebedor r
	INNER JOIN mw_produtor p ON r.id_produtor=p.id_produtor
	WHERE r.id_produtor = ? AND r.in_ativo=1) AS recebedor
	WHERE HasPermission=1
	ORDER BY ds_razao_social
		";
		$params = array($_SESSION["admin"], $_POST["produtor"]);
		$result = executeSQL($mainConnection, $query, $params);
		$json = array();
		while ($rs = fetchResult($result)) {
			$json[] = array("id_recebedor" => $rs["id_recebedor"],
							"ds_razao_social" => utf8_encode2($rs["ds_razao_social"]),
							"cd_cpf_cnpj" => $rs["cd_cpf_cnpj"],
							"id_gateway" => $rs["id_gateway"],
							"recipient_id" => $rs["recipient_id"]);
		}
		$retorno = json_encode($json);
	}  else if ($_GET["action"] == 'load_evento') {
		$query = "SELECT id_base FROM mw_base b WHERE in_ativo = 1";
    	$stmt = executeSQL($mainConnection, $query, array());
		
		$pecas = array();
    	while ($rs = fetchResult($stmt)) {
    		$id_base = $rs["id_base"];

    		$conn = getConnection($id_base);

    		$query = "SELECT CodPeca FROM tabPeca tp WHERE tp.id_produtor = ?";
    		$stmt2 = executeSQL($conn, $query, array($_GET["produtor"]));
    		
    		while ($rs2 = fetchResult($stmt2)) {
    			$pecas[] = array("CodPeca" => $rs2["CodPeca"], "id_base" => $id_base);
    		}
    	}

    	$eventos = array();
    	for($i = 0; $i <= count($pecas); $i++) {
    		$query = "SELECT id_evento, ds_evento FROM mw_evento e WHERE e.CodPeca = ? AND e.id_base = ? AND in_ativo = 1 ORDER BY ds_evento";
    		$param = array($pecas[$i]["CodPeca"], $pecas[$i]["id_base"]);
    		$stmt = executeSQL($mainConnection, $query, $param);
    		while ($rs = fetchResult($stmt)) {
    			$eventos[] = array("id_evento" => $rs["id_evento"], "ds_evento" => utf8_encode2($rs["ds_evento"]));
    		}
    	}

    	$retorno = json_encode($eventos);
	} else if ($_GET['action'] == 'sacar') {
		$ret = efetuarSaquePagarme($_GET["recebedor"], $_POST["valor-saque"]);
		$retorno = json_encode($ret);
	} else if ($_GET['action'] == 'taxasaque') {
		$retAux = consultarSaldoRecebedorPagarme($_POST["recebedor"]);
		$waiting_funds = $retAux["waiting_funds"]["amount"];
		$transferred = $retAux["transferred"]["amount"];
		$available = $retAux["available"]["amount"];
		$retTaxa = consultarTaxaSaque();

		$ret = array("waiting_funds"=> $waiting_funds
		,"transferred"=> $transferred
		,"available"=> $available
		,"taxa"=> $retTaxa);
		$retorno = json_encode($ret);
	} else if ($_GET['action'] == 'antecipacao') {
		$ret = efetuarAntecipacaoPagarme($_GET["recebedor"], $_POST["valor"], $_POST["data"], $_POST["periodo"]);
		$retorno = json_encode($ret);
	} else if ($_GET['action'] == 'verificaantecipacao') {
		$ret = verificarAntecipacao($_GET["recebedor"], $_POST["valor"], $_POST["data"], $_POST["periodo"]);
		$retorno = $ret;
	} else if ($_GET['action'] == 'antecipacaomaxmin') {
		$ret = verificaMinimoMaximoAntecipacao($_GET["recebedor"], $_POST["data"], $_POST["periodo"]);
		$retorno = $ret;
	} else if ($_GET['action'] == 'gettransaction') {
		$ret = getTransaction($_GET["transaction_id"]);
		// error_log($ret);
		$retorno = $ret;
	} else if ($_GET['action'] == 'listantecipations') {
		$ret = consultarAntecipaveis($_GET["recebedor"]);
		$retorno = json_encode($ret);
		// error_log($ret);
	} else if ($_GET['action'] == 'listtransfer') {
		$ret = consultarTransferencias($_GET["recebedor"]);
		// error_log($ret);
		$retorno = json_encode($ret);
	} 
	else {
		error_log("Action: ".$_GET['action']);
		$retorno = "Nenhuma ação executada.";
	}

	if (is_array($retorno)) {
        echo $retorno[0]['message'];
    } else {
        echo $retorno;
    }
}

?>