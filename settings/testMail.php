<?php
require("PHPMailer/class.phpmailer.php");

echo $_SERVER['LOCAL_ADDR'];

function authSendEmail($from, $namefrom, $to, $nameto, $subject, $message, $copiesTo = array(), $hiddenCopiesTo = array(), $charset = 'utf8', $attachment = array()) {
	
	$mail = new PHPMailer();
	
	$mail->SMTPDebug = 0;
	
	$mail->SetLanguage('br');
	
	$mail->IsSMTP();
	$mail->Host = 'smtp-relay.gmail.com';

	$mail->SMTPSecure = "tls";
	
	$mail->Port = 587;
	
	$mail->From = ($from ? $from : 'compreingressos@siscompre.com');
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

	echo print_r($error, true);
	//log_email($mail->Subject, $mail->From, $to, $mail->Host, $enviado, $error);

	if ($enviado) {
		if (!empty($attachment)) {
			foreach($attachment as $file) {
				unlink($file['path']);
			}
			limparImagesTemp();
		}

		return true;
	} else {
		return authSendEmail_alternativo($from, $namefrom, $to, $nameto, $subject, $message, $copiesTo, $hiddenCopiesTo, $charset, $attachment);
	}
}

function authSendEmail_alternativo($from, $namefrom, $to, $nameto, $subject, $message, $copiesTo = array(), $hiddenCopiesTo = array(), $charset = 'utf8', $attachment = array()) {
	
	$mail = new PHPMailer();
	
	$mail->SMTPDebug = 0;
	
	$mail->SetLanguage('us');
	
	$mail->IsSMTP();
	$mail->Host = 'smtp.gmail.com';//"smtp.compreingressos.com";

	// somente gmail
	$mail->SMTPSecure = "tls";
	
	$mail->Port = 587;
	$mail->SMTPAuth = true;
	$mail->Username = 'compreingressos@gmail.com';
	$mail->Password = '743081@clc';
	
	// somente gmail
	$mail->From = 'compreingressos@gmail.com';
	$mail->FromName = $namefrom;
	
	$mail->AddAddress($to, $nameto);
	//$mail->AddAddress('e-mail@destino2.com.br');
	//$mail->AddCC('copia@dominio.com.br', 'Copia');
	//$mail->AddBCC('CopiaOculta@dominio.com.br', 'Copia Oculta');
	
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
	
	echo "<br /> <br />" . print_r($mail, true);

	$enviado = $mail->Send();

	$mail->ClearAllRecipients();
	$mail->ClearAttachments();

	$error = $mail->ErrorInfo;

	echo "<br /> <br />". print_r($error, true);

	if ($enviado) {
		if (!empty($attachment)) {
			foreach($attachment as $file) {
				unlink($file['path']);
			}
			limparImagesTemp();
		}

		return true;
	} else {
		return authSendEmail_alternativo2($from, $namefrom, $to, $nameto, $subject, $message, $copiesTo, $hiddenCopiesTo, $charset, $attachment);
	}
}


echo 'Resultado (GMail): ';

$successMail = authSendEmail('compreingressos@gmail.com', utf8_decode('COMPREINGRESSOS.COM - AGÊNCIA DE VENDA DE INGRESSOS'), 'blcoccaro@gmail.com', 'Test1', 'teste', utf8_decode('testando envio de email'), array(), array('Pedidos=>jefferson.ferreira@cc.com.br'), 'iso-8859-1');

var_dump($successMail);


//echo '<br /><br />Resultado (Google Apps): ';

//$successMail = authSendEmail_alternativo4('compreingressos@gmail.com', utf8_decode('COMPREINGRESSOS.COM - AGÊNCIA DE VENDA DE INGRESSOS'), 'blcoccaro@gmail.com', 'Teste5', 'teste', utf8_decode('testando envio de email'), array(), array('Pedidos=>jefferson.ferreira@cc.com.br'), 'iso-8859-1');

//var_dump($successMail);
?>