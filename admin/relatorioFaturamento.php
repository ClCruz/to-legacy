<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 15, true)) {

    $pagina = basename(__FILE__);
    $mes = date("m") - 1;
?>
    <script type="text/javascript" src="../javascripts/jquery.ui.datepicker-pt-BR.js"></script>
    <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
    <script type="text/javascript" language="javascript">
        $(function() {
	    var pagina = '<?php echo $pagina; ?>'
	    $('.button').button();
	    //$('#periodo').buttonset();
	    $('input.datepicker').datepicker({
		changeMonth: true,
		changeYear: true,
		onSelect: function(date, e) {
		    if ($(this).is('#dt_inicial')) {
			$('#dt_final').datepicker('option', 'minDate', $(this).datepicker('getDate'));
		    }
		}
	    }).datepicker('option', $.datepicker.regional['pt-BR']);

	    //Gera relatorio
	    $("#btnRelatorio").click(function(){
		if ($("#local").val() == "") {
		    $.dialog({title: 'Alerta...', text: 'Selecione o local!'});
		} else if ($(".periodo").is(':checked') == false) {
		    $.dialog({title: 'Alerta...', text: 'Escolha o período!'});
		} else {
		    var teatro = $("#local").find('option').filter(":selected").text(),
			peca = $("#eventos").find('option').filter(":selected").text(),
			url = ".php?" + $('form[name="frmFaturamento"]').serialize() +
			      "&DescPeca=" + peca + "&teatro=" + teatro,
			options = "width=920, scrollbars=yes, height=600";

		    switch($("#tipo").val()){
			case 'detalhado':
			    window.open("relFaturamentoDet" + url, "", options, "");
			    break;
			case 'detalhado_peca':
			    window.open("relFaturamentoPorPeca" + url , "", options, "");
			    break;
			case 'resumido':
			    window.open("relFaturamentoRes" + url , "", options, "");
			    break;
			case 'resumido_peca':
			    window.open("relFaturamentoPorPecaRes" + url , "", options, "");
			    break;
		    }
		}
	    });

	    $("#local").change(function(){
		$.ajax({
		    url: 'carregaEventos.php',
		    type: 'post',
		    data: 'local=' + $('#local').val() +
			  '&idUsuario=<?php echo $_SESSION["admin"]; ?>',
		    success: function(data){
			$('#eventos').html(data).change();
		    }
		});
	    });

	    $('#eventos').change(function(){
		console.log($(this).val());
		if ($(this).val() != 'null') {
		    $('#tipo option:odd').attr('disabled', true);
		} else {
		    $('#tipo option:odd').attr('disabled', false);
		}
	    })
        });
    </script>
    <h2>Relatório de Faturamento</h2>
    <form action="" name="frmFaturamento">
        <table border="0" cellpadding="2" cellspacing="2">
    	<tr>
    	    <td>Data Inicial</td>
    	    <td>
    		<input type="text" value="<?php echo (isset($_GET["dt_inicial"])) ? $_GET["dt_inicial"] : date("d") . "/" . $mes . "/" . date("Y") ?>" class="datepicker" id="dt_inicial" name="dt_inicial" />
    	    </td>
    	    <td>Data Final</td>
    	    <td>
    		<input type="text" class="datepicker" value="<?php echo (isset($_GET["dt_final"])) ? $_GET["dt_final"] : date("d/m/Y") ?>" id="dt_final" name="dt_final" />
    	    </td>
    	</tr>
    	<tr>
    	    <td>Local</td>
    	    <td><?php echo comboTeatro("local", 0); ?></td>
    	    <td>Eventos</td>
    	    <td><select name="eventos" id="eventos"></select></td>
    	</tr>
    	<tr>
    	    <td>Per&iacute;odo</td>
	    <td>
		<div id="periodo">
		    <label>Venda&nbsp;<input type="radio" name="periodo" class="periodo" value="venda" /></label>
		    <label>Ocorr&ecirc;ncia&nbsp;<input type="radio" name="periodo"  class="periodo" value="ocorrencia" /></label>
		</div>
	    </td>
    	    <td>Tipo de Relatório</td>
    	    <td>
		<select name="tipo" id="tipo">
    		    <option value="detalhado">Detalhado</option>
    		    <option value="detalhado_peca">Detalhado por evento</option>
    		    <option value="resumido">Resumido</option>
    		    <option value="resumido_peca">Resumido por evento</option>
    		</select>
	    </td>
    	</tr>
    	<tr>
    	    <td colspan="4">
    		<input type="button" class="button" id="btnRelatorio" value="Buscar" />
    	    </td>
    	</tr>
        </table>
    </form>
<?php
}
?>