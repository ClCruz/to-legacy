<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 20, true)) {

$pagina = basename(__FILE__);

?>
<script type="text/javascript" src="../javascripts/jquery.ui.datepicker-pt-BR.js"></script>
<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script type="text/javascript" language="javascript">
$(function() {
	var pagina = '<?php echo $pagina; ?>'
	$('.button').button();
	$(".datepicker").datepicker();
	
	//Gera relatorio
	$("#btnRelatorio").click(function(){
            if($("#local").val() == "")
                $.dialog({title: 'Alerta...', text: 'Selecione o local!'});
            else{
                var url = ".php?dt_inicial="+ $("#dt_inicial").val() + "&dt_final="+
                    $("#dt_final").val() +"&local="+ $("#local").val() + "";
                    window.open("relVendasPromocionais" + url, "","width=920, scrollbars=yes, height=600", "");
            }
	});	
});
</script>
<style type="text/css">
#paginacao{
	width: 100%;
	text-align: center;
	margin-top: 10px;	
}
.tableData{
    width: 600px !important;
}
</style>
<h2>Relatório Vendas Promocionais</h2>
<?php 
	$mes = date("m") - 1;
?>
<form>
<table width="600" class="tableData" border="0" cellpadding="2" cellspacing="2">
    <tr>
        <td><span style="width: 200px;text-align: left;">Data Inicial</span></td>
        <td><span style="width: 200px;text-align: left;">
          <input type="text" value="<?php echo (isset($_GET["dt_inicial"])) ? $_GET["dt_inicial"] : date("d")."/".$mes ."/".date("Y") ?>" class="datepicker" id="dt_inicial" name="dt_inicial" />
        </span></td>
        <td><span style="width: 200px;text-align: left;">Data Final</span></td>
        <td><span style="width: 200px;text-align: left;">
          <input type="text" class="datepicker" value="<?php echo (isset($_GET["dt_final"])) ? $_GET["dt_final"] : date("d/m/Y") ?>" id="dt_final" name="dt_final" />
        </span></td>
    </tr>
    <tr>
        <td><span style="width: 200px;text-align: left;">Local</span></td>
        <td><span style="width: 200px;text-align: left;">
          <?php echo comboTeatro("local", ""); ?>
        </span></td>
        <td><span style="width:100%;text-align: left;">
          <input type="button" class="button" id="btnRelatorio" value="Buscar" />
        </span></td>
    </tr>
</table><br><br>

</form>
<?php
}
?>