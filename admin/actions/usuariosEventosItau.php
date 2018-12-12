<?php
	require_once('../settings/functions.php');

	function totalEventos($idBase, $idUsuario, $conn){
		$queryTotal = "SELECT ROW_NUMBER() OVER(ORDER BY E.DS_EVENTO) AS LINHA,
						CASE WHEN U.ID_USUARIO IS NOT NULL THEN 'checked' ELSE NULL END AS CHECKED
						FROM MW_EVENTO E
						LEFT JOIN MW_USUARIO_ITAU_EVENTO U ON U.ID_USUARIO = ? and E.ID_EVENTO = U.ID_EVENTO
						WHERE E.ID_BASE = ? AND E.IN_VENDE_ITAU = 1";
		
		$params = array($idUsuario, $idBase);
		$total = numRows($conn, $queryTotal, $params);
		if (!sqlErrors()) {
			return $total;
		} else {
			echo "Erro #001: <br>";
			print_r(sqlErrors());
			echo "<br>";
		}
	}
	
	/**
	 * Recupera todos os eventos na base de dados selecionada
	 * @param string $idUsuario Identificador do usuario
	 * @param int $idBase Identificador da base de dados selecionada
	 * @param int $offset Linha inicial da consulta na base de dados
	 * @param int $final Linha final da consulta 
	 * @param bool $paginacao True || False
	 * @param int $conn Identificador da conexao do banco de dados
	 * @return int $result Link identificador da conexao
	**/
	function recuperarEventos($idUsuario, $idBase, $offset, $final, $paginacao, $conn){
		if ($paginacao) {
			$row_number = "ROW_NUMBER() OVER(ORDER BY E.DS_EVENTO) AS LINHA, ";
			$between = "WHERE LINHA BETWEEN ".$offset." AND ".$final;
		} else {
			$row_number = " ";
			$between = " ";	
		}
		
		$query = 'WITH RESULTADO AS (
					SELECT E.ID_EVENTO, E.DS_EVENTO, ' . $row_number . '
					CASE WHEN U.ID_USUARIO IS NOT NULL THEN \'checked\' ELSE NULL END AS CHECKED
					FROM MW_EVENTO E
					LEFT JOIN MW_USUARIO_ITAU_EVENTO U ON U.ID_USUARIO = ? and E.ID_EVENTO = U.ID_EVENTO
					WHERE E.ID_BASE = ? AND E.IN_VENDE_ITAU = 1)
					SELECT * FROM RESULTADO ' . $between . ' ORDER BY 2';
		$params = array($idUsuario, $idBase);
		$result = executeSQL($conn, $query, $params);
		
		if (!sqlErrors()) {
			return $result;
		} else {
			echo "Erro #002: <br>";
			print_r(sqlErrors());
			echo "<br>";
		}
	}
	
	function checarEvento($idUsuario, $idEvento, $conn){
		$params = array($idUsuario, $idEvento);
		$sql = "SELECT 1 FROM MW_USUARIO_ITAU_EVENTO WHERE ID_USUARIO = ? AND ID_EVENTO = ?";
		
		if (numRows($conn, $sql, $params) > 0)	
			return true;
		else
			return false;
	}
	
	function cadastrarAcessoEvento($idUsuario, $idEvento, $idBase, $conn){
		if (is_array($idEvento)) {
			foreach ($idEvento as $value) {
				if (!checarEvento($idUsuario, $value, $conn)) {
					$sql = "INSERT INTO MW_USUARIO_ITAU_EVENTO (ID_USUARIO, ID_EVENTO) VALUES (?, ?)";
					$params = array($idUsuario, $value);
					executeSQL($conn, $sql, $params);

	                $log = new Log($_SESSION['admin']);
	                $log->__set('funcionalidade', 'SISBIN x Permissões x Bases x Eventos');
	                $log->__set('parametros', $params);
	                $log->__set('log', $sql);
	                $log->save($conn);
				}
			}
		} else if ($idEvento == "geral") {
			$result = recuperarEventos($idUsuario, $idBase, 0, 0, false, $conn);
			if($result){
				while ($idEvento = fetchResult($result)) {
					if (!$idEvento['CHECKED']) {
						$query = "INSERT INTO MW_USUARIO_ITAU_EVENTO (ID_USUARIO, ID_EVENTO) VALUES (?, ?)";
						$params = array($idUsuario, $idEvento['ID_EVENTO']);
						executeSQL($conn, $query, $params);	

		                $log = new Log($_SESSION['admin']);
		                $log->__set('funcionalidade', 'SISBIN x Permissões x Bases x Eventos');
		                $log->__set('parametros', $params);
		                $log->__set('log', $query);
		                $log->save($conn);
					}
				}
			}
		} else {
			$query = "INSERT INTO MW_USUARIO_ITAU_EVENTO (ID_USUARIO, ID_EVENTO) VALUES (?, ?)";
			$params = array($idUsuario, $idEvento);
			executeSQL($conn, $query, $params);

            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'SISBIN x Permissões x Bases x Eventos');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($conn);
		}
		
		if (!sqlErrors()) {
			return "OK";
		} else {
			echo "Erro #003: ";
			print_r(sqlErrors());
			echo "<br>";
		}
	}
	
	function deletarAcessoEvento($idUsuario, $idEvento, $idBase, $conn){
		if (is_array($idEvento)) {
			foreach($idEvento as $value){
				$sql = "DELETE FROM MW_USUARIO_ITAU_EVENTO WHERE ID_USUARIO = ? AND ID_EVENTO = ?";	
				$params = array($idUsuario, $value);
				executeSQL($conn, $sql, $params);

                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'SISBIN x Permissões x Bases x Eventos');
                $log->__set('parametros', $params);
                $log->__set('log', $sql);
                $log->save($conn);
			}
		} else if ($idEvento == "geral") {
			$sql = "DELETE U
					FROM MW_USUARIO_ITAU_EVENTO U
					INNER JOIN MW_EVENTO E ON U.ID_EVENTO = E.ID_EVENTO
					WHERE U.ID_USUARIO = ? AND E.ID_BASE = ?";
			$params = array($idUsuario, $idBase);
			executeSQL($conn, $sql, $params);

            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'SISBIN x Permissões x Bases x Eventos');
            $log->__set('parametros', $params);
            $log->__set('log', $sql);
            $log->save($conn);
		} else {
			$query = "DELETE FROM MW_USUARIO_ITAU_EVENTO WHERE ID_USUARIO = ? AND ID_EVENTO = ?";
			$params = array($idUsuario, $idEvento);
			executeSQL($conn, $query, $params);

            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'SISBIN x Permissões x Bases x Eventos');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($conn);
		}
		
		if (!sqlErrors()) {
			return "OK";
		} else {
			echo "Erro #004: ";
			print_r(sqlErrors());
			echo "<br>";
		}
	}
?>