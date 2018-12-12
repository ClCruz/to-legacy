<?php
	require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
	require_once($_SERVER['DOCUMENT_ROOT'].'/settings/functions.php');
	
	/**
	 * Retorna o numero total de eventos
	 * @param string $nomeBase Nome da base de dados a ser consultada
	 * @param int Identificador da base de dados
	 * @param int Identificador do usuário na base de dados
	 * @param int Identificador da conexao do banco de dados
	 * @return int Total de eventos na base de dados
	**/
	function totalEventos($nomeBase, $idBase, $idUsuario, $conn){
		$queryTotal = 'SELECT P.CODPECA, P.NOMPECA, ROW_NUMBER() OVER(ORDER BY P.NOMPECA) AS LINHA,
						( SELECT \'checked\' FROM MW_ACESSO_CONCEDIDO AC2 
						WHERE AC2.ID_USUARIO = '.$idUsuario.' AND AC2.CODPECA = P.CODPECA AND AC2.ID_BASE = '.$idBase.') AS CHECKED
		  				FROM '.$nomeBase.'..TABPECA P
		 				 LEFT JOIN CI_MIDDLEWAY..MW_ACESSO_CONCEDIDO A ON A.CODPECA = P.CODPECA AND A.ID_BASE = '.$idBase.' AND A.ID_USUARIO = '.$idUsuario.'
		  				WHERE STAPECA = \'A\' ORDER BY 2';
		  
		$total = numRows($conn, $queryTotal);
		if(!sqlErrors())
			return $total;
		else{
			
			echo "Erro #001: <br>";
			print_r(sqlErrors());
			echo "<br>";
		}
	}
	
	/**
	 * Recupera todos os eventos na base de dados selecionada
	 * @param string $idUsuario Identificador do usuario
	 * @param string $nomeBase Nome da base de dados selecionada
	 * @param int $idBase Identificador da base de dados selecionada
	 * @param int $offset Linha inicial da consulta na base de dados
	 * @param int $final Linha final da consulta 
	 * @param bool $paginacao True || False
	 * @param int $conn Identificador da conexao do banco de dados
	 * @return int $result Link identificador da conexao
	**/
	function recuperarEventos($idUsuario, $nomeBase, $idBase, $offset, $final, $paginacao, $conn){
		if($paginacao){
			$row_number = "ROW_NUMBER() OVER(ORDER BY P.NOMPECA) AS LINHA,";
			$between = "WHERE LINHA BETWEEN ".$offset." AND ".$final."";
		}else{
			$row_number = " ";
			$between = " ";	
		}
		
		$query = 'WITH RESULTADO AS (
					SELECT P.CODPECA, P.NOMPECA, '.$row_number.'
					( SELECT \'checked\' FROM MW_ACESSO_CONCEDIDO AC2 
					WHERE AC2.ID_USUARIO = '.$idUsuario.' AND AC2.CODPECA = P.CODPECA AND AC2.ID_BASE = '.$idBase.') AS CHECKED
				  	FROM '.$nomeBase.'..TABPECA P
				  	LEFT JOIN CI_MIDDLEWAY..MW_ACESSO_CONCEDIDO A ON A.CODPECA = P.CODPECA AND A.ID_BASE = '.$idBase.' AND A.ID_USUARIO = '.$idUsuario.'
			  		WHERE STAPECA = \'A\')
				  	SELECT * FROM RESULTADO '.$between.' ORDER BY 2';
		  
		$result = executeSQL($conn, $query);	
		if(!sqlErrors())
			return $result;
		else{
			echo "Erro #002: <br>";
			print_r(sqlErrors());
			echo "<br>";
		}
	}
	
	function checarEvento($idUsuario, $idBase, $evento, $conn){
		$params = array($idUsuario, $idBase, $evento);
		$sql = "SELECT CODPECA FROM MW_ACESSO_CONCEDIDO WHERE ID_USUARIO = ". $idUsuario ." AND ID_BASE = ". $idBase ."AND CODPECA = ". $evento ."";
		if(numRows($conn, $sql) > 0)	
			return true;
		else
			return false;
	}
	
	/**
	 * Cadastrar acesso aos eventos na base de dados
	 * @param int $idUsuario Identificador do usuario na base de dados
	 * @param array $eventos Lista de eventos a serem incluidos na base de dados
	 * @param int $conn Identificador da conexao do banco de dados
	 * @return string "" || String de erro
	**/
	function cadastrarAcessoEvento($idUsuario, $idBase, $nomeBase, $evento, $conn){
		if(is_array($evento)){
			foreach($evento as $key => $value){
				if(!checarEvento($idUsuario, $idBase, $value, $conn)){
					$paramns = array($idUsuario, $idBase, $value);
					//$sql = "INSERT INTO MW_ACESSO_CONCEDIDO (ID_USUARIO, ID_BASE, CODPECA) VALUES(". $idUsuario .",". $idBase .",". $value .")";
					$sql = "INSERT INTO MW_ACESSO_CONCEDIDO (ID_USUARIO, ID_BASE, CODPECA) VALUES(?,?,?)";
					executeSQL($conn, $sql,$paramns);

		            $log = new Log($_SESSION['admin']);
		            $log->__set('funcionalidade', 'Liberar Permissões x Bases');
		            $log->__set('log', $sql);
		            $log->save($conn);
				}
			}
		}
		else if($evento == "geral"){
			$result = recuperarEventos($idUsuario, $nomeBase, $idBase, 0, 0, false, $conn);
			if($result){
				while($idEvento = fetchResult($result)){
					$sql = "INSERT INTO MW_ACESSO_CONCEDIDO (ID_USUARIO, ID_BASE, CODPECA) VALUES(". $idUsuario .",". $idBase .",". $idEvento["CODPECA"] .")";
					executeSQL($conn, $sql);	
				
		            $log = new Log($_SESSION['admin']);
		            $log->__set('funcionalidade', 'Liberar Permissões x Bases');
		            $log->__set('log', $sql);
		            $log->save($conn);
				}
			}
		}		
		else{
			$sql = "INSERT INTO MW_ACESSO_CONCEDIDO (ID_USUARIO, ID_BASE, CODPECA) VALUES(". $idUsuario .",". $idBase .",". $evento .")";
			executeSQL($conn, $sql);	
				
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Liberar Permissões x Bases');
            $log->__set('log', $sql);
            $log->save($conn);
		}
		
		if(!sqlErrors()){
			return "OK";
		}
		else{
			echo "Erro #003: ";
			print_r(sqlErrors());
			echo "<br>";
		}
	}
	
	/**
	 * Deletar acesso de usuário ao evento
	 * @param int $idUsuario Identificador do usuario na base de dados
	 * @param array $eventos Lista de eventos a serem incluidos na base de dados
	 * @param int $conn Identificador da conexao do banco de dados
	 * @return string "" || String de erro
	**/
	function deletarAcessoEvento($idUsuario, $idBase, $evento, $conn){
		if(is_array($evento)){
			foreach($evento as $key => $value){
				$params = array($idUsuario, $idBase, $value);
				$sql = "DELETE FROM MW_ACESSO_CONCEDIDO WHERE ID_USUARIO = ? AND ID_BASE = ? AND CODPECA = ?";	
				executeSQL($conn, $sql, $params);

                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Liberar Permissões x Bases');
                $log->__set('parametros', $params);
                $log->__set('log', $sql);
                $log->save($conn);
			}
		}
		else if($evento == "geral"){
			$sql = "DELETE FROM MW_ACESSO_CONCEDIDO WHERE ID_USUARIO = ? AND ID_BASE = ?";
			$params = array($idUsuario, $idBase);
			executeSQL($conn, $sql, $params);
				
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Liberar Permissões x Bases');
            $log->__set('parametros', $params);
            $log->__set('log', $sql);
            $log->save($conn);
		}
		else{
			$sql = "DELETE FROM MW_ACESSO_CONCEDIDO WHERE ID_USUARIO = ? AND ID_BASE = ? AND CODPECA = ?";
			$params = array($idUsuario, $idBase, $evento);
			executeSQL($conn, $sql, $params);
				
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Liberar Permissões x Bases');
            $log->__set('parametros', $params);
            $log->__set('log', $sql);
            $log->save($conn);
		}
		if(!sqlErrors()){
			return "OK";
		}else{
			echo "Erro #004: ";
			print_r(sqlErrors());
			echo "<br>";
		}
	}
	
	/**
	 * Enviar email a usuário com notificação de permissões
	 * @param int $idUsuario Identificador do usuario na base de dados
	 * @param int $idBase Identificador da base de dados selecionada
	 * @param int $conn Identificador da conexao do banco de dados
	 * @return string "" || String de erro
	**/
	function notificarUsuarioEventos($idUsuario, $idBase, $nomeBase, $conn){
		$query = "SELECT CD_LOGIN, DS_NOME, DS_EMAIL FROM MW_USUARIO WHERE ID_USUARIO = ?";
		$params = array($idUsuario);
		$user = executeSQL($conn, $query, $params, true);

		$query = "SELECT DS_NOME_TEATRO FROM MW_BASE WHERE ID_BASE = ?";
		$params = array($idBase);
		$base = executeSQL($conn, $query, $params, true);

		$query = "SELECT P.NOMPECA
					FROM ".$nomeBase."..TABPECA P
					INNER JOIN CI_MIDDLEWAY..MW_ACESSO_CONCEDIDO A ON A.CODPECA = P.CODPECA
					WHERE A.ID_BASE = ? AND A.ID_USUARIO = ? AND STAPECA = 'A'
					ORDER BY 1";
		$params = array($idBase, $idUsuario);
		$result = executeSQL($conn, $query, $params);

		
		ob_start();
		?>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		
		<p>Prezado <?php echo $user['DS_NOME']; ?>,</p>
		<p> Informamos que o acesso ao Local <?php echo $base['DS_NOME_TEATRO']; ?>, do(s) evento(s) abaixo,
 			est&aacute;(&atilde;o) liberado(s) para visualiza&ccedil;&atilde;o no sistema.</p>
 		<ul>
		<?php
		while ($rs = fetchResult($result)) {
			echo '<li>' . $rs['NOMPECA'] . '</li>';
		}
		?>
		</ul>
		<p>Seguem informa&ccedil;&otilde;es para acesso:</p>
		<p>URL: <a href="<?php echo multiSite_getURICompra("/admin/?p=relatorioBordero")?>"><?php echo multiSite_getURICompra("/admin/?p=relatorioBordero")?></a></p>
		<p>Usu&aacute;rio: <?php echo $user['CD_LOGIN']; ?></p>
		<p>Senha: caso n&atilde;o lembre a senha clique <a href="<?php echo multiSite_getURICompra("admin/gerarNovaSenha.php?email=" .$user['DS_EMAIL'])?>">aqui</a> para receber uma nova senha, que ser&aacute; enviada para o email <?php echo $user['DS_EMAIL']; ?>.</p>
		<p>Att.<br/>
		<?php echo multiSite_getName(); ?></p>
		<?php
		$body = ob_get_contents();
		ob_end_clean();


		$nameto = $rs['DS_NOME'];
		$to = $user['DS_EMAIL'];
		$subject = utf8_decode('Notificação de Permissão');
		
		$namefrom = utf8_decode(multiSite_getTitle());
		$from = '';

		echo authSendEmail($from, $namefrom, $to, $nameto, $subject, $body) === true ? 'true' : '<br><br>Se o erro persistir, favor entrar em contato com o suporte.';
	}
?>