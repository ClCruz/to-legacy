<?php
require_once('acessoLogadoDie.php');
require_once('../settings/functions.php');

$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 660, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action']) or isset($_POST['codigo'])) {
        
        require('actions/'.$pagina);
        
    } else {
    	$query = "SELECT id_gateway FROM mw_produtor WHERE id_produtor = ?";
    	$stmtgateway = executeSQL($mainConnection, $query, array($_GET["produtor"]), true);

        $id_gateway = $stmtgateway["id_gateway"];


        $query = "SELECT cd_banco, ds_banco FROM mw_banco ORDER BY ds_banco";
        $stmtBanco = executeSQL($mainConnection, $query);

    	$query = "SELECT * FROM mw_recebedor cb 
                  INNER JOIN mw_banco b ON b.cd_banco = cb.cd_banco 
                  WHERE id_produtor = ?
                  ORDER BY in_ativo DESC, ds_razao_social";
    	$stmt = executeSQL($mainConnection, $query, array($_GET["produtor"]));
?>
<style type="text/css">
	label, input { display:block; }
    #conta input.text, #conta select { margin-bottom:12px; width:95%; padding: .4em; }
    fieldset { padding:10px; margin-top:10px; }
    .td-action {text-align: center; width: 50px;}
    .th-action {text-align: center; width: 100px;}
    .add-conta {margin-bottom: 20px; text-align: right;}
    .add-conta label {display: inline; float: left; margin-top: 7px;}
    .add-conta select {margin-top: 5px; margin-bottom: 0px;}
    .conta {width: 200px !important; display: inline;}
    .conta-dv {width: 50px !important; display: inline;}
    .agencia {width: 200px !important; display: inline;}
    .agencia-dv {width: 50px !important; display: inline;}
    #produtor {float: left; width: unset !important; padding: unset;}
    #app h2, .appExtension h2 {margin: 15px 0px 15px 0px;}
    .ui-dialog{ padding: .3em; }
    .validateTips { border: 1px solid transparent; padding: 0.3em; }
    #app #new {margin: 0px;}
    #app #newTICKETSPAY {margin: 0px;}
    
    .text-left{text-align: left;}
    .text-right{text-align: right;}
    .text-center{text-align: center;}
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
        razao_social= $("#razao_social"),
        cpf_cnpj    = $("#cpf_cnpj"),
        nome        = $("#nome"),
        email       = $("#email"),
        telefone    = $("#telefone"),
        celular     = $("#celular"),
        banco       = $("#banco"),
        conta       = $("#conta_bancaria"),
        dv_conta_bancaria = $("#dv_conta_bancaria"),
        agencia     = $("#agencia"),
        dv_agencia  = $("#dv_agencia"),
        tipo        = $("#tipo"),
        split       = $("#split"),
        status      = $("#status"),
        transfer_day= $("#transfer_day"),
        allFields   = $([]).add(produtor).add(razao_social).add(cpf_cnpj).add(nome).add(email).add(celular).add(banco).add(conta).add(dv_conta_bancaria).add(agencia).add(tipo).add(split),
        tips        = $(".validateTips");

	$('.button').button();

    $("#telefone").mask("99 9999-9999");
    $('#celular').mask("99 9999-9999?9");

    $("#agencia").keypress(verificaNumero);
    $("#dv_agencia").keypress(verificaNumero);
    $("#conta_bancaria").keypress(verificaNumero);
    $("#dv_conta_bancaria").keypress(verificaNumero);
    $("#split").keypress(verificaNumero);
    $("#cpf_cnpj").keypress(verificaNumero);
    $("#transfer_day").keypress(verificaNumero);   

    $('#app table').delegate('a', 'click', function(event) {
        event.preventDefault();

        var $this = $(this),
        href = $this.attr('href'),
        id = 'id=' + $.getUrlVar('id', href),
        tr = $this.closest('tr');

        if (href.indexOf('?action=edit') != -1) {
        	$.get('recebedor.php?action=load&' + id, function(data) {
            	data = $.parseJSON(data);

            	$("#id").val(data.id);
            	$("#razao_social").val(data.razao_social);
                $("#cpf_cnpj").val(data.cpf_cnpj);
                $("#nome").val(data.nome);
                $("#email").val(data.email);
                $("#telefone").val(data.telefone);
                $("#celular").val(data.celular);
                $("#banco").val(data.banco);
            	$("#conta_bancaria").val(data.conta_bancaria);
                $("#dv_conta_bancaria").val(data.dv_conta_bancaria);
            	$("#agencia").val(data.agencia);
                $("#dv_agencia").val(data.dv_agencia);
            	$("#tipo").val(data.tipo);
            	$("#split").val(data.split);
            	$("#status").val(data.status);
                $("#transfer_day").val(data.transfer_day);              
                $("#recipient_id").val(data.recipient_id);  

                $("#razao_social").attr("readonly", true);
                $("#cpf_cnpj").attr("readonly", true);

            	dialog.dialog( "open" );
                
                $("#recipient_id").hide();
                $("#recipient_id_label").hide();
                

                if ($("#id_gateway").val() == "7") {
                    $("#recipient_id").show();
                    $("#recipient_id_label").show();
                }
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
            url: 'recebedor.php',
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

        if( !verificaCPF( cpf_cnpj.val() ) && !verificaCNPJ( cpf_cnpj.val() ) ){
            valid = false;
            updateTips ("CPF / CNPJ inválido!");
            cpf_cnpj.addClass('ui-state-error');
        }

        if (transfer_day.val() != "") {
            var transferday = Number(transfer_day.val());
            if (transferday<0 || transferday>31) {
                valid = false;
                updateTips ("Dia da transferência precisa ser entre 1 e 31!");
                transfer_day.addClass('ui-state-error');
            }
        }
        else {
            valid = false;
                updateTips ("Dia da transferência precisa ser entre 1 e 31!");
                transfer_day.addClass('ui-state-error');
        }

        valid = valid && checkRegexp( email, emailRegex, "E-mail inválido!" );        

        if ( valid ) {
        	if ( id.val() == "" ){
                var p = 'recebedor.php?action=add&produtor='+ produtor.val();
            }else{
                var p = 'recebedor.php?action=update&id='+ id.val() +'&produtor='+ produtor.val();
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
                text: 'Selecione o Organizador!'
            });
        } else {
            $("#razao_social").attr("readonly", false);
            $("#cpf_cnpj").attr("readonly", false);
            dialog.dialog( "open" );    
        }        

        $("#recipient_id").hide();
        $("#recipient_id_label").hide();

        if ($("#id_gateway").val() == "7") {
            $("#recipient_id").show();
            $("#recipient_id_label").show();
        }
    });
    $('#newTICKETSPAY').button().click(function(event) {
    	event.preventDefault();
        if($("#produtor").val() == -1) {
            $.dialog({
                title: 'Alerta...',
                text: 'Selecione o Organizador!'
            });
        } else {
            var p = 'recebedor.php?action=addtp&produtor='+ produtor.val();
            
            $.ajax({
				url: p,
				type: 'post',
				data: "",
				success: function(data) {
					if (trim(data).substr(0, 4) == 'true') {
                        location.reload();
                    } else {
                        $.dialog({text: "Recebedor já existente."});
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
        }        
    });

    form = dialog.find( "form" ).on( "submit", function( event ) {
        event.preventDefault();
        add();
    });

    $("#produtor").change(function() {
        location.href = "?p=recebedor&produtor=" + $(this).val();

        // $.ajax({
		// 		url: 'recebedor.php?action=getgateway&produtor='+ produtor.val(),
		// 		type: 'post',
		// 		data: {},
		// 		success: function(data) {
		// 			$("#id_gateway").val(data);
		// 		},
		// 		error: function(){
        //             $.dialog({
        //                 title: 'Erro...',
        //                 text: 'Erro na chamada dos dados !!!'
        //             });
        //             return false;
        //         }
		// 	});
    })

});
</script>
<h2>Recebedores</h2>

<div id="dialog-form" title="Informações do Recebedor">
	<p class="validateTips"></p>
	<form id="conta" name="conta" action="?p=recebedor" method="POST">
		<input type="hidden" name="id" id="id" value="" />
        <input type="hidden" name="id_produtor" id="id_produtor" value="" />
        
        <fieldset>
            <legend>Dados do Recebedor</legend>
            <label for="razao_social">Razão Social:</label>
            <input type="text" id="razao_social" name="razao_social" maxlength="30" class="text ui-widget-content ui-corner-all" />
            <label for="cpf_cnpj">CPF / CNPJ:</label>
            <input type="text" id="cpf_cnpj" name="cpf_cnpj" maxlength="14" class="text ui-widget-content ui-corner-all" />
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" maxlength="100" class="text ui-widget-content ui-corner-all" />
            <label for="email">E-mail:</label>
            <input type="text" id="email" name="email" maxlength="100" class="text ui-widget-content ui-corner-all"/>
            <label for="telefone">Telefone:</label>
            <input type="text" id="telefone" name="telefone" maxlength="10" class="text ui-widget-content ui-corner-all" />
            <label for="celular">Celular:</label>
            <input type="text" id="celular" name="celular" maxlength="10" class="text ui-widget-content ui-corner-all" />
            <label for="transfer_day">Dia do mês para o repasse:</label>
            <input type="text" id="transfer_day" name="transfer_day" maxlength="2" class="text ui-widget-content ui-corner-all" />
            <label for="recipient_id" id="recipient_id_label" >Código no gateway:</label>
            <input type="text" id="recipient_id" name="recipient_id" maxlength="250" class="text ui-widget-content ui-corner-all" />
        </fieldset>

        <fieldset>
            <legend>Informacoes da Conta</legend>
		    <label for="banco">Banco:</label>		    
            <select id="banco" name="banco" class="ui-widget-content ui-corner-all" />
		    <?php while($rs = fetchResult($stmtBanco)) { ?>
            <option value="<?php echo $rs['cd_banco']; ?>"><?php echo utf8_encode2($rs['cd_banco'] . " - " . $rs['ds_banco']); ?></option>
            <?php } ?>
            </select>            
            <label for="agencia">Agência:</label>
		    <input type="text" id="agencia" name="agencia" maxlength="5" class="text ui-widget-content ui-corner-all agencia" /> - 
            <input type="text" id="dv_agencia" name="dv_agencia" maxlength="1" class="text ui-widget-content ui-corner-all agencia-dv" />
		    <label for="conta_bancaria">Conta:</label>
		    <input type="text" id="conta_bancaria" name="conta_bancaria" maxlength="13" class="text ui-widget-content ui-corner-all conta" /> - 
            <input type="text" id="dv_conta_bancaria" name="dv_conta_bancaria" maxlength="1" class="text ui-widget-content ui-corner-all conta-dv" />
		    <label for="tipo">Tipo da Conta:</label>
			<select id="tipo" name="tipo" class="ui-widget-content ui-corner-all">
                <option value="CC">Conta Corrente</option>
                <option value="CP">Conta Poupança</option>
            </select>
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
        <input type="hidden" name="id_gateway" id="id_gateway" value="<?php echo $id_gateway ?>" />
        <label>Organizador: </label>
        <select id="produtor" name="produtor">
            <option value="-1">Selecione</option>
            <?php
                $query = "SELECT id_produtor
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
		<a id="new" href="#new">Novo</a>	
		<a id="newTICKETSPAY" href="#newTICKETSPAY">Cadastrar TicketOffice</a>	
	</div>
</form>

<table class="ui-widget ui-widget-content">
	<thead>
		<tr class="ui-widget-header">
			<th class="text-left">Nome</th>
			<th class="text-left">Banco</th>
			<th class="text-right">Agência</th>
			<th class="text-right">Conta</th>
			<th class="text-center">Tipo da Conta</th>
            <!--th class="text-left">Recebedor (Pagar.me)</th-->
            <th class="text-left">Status</th>                
			<th colspan="2" class="th-action">Ações</th>
		</tr>
	</thead>
	<tbody>
		<?php while($rs = fetchResult($stmt)) { 
			$id = $rs["id_recebedor"];
            $conta = ($rs["dv_conta_bancaria"] != "") ? $rs["cd_conta_bancaria"] ."-". $rs["dv_conta_bancaria"] : $rs["cd_conta_bancaria"];
            $agencia = ($rs["dv_agencia"] != "") ? $rs["cd_agencia"] ."-". $rs["dv_agencia"] : $rs["cd_agencia"];
		?>
		<tr>
			<td class="text-left"><?php echo utf8_encode2($rs["ds_nome"]); ?></td>
			<td class="text-left"><?php echo utf8_encode2($rs["cd_banco"] . " - " . $rs["ds_banco"]); ?></td>
			<td class="text-right"><?php echo $agencia; ?></td>
			<td class="text-right"><?php echo $conta; ?></td>
			<td class="text-center"><?php echo $rs["cd_tipo_conta"] == "CC" ? "Conta Corrente" : "Conta Poupança"; ?></td>
            <!--td class="text-left"><?php //echo $rs["recipient_id"]; ?></td-->
            <td class="text-left"><?php echo $rs["in_ativo"] ? "Ativo" : "Inativo"; ?></td>
			<td class="td-action"><a href="<?php echo $pagina; ?>?action=edit&id=<?php echo $id; ?>" class="button">Editar</a></td>
            <td class="td-action"><a href="<?php echo $pagina; ?>?action=delete&id=<?php echo $id; ?>" class="button">Apagar</a></td>
		</tr>
		<?php } ?>
	</tbody>
</table>
<br/>
<?php
	}
}
?>