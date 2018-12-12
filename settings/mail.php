<?php
require("PHPMailer/class.phpmailer.php");
require_once($_SERVER['DOCUMENT_ROOT']."/config/whitelabel.php");
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");

function log_email($assunto, $de, $para, $conta, $sucesso, $erro) {
	require_once('../settings/functions.php');
	$mainConnection = mainConnection();
	$query = "INSERT INTO MW_EMAIL_LOG (DT_ENVIO, DS_ASSUNTO, DS_DE, DS_PARA, DS_CONTA, IN_SUCESSO, DS_ERRO) VALUES (GETDATE(), ?, ?, ?, ?, ?, ?)";
	$params = array($assunto, $de, $para, $conta, ($sucesso ? 1 : 0), $erro);
	executeSQL($mainConnection, $query, $params);
}

function sendMail_register($from, $namefrom, $to, $nameto, $subject, $message, $copiesTo = array(), $hiddenCopiesTo = array(), $charset = 'utf8', $attachment = array()) {
	$conf = emailConfiguration();
	$mail = new PHPMailer();
	$mail->SMTPDebug = 3;
	
	$mail->SetLanguage('br');
	
	$mail->IsSMTP();
	$mail->Host = $conf["smtp"];

	$mail->SMTPSecure = $conf["smtpsecure"];
	
	$mail->Port = $conf["port"];
	
	$mail->From = ($from ? $from : multiSite_getEmail('register'));
	$mail->FromName = $namefrom;
	
	$mail->AddAddress($to, $nameto);
	
	if (!empty($copiesTo)) {
		foreach($copiesTo as $address) {
			$address = explode('=>', $address);
			
			$name = trim($address[0]);
			$email = trim($address[1]);
			
			$mail->AddCC($email, $name);
		}
	}

	if (!empty($hiddenCopiesTo)) {
		foreach($hiddenCopiesTo as $address) {
			$address = explode('=>', $address);
			
			$name = trim($address[0]);
			$email = trim($address[1]);
			
			$mail->AddBCC($email, $name);
		}
	}

	if (!empty($attachment)) {
		foreach($attachment as $file) {
			if ($file['cid']) {
				$mail->AddEmbeddedImage($file['path'], $file['cid']);
				//and on the <img> tag put src='cid:file_cid'
			} else {
				$mail->AddAttachment($file['path'], $file['new_name']);  
			}
		}
	}
	
	$mail->IsHTML(true);
	$mail->CharSet = $charset;
	
	$mail->Subject  = $subject;
	$mail->Body = $message;
	//$mail->AltBody = 'plain text';
	
	$enviado = $mail->Send();

	$mail->ClearAllRecipients();
	$mail->ClearAttachments();

	$error = $mail->ErrorInfo;

	log_email($mail->Subject, $mail->From, $to, $mail->Host, $enviado, $error);
	//die(print_r($mail->ErrorInfo, true));
	if ($enviado) {
		if (!empty($attachment)) {
			foreach($attachment as $file) {
				unlink($file['path']);
			}
			limparImagesTemp();
		}

		return true;
	} else {
		return false;
		//return authSendEmail_alternativo($from, $namefrom, $to, $nameto, $subject, $message, $copiesTo, $hiddenCopiesTo, $charset, $attachment);
	}
}
function sendToAPI($from, $fromName, $to, $toName, $subject, $msg) {	
	$apiuser = "leonel.costa@tixs.me";
	$apikey = "b175cc5be004456855e061f1fb8f113b";
	$url = 'http://app1.iagentesmtp.com.br/api/v1/send.json';
	$fields = array(
		'api_user' => urlencode($apiuser),
		'api_key' => urlencode($apikey),
		'from' => urlencode($from),
		'fromname' => urlencode($fromName),
		'to' => urlencode($to),
		'toname' => urlencode($toName),
		'subject' => urlencode($subject),
		'html' => urlencode($msg),
	);

	//url-ify the data for the POST
	foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
	rtrim($fields_string, '&');

	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch,CURLOPT_POST, count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

	//execute post
	$result = curl_exec($ch);
	//close connection
	curl_close($ch);

	return $result;
}


function authSendEmail($from, $namefrom, $to, $nameto, $subject, $message, $copiesTo = array(), $hiddenCopiesTo = array(), $charset = 'utf8', $attachment = array()) {
	if ($from == null || $from == "")
		$from = getwhitelabelemail()["noreply"]["email"];

	if ($namefrom == null || $namefrom == "")
		$namefrom = getwhitelabelemail()["noreply"]["from"];
		
	$ret = sendToAPI($from, $namefrom, $to, $nameto, $subject, $message);
	return true;
	//log_email($subject, $from, $to, "api", $enviado, $error);
}
?>