<?php
require_once('../settings/functions.php');

if (acessoPermitido($mainConnection, $_SESSION['admin'], 661, true)) {
	if ($_GET['action'] == 'add') {

		$query = "SET NOCOUNT ON;INSERT INTO mw_permissao_split (id_usuario, id_produtor, id_recebedor, dt_criado, dt_alterado, bit_saque, bit_antecipacao) VALUES (?, ?, ?, GETDATE(), GETDATE(), ?, ?); SELECT @@IDENTITY id_permissao_split;SET NOCOUNT OFF;";
		
		$params = array($_POST["id_usuario"], 
						$_POST["id_produtor"] == 0 ? NULL : $_POST["id_produtor"],
						$_POST["id_recebedor"] == 0 ? NULL : $_POST["id_recebedor"],
						($_POST["bit_saque"] == null ? 0 : $_POST["bit_saque"]),
						($_POST["bit_antecipacao"] == null ? 0 : $_POST["bit_antecipacao"]));

		$rs = executeSQL($mainConnection, $query, $params, true);
		if(sqlErrors()) {
			$retorno = sqlErrors();
		}
		else {
			$retorno = "OK";
		}
	}  else if ($_GET['action'] == 'check'){
		if ($_GET["id_produtor"] == null || $_GET["id_produtor"] == "0") {
			$query = "SELECT TOP 1 1 
			FROM mw_permissao_split WHERE id_usuario = ? AND id_produtor IS NULL";
			$param = array($_GET["id_usuario"]);
		}
		else {
			if ($_GET["id_recebedor"] == null || $_GET["id_recebedor"] == "0") {
				$query = "SELECT TOP 1 1 
				FROM mw_permissao_split WHERE id_usuario = ? AND id_produtor = ? AND id_recebedor IS NULL";
				$param = array($_GET["id_usuario"], $_GET["id_produtor"]);				
			}
			else {
				$query = "SELECT TOP 1 1 
				FROM mw_permissao_split WHERE id_usuario = ? AND id_produtor = ? AND id_recebedor = ?";
				$param = array($_GET["id_usuario"], $_GET["id_produtor"], $_GET["id_recebedor"]);				
			}
		}		

		$stmt  = executeSQL($mainConnection, $query, $param);		
		$retorno = array(
			"ok" => true);

		while($rs = fetchResult($stmt)){
        	$retorno = array("ok" => false);
		}
		
		$retorno = json_encode($retorno);

    } else if ($_GET['action'] == 'update' and isset($_GET['id'])) {

		$query = "UPDATE mw_permissao_split
				  SET id_usuario = ?,
				  id_produtor = ?,
				  id_recebedor = ?,
				  bit_saque = ?,
				  bit_antecipacao = ?,
				  dt_alterado = GETDATE()
				  WHERE id_permissaosplit = ?";

		$params = array($_POST["id_usuario"], 
		$_POST["id_produtor"] == 0 ? NULL : $_POST["id_produtor"],
		$_POST["id_recebedor"] == 0 ? NULL : $_POST["id_recebedor"],
		($_POST["bit_saque"] == null ? 0 : $_POST["bit_saque"]),
		($_POST["bit_antecipacao"] == null ? 0 : $_POST["bit_antecipacao"]),
		$_POST['id_permissaosplit']);

		$rs = executeSQL($mainConnection, $query, $params);
		$retorno = sqlErrors();
	} else if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */

		$query = 'DELETE FROM mw_permissao_split WHERE id_permissaosplit = ?';
        $params = array($_GET['id']);
        
        if (executeSQL($mainConnection, $query, $params)) {
            $retorno = 'true';
        } else {
            $retorno = sqlErrors();
        }

	 } else if ($_GET['action'] == 'load'){
		$query = "SELECT
                   ps.id_permissaosplit
                   ,ps.id_usuario
                   ,ISNULL(ps.id_produtor,0)
                   ,ISNULL(ps.id_recebedor,0)
                   ,ps.dt_criado
                   ,ps.dt_alterado
				   ,ISNULL(p.ds_razao_social, 'Todos') RazaoSocialProdutor
				   ,p.cd_cpf_cnpj DocumentoProdutor
				   ,ISNULL(r.ds_razao_social, 'Todos') RazaoSocialRecebedor
				   ,r.cd_cpf_cnpj DocumentoRecebedor
				   ,u.ds_nome NomeUsuario
				   ,ps.bit_saque
				   ,ps.bit_antecipacao
                  FROM mw_permissao_split ps
				  INNER JOIN mw_usuario u ON ps.id_usuario=u.id_usuario
				  LEFT JOIN mw_produtor p ON ps.id_produtor=p.id_produtor
				  LEFT JOIN mw_recebedor r ON ps.id_recebedor=r.id_recebedor";

		$params = array();
		if (isset($_GET['id'])) {
			$query .= ' WHERE id_permissaosplit = ?';
			$params = array($_GET['id']);
		}
		
		$result = executeSQL($mainConnection, $query, $params);
		$json = array();

        while ($rs = fetchResult($result)) {            
            $json[] = array(
            	"id_permissaosplit" => $rs["id_permissaosplit"],
            	"id_usuario" => $rs["id_usuario"],
            	"id_produtor" => $rs["id_produtor"],
            	"id_recebedor" => $rs["id_recebedor"],
            	"dt_criado" => $rs["dt_criado"],
            	"dt_alterado" => $rs["dt_alterado"],
            	"bit_saque" => $rs["bit_saque"],
            	"bit_antecipacao" => $rs["bit_antecipacao"],
            	"DocumentoProdutor" => $rs["DocumentoProdutor"],
            	"DocumentoRecebedor" => $rs["DocumentoRecebedor"],
            	"RazaoSocialProdutor" => utf8_encode2($rs["RazaoSocialProdutor"]),
            	"RazaoSocialRecebedor" => utf8_encode2($rs["RazaoSocialRecebedor"]),
            	"NomeUsuario" => utf8_encode2($rs["NomeUsuario"])
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