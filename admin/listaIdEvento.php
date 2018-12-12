<?php

require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 13, true)) {

$pagina = basename(__FILE__);

	$oldquery = 'SELECT E.id_evento, E.ds_evento, E.in_ativo, 
CONVERT(VARCHAR(10), MIN(A.dt_apresentacao),103) AS data_inicial, 
CONVERT(VARCHAR(10), MAX(A.dt_apresentacao),103) AS data_final 
FROM MW_EVENTO AS E LEFT JOIN MW_APRESENTACAO A ON A.id_evento = E.id_evento
WHERE E.ID_BASE = ?
GROUP BY E.id_base, E.id_evento, E.ds_evento, E.in_ativo ORDER BY E.ds_evento, E.id_evento';

//$result = executeSQL($mainConnection, $oldquery, array($_GET['teatro']));

//$resultTeatros = executeSQL($mainConnection, 'SELECT ID_BASE, DS_NOME_TEATRO FROM MW_BASE WHERE IN_ATIVO = \'1\'');

	$newResultQuery = 'SELECT A.id_evento, A.ds_evento, A.in_ativo 
, CONVERT(VARCHAR(10), MIN(B.dt_apresentacao),103) AS data_inicial
, CONVERT(VARCHAR(10), MAX(B.dt_apresentacao),103) AS data_final
FROM CI_MIDDLEWAY.dbo.mw_evento AS A
LEFT JOIN CI_MIDDLEWAY.dbo.mw_apresentacao AS B ON A.id_evento = B.id_evento 
WHERE A.id_base = ?
GROUP BY A.id_base, A.id_evento, A.ds_evento, A.in_ativo 
HAVING MAX(B.dt_apresentacao) >= GETDATE()
ORDER BY A.ds_evento, A.id_evento
';

	if ( !isset($_GET['cartaz']) || ( isset($_GET['cartaz']) && $_GET['cartaz'] == 1 ) )
	{
		$query = $newResultQuery;
		$iptCartazCheck = 'checked="checked"';
	}
	else if ( isset($_GET['cartaz']) && $_GET['cartaz'] == 0 )
	{
		$query = $oldquery;
		$iptCartazCheck = '';
	}

	$newResult = fetchAssoc( executeSQL($mainConnection, $query, array($_GET['teatro']) ) );
	
?>

<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>';
	var getParamns = {};
	getUrlParamns();

	var selectTeatro = document.getElementById('teatro');
	selectTeatro.onchange = function () {
		setParamns('teatro', this.value);
		goTo();
	};
	
	$('tr:not(.ui-widget-header)').hover(function() {
		$(this).addClass('ui-state-hover');
	}, function() {
		$(this).removeClass('ui-state-hover');
	});

	var btnEvtAtivo = $('input[name="eventoativo"]')[0];
	btnEvtAtivo.onchange = function ()
	{
		//alert(this.checked);
		var newsrc = ( this.checked ) ? '1' : '0';

		document.location.search.replace('&cartaz');

		//getUrlParamns();
		setParamns('cartaz', newsrc);
		goTo();
	};

	/*
	* Pega os parametros GET e envia para um objeto Javascript
	* */
	function getUrlParamns()
	{
		var paramns = document.location.search;
		paramns = paramns.replace('?','&');
		paramns = paramns.split('&');
		var obj = {};
		var i =0;
		for(x in paramns)
		{
			var item = paramns[x];
			if (item != '')
			{
				item = item.split('=');
				eval("obj['"+item[0]+"'] = {}");
				eval("obj['"+item[0]+"'].value = '"+item[1]+"';");
				i++;
			}
		}
		getParamns = obj;
	}

	/*
	* Cria ou altera novos parametros GET que serão utilizados depois em goTo()
	* */
	function setParamns(paramn, value)
	{
		if (eval('getParamns.'+paramn))
		{
			eval('getParamns.'+paramn+'.value = "'+value+'"');
		}
		else
		{
			eval("getParamns['"+paramn+"'] = {}");
			eval("getParamns['"+paramn+"'].value = '"+value+"';");
		}
	}

	function goTo()
	{
		var i 	= 0;
		var str = '';
		for(x in getParamns)
		{
			str += ( i == 0 ) ? '?' : '&';
			str += x+'='+getParamns[x].value;
			i++;
		}

		document.location = document.location.origin + document.location.pathname + str;
	}
});
</script>
<h2>Lista de Eventos</h2>
<p style="width:200px;"><?php echo comboTeatro('teatro', $_GET['teatro']); ?></p>
<label>Em Cartaz</label>
<input type="checkbox" name="eventoativo" value="1" <?php echo $iptCartazCheck ?> />
<table class="ui-widget ui-widget-content">
	<thead>
		<tr class="ui-widget-header">
			<th>ID</th>
			<th>Evento</th>
			<th>Data de Início</th>
			<th>Data de Término</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($newResult as $resp): ?>
			<tr>
				<td><?php echo $resp['id_evento']; ?></td>
				<td><?php echo utf8_encode2($resp['ds_evento']); ?></td>
				<td><?php echo $resp['data_inicial']; ?></td>
				<td><?php echo $resp['data_final'] ?></td>
				<td><?php echo ($resp['in_ativo'] ? 'Ativo' : 'Inativo'); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
}
?>