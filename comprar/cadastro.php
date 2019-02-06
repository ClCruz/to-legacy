<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
require_once('../settings/Utils.php');
require_once('../log4php/log.php');
require_once('../settings/MCAPI.class.php');
if (isset($_GET['action'])) {
	$mainConnection = mainConnection();
	session_start();

	function dispararTrocaSenha($email) {
		if (isset($_SESSION['operador'])) {
			$_GET['email'] = $email;
			include('esqueciSenha.php');
			ob_end_clean();
		}
	}

	function validaNascimento(){
		$dia = ( isset($_POST['nascimento_dia']) && !empty($_POST['nascimento_dia']) ) ? true : false;
		$mes = ( isset($_POST['nascimento_mes']) && !empty($_POST['nascimento_mes']) ) ? true : false;
		$ano = ( isset($_POST['nascimento_ano']) && !empty($_POST['nascimento_ano']) ) ? true : false;
		
		return ( $dia && $mes && $ano ) ? $_POST['nascimento_dia'].'/'.$_POST['nascimento_mes'].'/'.$_POST['nascimento_ano'] : NULL;
	}
	
	foreach ($_POST as $key => $val) {
		if (!is_array($_POST[$key])) {
			$_POST[$key] = utf8_decode($val);
		}
		if ($val == '' or $val == ' ') {
			$_POST[$key] = NULL;
		}
	}
	
	if ($_GET['action'] == 'add' or $_GET['action'] == 'update') {
		// formatacao dos campos do layout 2.0 para o antigo (para manter compatibilidade)
		$_POST['cpf'] = preg_replace("/[^0-9]/", "", $_POST['cpf']);

		$_POST['ddd1'] = $_POST['ddd_fixo'];
		$_POST['telefone'] = $_POST['fixo'];
		$_POST['ddd2'] = $_POST['ddd_celular'];

		if (!isset($_POST['extra_info'])) $_POST['extra_info'] = 'N';
		if (!isset($_POST['extra_sms'])) $_POST['extra_sms'] = 'N';
		if (!isset($_POST['concordo'])) $_POST['concordo'] = 'N';

		if( !$_POST['checkbox_estrangeiro'] )
		{
			if (!verificaCPF($_POST['cpf']))
			{
				echo 'CPF Inválido';
				exit();
			}
		}
	}
	
	if ($_GET['action'] == 'add') {
		if (!(isset($_SESSION['operador']) and is_numeric($_SESSION['operador']))) {
			require_once('../settings/settings.php');
			// reCAPTCHA v2 ---------------
			//$post_data = http_build_query(array('secret'    => $recaptcha_cadastro['private_key'],
			                                    //'response'  => $_POST["g-recaptcha-response"],
			                                    //'remoteip'  => $_SERVER["HTTP_X_FORWARDED_FOR"]));


			//$ch = curl_init();
			//curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
			//curl_setopt($ch, CURLOPT_POST, 1);
			//curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			//curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			//$server_output = curl_exec($ch);
			//curl_close($ch);

			//$resp = json_decode($server_output, true);

			//if (!$_ENV['IS_TEST']) {
			//	if (!$resp['success']) {
				    // set the error code so that we can display it
			//	    $error = $resp->error;
			//	    echo "Entre com a informação solicitada no campo Autenticidade.";
			//	    exit();
			//	}
			//}
		}

		if (!$_POST['concordo']) {
			echo utf8_encode2('Você deve concordar com os termos de uso e com a política de privacidade para se cadastrar!');
			exit();
		}
		
		$query = 'SELECT 1 FROM MW_CLIENTE WHERE CD_EMAIL_LOGIN = ?';
		$params = array($_POST['email1']);
		$result = executeSQL($mainConnection, $query, $params);
		if (hasRows($result)) {
			echo 'Já existe um usuário cadastrado com esse e-mail.';
			exit();
		}
		$query = 'SELECT id_cliente, ds_nome, ds_sobrenome FROM MW_CLIENTE WHERE CD_CPF = ?';
		$params = array($_POST['cpf']);
		$result = fetchAssoc( executeSQL($mainConnection, $query, $params) );

		function addPOSUser($reg)
		{
			$mainConnection = mainConnection();
			//echo 'Função de add usuário pre cadastro via POS com CPF';
			$query = 'UPDATE MW_CLIENTE SET
							cd_password = ?,
							DS_NOME = ?,
							DS_SOBRENOME = ?,
							DT_NASCIMENTO = CONVERT(DATETIME, ?, 103),
							DS_DDD_TELEFONE = ?,
							DS_DDD_CELULAR = ?,
							DS_TELEFONE = ?,
							DS_CELULAR = ?,
							CD_RG = ?,
							CD_CPF = ?,
							DS_ENDERECO = ?,
							NR_ENDERECO = ?,
							DS_COMPL_ENDERECO = ?,
							DS_BAIRRO = ?,
							DS_CIDADE = ?,
							ID_ESTADO = ?,
							CD_CEP = ?,
							CD_EMAIL_LOGIN = ?,
							IN_RECEBE_INFO = ?,
							IN_RECEBE_SMS = ?,
							IN_SEXO = ?,
							ID_DOC_ESTRANGEIRO = ?
						WHERE ID_CLIENTE = ?';
			$params = array(
				$senha = isset($_SESSION['operador'])  ? '' : md5($_POST['senha1']),
				utf8_encode2($_POST['nome']),
				utf8_encode2($_POST['sobrenome']),
				validaNascimento(),
				$_POST['ddd1'],
				$_POST['ddd2'],
				$_POST['telefone'],
				$_POST['celular'],
				$_POST['rg'],
				$_POST['cpf'],
				utf8_encode2($_POST['endereco']),
				utf8_encode2($_POST['numero_endereco']),
				utf8_encode2($_POST['complemento']),
				utf8_encode2($_POST['bairro']),
				utf8_encode2($_POST['cidade']),
				utf8_encode2($_POST['estado']),
				$_POST['cep'],
				$_POST['email1'],
				utf8_encode2($_POST['extra_info']),
				$_POST['extra_sms'],
				$_POST['sexo'],
				$_POST['tipo_documento'],
				$reg['id_cliente']
			);
			die("oi3");

			if (executeSQL($mainConnection, $query, $params)) {
				// die("oi2");
				$errors = sqlErrors();
				if (empty($errors)) {

					if (!(isset($_SESSION['operador']) and is_numeric($_SESSION['operador']))) {
						sendConfirmationMail($reg['id_cliente']);
						$retorno = 'true';
				

					}else{
						dispararTrocaSenha($_POST['email1']);
						//$retorno = 'Usuário pré registrado em POS atualizado com sucesso.';
						$retorno = 'true';
					}
					//$send_mailchimp = true;
				} else {
					$retorno = sqlErrors();
				}
			} else {
				$retorno = sqlErrors();
			}

			if (is_array($retorno)) {
				if ($retorno[0]['code'] == 242) {
					echo 'Data de Nascimento inválida';
				} else {
					var_dump($query, $params, $retorno);
				}
			} else {
				echo $retorno;
			}
		}

		if ( !empty($result) )
		{
			$reg = $result[0];
			if ( $reg['ds_nome'] == 'POS' && $reg['ds_sobrenome'] == 'POS' )
			{
				addPOSUser($reg);
			}
			else
			{
				echo 'Já existe um usuário cadastrado com esse CPF.';
			}

			exit();
		}
		
		$newID = executeSQL($mainConnection, 'SELECT ISNULL(MAX(ID_CLIENTE), 0) + 1 FROM MW_CLIENTE', array(), true);
		$newID = $newID[0];

		// se for do exterior usar o id de usuario como cpf
		if( empty($_POST['cpf']) && $_POST['checkbox_estrangeiro'] ){
			$_POST['cpf'] = substr('00000000000' . $newID, -11);
		}
		
		$query = 'INSERT INTO MW_CLIENTE
						(
							ID_CLIENTE,
							DS_NOME,
							DS_SOBRENOME,
							DT_NASCIMENTO,
							DS_DDD_TELEFONE,
							DS_TELEFONE,
							DS_DDD_CELULAR,
							DS_CELULAR,
							CD_RG,
							CD_CPF,
							DS_ENDERECO,
							NR_ENDERECO,
							DS_COMPL_ENDERECO,
							DS_BAIRRO,
							DS_CIDADE,
							ID_ESTADO,
							CD_CEP,
							CD_EMAIL_LOGIN,
							CD_PASSWORD,
							IN_RECEBE_INFO,
							IN_RECEBE_SMS,
							IN_CONCORDA_TERMOS,
							IN_SEXO,
							ID_DOC_ESTRANGEIRO
						)
						VALUES
						('.$newID.',?,?,CONVERT(DATETIME, ?, 103),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
		$params = array(
							utf8_encode2($_POST['nome']),
							utf8_encode2($_POST['sobrenome']),
							validaNascimento(),
							$_POST['ddd1'],
							$_POST['telefone'],
							$_POST['ddd2'],
							$_POST['celular'],
							$_POST['rg'],
							$_POST['cpf'],
							utf8_encode2($_POST['endereco']),
							utf8_encode2($_POST['numero_endereco']),
							utf8_encode2($_POST['complemento']),
							utf8_encode2($_POST['bairro']),
							utf8_encode2($_POST['cidade']),
							$_POST['estado'],
							$_POST['cep'],
							$_POST['email1'],
							md5($_POST['senha1']),
							utf8_encode2($_POST['extra_info']),
							$_POST['extra_sms'],
							$_POST['concordo'],
							$_POST['sexo'],
							$_POST['tipo_documento']
							);

		if (executeSQL($mainConnection, $query, $params)) {
			
			if (!(isset($_SESSION['operador']) and is_numeric($_SESSION['operador']))) {
				sendConfirmationMail($newID, preg_match('/assinatura/', $_GET['redirect']));
				header("Location: minha_conta.php",  true,  301 );  exit;
			}
			
			$retorno = 'true';
			$send_mailchimp = true;
			$email = $_POST['email1'];
			
			dispararTrocaSenha($_POST['email1']);
			
			// $_SESSION['user'] = $newID;
			
			

		} else {
			$retorno = sqlErrors();
		}
	} else if ($_GET['action'] == 'update' and isset($_SESSION['user'])) {
		
		if (isset($_SESSION['operador']) and is_numeric($_SESSION['operador'])) {
			$query = 'SELECT 1 FROM MW_CLIENTE WHERE CD_EMAIL_LOGIN = ? AND ID_CLIENTE <> ?';
			$params = array($_POST['email'], $_SESSION['user']);
			$result = executeSQL($mainConnection, $query, $params);
			
			if (hasRows($result)) {
				echo 'Já existe um usuário cadastrado com esse email.';
				die();
			}

			if (strlen($_POST['email']) < 3) {
				echo 'Favor informar um e-mail válido.';
				die();
			}
		}

		// se for do exterior usar o id de usuario como cpf
		if( empty($_POST['cpf']) && $_POST['checkbox_estrangeiro'] ){
			$_POST['cpf'] = substr('00000000000' . $_SESSION['user'], -11);
		}
		
		$query = 'SELECT CD_EMAIL_LOGIN FROM MW_CLIENTE WHERE ID_CLIENTE = ?';
		$params = array($_SESSION['user']);
		$rs = executeSQL($mainConnection, $query, $params, true);
		$email = $rs['CD_EMAIL_LOGIN'];
		
		$query = 'UPDATE MW_CLIENTE SET
							DS_NOME = ?,
							DS_SOBRENOME = ?,
							DT_NASCIMENTO = CONVERT(DATETIME, ?, 103),
							DS_DDD_TELEFONE = ?,
							DS_DDD_CELULAR = ?,
							DS_TELEFONE = ?,
							DS_CELULAR = ?,
							CD_RG = ?,
							CD_CPF = ?,
							DS_ENDERECO = ?,
							NR_ENDERECO = ?,
							DS_COMPL_ENDERECO = ?,
							DS_BAIRRO = ?,
							DS_CIDADE = ?,
							ID_ESTADO = ?,
							CD_CEP = ?,' . ((isset($_SESSION['operador']) and is_numeric($_SESSION['operador'])) ? 'CD_EMAIL_LOGIN = ?,' : '') . '
							IN_RECEBE_INFO = ?,
							IN_RECEBE_SMS = ?,
							IN_SEXO = ?,
							ID_DOC_ESTRANGEIRO = ?
						WHERE ID_CLIENTE = ?';
		$params = array(
							utf8_encode2($_POST['nome']),
							utf8_encode2($_POST['sobrenome']),
							validaNascimento(),
							$_POST['ddd1'],
							$_POST['ddd2'],
							$_POST['telefone'],
							$_POST['celular'],
							$_POST['rg'],
							$_POST['cpf'],
							utf8_encode2($_POST['endereco']),
							utf8_encode2($_POST['numero_endereco']),
							utf8_encode2($_POST['complemento']),
							utf8_encode2($_POST['bairro']),
							utf8_encode2($_POST['cidade']),
							$_POST['estado'],
							$_POST['cep']
							);
		if (isset($_SESSION['operador']) and is_numeric($_SESSION['operador'])) {
			$params[] = $_POST['email'];
		}
		$params[] = utf8_encode2($_POST['extra_info']);
		$params[] = $_POST['extra_sms'];
		$params[] = $_POST['sexo'];
		$params[] = $_POST['tipo_documento'];
		$params[] = $_SESSION['user'];
		
		if (executeSQL($mainConnection, $query, $params)) {
			$errors = sqlErrors();
			if (empty($errors)) {
				dispararTrocaSenha($_POST['email']);
				
				$retorno = 'Seus dados foram atualizados com sucesso!';
				$send_mailchimp = true;
			} else {
				$retorno = sqlErrors();
			}
		} else {
			$retorno = sqlErrors();
		}
	} else if ($_GET['action'] == 'passChange') {
		if (isset($_POST['senha1']) and strlen($_POST['senha1']) >= 6) {
			$query = 'SELECT CD_PASSWORD FROM MW_CLIENTE WHERE ID_CLIENTE = ?';
			$params = array($_SESSION['user']);
			
			$rs = executeSQL($mainConnection, $query, $params, true);
			
			if ($rs[0] == md5($_POST['senha'])) {
				$query = 'UPDATE MW_CLIENTE SET
									CD_PASSWORD = ?
								WHERE ID_CLIENTE = ?';
				$params = array(md5($_POST['senha1']), $_SESSION['user']);
				
				if (executeSQL($mainConnection, $query, $params)) {
					$retorno = 'true';
				} else {
					$retorno = sqlErrors();
				}
			} else {
				$retorno = 'false';
			}
		} else {
			$retorno = 'A senha nova deve ter, no mínimo, 6 caracteres.';
		}
	} else if ($_GET['action'] == 'manageAddresses' and isset($_SESSION['user'])) {

		if ($_POST['id'] || $_GET['id']) {
			$id_endereco = ($_POST['id'] ? $_POST['id'] : $_GET['id']);
			$query = 'DELETE FROM MW_ENDERECO_CLIENTE
						WHERE ID_CLIENTE = ? AND ID_ENDERECO_CLIENTE = ?';
			$params = array($_SESSION['user'], $id_endereco);
			
			if (executeSQL($mainConnection, $query, $params)) {
				$retorno = 'true';
			} else {
				$retorno = sqlErrors();
			}
		}
		
		if ($_POST['endereco']) {
			$query = 'SELECT COUNT(1) AS ENDERECOS_REGISTRADOS FROM MW_ENDERECO_CLIENTE WHERE ID_CLIENTE = ?';
			$rs = executeSQL($mainConnection, $query, array($_SESSION['user']), true);

			if ($rs['ENDERECOS_REGISTRADOS'] < 3) {

				$_POST['cep'] = str_replace('-', '', $_POST['cep']);
				$query = 'INSERT INTO MW_ENDERECO_CLIENTE
								(DS_ENDERECO, DS_COMPL_ENDERECO, DS_BAIRRO, DS_CIDADE, CD_CEP, ID_ESTADO, ID_CLIENTE, NM_ENDERECO, NR_ENDERECO)
								VALUES
								(?, ?, ?, ?, ?, ?, ?, ?, ?); SELECT SCOPE_IDENTITY();';
				$params = array(utf8_encode2($_POST['endereco']), utf8_encode2($_POST['complemento']), utf8_encode2($_POST['bairro']), utf8_encode2($_POST['cidade']), $_POST['cep'], $_POST['estado'], $_SESSION['user'], utf8_encode2($_POST['nome']), $_POST['numero_endereco']);

				$rs = executeSQL($mainConnection, $query, $params);

				if ($rs)
				{
					$lastID = getLastID($rs);
					$retorno = 'true?'.$lastID;
					if ($_COOKIE['entrega'] == $id_endereco) setcookie('entrega', $lastID);
				}
				else
				{
					$retorno = sqlErrors();
				}
			} else {
				$retorno = "O número máximo de endereços registrados foi atingido.<br><br>Favor apagar/alterar um endereço para continuar.";
			}
		}
		
	} else if ($_GET['action'] == 'getAddresses' and isset($_SESSION['user']) and $_GET['id']) {
		
		$retorno = json_encode(getEnderecoCliente($_SESSION['user'], $_GET['id']));
	
	} else if ($_GET['action'] == 'cancelar_assinatura' AND isset($_GET['id'])) {

		$query = "SELECT
	                A.QT_DIAS_CANCELAMENTO,
	                DATEDIFF(DAY, AC.DT_COMPRA, GETDATE()) AS DIAS_DESDE_COMPRA,
	                DC.ID_DADOS_CARTAO
	                FROM MW_ASSINATURA A
	                INNER JOIN MW_ASSINATURA_CLIENTE AC ON AC.ID_ASSINATURA = A.ID_ASSINATURA
	                INNER JOIN MW_DADOS_CARTAO DC ON DC.ID_DADOS_CARTAO = AC.ID_DADOS_CARTAO
	                WHERE AC.ID_CLIENTE = ? AND AC.ID_ASSINATURA_CLIENTE = ?";
	    $params = array($_SESSION['user'], $_GET['id']);
	    $rs = executeSQL($mainConnection, $query, $params, true);

		if (!empty($rs)) {
			if (!isset($_SESSION['operador']) AND $rs['DIAS_DESDE_COMPRA'] <= $rs['QT_DIAS_CANCELAMENTO']) {
				$retorno = "Você ainda não pode cancelar esta assinatura.";
			} else {
				$query = "UPDATE MW_ASSINATURA_CLIENTE SET IN_ATIVO = 0
			                WHERE ID_CLIENTE = ? AND ID_ASSINATURA_CLIENTE = ?";
			    executeSQL($mainConnection, $query, $params);

			    $retorno = 'true';
			}
		} else {
			$retorno = "Assinatura não encontrada.";
		}
	}

	if ($send_mailchimp) {
		$query = "SELECT DS_ESTADO FROM MW_ESTADO WHERE ID_ESTADO = ?";
		$rs = executeSQL($mainConnection, $query, array($_POST['estado']), true);

		$mcapi = new MCAPI($MailChimp['api_key']);
		$user_data = array(
			'nm_email' => $_POST['email'],
			'nome' => utf8_encode2($_POST['nome']),
			'apelido' => utf8_encode2($_POST['sobrenome']),
			'ddd_fone' => $_POST['ddd1'],
			'telefone' => $_POST['telefone'],
			'ddd_celular' => $_POST['ddd2'],
			'celular' => $_POST['celular'],
			'bairro' => utf8_encode2($_POST['bairro']),
			'cidade' => utf8_encode2($_POST['cidade']),
			'cep' => $_POST['cep'],
			'dt_nascimento' => validaNascimento(),
			'sexo' => $_POST['sexo'],
			'uf' => $rs['DS_ESTADO']
		);

		/*
		if ($_GET['action'] == 'update') {
			$update = true;
			$user_data['EMAIL'] = $_POST['email'];
			$new_email = $user_data['EMAIL'];
		} else {
			$update = false;
			$new_email = $_POST['email1'];
		}
		*/

		// All in Mail login
		require_once('../settings/nusoap-0.9.5/lib/nusoap.php');

		$client = new nusoap_client("http://painel01.allinmail.com.br/wsAllin/login.php?wsdl", true);
		$ticket = $client->call('getTicket', array($mail_mkt['login'], $mail_mkt['senha']));

		// formato All in Mail
		foreach ($user_data as $key => $value)
			$user_data[$key] = str_replace(';', ' ', $value);

		$campos = implode(';', array_keys($user_data));
		$valor = implode(';', array_values($user_data));

		// adiciona na lista
		$client = new nusoap_client("http://painel01.allinmail.com.br/wsAllin/inserir_email_base.php?wsdl", true);
		$arr = array(
			"nm_lista"	=> $mail_mkt['lista'],
			"campos"	=> $campos,
			"valor"		=> $valor
		);
		$result = $client->call('inserirEmailBase', array($ticket, $arr));
		
		// remove ou adiciona na lista de optout
		$client = new nusoap_client("http://painel01.allinmail.com.br/wsAllin/optoutInOut.php?wsdl", true);

		if ($_POST['extra_info'] != 'S') {
			$result = $client->call('inserirOptout', array($ticket, $_POST['email']));
		} else {
			$result = $client->call('removerOptout', array($ticket, $_POST['email']));
		}
	}
	
	if (is_array($retorno)) {
		if ($retorno[0]['code'] == 242) {
			echo 'Data de Nascimento inválida';
		} else {
			//var_dump($query, $params, $retorno);
//			printr($query);
//			printr($params);
//			printr($retorno);
			echo "Um erro inesperado ocorreu. Favor informar o suporte.";
		}
	} else {
		echo $retorno;
	}
}
?>