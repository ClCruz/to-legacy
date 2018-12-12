<?php
if (!empty($_FILES)) {
	$tempFile = $_FILES['Filedata']['tmp_name'];
	$targetPath = $_SERVER['DOCUMENT_ROOT'] . $_REQUEST['folder'] . '/';
	$targetFile =  str_replace('/', '\\', str_replace('//','\\',$targetPath)) . $_FILES['Filedata']['name'];
	
	$fileTypes  = str_replace('*.','',$_REQUEST['fileext']);
	$fileTypes  = str_replace(';','|',$fileTypes);
	$typesArray = split('\|',$fileTypes);
	$fileParts  = pathinfo($_FILES['Filedata']['name']);
	
	if (in_array($fileParts['extension'],$typesArray)) {
		mkdir(str_replace('//','/',$targetPath), 0755, true);
		
		move_uploaded_file($tempFile,$targetFile);
		echo 'true?' . $_REQUEST['folder'] . '/' . $_FILES['Filedata']['name'];
	} else {
		echo 'Tipo de arquivo inválido!';
	}
}
?>