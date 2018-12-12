<?php

require_once($_SERVER['DOCUMENT_ROOT']."/settings/functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
session_start();
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
    
    <title><?php echo $title; ?></title>
	
	<!-- Font Awesome -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Bootstrap core CSS -->
    <link href="../stylesheets/bootstrap.min.css" rel="stylesheet">
    <!-- Material Design Bootstrap -->
    <link href="../stylesheets/mdb.min.css" rel="stylesheet">
    
    <script type='text/javascript' src='../javascripts/jquery.js'></script>
    <script type='text/javascript' src='../javascripts/jquery-ui.js'></script>
    <script type='text/javascript' src='../javascripts/jquery.ui.datepicker-pt-BR.js'></script>
    <script type='text/javascript' src='../javascripts/jquery.utils.js'></script>
	 
	 <script>
		 $(function(){

		 	$('#menu-bt').button({
				text: true,
				icons: {
					secondary: "ui-icon-triangle-1-s"
				}
	        }).removeClass('ui-corner-all').on('click', function(){
	        	$('#menu-items').slideToggle();
	        });

	        $('#menu-items').hide().menu();
			
		 });
	 </script>
</head>

<body style="background: #333;">
