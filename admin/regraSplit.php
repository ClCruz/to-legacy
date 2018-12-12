<?php
require_once('acessoLogadoDie.php');
require_once('../settings/functions.php');

$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 650, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action']) or isset($_POST['codigo'])) {
        
        require('actions/'.$pagina);
        
    } else {

?>
<style type="text/css">	
    fieldset { padding:0; border:0; margin-top:25px; }
    
    .td-action {text-align: center; width: 50px;}
    .th-action {text-align: center; width: 100px;}
        
    #app h2, .appExtension h2 {margin: 15px 0px 15px 0px;}
    
    .ui-dialog{ padding: .3em; }
    .validateTips { border: 1px solid transparent; padding: 0.3em; }

    .filtro {text-align: left; padding: 10px 0px;}
    .col-sm-3 {display: inline;}

    #app #new {margin: 0px;}

    /* #regra label, #regra input { display:block; } */
    #regra input.text, #regra select { margin-bottom:12px; width:95%; padding: .4em; }
    /**#produtor {float: left; width: initial; padding: initial;}**/

.ui-widget-header-gray {
	border: 1px solid #000000;
	background: #e3e8e3;
	color: #000000;
	font-weight: bold;
}
.ui-widget-header-green {
	border: 1px solid #00ff87;
	background: #e3e8e3;
	color: #000000;
	font-weight: bold;
}

</style>
<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script src="../javascripts/cleave.min.js"></script>
<script src="../javascripts/numeral.min.js"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>';
	var dialog, 
		form,
        id          = $("#id"),
        produtor    = $("#produtor"),
        id_produtor = $("#id_produtor"),
        percentage_credit_web       = $("#percentage_credit_web"),
        percentage_debit_web       = $("#percentage_debit_web"),
        percentage_boleto_web       = $("#percentage_boleto_web"),
        percentage_credit_box_office       = $("#percentage_credit_box_office"),
        percentage_debit_box_office       = $("#percentage_debit_box_office"),
        //liable       = $("#liable"),
        charge_processing_fee       = $("#charge_processing_fee"),
        status      = $("#status"),
        evento      = $("#evento"),
        recebedor   = $("#recebedor"),
        allFields   = $([]),
        tips        = $(".validateTips");

	$('.button').button();
    //$("#split").keypress(verificaNumero);
    new Cleave('.per', {
        numeral: true,
        numeralPositiveOnly: true,
        numeralThousandsGroupStyle: "none",
        numeralDecimalMark: ',',
        numeralDecimalScale: 2,
    });

    function numberToBR(value) {
        if (value == null || value == undefined) return "0";
        return value.toString().replace(".", ",");
    }
    function numberToUS(value) {
        if (value == null || value == undefined) return "0";
        return value.toString().replace(",", ".");
    }


    $('#app table').delegate('a', 'click', function(event) {
        event.preventDefault();

        var $this = $(this),
        href = $this.attr('href'),
        id = 'id=' + $.getUrlVar('id', href),
        tr = $this.closest('tr');

        if (href.indexOf('?action=edit') != -1) {
        	$.get('regraSplit.php?action=load&' + id, function(data) {
                data = $.parseJSON(data);
                console.log(data);

            	$("#id").val(data.id);            	
                $("#percentage_credit_web").val(numberToBR(numeral(data.percentage_credit_web)._value));
                $("#percentage_debit_web").val(numberToBR(numeral(data.percentage_debit_web)._value));
                $("#percentage_boleto_web").val(numberToBR(numeral(data.percentage_boleto_web)._value));
                $("#percentage_credit_box_office").val(numberToBR(numeral(data.percentage_credit_box_office)._value));
                $("#percentage_debit_box_office").val(numberToBR(numeral(data.percentage_debit_box_office)._value));
                //$('#liable').prop('checked', data.liable);
                $('#charge_processing_fee').prop('checked', data.charge_processing_fee);
                $("#status").val(data.status);
                //$("#recebedor").val(data.recebedor);
                // $("#recebedor").attr("disabled", true);             
                atualizarRecebedor(data.recebedor, true);

            	dialog.dialog( "open" );
            });
        }  else if (href.indexOf('?action=delete') != -1) {
        	$.confirmDialog({
                text: 'Tem certeza que deseja apagar este registro?',
                uiOptions: {
                    buttons: {
                        'Sim': function() {
                            $(this).dialog('close');
                            $.get(href, function(data) {
                                if (data.replace(/^\s*/, "").replace(/\s*$/, "") == 'true') {
                                    atualizarSplit();
                                } else {
                                    $.dialog({text: data});
                                }
                            });
                        }
                    }
                }
            });
        }
    });

	function updateTips( t ) {
	    tips
	    .text( t )
	    .addClass( "ui-state-highlight" );
	    setTimeout(function() {
	        tips.removeClass( "ui-state-highlight", 1500 );
	    }, 500 );
	}	
	
	function checkRegexp( o, regexp, n ) {
	    if ( !( regexp.test( o.val() ) ) ) {
	        o.addClass( "ui-state-error" );
	        updateTips( n );
	        return false;
	    } else {
	        return true;
	    }
	}

	dialog = $( "#dialog-form" ).dialog({
        autoOpen: false,
        height: 600,
        width: 600,
        modal: true,
        buttons: {
            "Salvar": add,
            Cancelar: function() {
                dialog.dialog( "close" );
            }
        },
        close: function() {
            document.forms[1].reset();
            id.val("");
            tips.text("");
            tips.removeClass( "ui-state-highlight");
            allFields.removeClass( "ui-state-error" );
        }
    });

    function check(percentage_credit_web, percentage_debit_web, percentage_boleto_web, percentage_credit_box_office, percentage_debit_box_office, recebedor) {
        notValid = "";

        recebedor = (recebedor == null || recebedor == "") ? -1 : recebedor;

        $.ajax({
            url: 'regraSplit.php',
            async: false,
            type: 'get',
            data: 'action=check&produtor='+ $('#produtor').val() + '&evento='+ $("#evento").val() + '&recebedor='+ recebedor,
            success: function(data) {
                data = $.parseJSON(data);
                if ((percentage_credit_web + numeral(numberToUS(data.percentage_credit_web))._value)>100) { 
                    notValid = "<br />Percentual - Web - Crédito";
                }
                if ((percentage_debit_web + numeral(numberToUS(data.percentage_debit_web))._value)>100) { 
                    notValid = "<br />Percentual - Web - Debito";
                }
                if ((percentage_boleto_web + numeral(numberToUS(data.percentage_boleto_web))._value)>100) { 
                    notValid = "<br />Percentual - Web - Boleto";
                }
                if ((percentage_credit_box_office + numeral(numberToUS(data.percentage_credit_box_office))._value)>100) { 
                    notValid = "<br />Percentual - Bilheteria - Crédito";
                }
                if ((percentage_debit_box_office + numeral(numberToUS(data.percentage_debit_box_office))._value)>100) { 
                    notValid = "<br />Percentual - Bilheteria - Debito";
                }
            }
        });

        return notValid;
    }
    function checkRecebedor() {
        ret = false;
        if (($('#produtor').val() == "" || $('#produtor').val() == "0" || $('#produtor').val() == "-1")
        || ($('#evento').val() == "" || $('#evento').val() == "0" || $('#evento').val() == "-1")
        || ($('#recebedor').val() == "" || $('#recebedor').val() == "0" || $('#recebedor').val() == "-1"))
        {
            return ret;
        }

        $.ajax({
            url: 'regraSplit.php',
            async: false,
            type: 'get',
            data: 'action=checkRecebedorOk&produtor='+ $('#produtor').val() + '&evento='+ $("#evento").val() + '&recebedor='+ $("#recebedor").val(),
            success: function(data) {
                data = $.parseJSON(data);
                ret = data.length == 0;
            }
        });

        return ret;
    }

    function add() {
    	var valid = true;
        allFields.removeClass( 'ui-state-error' );
        $("#recebedor").attr("disabled", false);
        $.each(allFields, function() {
            var $this = $(this);
            if ($this.val() == '') {
                $this.addClass('ui-state-error');
                valid = false;
            } else {
                $this.removeClass('ui-state-error');
            }
        });

        if (id.val() == "" && !checkRecebedor()) {
            $.dialog({text: "Já existe cadastro para esse recebedor nesse evento com esse organizador."});
            return;
        }

        var vl_percentage_credit_web = percentage_credit_web.val() == "" ? numeral("0")._value : numeral(numberToUS(percentage_credit_web.val()))._value;
        var vl_percentage_debit_web = percentage_debit_web.val() == "" ? numeral("0")._value : numeral(numberToUS(percentage_debit_web.val()))._value;
        var vl_percentage_boleto_web = percentage_boleto_web.val() == "" ? numeral("0")._value : numeral(numberToUS(percentage_boleto_web.val()))._value;
        var vl_percentage_credit_box_office = percentage_credit_box_office.val() == "" ? numeral("0")._value : numeral(numberToUS(percentage_credit_box_office.val()))._value;
        var vl_percentage_debit_box_office = percentage_debit_box_office.val() == "" ? numeral("0")._value : numeral(numberToUS(percentage_debit_box_office.val()))._value;

        if(vl_percentage_credit_web < 0 || vl_percentage_debit_web < 0 ||
        vl_percentage_boleto_web < 0 || vl_percentage_credit_box_office < 0 ||
        vl_percentage_debit_box_office < 0) {
            tips.text("O valor do Split deve ser maior que zero.").addClass( "ui-state-highlight" );
            split.addClass("ui-state-error");
            valid = false;
        }

        if(vl_percentage_credit_web > 100 || vl_percentage_debit_web > 100 ||
        vl_percentage_boleto_web > 100 || vl_percentage_credit_box_office > 100 ||
        vl_percentage_debit_box_office > 100) {
            tips.text("O valor do Split não pode ser maior do que 100.").addClass( "ui-state-highlight" );
            split.addClass("ui-state-error");
            valid = false;
        }

        var checkResult = check(vl_percentage_credit_web, vl_percentage_debit_web, vl_percentage_boleto_web, 
        vl_percentage_credit_box_office,vl_percentage_debit_box_office, recebedor.val());
        if(checkResult!= "") {
            $.dialog({text: "Por favor verificar os seguintes valores: <br />" + checkResult});
            valid = false;
        }        

        if ( valid ) {
        	if ( id.val() == "" ){
                var p = 'regraSplit.php?action=add&produtor='+ produtor.val() +'&evento='+ evento.val();
            }else{
                var p = 'regraSplit.php?action=update&id='+ id.val() +'&produtor='+ produtor.val();
            }


            var dataToSend = regraGetJSON();

            $.ajax({
				url: p,
				type: 'post',
				data: dataToSend,
				success: function(data) {
					if (trim(data).substr(0, 4) == 'true') {
                        atualizarSplit();
                    } else {
                        $.dialog({text: data});
                    }
				},
				error: function(){
                    $.dialog({
                        title: 'Erro...',
                        text: 'Erro na chamada dos dados !!!'
                    });
                    return false;
                }
			});
			dialog.dialog( "close" );
        }
        return valid;
    }

    $('#new').button().click(function(event) {
    	event.preventDefault();
        if($("#produtor").val() == -1) {
            $.dialog({
                title: 'Alerta...',
                text: 'Selecione o Organizador!'
            });
        } else if($("#evento").val() == -1) { 
            $.dialog({
                title: 'Alerta...',
                text: 'Selecione o Evento!'
            });
        } else {
            atualizarRecebedor();
            dialog.dialog( "open" );    
        }        
    });

    form = dialog.find( "form" ).on( "submit", function( event ) {
        event.preventDefault();
        add();
    });

    $("#produtor").change(function() {
        $("#evento").html('<option value="-1">Aguarde...</option>');
        $.ajax({
            url: pagina + '?action=load_evento',
            type: 'post',
            data: $('#dados').serialize(),
            success: function(data) {
                data = $.parseJSON(data);
                $("#evento").html('<option value="-1">Selecione...</option>');
                $.each(data, function(key, value) {
                    $("#evento").append('<option value='+ value.id_evento + '>' + value.ds_evento + '</option>');
                });
            },
            error: function(){
                $("#evento").html('<option value="-1">Selecione...</option>');
                $.dialog({
                    title: 'Erro...',
                    text: 'Erro na chamada dos dados !!!'
                });
                return false;
            }
        });
    });

    $("#evento").change(function() {
        $("#table-split tbody").html("");
        $("#table-split tfoot").html("");
        atualizarSplit();
    });

    function atualizarSplit() {
        $("#table-split tbody").html("");
        $("#table-split tfoot").html("");
        $.ajax({
            url: pagina + '?action=load_split',
            type: 'post',
            data: $('#dados').serialize(),
            success: function(data) {
                data = $.parseJSON(data);
                var total_percentage_credit_web = 0;
                var total_percentage_debit_web = 0;
                var total_percentage_boleto_web = 0;
                var total_percentage_credit_box_office = 0;
                var total_percentage_debit_box_office = 0;

                if (data.length == 0) {
                    $("#table-split tbody").append("<tr><td colspan='10 text-center'>Sem dados</td></tr>");
                }

                $.each(data, function(key, value) {
                    total_percentage_credit_web += numeral(value.percentage_credit_web)._value;
                    total_percentage_debit_web += numeral(value.percentage_debit_web)._value;
                    total_percentage_boleto_web += numeral(value.percentage_boleto_web)._value;
                    total_percentage_credit_box_office += numeral(value.percentage_credit_box_office)._value;
                    total_percentage_debit_box_office += numeral(value.percentage_debit_box_office)._value;
                    
                    var toAppend = "<tr>";
                    toAppend += '<td>' + value.ds_razao_social + '</td>';
                    toAppend += '<td>' + numberToBR(numeral(value.percentage_credit_web)._value) + ' %</td>';
                    toAppend += '<td>' + numberToBR(numeral(value.percentage_debit_web)._value) + ' %</td>';
                    toAppend += '<td>' + numberToBR(numeral(value.percentage_boleto_web)._value) + ' %</td>';
                    toAppend += '<td>' + numberToBR(numeral(value.percentage_credit_box_office)._value) + ' %</td>';
                    toAppend += '<td>' + numberToBR(numeral(value.percentage_debit_box_office)._value) + ' %</td>';
                    toAppend += '<td>' + (value.charge_processing_fee ? "Sim" : "Não") + '</td>';
                    //.append('<td>' + (value.liable ? "Sim" : "Não") + '</td>')
                    toAppend += '<td>-</td>';
                    toAppend += '<td class="td-action"><a href="<?php echo $pagina; ?>?action=edit&id='+ value.id_regra_split +'" class="button">Editar</a></td>';
                    toAppend += '<td class="td-action"><a href="<?php echo $pagina; ?>?action=delete&id='+ value.id_regra_split +'" class="button">Apagar</a></td>';
                    toAppend += "</tr>";
                    $("#table-split tbody").append(toAppend);
                });
                $("#table-split tfoot").html("");

                var warning_total_percentage_credit_web = total_percentage_credit_web == 100 ? "ui-widget-header-green" : (total_percentage_credit_web > 0 && (total_percentage_credit_web <100 || total_percentage_credit_web > 100) ? "ui-widget-header" : "ui-widget-header-gray");
                var warning_total_percentage_debit_web = total_percentage_debit_web == 100 ? "ui-widget-header-green" : (total_percentage_debit_web > 0 && (total_percentage_debit_web <100 || total_percentage_debit_web > 100) ? "ui-widget-header" : "ui-widget-header-gray");
                var warning_total_percentage_boleto_web = total_percentage_boleto_web == 100 ? "ui-widget-header-green" : (total_percentage_boleto_web > 0 && (total_percentage_boleto_web <100 || total_percentage_boleto_web > 100) ? "ui-widget-header" : "ui-widget-header-gray");
                var warning_total_percentage_credit_box_office = total_percentage_credit_box_office == 100 ? "ui-widget-header-green" : (total_percentage_credit_box_office > 0 && (total_percentage_credit_box_office <100 || total_percentage_credit_box_office > 100) ? "ui-widget-header" : "ui-widget-header-gray");
                var warning_total_percentage_debit_box_office = total_percentage_debit_box_office == 100 ? "ui-widget-header-green" : (total_percentage_debit_box_office > 0 && (total_percentage_debit_box_office <100 || total_percentage_debit_box_office > 100) ? "ui-widget-header" : "ui-widget-header-gray");

                var footAppend = "<tr class=ui-widget-header'>"
                footAppend += '<td class="text-right ui-widget-header-gray"><b>Total</b></td>';
                footAppend += '<td class="text-right ' + warning_total_percentage_credit_web + '">' + numberToBR(total_percentage_credit_web) + ' %</td>';
                footAppend += '<td class="text-right ' + warning_total_percentage_debit_web + '">' + numberToBR(total_percentage_debit_web) + ' %</td>';
                footAppend += '<td class="text-right ' + warning_total_percentage_boleto_web + '">' + numberToBR(total_percentage_boleto_web) + ' %</td>';
                footAppend += '<td class="text-right ' + warning_total_percentage_credit_box_office + '">' + numberToBR(total_percentage_credit_box_office) + ' %</td>';
                footAppend += '<td class="text-right ' + warning_total_percentage_debit_box_office + '">' + numberToBR(total_percentage_debit_box_office) + ' %</td>';
                footAppend += '<td class="text-right ui-widget-header-gray" colspan="4"></td>';
                footAppend += "</tr>";
                $("#table-split tfoot").append(footAppend);
                $('.button').button();
            },
            error: function(){                
                $.dialog({
                    title: 'Erro...',
                    text: 'Erro na chamada dos dados !!!'
                });
                return false;
            }
        });
    }
    function regraGetJSON() {
        var ret = {
            "id": $("#id").val(),
            "id_produtor": $("#id_produtor").val(),
            "recebedor": $("#recebedor").val(),
            "percentage_credit_web": $("#percentage_credit_web").val() == "" ? numeral("0")._value : numeral(numberToUS($("#percentage_credit_web").val()))._value,
            "percentage_debit_web": $("#percentage_debit_web").val() == "" ? numeral("0")._value : numeral(numberToUS($("#percentage_debit_web").val()))._value,
            "percentage_boleto_web": $("#percentage_boleto_web").val() == "" ? numeral("0")._value : numeral(numberToUS($("#percentage_boleto_web").val()))._value,
            "percentage_credit_box_office": $("#percentage_credit_box_office").val() == "" ? numeral("0")._value : numeral(numberToUS($("#percentage_credit_box_office").val()))._value,
            "percentage_debit_box_office": $("#percentage_debit_box_office").val() == "" ? numeral("0")._value : numeral(numberToUS($("#percentage_debit_box_office").val()))._value,
            "charge_processing_fee": $("#charge_processing_fee:checked").length == 0 ? 0 : 1
        };
        console.log(ret);
        return ret;
    }

    function atualizarRecebedor(id_recebedor, disable) {
        id_recebedor = id_recebedor == undefined ? null : id_recebedor;
        disable = disable == undefined ? false : disable;
        $.ajax({
            url: pagina + '?action=load_recebedor',
            type: 'post',
            data: $('#dados').serialize(),
            success: function(data) {
                data = $.parseJSON(data);
                $("#recebedor").html('<option value="-1">Selecione...</option>');
                $.each(data, function(key, value) {
                    $("#recebedor").append('<option value='+ value.id_recebedor + '>' + value.ds_razao_social + '</option>');
                });

                if (id_recebedor!=null) {
                    $("#recebedor").val(id_recebedor);
                    if (disable)
                        $("#recebedor").attr("disabled", true); 
                }

            },
            error: function(){
                $("#evento").html('<option value="-1">Selecione...</option>');
                $.dialog({
                    title: 'Erro...',
                    text: 'Erro na chamada dos dados !!!'
                });
                return false;
            }
        });
    }

});
</script>
<h2>Regra de Split</h2>

<div id="dialog-form" title="Informações da Regra">
	<p class="validateTips"></p>
	<form id="regra" name="regra" action="?p=regraSplit" method="POST">
		<fieldset>
			<input type="hidden" name="id" id="id" value="" />
            <input type="hidden" name="id_produtor" id="id_produtor" value="" />		    		    
		    <label for="recebedor">Recebedor:</label>
            <select name="recebedor" id="recebedor">
                <option value="-1">Selecione...</option>
                <?php while($rs = fetchResult($stmt)) { ?>
                <option value="<?php echo $rs["id_conta_bancaria"]; ?>"><?php echo $rs["cd_conta_bancaria"]; ?></option>
                <?php } ?>
            </select>

            <fieldset>
                <legend style="padding-bottom:13px">Percentual Web:</legend>
                    <label for="percentage_credit_web">Crédito:</label>
                    <input type="text" id="percentage_credit_web" name="percentage_credit_web" maxlength="6" style="width: 49px;" class="per text" />		    
                    <label for="split">Debito:</label>
                    <input type="text" id="percentage_debit_web" name="percentage_debit_web" maxlength="6" style="width: 49px;" class="per text" />		    
                    <label for="split">Boleto:</label>
                    <input type="text" id="percentage_boleto_web" name="percentage_boleto_web" maxlength="6" style="width: 49px;" class="per text" />		    
            </fieldset>
            <fieldset>
                <legend style="padding-bottom:13px">Percentual Bilheteria:</legend>
                <label for="split">Crédito:</label>
                <input type="text" id="percentage_credit_box_office" name="percentage_credit_box_office" maxlength="6" style="width: 49px;" class="per text" />		    
                <label for="split">Debito:</label>
                <input type="text" id="percentage_debit_box_office" name="percentage_debit_box_office" maxlength="6" style="width: 49px;" class="per text" />		    
            </fieldset>
            <fieldset>
                <label for="charge_processing_fee">MDR:</label>
                <input type="checkbox" id="charge_processing_fee" value="1" name="charge_processing_fee" />		    
                <!-- <label for="liable">ChargeBack:</label>
                <input type="checkbox" id="liable" name="liable" value="1" />		     -->
            </fieldset>
		</fieldset>
	</form>
</div>

<form id="dados" name="dados" method="post">
	
    <div class="filtro">
        <div class="col-sm-3">
            <label>Organizador:</label>
            <select id="produtor" name="produtor">
                <option value="-1">Selecione</option>
                <?php
                    $query = "
                    SELECT id_produtor
                    ,ds_razao_social
                    ,HasPermission
                    FROM (
                    SELECT p.id_produtor
                    ,p.ds_razao_social
                    ,ISNULL((SELECT 1 FROM mw_permissao_split sub WHERE sub.id_usuario=? AND (sub.id_produtor=p.id_produtor OR sub.id_produtor IS NULL)),0) HasPermission
                    FROM mw_produtor p
                    WHERE p.in_ativo = 1 ) as produtor
                    WHERE HasPermission=1
                    ORDER BY ds_razao_social";
                    $stmtProdutor = executeSQL($mainConnection, $query, array($_SESSION["admin"]));
                    while($rs = fetchResult($stmtProdutor)) {
                        $selected = $rs["id_produtor"] == $_GET["produtor"] ? "selected" : "";
                ?>
                <option <?php echo $selected; ?> value="<?php echo $rs['id_produtor']; ?>"><?php echo utf8_encode2($rs['ds_razao_social']); ?></option>
                <?php
                    }
                ?>
            </select>
        </div>

        <div class="col-sm-3">
            <label>Evento:</label>
            <select id="evento" name="evento">
                <option value="-1">Selecione...</option>                
            </select>	
        </div>
    
        <div class="col-sm-3">
            <a id="new" href="#new">Novo</a>
        </div>
    </div>

	<table id="table-split" class="ui-widget ui-widget-content">
		<thead>
			<tr class="ui-widget-header">
                <th>Recebedor</th>
				<th>% Split - Web - Crédito</th>  
				<th>% Split - Web - Debito</th>  
				<th>% Split - Web - Boleto</th>  
				<th>% Split - Bilheteria - Crédito</th>  
				<th>% Split - Bilheteria - Débito</th>  
				<th>MDR</th>  
				<!-- <th>ChargeBack</th>   -->
                <th>Valor mínimo</th>  
				<th colspan="2" class="th-action">Ações</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
		<tfoot>
		</tfoot>
	</table>
</form>
<br/>
<?php
	}
}
?>