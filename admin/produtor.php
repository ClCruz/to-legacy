<?php
require_once('acessoLogadoDie.php');
require_once('../settings/functions.php');

$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 620, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action']) or isset($_POST['codigo'])) {
        
        require('actions/'.$pagina);
        
    } else {

    	$query = "SELECT p.id_produtor
        ,p.ds_razao_social
        ,p.cd_cpf_cnpj
        ,p.ds_nome_contato
        ,p.cd_email
        ,p.ds_ddd_telefone
        ,p.ds_telefone
        ,p.ds_ddd_celular
        ,p.ds_celular
        ,p.in_ativo
        ,p.id_gateway 
        ,g.ds_gateway
        FROM mw_produtor p
        LEFT JOIN mw_gateway g ON p.id_gateway=g.id_gateway
        WHERE in_ativo = 1 ORDER BY ds_razao_social";
    	$stmt = executeSQL($mainConnection, $query, array());
?>
<style type="text/css">
	label, input { display:block; }
    input.text, select { margin-bottom:12px; width:95%; padding: .4em; }
    fieldset { padding:0; border:0; margin-top:25px; }
    .td-action {text-align: center; width: 50px;}
    .th-action {text-align: center; width: 100px;}
    .add-produtor {margin-bottom: 20px; text-align: right;}
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
		emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/,
        id = $( "#id" ),
        razao_social = $("#razao_social"),
        cpf_cnpj = $("#cpf_cnpj"),
        nome = $("#nome"),
        email = $("#email"),
        telefone = $("#telefone"),
        celular = $("#celular"),
        allFields = $( [] ).add(razao_social).add(cpf_cnpj).add(nome).add(email).add(celular),
        tips = $( ".validateTips" );

	$('.button').button();
	$("#telefone").mask("99 9999-9999");
    $('#celular').mask("99 9999-9999?9");

    $("#cpf_cnpj").keypress(verificaNumero);

    $('#app table').delegate('a', 'click', function(event) {
        event.preventDefault();

        var $this = $(this),
        href = $this.attr('href'),
        id = 'id=' + $.getUrlVar('id', href),
        tr = $this.closest('tr');

        if (href.indexOf('?action=edit') != -1) {
        	$.get('produtor.php?action=load&' + id, function(data) {
            	data = $.parseJSON(data);

            	$("#id").val(data.id);
            	$("#razao_social").val(data.razao_social);
            	$("#cpf_cnpj").val(data.cpf_cnpj);
            	$("#nome").val(data.nome);
            	$("#email").val(data.email);
            	$("#telefone").val(data.telefone);
            	$("#celular").val(data.celular);

                $("#id_gateway").val(data.id_gateway);

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
            allFields.removeClass( "ui-state-error" );
        }
    });

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

        if( !verificaCPF( cpf_cnpj.val() ) && !verificaCNPJ( cpf_cnpj.val() ) ){
            valid = false;
            updateTips ("CPF / CNPJ inválido!");
            cpf_cnpj.addClass('ui-state-error');
        }

        valid = valid && checkRegexp( email, emailRegex, "E-mail inválido!" );

        if ( valid ) {
        	if ( id.val() == "" ){
                var p = 'produtor.php?action=add';
            }else{
                var p = 'produtor.php?action=update&id='+ id.val();
            }

            $.ajax({
				url: p,
				type: 'post',
				data: $('#produtor').serialize(),
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
        dialog.dialog( "open" );
    });

    form = dialog.find( "form" ).on( "submit", function( event ) {
        event.preventDefault();
        add();
    });

});
</script>
<h2>Organizadores</h2>

<div id="dialog-form" title="Informações do Organizador">
	<p class="validateTips"></p>
	<form id="produtor" name="produtor" action="?p=produtor" method="POST">
		<fieldset>
			<input type="hidden" name="id" id="id" value="" />
		    <label for="razao_social">Razão Social:</label>
		    <input type="text" id="razao_social" name="razao_social" maxlength="250" class="text ui-widget-content ui-corner-all" />
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
		    <label for="id_gateway">Gateway:</label>
            <select id="id_gateway" name="id_gateway" class="ui-widget-content ui-corner-all">
                <option value="6">Pagarme</option>
                <option value="7">Pinbank (Ti Pagos)</option>
            </select>
		</fieldset>
	</form>
</div>

<form id="dados" name="dados" method="post">
	
	<div class="add-produtor">
		<a id="new" href="#new">Novo</a>	
	</div>

	<table class="ui-widget ui-widget-content">
		<thead>
			<tr class="ui-widget-header">
				<th>Razão Social</th>
				<th>CPF / CNPJ</th>
				<th>Nome</th>
				<th>E-mail</th>
				<th>Telefone</th>
				<th>Celular</th>
                <th>Gateway</th>
				<th colspan="2" class="th-action">Ações</th>
			</tr>
		</thead>
		<tbody>
			<?php while($rs = fetchResult($stmt)) { 
				$id = $rs["id_produtor"];
			?>
			<tr>
				<td><?php echo utf8_encode2($rs["ds_razao_social"]); ?></td>
				<td><?php echo $rs["cd_cpf_cnpj"]; ?></td>
				<td><?php echo utf8_encode2($rs["ds_nome_contato"]); ?></td>
				<td><?php echo $rs["cd_email"]; ?></td>
				<td><?php echo $rs["ds_ddd_telefone"] ." ". $rs["ds_telefone"]; ?></td>
				<td><?php echo $rs["ds_ddd_celular"] ." ". $rs["ds_celular"]; ?></td>
                <td><?php echo $rs["ds_gateway"]; ?></td>
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