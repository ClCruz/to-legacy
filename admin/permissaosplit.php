<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 661, true)) {
    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);
    } else {
?>
        <link rel="stylesheet" href="../stylesheets/loading.css" >
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script type="text/javascript" src="../javascripts/functions.js"></script>
        <script type="text/javascript" src="../javascripts/loading.js"></script>
        <style type="text/css">
	label, input { display:block; }
    .toSave select { margin-bottom:12px; width:95%; padding: .4em; }
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
    .text-left{text-align: left;}
    .text-right{text-align: right;}
    .text-center{text-align: center;}
</style>
        <script type="text/javascript">
            $(function() {
                var pagina = '<?php echo $pagina; ?>';
                var dialog;

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
                            $("#id_usuario").val("");
                            $("#id_produtor").val("");
                            $("#id_recebedor").val("");
                        }
                    });

                function loading(id, message, slideOnStart, slideOnStop) {
                    slideOnStart = slideOnStart == undefined || slideOnStart == null ? true : slideOnStart;
                    slideOnStop = slideOnStop == undefined || slideOnStop == null ? true : slideOnStop;
                    message = message == undefined || message == null ? "Carregando" : message;
                    if (!$(id).is(':loading')) {
                    $(id).loading(
                        { 
                            theme: 'dark',
                            stoppable: true, 
                            message: message,
                            onStart: function(loading) {
                                loading.overlay.slideDown(slideOnStart ? 400: 1);
                            },
                            onStop: function(loading) {
                                console.log(id + " stop");
                                loading.overlay.slideUp(slideOnStop ? 400: 1);
                            }
                        });
                    }
                }

                $('#app table').delegate('a', 'click', function(event) {
                    event.preventDefault();

                    var $this = $(this),
                    href = $this.attr('href'),
                    id = 'id=' + $.getUrlVar('id', href),
                    tr = $this.closest('tr');

                    if (href.indexOf('?action=edit') != -1) {
                        // $.get('permissaosplit.php?action=load&' + id, function(data) {
                        //     data = $.parseJSON(data);

                        //     $("#id_permissaosplit").val(data.id_permissaosplit);
                        //     $("#id_usuario").val(data.id_usuario);
                        //     $("#id_produtor").val(data.id_produtor);
                        //     $("#id_recebedor").val(data.id_recebedor);
                        //     dialog.dialog( "open" );
                        // });
                    }  else if (href.indexOf('?action=delete') != -1) {
                        $.confirmDialog({
                            text: 'Tem certeza que deseja apagar este registro?',
                            uiOptions: {
                                buttons: {
                                    'Sim': function() {
                                        $(this).dialog('close');
                                        $.get(href, function(data) {
                                            if (data.replace(/^\s*/, "").replace(/\s*$/, "") == 'true') {
                                                loadGrid();
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

                
                function checkAdd() {
                    notValid = "";

                    $.ajax({
                        url: 'permissaosplit.php',
                        async: false,
                        type: 'get',
                        data: 'action=check&id_usuario='+ $('#id_usuario').val() + '&id_produtor='+ $("#id_produtor").val() + '&id_recebedor='+ $("#id_recebedor").val(),
                        success: function(data) {
                            data = $.parseJSON(data);
                            if (!data.ok) {
                                notValid = "Dados já cadastrados.";
                            }
                        }
                    });

                    return notValid;
                }
                function add() {
                    var valid = true;

                    $("#id_produtor").removeClass('ui-state-error');

                    if ($("#id_usuario").val() == "-1") {
                        $("#id_usuario").addClass('ui-state-error');
                        valid = false;
                        $.dialog({text: "Escolha um usuário."});
                    } 
                    var check = checkAdd(); 
                    if (check!="") {
                        valid = false;
                        $.dialog({text: check});
                    }
                  

                    if ( valid ) {
                        loading(".ui-dialog:visible", null, true, false);
                        
                        if ( $("#id_permissaosplit").val() == "" ){
                            var p = 'permissaosplit.php?action=add';
                        }else{
                            var p = 'permissaosplit.php?action=update&id_permissaosplit='+ $("#id_permissaosplit").val()
                        }

                        $.ajax({
                            url: p,
                            type: 'post',
                            data: $('#toSave').serialize(),
                            success: function(data) {
                                $(".ui-dialog").loading("stop");
                               
                                dialog.dialog( "close" );    
                            
                                if (data == 'OK') {
                                    loadGrid();
                                } else {
                                    $.dialog({text: data});
                                }
                            },
                            error: function(){
                                $(".ui-dialog").loading("stop");
                                dialog.dialog( "close" );    
                            
                                $.dialog({
                                    title: 'Erro...',
                                    text: 'Erro na chamada dos dados !!!'
                                });
                                return false;
                            }
                        });
                        
                    }
                    return valid;
                }

                $('#new').button().click(function(event) {
                    event.preventDefault();
                    
                    dialog.dialog( "open" );    
                    loading(".ui-dialog:visible");
                    loadUsuario(null);
                    loadProdutor(null);

                    $(".ui-dialog").loading("stop");
                });

                $(".produtor").change(function() {
                        var id_produtor = $(this).val();
                        loadRecebedor(null, id_produtor);
                    });

                function loadUsuario(idtoselect) {
                    $("#id_usuario").html('<option value="-1">Aguarde...</option>');
                    $.ajax({
                        url: 'usuarios.php?action=selectload',
                        type: 'post',
                        data: {},
                        success: function(data) {
                            data = $.parseJSON(data);
                            $("#id_usuario").html('<option value="-1">Selecione...</option>');
                            $.each(data, function(key, value) {
                                var selected = "";
                                if (idtoselect!= null && idtoselect!=undefined && idtoselect == value.id_usuario) {
                                    selected = "selected";
                                }
                                $("#id_usuario").append('<option value='+ value.id_usuario + ' ' + selected + '>' + value.ds_nome + '</option>');
                            });
                        },
                        error: function(){
                            $("#id_usuario").html('<option value="-1">Selecione...</option>');
                            $.dialog({
                                title: 'Erro...',
                                text: 'Erro na chamada dos dados !!!'
                            });
                            return false;
                        }
                    });
                }

                function loadProdutor(idtoselect) {
                    $("#id_produtor").html('<option value="-1">Aguarde...</option>');
                    $.ajax({
                        url: 'produtor.php?action=selectload',
                        type: 'post',
                        data: {},
                        success: function(data) {
                            data = $.parseJSON(data);
                            $("#id_produtor").html('<option value="0">Todos</option>');
                            
                            $.each(data, function(key, value) {
                                var documentoProdutor = "";
                                if (value.cd_cpf_cnpj.length == 14) {
                                    documentoProdutor = value.cd_cpf_cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/g,"\$1.\$2.\$3\/\$4\-\$5");
                                }
                                else {
                                    documentoProdutor = value.cd_cpf_cnpj.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/g,"\$1.\$2.\$3\-\$4");
                                }

                                var selected = "";
                                if (idtoselect!= null && idtoselect!=undefined && idtoselect == value.id_produtor) {
                                    selected = "selected";
                                }
                                $("#id_produtor").append('<option value='+ value.id_produtor + ' ' + selected + '>' + value.ds_razao_social + " (" + documentoProdutor + ")" + '</option>');
                            });
                        },
                        error: function(){
                            $("#id_produtor").html('<option value="-1">Erro...</option>');
                            $.dialog({
                                title: 'Erro...',
                                text: 'Erro na chamada dos dados !!!'
                            });
                            return false;
                        }
                    });
                }

                function loadRecebedor(idtoselect, id_produtor) {
                    $("#id_recebedor").loading();
                    $("#id_recebedor").html('<option value="-1">Aguarde...</option>');
                    $.ajax({
                        url: 'recebedor.php?action=selectload&id_produtor=' + id_produtor,
                        type: 'post',
                        data: {},
                        success: function(data) {
                            $("#id_recebedor").loading("stop");
                            data = $.parseJSON(data);
                            $("#id_recebedor").html('<option value="0">Todos</option>');
                            console.log(data);
                            $.each(data, function(key, value) {
                                var documento = "";
                                if (value.cd_cpf_cnpj.length == 14) {
                                    documento = value.cd_cpf_cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/g,"\$1.\$2.\$3\/\$4\-\$5");
                                }
                                else {
                                    documento = value.cd_cpf_cnpj.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/g,"\$1.\$2.\$3\-\$4");
                                }

                                var selected = "";
                                if (idtoselect!= null && idtoselect!=undefined && idtoselect == value.id_recebedor) {
                                    selected = "selected";
                                }
                                $("#id_recebedor").append('<option value='+ value.id_recebedor + ' ' + selected + '>' + value.ds_razao_social + " (" + documento + ")" + '</option>');
                            });
                        },
                        error: function(){
                            $("#id_recebedor").loading("stop");
                            $("#id_recebedor").html('<option value="-1">Erro...</option>');
                            $.dialog({
                                title: 'Erro...',
                                text: 'Erro na chamada dos dados !!!'
                            });
                            return false;
                        }
                    });
                }

                $(document).ready(function () {
                    loadGrid();
                });

                function loadGrid() {
                    $("#table-grid tbody").html("");
                    loading("#table-grid:visible");
                    $.ajax({
                        url: pagina + '?action=load',
                        type: 'post',
                        data: {},
                        success: function(data) {	
                            data = $.parseJSON(data);
                            $("#table-grid tbody").html("");
                            if (data.length == 0)
                                $("#table-grid tbody").html("<tr><td colspan='7'>Nenhum dado encontrado.</td></tr>");

                            var total = 0;
                            $.each(data, function(key, value) {
                                var documentoProdutor = "";
                                if (value.DocumentoProdutor != null) {
                                    if (value.DocumentoProdutor.length == 14) {
                                        documentoProdutor = "(" + value.DocumentoProdutor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/g,"\$1.\$2.\$3\/\$4\-\$5") + ")";
                                    }
                                    else {
                                        documentoProdutor = "(" + value.DocumentoProdutor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/g,"\$1.\$2.\$3\-\$4") + ")";
                                    }
                                }
                                var documentoRecebedor = "";
                                if (value.DocumentoRecebedor != null) {
                                    if (value.DocumentoRecebedor.length == 14) {
                                        documentoRecebedor = "(" + value.DocumentoRecebedor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/g,"\$1.\$2.\$3\/\$4\-\$5")+ ")";
                                    }
                                    else {
                                        documentoRecebedor = "(" + value.DocumentoRecebedor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/g,"\$1.\$2.\$3\-\$4")+ ")";
                                    }
                                }
                                var toAppend = "<tr style='cursor: pointer;' id='" + value.id_permissaosplit + "' class='toClick trline' data='" + value.id_permissaosplit + "'>";
                                toAppend += "<td>"+ value.NomeUsuario +"</td>";
                                toAppend += "<td>"+ value.RazaoSocialProdutor + documentoProdutor +"</td>";
                                toAppend += "<td>"+ value.RazaoSocialRecebedor + documentoRecebedor +"</td>";
                                toAppend += "<td>"+ (value.bit_saque ? "Sim" : "Não") +"</td>";
                                toAppend += "<td>"+ (value.bit_antecipacao ? "Sim" : "Não") +"</td>";
                                // toAppend += "<td><a href='" + pagina + "?action=edit&id=" + value.id_permissaosplit + "'>" +"Editar</a></td>";
                                toAppend += "<td><a href='" + pagina + "?action=delete&id=" + value.id_permissaosplit + "'>" +"Apagar</a></td>";
                                
                                toAppend += "</tr>";
                                $("#table-grid tbody").append(toAppend);
                            });
                            $("#table-grid tfoot").html("");
                            $("#table-grid").loading("stop");
                        },
                        error: function(){
                            $("#table-grid tbody").html("");
                            $("#table-grid").loading("stop");
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
        <h2>Permissões para split</h2>
        <form id="dados" name="dados" method="post">
            <table id="table-grid" class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header ">
                        <th width="20%">Usuário</th>
                        <th width="20%">Organizador</th>
                        <th width="20%">Recebedor</th>
                        <th width="20%">Permite Saque?</th>
                        <th width="20%">Permite Antecipação?</th>
                        <th style="text-align: center;" colspan="2" width="10%">Ações</th>
                    </tr>
                </thead>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
        </tbody>
    </table>
    <a id="new" href="#new">Novo</a>
    </form>
<div id="dialog-form" class="toSave" title="Permissão">
	<p class="validateTips"></p>
	<form id="toSave" name="toSave" action="?p=permissaosplit" method="POST">
		<input type="hidden" name="id" id="id_permissaosplit" value="" />
        
        <fieldset>
            <legend>Dados</legend>
            <label for="id_usuario">Usuário:</label>
            <select id="id_usuario" name="id_usuario" class="ui-widget-content ui-corner-all">
            </select>
            <label for="id_produtor">Organizador:</label>
            <select id="id_produtor" name="id_produtor" class="produtor ui-widget-content ui-corner-all">
            </select>
            <label for="id_recebedor">Recebedor:</label>
            <select id="id_recebedor" name="id_recebedor" class="ui-widget-content ui-corner-all">
            </select>
            <label for="bit_saque">Permite Saque:</label>
            <input type="checkbox" id="bit_saque" value="1" name="bit_saque" />		    
            <label for="bit_antecipacao">Permite antecipação:</label>
            <input type="checkbox" id="bit_antecipacao" value="1" name="bit_antecipacao" />		    
            </select>
        </fieldset>
	</form>
</div>

        <?php }
}?>