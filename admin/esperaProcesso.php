<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
    <meta http-equiv="refresh" content="2;url=<?php echo $_GET['redirect']; ?>">

    <title><?php echo $title; ?></title>

    <link rel='stylesheet' type='text/css' href='../stylesheets/reset.css' />
    <link rel="stylesheet" type="text/css" href="../stylesheets/customred/jquery-ui-1.10.3.custom.css"/>
    <link rel='stylesheet' type='text/css' href='../stylesheets/admin.css' />
    <link rel='stylesheet' type='text/css' href='../stylesheets/ajustes.css' />
    <script type='text/javascript' src='../javascripts/jquery-ui.js'></script>
    <script language="javascript">
        $.load(function Carregar(){
            $("#carregando").ajaxStart(function(){
                $(this).show();
            });
        });
    </script>
</head>
<body style="text-align: center;">
    <div class="ui-corner-all ui-widget ui-widget-content"
	 style="width: 500px; margin: 20% auto; padding: 20px;
		text-align: left; border: 3px solid red;">
	<h1>AGUARDE ALGUNS INSTANTES, ENQUANTO EFETUAMOS SUA CONSULTA.....</h1><br/>

	<h2>ATENÇÃO</h2><br/>

	<p>Os valores apresentados são baseados nas informações de vendas
	    disponíveis até essa consulta e poderão ser alterados a qualquer
	    momento em função de novos lançamentos, até o dia da apresentação
	    do evento.
	</p><br/>

	<p>Se o resultado da consulta demorar demais você pode clicar
	    <a href="<?php echo $_GET['redirect']; ?>">aqui</a> para reprocessá-lo.</p><br/>

        <div id="carregando" aling="center"><img src="../images/loading.gif"> carregando...</div>
    </div>
</body>
</html>