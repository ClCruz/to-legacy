<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 620, true)) {

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

		$query = "INSERT INTO mw_produtor VALUES(?, ?, ?, ?, ?, ?, ?, ?, 1, ?);";
		$params = array(strtoupper(utf8_encode2(trim($_POST["razao_social"]))), 
						trim($_POST["cpf_cnpj"]), 
						ucwords(utf8_encode2(trim($_POST["nome"]))), 
						trim(strtolower($_POST["email"])), 
						trim($ddd_telefone),
						trim($telefone), 
						$ddd_celular, 
						$celular,
						$_POST["id_gateway"]);

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
					  in_ativo = 1,
					  id_gateway = ?
				 WHERE id_produtor = ?";

		$params = array(strtoupper(utf8_encode2(trim($_POST["razao_social"]))), 						
						ucwords(utf8_encode2(trim($_POST["nome"]))), 
						trim($_POST["cpf_cnpj"]), 
						trim(strtolower($_POST["email"])), 
						trim($ddd_telefone),
						trim($telefone), 
						trim($ddd_celular), 
						trim($celular),
						$_POST["id_gateway"],
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

	 } else if ($_GET['action'] == 'load' and isset($_GET['id'])){
		$query = 'SELECT
                   p.id_produtor,
                   p.ds_razao_social,
                   p.ds_nome_contato,
                   p.cd_cpf_cnpj,
                   p.cd_email,
                   p.ds_ddd_telefone,
                   p.ds_telefone,
                   p.ds_ddd_celular,
                   p.ds_celular,
				   p.id_gateway,
				   g.ds_gateway
                  FROM mw_produtor p
				  LEFT JOIN mw_gateway g ON p.id_gateway=g.id_gateway 
				  WHERE id_produtor = ?';
        $params = array($_GET['id']);
        $result = executeSQL($mainConnection, $query, $params);

        while ($rs = fetchResult($result)) {            
            $ret = array(
            	"id" => $rs["id_produtor"],
            	"razao_social" => utf8_encode2($rs["ds_razao_social"]),
            	"nome" => utf8_encode2($rs["ds_nome_contato"]),
            	"cpf_cnpj" => $rs["cd_cpf_cnpj"],
            	"email" => $rs["cd_email"],
            	"telefone" => $rs["ds_ddd_telefone"] ." ". substr($rs["ds_telefone"], 0, 4) ."-". substr($rs["ds_telefone"], 4, 5),
				"celular" => $rs["ds_ddd_celular"] ." ". substr($rs["ds_celular"], 0, 4) ."-". substr($rs["ds_celular"], 4, 5),
				"id_gateway" => $rs["id_gateway"],
				"ds_gateway" => $rs["ds_gateway"]
            );
        }
        $retorno = json_encode($ret);

	} else if ($_GET['action'] == 'selectload'){
		$query = 'SELECT
                   id_produtor,
                   ds_razao_social,
                   cd_cpf_cnpj
				  FROM mw_produtor
				  WHERE in_ativo=1
				  ORDER BY ds_razao_social';
		
		$params = array();
        
		$result = executeSQL($mainConnection, $query, $params);
		
		$json = array();

        while ($rs = fetchResult($result)) {            
            $json[] = array(
            	"id_produtor" => $rs["id_produtor"],
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