<?php
require_once('acessoLogadoDie.php');
require_once('../settings/functions.php');

$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 630, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action']) or isset($_POST['codigo'])) {
        
        require('actions/'.$pagina);
        
    } else {

        $query = "SELECT cd_banco, ds_banco FROM mw_banco ORDER BY ds_banco";
        $stmtBanco = executeSQL($mainConnection, $query);

    	$query = "SELECT * FROM mw_conta_bancaria cb INNER JOIN mw_banco b ON b.cd_banco = cb.cd_banco WHERE id_produtor = ? AND in_ativo = 1";
    	$stmt = executeSQL($mainConnection, $query, array($_GET["produtor"]));
?>
<style type="text/css">
	label, input { display:block; }
    input.text, select { margin-bottom:12px; width:95%; padding: .4em; }
    fieldset { padding:0; border:0; margin-top:25px; }
    .td-action {text-align: center; width: 50px;}
    .th-action {text-align: center; width: 100px;}
    .add-conta {margin-bottom: 20px; text-align: right;}
    .add-conta label {display: inline; float: left;}
    .conta {width: 200px !important; display: inline;}
    .conta-dv {width: 50px !important; display: inline;}
    #produtor {float: left; width: initial; padding: initial;}
    #app h2, .appExtension h2 {margin: 15px 0px 15px 0px;}
    .ui-dialog{ padding: .3em; }
    .validateTips { border: 1px solid transparent; padding: 0.3em; }
</style>
<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script src="../javascripts/jquery.maskedinput.min.js" type="text/javascript"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>';
	var dialog, 
		form,
		emailRegex  = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/,
        id          = $("#id"),
        produtor    = $("#produtor"),
        id_produtor = $("#id_produtor"),
        banco       = $("#banco"),
        conta       = $("#conta_bancaria"),
        dv_conta    = $("#dv_conta"),
        agencia     = $("#agencia"),
        tipo        = $("#tipo"),
        split       = $("#split"),
        status      = $("#status"),
        allFields   = $([]).add(produtor).add(banco).add(conta).add(agencia).add(tipo).add(split),
        tips        = $(".validateTips");

	$('.button').button();

    $("#agencia").keypress(verificaNumero);
    $("#conta_bancaria").keypress(verificaNumero);
    $("#dv_conta_bancaria").keypress(verificaNumero);
    $("#split").keypress(verificaNumero);

    $('#app table').delegate('a', 'click', function(event) {
        event.preventDefault();

        var $this = $(this),
        href = $this.attr('href'),
        id = 'id=' + $.getUrlVar('id', href),
        tr = $this.closest('tr');

        if (href.indexOf('?action=edit') != -1) {
        	$.get('contaBancaria.php?action=load&' + id, function(data) {
            	data = $.parseJSON(data);

            	$("#id").val(data.id);
            	$("#banco").val(data.banco);
            	$("#conta_bancaria").val(data.conta_bancaria);
                $("#dv_conta_bancaria").val(data.dv_conta_bancaria);
            	$("#agencia").val(data.agencia);
            	$("#tipo").val(data.tipo);
            	$("#split").val(data.split);
            	$("#status").val(data.status);

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
                                    tr.remove();
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

    function check(split, conta) {
        valid = true;

        conta = (conta == null || conta == "") ? -1 : conta;

        $.ajax({
            url: 'contaBancaria.php',
            async: false,
            type: 'get',
            data: 'action=check&produtor='+ $('#produtor').val() + '&conta='+ conta,
            success: function(data) {
                soma = parseInt(data) + parseInt(split);
                if(soma > 100) {                
                    valid = false;
                }
            }
        });

        return valid;
    }

    function add() {
    	var valid = true;
        allFields.removeClass( 'ui-state-error' );
        $.each(allFields, function() {
            var $this = $(this);
            if ($this.val() == '') {
                $this.addClass('ui-state-error');
                valid = false;
            } else {
                $this.removeClass('ui-state-error');
            }
        });        

        if(split.val() > 100) {
            tips.text("O valor do Split não pode ser maior do que 100.").addClass( "ui-state-highlight" );
            split.addClass("ui-state-error");
            valid = false;
        }

        if(!check(split.val(), id.val())) {
            tips.text("O valor do Split na soma das contas não pode ser maior do que 100.").addClass( "ui-state-highlight" );
            split.addClass("ui-state-error");
            valid = false;
        }        

        if ( valid ) {
        	if ( id.val() == "" ){
                var p = 'contaBancaria.php?action=add&produtor='+ produtor.val();
            }else{
                var p = 'contaBancaria.php?action=update&id='+ id.val() +'&produtor='+ produtor.val();
            }

            $.ajax({
				url: p,
				type: 'post',
				data: $('#conta').serialize(),
				success: function(data) {
					if (trim(data).substr(0, 4) == 'true') {
                        location.reload();
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
                text: 'Selecione o Produtor!'
            });
        } else {
            dialog.dialog( "open" );    
        }        
    });

    form = dialog.find( "form" ).on( "submit", function( event ) {
        event.preventDefault();
        add();
    });

    $("#produtor").change(function() {
        location.href = "?p=contaBancaria&produtor=" + $(this).val();
    })

});
</script>
<h2>Contas Bancárias</h2>

<div id="dialog-form" title="Informações da Conta">
	<p class="validateTips"></p>
	<form id="conta" name="conta" action="?p=contaBancaria" method="POST">
		<fieldset>
			<input type="hidden" name="id" id="id" value="" />
            <input type="hidden" name="id_produtor" id="id_produtor" value="" />
		    <label for="banco">Banco:</label>		    
            <select id="banco" name="banco" class="ui-widget-content ui-corner-all" />
		    <?php while($rs = fetchResult($stmtBanco)) { ?>
            <option value="<?php echo $rs['cd_banco']; ?>"><?php echo utf8_encode2($rs['ds_banco']); ?></option>
            <?php } ?>
            </select>            
            <label for="agencia">Agência:</label>
		    <input type="text" id="agencia" name="agencia" maxlength="5" class="text ui-widget-content ui-corner-all" />
		    <label for="conta_bancaria">Conta:</label>
		    <input type="text" id="conta_bancaria" name="conta_bancaria" maxlength="13" class="text ui-widget-content ui-corner-all conta" /> - 
            <input type="text" id="dv_conta_bancaria" name="dv_conta_bancaria" maxlength="1" class="text ui-widget-content ui-corner-all conta-dv" />
		    <label for="tipo">Tipo da Conta:</label>
			<select id="tipo" name="tipo" class="ui-widget-content ui-corner-all">
                <option value="CC">Conta Corrente</option>
                <option value="CP">Conta Poupança</option>
            </select>
		    <label for="split">Percentual p/ Split:</label>
		    <input type="text" id="split" name="split" maxlength="3" class="text ui-widget-content ui-corner-all" />
		    <label for="status">Status:</label>
            <select id="status" name="status" class="ui-widget-content ui-corner-all">
                <option value="1">Ativo</option>
                <option value="0">Inativo</option>
            </select>
		</fieldset>
	</form>
</div>

<form id="dados" name="dados" method="post">
	
	<div class="add-conta">
        <label>Produtor:</label>
        <select id="produtor" name="produtor">
            <option value="-1">Selecione</option>
            <?php
                $query = "SELECT id_produtor, ds_razao_social FROM mw_produtor WHERE in_ativo = 1 ORDER BY ds_razao_social";
                $stmtProdutor = executeSQL($mainConnection, $query);
                while($rs = fetchResult($stmtProdutor)) {
                    $selected = $rs["id_produtor"] == $_GET["produtor"] ? "selected" : "";
            ?>
            <option <?php echo $selected; ?> value="<?php echo $rs['id_produtor']; ?>"><?php echo utf8_encode2($rs['ds_razao_social']); ?></option>
            <?php
                }
            ?>
        </select>
		<a id="new" href="#new">Novo</a>	
	</div>

	<table class="ui-widget ui-widget-content">
		<thead>
			<tr class="ui-widget-header">
				<th>Código do Banco</th>
				<th>Banco</th>
				<th>Agência</th>
				<th>Conta</th>
				<th>Tipo da Conta</th>
				<th>Percentual p/ Split</th>
                <th>Recebedor (Pagar.me)</th>
                <th>Status</th>                
				<th colspan="2" class="th-action">Ações</th>
			</tr>
		</thead>
		<tbody>
			<?php while($rs = fetchResult($stmt)) { 
				$id = $rs["id_conta_bancaria"];
                $conta = ($rs["dv_conta_bancaria"] != "") ? $rs["cd_conta_bancaria"] ."-". $rs["dv_conta_bancaria"] : $rs["cd_conta_bancaria"];
			?>
			<tr>
				<td><?php echo $rs["cd_banco"]; ?></td>
				<td><?php echo utf8_encode2($rs["ds_banco"]); ?></td>
				<td><?php echo $rs["cd_agencia"]; ?></td>
				<td><?php echo $conta; ?></td>
				<td><?php echo $rs["cd_tipo_conta"] == "CC" ? "Conta Corrente" : "Conta Poupança"; ?></td>
				<td><?php echo $rs["nr_percentual_split"]; ?></td>
                <td><?php echo $rs["recipient_id"]; ?></td>
                <td><?php echo $rs["in_ativo"] ? "Ativo" : "Inativo"; ?></td>
				<td class="td-action"><a href="<?php echo $pagina; ?>?action=edit&id=<?php echo $id; ?>" class="button">Editar</a></td>
                <td class="td-action"><a href="<?php echo $pagina; ?>?action=delete&id=<?php echo $id; ?>" class="button">Apagar</a></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</form>
<br/>
<?php
	}
}
?>