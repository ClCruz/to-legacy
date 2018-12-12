<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 650, true)) {

	if ($_GET['action'] != 'delete') {
		
	}

	if ($_GET['action'] == 'add') {
		$query = "INSERT INTO mw_regra_split 
		(id_produtor, id_evento, id_recebedor, 
		liable, charge_processing_fee, in_ativo, 
		percentage_credit_web, percentage_debit_web, percentage_boleto_web, 
		percentage_credit_box_office, percentage_debit_box_office, nr_percentual_split) VALUES 
		(?, ?, ?, 
		?, ?, ?,
		?, ?, ?,
		?, ?, ?);";
		// $params = array($_GET["produtor"], $_GET["evento"], $_POST["recebedor"], $_POST["split"], $_POST["liable"] == null ? 0 : 1, $_POST["charge_processing_fee"] == null ? 0 : 1);
		$params = array($_GET["produtor"], $_GET["evento"], $_POST["recebedor"], 
			1, ($_POST["charge_processing_fee"] == null ? 0 : $_POST["charge_processing_fee"]), 1,
			floatval($_POST["percentage_credit_web"]), 
			floatval($_POST["percentage_debit_web"]), 
			floatval($_POST["percentage_boleto_web"]),
			floatval($_POST["percentage_credit_box_office"]), 
			floatval($_POST["percentage_debit_box_office"]),
			floatval($_POST["percentage_credit_web"])
		);

		executeSQL($mainConnection, $query, $params, false);

		$retorno = 'true?id=';

		if(sqlErrors()) {
			$retorno = sqlErrors();
		}
	} else if ($_GET['action'] == 'update' and isset($_GET['id'])) {

		$query = "UPDATE mw_regra_split SET 
			charge_processing_fee = ? 
			,percentage_credit_web = ?
			,percentage_debit_web = ?
			,percentage_boleto_web = ?
			,percentage_credit_box_office = ?
			,percentage_debit_box_office = ?
			,nr_percentual_split = ? 
		WHERE id_regra_split = ?";
		// $query = "UPDATE mw_regra_split SET nr_percentual_split = ?, liable = ?, charge_processing_fee = ? WHERE id_regra_split = ?";

		$params = array(($_POST["charge_processing_fee"] == null ? 0 : $_POST["charge_processing_fee"]),
		floatval($_POST["percentage_credit_web"]), 
		floatval($_POST["percentage_debit_web"]), 
		floatval($_POST["percentage_boleto_web"]),
		floatval($_POST["percentage_credit_box_office"]), 
		floatval($_POST["percentage_debit_box_office"]),
		floatval($_POST["percentage_credit_web"]), 
		$_GET['id']);
		// $params = array(trim($_POST["split"]), $_POST["liable"] == null ? 0 : 1, $_POST["charge_processing_fee"] == null ? 0 : 1, $_GET['id']);

		if (executeSQL($mainConnection, $query, $params)) {
            $retorno = 'true?id=' . $_GET['id'];
        } else {
            $retorno = sqlErrors();
        }

	} else if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */

		$query = 'UPDATE mw_regra_split SET in_ativo = 0 WHERE id_regra_split = ?';
        $params = array($_GET['id']);
        
        if (executeSQL($mainConnection, $query, $params)) {
            $retorno = 'true';
        } else {
            $retorno = sqlErrors();
        }

	 } else if ($_GET['action'] == 'load' and isset($_GET['id'])){
		$query = 'SELECT
                   id_regra_split,
                   id_produtor,
                   id_evento,
                   id_recebedor,
				   liable,
				   charge_processing_fee,
                   in_ativo,
				   percentage_credit_web,
				   percentage_debit_web,
				   percentage_boleto_web,
				   percentage_credit_box_office,
				   percentage_debit_box_office
                  FROM mw_regra_split WHERE id_regra_split = ?';
        $params = array($_GET['id']);
        $result = executeSQL($mainConnection, $query, $params);

        while ($rs = fetchResult($result)) {            
            $ret = array(
            	"id" => $rs["id_regra_split"],
            	"produtor" => $rs["id_produtor"],
                "evento" => $rs["id_evento"],
                "recebedor" => $rs["id_recebedor"],
            	"liable" => $rs["liable"],
            	"charge_processing_fee" => $rs["charge_processing_fee"],
            	"percentage_credit_web" => $rs["percentage_credit_web"],
            	"percentage_debit_web" => $rs["percentage_debit_web"],
            	"percentage_boleto_web" => $rs["percentage_boleto_web"],
            	"percentage_credit_box_office" => $rs["percentage_credit_box_office"],
            	"percentage_debit_box_office" => $rs["percentage_debit_box_office"],
            	"status" => $rs["in_ativo"]
            );
        }
        $retorno = json_encode($ret);

    } else if ($_GET['action'] == 'check' and isset($_GET['produtor'])){
    	$query = "SELECT 
		SUM(percentage_credit_web) AS percentage_credit_web, 
		SUM(percentage_debit_web) AS percentage_debit_web, 
		SUM(percentage_boleto_web) AS percentage_boleto_web, 
		SUM(percentage_credit_box_office) AS percentage_credit_box_office, 
		SUM(percentage_debit_box_office) AS percentage_debit_box_office
		FROM mw_regra_split WHERE id_produtor = ? AND id_evento = ? AND id_recebedor != ? AND in_ativo = 1";
		$param = array($_GET["produtor"], $_GET["evento"], $_GET["recebedor"]);

		$stmt  = executeSQL($mainConnection, $query, $param);		
		$retorno = array(
			"percentage_credit_web" => 0,
			"percentage_debit_web" => 0,
			"percentage_boleto_web" => 0,
			"percentage_credit_box_office" => 0,
			"percentage_debit_box_office" => 0);

		while($rs = fetchResult($stmt)){
        	$retorno = array(
        					"percentage_credit_web" => $rs["percentage_credit_web"],
        					"percentage_debit_web" => $rs["percentage_debit_web"],
        					"percentage_boleto_web" => $rs["percentage_boleto_web"],
        					"percentage_credit_box_office" => $rs["percentage_credit_box_office"],
        					"percentage_debit_box_office" => $rs["percentage_debit_box_office"]);
		}

		
		$retorno = json_encode($retorno);

    } else if ($_GET['action'] == 'checkRecebedorOk') {
    	$query = "SELECT * 
    			  FROM mw_regra_split rs
    			  WHERE rs.id_produtor = ? AND rs.id_evento=? AND rs.in_ativo = 1 AND rs.id_recebedor=?";
        $stmt = executeSQL($mainConnection, $query, array($_GET["produtor"], $_GET["evento"], $_GET["recebedor"]));
        $json = array();
        while($rs = fetchResult($stmt)){
        	$json[] = array("id_regra_split" => $rs["id_regra_split"]);
		}
		//error_log("erro: " . print_r( sqlsrv_errors(), true));
        $retorno = json_encode($json);
	} else if ($_GET['action'] == 'load_split') {
    	$query = "SELECT * 
    			  FROM mw_regra_split rs 
    			  INNER JOIN mw_recebedor cb ON cb.id_recebedor = rs.id_recebedor 
    			  WHERE rs.id_produtor = ? AND rs.id_evento=? AND rs.in_ativo = 1";
        $stmt = executeSQL($mainConnection, $query, array($_POST["produtor"], $_POST["evento"]));
        $json = array();
        while($rs = fetchResult($stmt)){
        	$json[] = array("id_regra_split" => $rs["id_regra_split"],
        					"ds_razao_social" => utf8_encode2($rs["ds_razao_social"]),
        					"liable" => $rs["liable"],
        					"charge_processing_fee" => $rs["charge_processing_fee"],
        					"percentage_credit_web" => $rs["percentage_credit_web"],
        					"percentage_debit_web" => $rs["percentage_debit_web"],
        					"percentage_boleto_web" => $rs["percentage_boleto_web"],
        					"percentage_credit_box_office" => $rs["percentage_credit_box_office"],
        					"percentage_debit_box_office" => $rs["percentage_debit_box_office"],
        					"in_ativo" => $rs["in_ativo"]);
        }
        $retorno = json_encode($json);
	} else if ($_GET['action'] == 'load_evento') {
    	$query = "SELECT id_base FROM mw_base b WHERE in_ativo = 1";
    	$stmt = executeSQL($mainConnection, $query, array());
		
		$pecas = array();
    	while ($rs = fetchResult($stmt)) {
    		$id_base = $rs["id_base"];

    		$conn = getConnection($id_base);

    		$query = "SELECT CodPeca FROM tabPeca tp WHERE tp.id_produtor = ?";
    		$stmt2 = executeSQL($conn, $query, array($_POST["produtor"]));
    		
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
    } else if ($_GET['action'] == 'load_recebedor') {
    	$query = "SELECT id_recebedor, ds_razao_social FROM mw_recebedor cb WHERE id_produtor = ? AND in_ativo = 1 ORDER BY ds_razao_social";
    	$stmt = executeSQL($mainConnection, $query, array($_POST["produtor"]));
    	$json = array();
    	while ($rs = fetchResult($stmt)) {
    		$json[] = array("id_recebedor" => $rs["id_recebedor"], "ds_razao_social" => utf8_encode2($rs["ds_razao_social"]));
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