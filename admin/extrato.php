<?php
require_once('acessoLogadoDie.php');
require_once('../settings/functions.php');
require_once('../log4php/log.php');

$mainConnection = mainConnection();
session_start();

log_trace("Page extrato.php");
if (acessoPermitido($mainConnection, $_SESSION['admin'], 640, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action']) or isset($_POST['codigo'])) {
        
        require('actions/'.$pagina);
        
    } else {

    	//$query = "SELECT * FROM mw_produtor WHERE in_ativo = 1 ORDER BY ds_razao_social";
    	//$stmt = executeSQL($mainConnection, $query, array());
?>
<style type="text/css">
	#app h2, .appExtension h2 {margin: 15px 0px 15px 0px;}
    #app form, .appExtension form {text-align: left;}
    #dialog-form label, #dialog-form input { display:block; }
    #dialog-form input.text, #dialog-form select { margin-bottom:12px; width:95%; padding: .4em; }
    #dialog-form-saque label, #dialog-form-saque input { display:block; }
    #dialog-form-saque input.text, #dialog-form-saque select { margin-bottom:12px; width:95%; padding: .4em; }
    /**fieldset { padding:0; border:0; margin-top:25px; }**/
    .td-action {text-align: center; width: 50px;}
    .th-action {text-align: center; width: 100px;}
    .text-left {text-align: left;}
    .text-right {text-align: right;}
    .produtor {margin-bottom: 20px; display: inline; float: right;}
    .ui-dialog{ padding: .3em; }
    .validateTips { border: 1px solid transparent; padding: 0.3em; }
    .saldo {display: block; margin-bottom: 20px; text-align: right;}
    .saldo .disponivel, .saldo .receber{display: inline; padding: 0px 10px; font-weight: bold; font-size: 14px;}
    .periodo {margin-bottom: 12px;}
    .periodo label {display: inline !important;}
    .periodo input {vertical-align: middle; !important; display: inline !important;}
    .fields{width: 50%; float: left; margin-bottom: 15px;}
    .fields label {display:block; font-weight: bold;}
    .fields select {width: 70%; margin-bottom: 10px;}
    .actions{width: 50%; float: right;}
    .trline:hover { background-color: #ffa;}
    #iconhelp{
        cursor: pointer;
        float: right;
        margin-right: 18px;
        margin-top: 0px;
        background-color: white;
        color:red !important;
        background-image:url(/stylesheets/customred/images/ui-icons_cc0000_256x240.png) !important;
    }   

</style>
<link rel="stylesheet" href="../stylesheets/introjs.css" >
<link rel="stylesheet" href="../stylesheets/loading.css" >
<script type="text/javascript" src="../javascripts/intro.js"></script>
<script type="text/javascript" src="../javascripts/moment.js"></script>
<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script type="text/javascript" src="../javascripts/loading.js"></script>
<script src="../javascripts/jquery.maskedinput.min.js" type="text/javascript"></script>
<script type='text/javascript' src='../javascripts/jquery.numeric.js'></script>
<script>
    function introSaque(){
        var intro = introJs();
          intro.setOptions({
            nextLabel: "Próximo",
            prevLabel: "Voltar",
            skipLabel: "Pular",
            doneLabel: "Finalizar",
            steps: [
              {
                element: '#dialog-form-saque',
                intro: "<h3>Como realizar um saque?</h3> <br /><br /> "
              },
              {
                  element: '#fsValorSaque',
                  intro: "<h4>É preciso selecionar o valor que você deseja sacar.</h4>"
              },
              {
                  element: '#valoresSaque',
                  intro: "<h4>Validar a taxa e quanto ficará o valor final.</h4>"
              },
              {
                  element: ".btnEfetuarSaque",
                  intro: "<h4>E aperte o botão efetuar saque.</h4>"
              },
              {
                element: '#dialog-form-saque',
                intro: "<h2><b>Quanto tempo demora para o dinheiro ficar disponível para saque?</b></h2><br /><p>Seus valores estarão disponíveis para saque na data de recebimento normal das parcelas.</p><p>Isso significa 31 dias após a transação de cartão de crédito ter sido criada (29 dias corridos + 2  dias úteis) no caso de transações com uma parcela e 2 dias úteis após o pagamento do boleto bancário. Caso a transação tenha de 2 a 12 parcelas, o recebimento normal será da seguinte forma: primeira parcela em 31 dias, segunda em 61, terceira em 91, e assim por diante.</p>"
              },
              {
                element: '#dialog-form-saque',
                intro: "<p><small><b>Atenção:</b></small></p><p><small>Os saques realizados em dias úteis até às 15h caem na sua conta bancária no mesmo dia. Já aqueles realizados após esse horário ou mesmo em finais de semana e feriados caem no próximo dia útil.</small></p><p><small>Em relação à taxa para saque, é importante lembrar que se sua conta cadastrada for Bradesco, a tarifa é isenta. Caso não seja Bradesco, pedimos para verificar a taxa que foi acordada. </small></p>"
              }
            ]
          });

          intro.start();
      }
      function introAntecipacao(){
        $("#trueForm").hide();
        $("#fakeForm").show();
        $("button > span:contains('Efetuar Antecipação')").parent().show();
        
        var intro = introJs();
          intro.setOptions({
            nextLabel: "Próximo",
            prevLabel: "Voltar",
            skipLabel: "Pular",
            doneLabel: "Finalizar",
            steps: [
              {
                element: '#dialog-form',
                intro: "<h3>Como realizar um antecipação manual?</h3> <br /><br /> "
              },
              {
                  element: '#fsComoDesejaAnteciparFake',
                  intro: "<h3>Parcelas do início ou do final</h3><br /><p>Ao antecipar do começo, o valor cobrado em taxas será menor, mas você pode ficar com um intervalo próximo do presente sem valores a receber.</p><br /><p>Se você antecipar do final, o valor cobrado em taxas será maior, mas você tem mais tempo fazer mais vendas e deixar o seu 'caixa' mais equilibrado.</p>"
              },
              {
                  element: '#fsQuandoDesejaReceberFake',
                  intro: "<h3>Selecione a data que deseja receber.</h3>"
              },
              {
                  element: "#fsValorFake",
                  intro: "<h3>Selecionar na régua o valor que deseja antecipar</h3><br /><p>Note que não é possível escolher um valor exato para antecipar. Isso acontece pois, nós não quebramos uma parcela para antecipar. Assim, se você tem uma venda de R$300,00 e outra de R$600,00, não conseguirá antecipar R$500,00. Poderá antecipar R$300,00 ou R$600,00.</p>"
              },
              {
                element: '#fsResumoFake',
                intro: "<h3>Clique no botão avançar para ele mostrar os valores reais e as taxas.</h3>"
              },
              {
                element: '#custoAntecipacaoFullFake',
                intro: "<h3>Aqui aparece quanto vai custar essa antecipação"
              },
              {
                element: '#valorAntecipacaoFullFake',
                intro: "<h3>Aqui aparece o quanto será o valor real  da antecipação</h3>"
              },
              {
                  element: ".btnEfetuarAntecipacao",
                  intro: "<h4>E aperte o botão efetuar a antecipação.</h4>"
              },
              {
                element: '#dialog-form',
                intro: "<h2><b>Essa demanda passará então pela análise da nossa equipe responsável e, caso aprovada, cairá na data escolhida por você na conta configurada para você.</p>"
              },
            ]
          });
          intro.oncomplete(function() {
            $("#trueForm").show();
            $("#fakeForm").hide();
            $("button > span:contains('Efetuar Antecipação')").parent().hide();
        });
        intro.onexit(function() {
            $("#trueForm").show();
            $("#fakeForm").hide();
            $("button > span:contains('Efetuar Antecipação')").parent().hide();
        });
        intro.start();
      }

$(function() {
	var pagina = '<?php echo $pagina; ?>';
	var dialog,
        dialogSaque, 
		form,
		emailRegex   = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/,
        id           = $( "#id" ),
        razao_social = $("#razao_social"),
        cpf_cnpj     = $("#cpf_cnpj"),
        nome         = $("#nome"),
        email        = $("#email"),
        telefone     = $("#telefone"),
        celular      = $("#celular"),
        recebedor    = $("#recebedor"),
        valor        = $("#valor"),
        data         = $("#data"),
        allFields    = $( [] ).add(valor).add(data),
        tips         = $( ".validateTips" );

	$('.button').button();
	$("#telefone").mask("99 9999-9999");
    $('#celular').mask("99 9999-9999?9");

    $("#cpf_cnpj").keypress(verificaNumero);
    $("#btn-saque").prop('disabled', true);
    $("#btn-antecipacao").prop('disabled', true);
    $("#data").datepicker({minDate: 0, dateFormat: 'dd/mm/yy',
        onClose: function(){
            antecipacaoMaximoMinimo(); 
			   }});
    $("#valor").numeric(",");

    $("#start_date").mask("99/99/9999");
    $("#end_date").mask("99/99/9999");

    $("#start_date").val(moment().add(-30, 'days').format("DD/MM/YYYY"));
    $("#end_date").val(moment().format("DD/MM/YYYY"));

    $('.extratoSearch').datepicker({
            changeMonth: true,
            changeYear: true,
            onSelect: function(date, e) {
                if ($(this).is('#start_date')) {
                    $('#end_date').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                }
            }
        }).datepicker('option', $.datepicker.regional['pt-BR']);

    function formatar(src, mask){
        var i = src.value.length;
        var saida = mask.substring(0,1);
        var texto = mask.substring(i)
        if (texto.substring(0,1) != saida)
        {
            src.value += texto.substring(0,1);
        }
    }

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
            "Efetuar Antecipação": antecipar,
            Cancelar: function() {
                destroySlider();
                dialog.dialog( "close" );
            }
        },
        close: function() {
            destroySlider();
            document.forms[1].reset();
            id.val("");
            tips.text("");
            allFields.removeClass( "ui-state-error" );
        }
    });
    dialogSaque = $( "#dialog-form-saque" ).dialog({
        autoOpen: false,
        height: 600,
        width: 600,
        modal: true,
        buttons: {
            "Efetuar Saque": sacar,
            Cancelar: function() {
                destroySliderSaque();
                dialogSaque.dialog( "close" );
            }
        },
        close: function() {
            destroySliderSaque();
        }
    });
    function movement_objectPayment_MethodToString(value) {
        var ret = value;
        switch (ret) {
            case "credit_card":
                ret = "Cartão de Crédito";
            break;
            case "debit_card":
                ret = "Cartão de Débito";
            break;
        }
        return ret;
    }
    function movement_objectTypeToString(value) {
        var ret = value;
        switch (ret) {
            case "debit":
                ret = "D";
            break;
            case "credit":
                ret = "C";
            break;
        }
        return ret;
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

    form = dialog.find( "form" ).on( "submit", function( event ) {
        event.preventDefault();
        add();
    });

    $("#status").change(function() {
        $("#spanPeriodo").hide();

        switch ($("#status").val()) {
            case "playables":
            break;
            case "available":
                $("#spanPeriodo").show();
            break;
            case "transfers":
            break;
            case "antecipations":
            break;
        }
    });

    function zerar_grid() {
        $("#table-extrato tbody").html("");
        $("#table-extrato-available tbody").html("");
        $("#table-antecipavel tbody").html("");
        $("#table-transfer tbody").html("");

        $("#table-antecipavel").hide();
        $("#table-transfer").hide();
        $("#table-extrato-available").hide();
        $("#table-extrato").hide();
        $("#table-first").show();
    }

    $("#produtor").change(function() {
        zerar_saldo();
        zerar_grid();
        $("#recebedor").html('<option value="-1">Aguarde...</option>');
        $("#evento").html('<option value="-1">Aguarde...</option>');
        
        $.ajax({
            url: pagina + '?action=load_recebedor',
            type: 'post',
            data: $('#dados').serialize(),
            success: function(data) {
                valor_areceber = 0;
                data = $.parseJSON(data);
                $("#recebedor").html('<option value="-1">Selecione...</option>');
                if (data.length >0 ) {
                    if (data[0].id_gateway == "7" || data[0].id_gateway == 7) {
                        $("#recebedor").html('<option value="-1">Não é possível realizar operações de extrato com esse organizador.</option>');
                        return;
                    }
                }
                $.each(data, function(key, value) {
                    $("#recebedor").append('<option value='+ value.recipient_id + '>' + value.ds_razao_social +' - '+ value.cd_cpf_cnpj + '</option>');
                });
            },
            error: function(){
                $("#recebedor").html('<option value="-1">Selecione...</option>');
                $.dialog({
                    title: 'Erro...',
                    text: 'Erro na chamada dos dados !!!'
                });
                return false;
            }
        });
        $.ajax({
            url: pagina + '?action=load_evento&produtor=' + $("#produtor").val(),
            type: 'post',
            data: {},
            success: function(data) {
                valor_areceber = 0;
                data = $.parseJSON(data);
                $("#evento").html('<option value="-1">Todos</option>');
                $("#evento").append('<option value="0">Bilheteria</option>');
                $.each(data, function(key, value) {
                    $("#evento").append('<option value='+ value.id_evento + '>' + value.ds_evento + '</option>');
                });
            },
            error: function(){
                $("#evento").html('<option value="-1">Erro...</option>');
                $.dialog({
                    title: 'Erro...',
                    text: 'Erro na chamada dos dados !!!'
                });
                return false;
            }
        });
    });

    var valor_areceber = 0;

    function lineClick(id) {
        getTransaction(id);
    }

    function zerar_saldo() {
        $(".disponivel span").html("R$ 0,00");
        $(".receber span").html("R$ 0,00");
    }

    function load_saldo() {
        loading(".saldo");
        valor_areceber = 0;
        zerar_saldo();
        $.ajax({
            url: pagina + '?action=load_saldo',
            type: 'post',
            data: $('#dados').serialize(),
            success: function(data) {
                data = $.parseJSON(data);
                $.each(data, function(key, value) {
                    var valor_disponivel = data.available.amount / 100;
                    valor_areceber = data.waiting_funds.amount;
                    $(".disponivel span").html("R$ "+ valor_disponivel);
                    $(".receber span").html("R$ "+ valor_areceber / 100);
                });
                var disponivel = ($(".disponivel span").val() > 0);
                var receber = ($(".receber span").val() > 0);
                
                $("#btn-saque").prop('disabled', disponivel);
                $("#btn-antecipacao").prop('disabled', receber);    
                $(".saldo").loading("stop");            
            },
            error: function(){
                zerar_saldo();
                $.dialog({text: 'Erro na chamada dos dados !!!'});
                return false;
            }
        });
    }
    
    $("#recebedor").change(function() {        
        load_saldo();        
    });
    
    $("#btnBuscarExtrato").click(function(event) {

        $("#table-first").show();
        $("#table-extrato tbody").html("");
        $("#table-extrato-available tbody").html("");
        $("#table-antecipavel tbody").html("");
        $("#table-transfer tbody").html("");
        
        $("#table-extrato").hide();
        $("#table-extrato-available").hide();
        $("#table-antecipavel").hide();
        $("#table-transfer").hide();
        
        switch ($("#status").val()) {
            case "transfers":
                loading("body", null, false);
                $.ajax({
                    url: pagina + '?action=listtransfer&recebedor='+ recebedor.val(),
                    type: 'post',
                    data: { },
                    success: function(data) {	
                        $("body").loading("stop");
                        $("#table-first").hide();
                        $("#table-transfer").show();
                        data = $.parseJSON(data);
                        $("#table-transfer tbody").html("");
                        if (data.length == 0)
                            $("#table-transfer tbody").html("<tr><td colspan='7'>Nenhum dado encontrado.</td></tr>");

                        var total = 0;
                        $.each(data, function(key, value) {
                            var statusAux = "-";
                            switch (value.status) {
                                case "pending_transfer":
                                    statusAux = "Pendente";
                                break;
                                case "transferred":
                                    statusAux = "Transferido";
                                break;
                                case "failed":
                                    statusAux = "Falha";
                                break;
                                case "processing":
                                    statusAux = "Processando";
                                break;
                                case "canceled":
                                    statusAux = "Cancelado";
                                break;
                            }

                            var toAppend = "<tr class='trline'><td>" + moment(value.date_created).format("DD/MM/YYYY HH:mm") +"</td>";
                            toAppend += "<td>"+ statusAux +"</td>";
                            toAppend += "<td>"+ value.type +"</td>";
                            toAppend += "<td title='" + ((value.name == null || value.name == undefined) ? "-" : value.name) + "'>"+ ((value.login == null || value.login == undefined) ? "-" : value.login) +"</td>";
                            toAppend += "<td>R$ "+ (value.amount/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";
                            toAppend += "<td>R$ "+ (value.fee/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";
                            toAppend += "<td>"+ (value.funding_estimated_date == null ? "-" : moment(value.funding_estimated_date).format("DD/MM/YYYY")) +"</td>";
                            toAppend += "<td>"+ (value.funding_date == null ? "-" : moment(value.funding_date).format("DD/MM/YYYY")) +"</td>";
                            toAppend += "</tr>";
                            $("#table-transfer tbody").append(toAppend);
                        });
                        $("#table-transfer tfoot").html("");

                        // var toAppend = "<tr class=ui-widget-header'>"
                        // toAppend += "<td colspan='5' class='text-right ui-widget-header'>Total R$ "+ ((total)/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";
                        // toAppend += "</tr>";
                        // $("#table-extrato tfoot").append(toAppend);

                        // $(".toClick").click(function(obj) {
                        //     lineClick($(this).attr("data"));
                        // });
                    },
                    error: function(){
                        $("body").loading("stop");
                        $("#table-transfer tbody").html("");
                        $.dialog({
                            title: 'Erro...',
                            text: 'Erro na chamada dos dados !!!'
                        });
                        return false;
                    }
                });
            break;
            case "antecipations":
                loading("body", null, false);
                $.ajax({
                    url: pagina + '?action=listantecipations&recebedor='+ recebedor.val(),
                    type: 'post',
                    data: { },
                    success: function(data) {	
                        $("body").loading("stop");
                        $("#table-first").hide();
                        $("#table-antecipavel").show();
                        data = $.parseJSON(data);
                        $("#table-antecipavel tbody").html("");
                        if (data.length == 0)
                            $("#table-antecipavel tbody").html("<tr><td colspan='5'>Nenhum dado encontrado.</td></tr>");

                        var total = 0;
                        $.each(data, function(key, value) {
                            var statusAux = "-";
                            switch (value.status) {
                                case "building":
                                    statusAux = "Criando";
                                break;
                                case "pending":
                                    statusAux = "Pendente";
                                break;
                                case "approved":
                                    statusAux = "Aprovado";
                                break;
                                case "refused":
                                    statusAux = "Recusado";
                                break;
                                case "canceled":
                                    statusAux = "Cancelado";
                                break;
                            }

                            var toAppend = "<tr class='trline'><td>" + moment(value.date_created).format("DD/MM/YYYY HH:mm") +"</td>";
                            toAppend += "<td>"+ statusAux +"</td>";
                            toAppend += "<td>R$ "+ (value.amount/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";
                            toAppend += "<td>R$ "+ ((value.fee+value.anticipation_fee)/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";
                            toAppend += "<td>"+ (value.payment_date == null ? "-" : moment(value.payment_date).format("DD/MM/YYYY")) +"</td>";
                            toAppend += "</tr>";
                            $("#table-antecipavel tbody").append(toAppend);
                        });
                        $("#table-antecipavel tfoot").html("");

                        // var toAppend = "<tr class=ui-widget-header'>"
                        // toAppend += "<td colspan='5' class='text-right ui-widget-header'>Total R$ "+ ((total)/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";
                        // toAppend += "</tr>";
                        // $("#table-extrato tfoot").append(toAppend);

                        // $(".toClick").click(function(obj) {
                        //     lineClick($(this).attr("data"));
                        // });
                    },
                    error: function(){
                        $("body").loading("stop");
                        $("#table-antecipavel tbody").html("");
                        $.dialog({
                            title: 'Erro...',
                            text: 'Erro na chamada dos dados !!!'
                        });
                        return false;
                    }
                });
            break;
            case "playables":
                if ($("#end_date").val() == "" && $("#start_date").val() == "") {
                    $.dialog({
                        title: 'Erro...',
                        text: 'Preencha os campos de data!'
                    });
                    return;
                }

                loading("body", null, false);
                $.ajax({
                    url: pagina + '?action=listpayables',
                    type: 'post',
                    data: $('#dados').serialize(),
                    success: function(data) {	
                        var nameTable = "#table-extrato";
                        var colspan = "8";

                        $("body").loading("stop");
                        $("#table-first").hide();
                        $(nameTable).show();
                        data = $.parseJSON(data);
                        $(nameTable + " tbody").html("");
                        if (data.length == 0)
                            $(nameTable + " tbody").html("<tr><td colspan='" + colspan + "'>Nenhum dado encontrado.</td></tr>");

                        var total = 0;
                        $.each(data, function(key, value) {
                            total += value.amount-value.fee;
                            
                            //console.log(value);
                            var toAppend = "";
                            var aux = (value.fee/100)*100/(value.amount/100);
                            var final = Math.trunc((aux*30)/1.87);
                            
                            toAppend = "<tr style='cursor: pointer;' id='" + value.transaction_id + "' class='toClick trline' data='" + value.transaction_id + "'><td>" + moment(value.date_created).format("DD/MM/YYYY HH:mm") +"</td>";
                            toAppend += "<td>"+ value.transaction_id +"</td>";
                            toAppend += "<td>"+ (movement_objectTypeToString(value.type) == "ted" ? "Transferência" : (value.ds_evento == null ? "Bilheteria" : value.ds_evento )) +"</td>";
                            toAppend += "<td>"+ (movement_objectTypeToString(value.type) == "ted" ? "-" : moment(value.payment_date).format("DD/MM/YYYY")) +"</td>";
                            toAppend += "<td>"+ movement_objectTypeToString(value.type) +"</td>";
                            toAppend += "<td>"+ (movement_objectTypeToString(value.type) == "ted" ? "-" : movement_objectPayment_MethodToString(value.payment_method)) +"</td>";
                            toAppend += "<td>R$ "+ (value.amount/100).toFixed(2).toString().replace(',','').replace('.',',') + " - R$ " + (value.fee/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";
                            toAppend += "<td class='text-right'>R$ "+ ((value.amount-value.fee)/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";

                            toAppend += "</tr>";
                            $(nameTable + " tbody").append(toAppend);
                        });
                        $(nameTable + " tfoot").html("");

                        var toAppend = "<tr class=ui-widget-header'>"
                        toAppend += "<td colspan='" + colspan + "' class='text-right ui-widget-header'>Total R$ "+ ((total)/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";
                        toAppend += "</tr>";
                        $(nameTable + " tfoot").append(toAppend);

                        $(".toClick").click(function(obj) {
                            lineClick($(this).attr("data"));
                        });
                    },
                    error: function(){
                        $("body").loading("stop");
                        var nameTable = "#table-extrato";

                        $(nameTable + " tbody").html("");
                        $.dialog({
                            title: 'Erro...',
                            text: 'Erro na chamada dos dados !!!'
                        });
                        return false;
                    }
                });
            break;
            case "available":
                if ($("#end_date").val() == "" && $("#start_date").val() == "") {
                    $.dialog({
                        title: 'Erro...',
                        text: 'Preencha os campos de data!'
                    });
                    return;
                }

                loading("body", null, false);
                $.ajax({
                    url: pagina + '?action=load',
                    type: 'post',
                    data: $('#dados').serialize(),
                    success: function(data) {	
                        var nameTable = "#table-extrato-available";
                        colspan = "10";

                        $("body").loading("stop");
                        $("#table-first").hide();
                        $(nameTable).show();
                        data = $.parseJSON(data);
                        $(nameTable + " tbody").html("");
                        if (data.length == 0)
                            $(nameTable + " tbody").html("<tr><td colspan='" + colspan + "'>Nenhum dado encontrado.</td></tr>");

                        var total = 0;
                        $.each(data, function(key, value) {
                            total += value.amount-value.fee;
                            
                            //console.log(value);
                            var toAppend = "";
                            var aux = (value.fee/100)*100/(value.amount/100);
                            var final = Math.trunc((aux*30)/1.87);


                            toAppend = "<tr style='cursor: pointer;' id='" + value.transaction_id + "' class='toClick trline' data='" + value.transaction_id + "'><td>" + moment(value.date_created).format("DD/MM/YYYY") +"</td>";
                            //toAppend += "<td>"+ moment(value.accrual_date).format("DD/MM/YYYY") +"</td>";
                            toAppend += "<td>"+ (value.type == "ted" || value.type == "refund" ? "-" : moment(value.accrual_date).format("DD/MM/YYYY HH:mm")) +"</td>";
                            toAppend += "<td>"+ (value.type == "ted" || value.type == "refund" ? "-" : (value.payment_method == "debit_card" ? "-" : final )) +"</td>";
                            toAppend += "<td>"+ (value.type == "ted" ? "-" : value.transaction_id) +"</td>";
                            toAppend += "<td>"+ (value.type == "ted" ? "Transferência" : (value.ds_evento == null ? "Bilheteria" : value.ds_evento )) +"</td>";
                            toAppend += "<td>"+ movement_objectTypeToString(value.type) +"</td>";
                            toAppend += "<td>"+ (value.type == "ted" ? "-" : movement_objectPayment_MethodToString(value.payment_method)) +"</td>";
                            toAppend += "<td>R$ "+ (value.amount/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";
                            toAppend += "<td>R$ "+ ((value.fee*-1)/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";
                            toAppend += "<td class='text-right'>R$ "+ ((value.amount-value.fee)/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";

                            toAppend += "</tr>";
                            $(nameTable + " tbody").append(toAppend);
                        });
                        $(nameTable + " tfoot").html("");

                        var toAppend = "<tr class=ui-widget-header'>"
                        toAppend += "<td colspan='" + colspan + "' class='text-right ui-widget-header'>Total R$ "+ ((total)/100).toFixed(2).toString().replace(',','').replace('.',',') +"</td>";
                        toAppend += "</tr>";
                        $(nameTable + " tfoot").append(toAppend);

                        $(".toClick").click(function(obj) {
                            lineClick($(this).attr("data"));
                        });
                    },
                    error: function(){
                        $("body").loading("stop");
                        var nameTable = "#table-extrato-available";
                        $(nameTable + " tbody").html("");
                        $.dialog({
                            title: 'Erro...',
                            text: 'Erro na chamada dos dados !!!'
                        });
                        return false;
                    }
                });
            break;
        }
        
        
    });

    $("#btn-saque").click(function(event){
        event.preventDefault();
        destroySlider();
        dialogSaque.dialog( "open" );
        loadSaldoToSaque();

      if($("#dialog-form-saque").parents(".ui-dialog").children(".ui-dialog-titlebar").children("#iconhelp").length==0) {
        $("#dialog-form-saque").children(".ui-dialog-titlebar").append("<span id='iconhelp' class='ui-icon ui-icon-help'></span>");
        $("#dialog-form-saque").parents(".ui-dialog").children(".ui-dialog-titlebar").append("<span id='iconhelp' title='Ajuda' class='ui-icon ui-icon-help'></span>");
        $("#dialog-form-saque").parents(".ui-dialog").children(".ui-dialog-titlebar").children("#iconhelp").click(function(){
            introSaque();
        });
        $("span:contains('Efetuar Saque')").addClass("btnEfetuarSaque");
      }
    });    

    function loadSaldoToSaque() {
        loading(".ui-dialog:visible");
        $.ajax({
            url: pagina + '?action=taxasaque',
            type: 'post',
            data: $('#dados').serialize(),
            success: function(data) {
                data = $.parseJSON(data);
                console.log(data);
                var minimum = 1;
                var available = data.available;
                // available = 145000;
                if ((available - data.taxa.ted) <=0) {
                    $.dialog({text: 'Valor disponível inferior com a cobrança da taxa.'});
                    dialogSaque.dialog( "close" );
                }
                else {
                    createSliderSaque({
                        minimum: {
                            amount: data.taxa.ted,
                        },
                        maximum: {
                            amount: available,
                        },
                        ted: {
                            amount: data.taxa.ted,
                        }
                    });
                }
                $(".ui-dialog").loading("stop");            
            },
            error: function(){
                $(".ui-dialog").loading("stop");            
                $.dialog({text: 'Erro na chamada dos dados !!!'});
                return false;
            }
        });
    }

    $("#btn-antecipacao").click(function(event){
        event.preventDefault();
        destroySlider();
        dialog.dialog( "open" );

      if($("#dialog-form").parents(".ui-dialog").children(".ui-dialog-titlebar").children("#iconhelp").length==0) {
        $("#dialog-form").children(".ui-dialog-titlebar").append("<span id='iconhelp' class='ui-icon ui-icon-help'></span>");
        
        $("#dialog-form").parents(".ui-dialog").children(".ui-dialog-titlebar").append("<span id='iconhelp' title='Ajuda' class='ui-icon ui-icon-help'></span>");
        $("#dialog-form").parents(".ui-dialog").children(".ui-dialog-titlebar").children("#iconhelp").click(function(){
            introAntecipacao();
        });
        $("span:contains('Efetuar Antecipação')").addClass("btnEfetuarAntecipacao");
        $( "#slider-amountFake" ).slider({
            range: "max",
            min: 0,
            max: 1000,
            step: 0.01,
            value: 0.01,
            slide: function( event, ui ) {                
            }
        });
        $("#slider-amountFake").slider('value',300);
        $("#valorShowFake").val("R$ 300,00");
      }

    });

    $("#btnResumoAntecipacao").click(function(event){
        verificaantecipacao();
    });

    function antecipar() {
        loading(".ui-dialog:visible");
        $.ajax({
            url: pagina + '?action=antecipacao&recebedor='+ recebedor.val(),
            type: 'post',
            data: $('#antecipacao').serialize(),
            success: function(data) {
                $(".ui-dialog").loading("stop");
                data = $.parseJSON(data);
                $.dialog({text: data.msg.split("\n").join("<br />")});
                if(data.status == 'success') {                    
                    dialog.dialog( "close" );
                }
            },
            error: function(data){
                $(".ui-dialog").loading("stop");
                $.dialog({text: data});
                return false;
            }
        });
    }
    function sacar() {
        loading(".ui-dialog:visible");
        $.ajax({
            url: pagina + '?action=sacar&recebedor='+ recebedor.val(),
            type: 'post',
            data: $('#saque').serialize(),
            success: function(data) {
                $(".ui-dialog").loading("stop");
                data = $.parseJSON(data);
                $.dialog({text: data.msg.split("\n").join("<br />")});
                if(data.status == 'success') {                    
                    dialogSaque.dialog( "close" );
                }
                load_saldo();
            },
            error: function(data){
                $(".ui-dialog").loading("stop");
                $.dialog({text: data});
                return false;
            }
        });
    }

    function antecipacaoMaximoMinimo() {
        if ($("#data").val() == "") {
            return;
        }
        loading(".ui-dialog:visible");
        $.ajax({
            url: pagina + '?action=antecipacaomaxmin&recebedor='+ recebedor.val(),
            type: 'post',
            data: $('#antecipacao').serialize(),
            success: function(aux) {
                var obj = $.parseJSON(aux);
                //console.log(obj);
                if (obj.errors && obj.errors.length>0) {
                    $.dialog({text: obj.errors[0].message});
                    $("#data").val("");
                    destroySlider();
                    blockAntecipacao();
                }
                else {
                    createSlider(obj);
                }
                $(".ui-dialog").loading("stop");
            },
            error: function(data){
                $(".ui-dialog").loading("stop");
                destroySlider();
                blockAntecipacao();
                $.dialog({text: data});
                return false;
            }
        });
    }

    function loading(id, message, stoppable) {
        message = message == undefined || message == null ? "Carregando" : message;
        stoppable = stoppable == undefined || stoppable == null ? true : stoppable;
        $(id).loading(
            { 
                theme: 'dark',
                stoppable: stoppable, 
                message: message,
                onStart: function(loading) {
                    loading.overlay.slideDown(400);
                },
                onStop: function(loading) {
                    loading.overlay.slideUp(400);
                }
            });
    }

    function getTransaction(id) {
        var nameTable = "#table-extrato";

        if ($("#status").val() == "available") {
            nameTable = "#table-extrato-available";
        }
        loading(nameTable);
        //$().loading({ stoppable: true, message: "Carregando..." });
        $.ajax({
            url: pagina + '?action=gettransaction&transaction_id='+ id,
            type: 'post',
            data: $('#antecipacao').serialize(),
            success: function(aux) {
                var obj = $.parseJSON(aux);
                var message = "Nome do cliente: " + (obj.customerName == "" ? obj.customerName : obj.card_holder_name);
                message+="<br /><br /><br />";
                message+="<p>Valor total da venda: R$ " + (obj.amount/100).toFixed(2).toString().replace(',','').replace('.',',') + "</p>"; 
                message+="<br /><p>Composição:</p>";
                $.each(obj.split, function( index, value ){
                    var amount = (value.amount/100).toFixed(2).toString().replace(',','').replace('.',','); 
                    var fee = (value.fee/100).toFixed(2).toString().replace(',','').replace('.',','); 
                    var total = ((value.amount-value.fee)/100).toFixed(2).toString().replace(',','').replace('.',','); 
                    var document = "";
                    if (value.documentType == "cnpj") {
                        document = value.documentNumber.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/g,"\$1.\$2.\$3\/\$4\-\$5");
                    }
                    else {
                        document = value.documentNumber.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/g,"\$1.\$2.\$3\-\$4");
                    }
                    message+="<br />"; 
                    message+="<p>" + value.name + " - " + document + "</p>";

                    if (value.documentNumber == "11665394000113") {
                        message+="<p>Valor a receber: R$ " + amount + "</p>";
                    }
                    else {
                        if (value.fee>0) {
                            message+="<p>Valor sem taxas: R$ " + amount + "</p>";
                            message+="<p>Taxas: R$ " + fee + "</p>";
                        }
                        message+="<p>Valor a receber: R$ " + total + "</p>";
                    }
                    // message+="<p>Valor sem taxas: R$ " + amount + "</p>";
                    // message+="<p>Taxas: R$ " + fee + "</p>";
                    // message+="<p>Valor a receber: R$" + total + "</p>";
                });
                message+="<br />";
                //console.log(obj);
                $(nameTable).loading("stop");
                $.dialog({text: message});
            },
            error: function(data){
                $.dialog({text: data});
                $(nameTable).loading("stop");
                
                return false;
            }
        });
    }

    $('#data').on('input',function(e){
        antecipacaoMaximoMinimo();
    });

    function verificaantecipacao() {
        loading(".ui-dialog:visible");
        $.ajax({
            url: pagina + '?action=verificaantecipacao&recebedor='+ recebedor.val(),
            type: 'post',
            data: $('#antecipacao').serialize(),
            success: function(data) {
                data = $.parseJSON(data);

                if (data.errors && data.errors.length>0) {
                    $(".ui-dialog").loading("stop");
                    $("#custoAntecipacaoFull").hide();
                    $("#valorAntecipacaoFull").hide();
                    $.dialog({text: data.errors[0].message});
                }
                else {
                    console.log(data);
                    $(".ui-dialog").loading("stop");
                    var amount = data.amount;
                    var fee = data.fee;
                    var antfee = data.anticipation_fee;
                    var valor = (amount-fee-antfee)/100;

                    $("#custoAntecipacaoFull").show();
                    $("#valorAntecipacaoFull").show();

                    $("#valorAntecipacao").val("R$ " + valor.toFixed(2).toString().replace(',','').replace('.',','));
                    $("#custoAntecipacao").val("R$ " + (antfee/100).toFixed(2).toString().replace(',','').replace('.',','));

                    $("#slider-amount").slider('value',valor.toFixed(2));
                    $("#valor").val(valor.toFixed(2));
                    $("#valorShow").val("R$ " + valor.toFixed(2).toString().replace(',','').replace('.',','));
                    unblockAntecipacao();
                }
            },
            error: function(data){
                $(".ui-dialog").loading("stop");
                $.dialog({text: data});
                return false;
            }
        });
    }

    function blockAntecipacao() {
        $("button > span:contains('Efetuar Antecipação')").parent().hide();
    }
    function unblockAntecipacao() {
        $("button > span:contains('Efetuar Antecipação')").parent().show();
    }
    function destroySlider() {
        if ($( "#slider-amount" ).hasClass("ui-slider"))
            $( "#slider-amount" ).slider( "destroy" );


        blockAntecipacao();
        $("#valorAntecipacaoFull").hide();
        $("#custoAntecipacaoFull").hide();

        $("#fsResumo").hide();
        $("#fsValor").hide();
    }
    function destroySliderSaque() {
        if ($( "#slider-amount-saque" ).hasClass("ui-slider"))
            $( "#slider-amount-saque" ).slider( "destroy" );

        $("#fsValorSaque").hide();
    }
    var sliderHelper = null;
    var sliderHelperSaque = null;
    function createSlider(obj) {
        destroySlider();
        $("#fsResumo").show();
        $("#fsValor").show();
        sliderHelper = obj;
        console.log(obj);

        var minAmount = obj.minimum.amount;
        var maxAmount = obj.maximum.amount;

        if (maxAmount == 0) {
            $.dialog({text: "Não é possivel criar antecipação, por favor verificar se já existem antecipações a serem realizadas."});
            destroySlider();
        }

        if (maxAmount == minAmount && maxAmount!=0) {
            $.dialog({text: "Só existe um valor a ser criado de antecipação."});
        }

        $( "#valor" ).val( (minAmount/100) );
        $( "#valorShow" ).val("R$ " + (minAmount/100).toFixed(2).toString().replace(',','').replace('.',','));

        $( "#slider-amount" ).slider({
            range: "max",
            min: minAmount/100,
            max: maxAmount/100,
            step: 0.01,
            value: 0.01,
            slide: function( event, ui ) {
                $( "#valor" ).val( ui.value );
                $( "#valorShow" ).val("R$ " + ui.value.toFixed(2).toString().replace(',','').replace('.',','));
                $("#custoAntecipacaoFull").hide();
                $("#valorAntecipacaoFull").hide();
                blockAntecipacao();
            }
        });
        $( "#valor" ).val( $( "#slider-amount" ).slider( "value" ) );
    }
    function createSliderSaque(obj) {
        destroySliderSaque();
        $("#fsValorSaque").show();
        sliderHelperSaque = obj;

        var minAmount = obj.minimum.amount;
        var maxAmount = obj.maximum.amount;

        if (maxAmount == 0) {
            $.dialog({text: "Não é possivel realizar um saque, por favor verificar se já existem saques a serem realizados."});
            destroySliderSaque();
            dialogSaque.dialog( "close" );
            return;
        }

        if (maxAmount == minAmount && maxAmount!=0) {
            $.dialog({text: "Só existe um valor para realizar o saque."});
        }

        $( "#valor-saque" ).val( (minAmount/100) );
        $( "#valorShow-saque" ).val("R$ " + (minAmount/100).toFixed(2).toString().replace(',','').replace('.',','));

        var aux = (minAmount/100)-(sliderHelperSaque.ted.amount/100);
        if (aux<0) {
            $("#valorSaque").val("R$ 0,00");    
        }
        else {
            $("#valorSaque").val("R$ " + (minAmount/100).toFixed(2).toString().replace(',','').replace('.',',') + " - R$ " + (sliderHelperSaque.ted.amount/100).toFixed(2).toString().replace(',','').replace('.',',') + " = R$ " + aux.toFixed(2).toString().replace(',','').replace('.',',')) ;
        }

        $( "#slider-amount-saque" ).slider({
            range: "max",
            min: minAmount/100,
            max: maxAmount/100,
            step: 0.01,
            value: 0.01,
            slide: function( event, ui ) {
                $( "#valor-saque" ).val( ui.value );
                $( "#valorShow-saque" ).val("R$ " + ui.value.toFixed(2).toString().replace(',','').replace('.',','));
                var aux = ui.value-(sliderHelperSaque.ted.amount/100);
                if (aux<0) {
                    $("#valorSaque").val("R$ 0,00");    
                }
                else {
                    $("#valorSaque").val("R$ " + ui.value.toFixed(2).toString().replace(',','').replace('.',',') + " - R$ " + (sliderHelperSaque.ted.amount/100).toFixed(2).toString().replace(',','').replace('.',',') + " = R$ " + aux.toFixed(2).toString().replace(',','').replace('.',',')) ;
                }
                
            }
        });
        $( "#valor-saque" ).val( $( "#slider-amount-saque" ).slider( "value" ) );
    }

});
</script>
<h2>Extrato</h2>
<?php

                $query = "SELECT
                   ps.id_permissaosplit
                   ,ps.id_usuario
                   ,ISNULL(ps.id_produtor,0)
                   ,ISNULL(ps.id_recebedor,0)
                   ,ps.dt_criado
                   ,ps.dt_alterado
				   ,ISNULL(p.ds_razao_social, 'Todos') RazaoSocialProdutor
				   ,p.cd_cpf_cnpj DocumentoProdutor
				   ,ISNULL(r.ds_razao_social, 'Todos') RazaoSocialRecebedor
				   ,r.cd_cpf_cnpj DocumentoRecebedor
				   ,u.ds_nome NomeUsuario
				   ,ps.bit_saque
				   ,ps.bit_antecipacao
                  FROM mw_permissao_split ps
				  INNER JOIN mw_usuario u ON ps.id_usuario=u.id_usuario
				  LEFT JOIN mw_produtor p ON ps.id_produtor=p.id_produtor
                  LEFT JOIN mw_recebedor r ON ps.id_recebedor=r.id_recebedor
                  WHERE ps.id_usuario=?";

        $params = array($_SESSION['admin']);
        
        $bit_saque = false;
        $bit_antecipacao = false;
		
		$result = executeSQL($mainConnection, $query, $params);
		$json = array();

        while ($rs = fetchResult($result)) {            
            $bit_saque = $rs["bit_saque"] == null || $rs["bit_saque"] == 0 ? false : true;
            $bit_antecipacao = $rs["bit_antecipacao"] == null || $rs["bit_antecipacao"] == 0 ? false : true;
        }
?>


<?php if ($bit_antecipacao) { ?>
<div id="dialog-form" title="Nova Antecipação">
    <div id="trueForm">
        <p class="validateTips"></p>
        <form id="antecipacao" name="antecipacao" action="?p=extrato" method="POST">
            <fieldset>
                <legend>Como deseja antecipar? </legend>
                <label for="radio-1" style="display:inline"><input type="radio" name="periodo" checked id="periodo-1" class="radio" style="display:inline" value="start"> Do início do saldo</label>
                <label for="radio-2" style="display:inline"><input type="radio" name="periodo" id="periodo-2" class="radio" style="display:inline" value="end">Do final do saldo</label>
            </fieldset>
            <br />
            <fieldset>
                <legend>Quando deseja receber? </legend>
                <input type="text" name="data" id="data" placeholder="ESCOLHA UMA DATA" class="text ui-widget-content ui-corner-all" />
            </fieldset>
            <br />
            <fieldset id="fsValor" style="display:none">
                <legend>Escolha o valor </legend>
                <div id="slider-amount"></div>
                <input type="text" name="valor" style="display:none" readonly id="valor" class="text ui-widget-content ui-corner-all" />
                <input type="text" name="valorShow" readonly id="valorShow" class="text ui-widget-content ui-corner-all" />
            </fieldset>

            <fieldset id="fsResumo" style="display:none">
                <legend>Resumo da antecipação</legend>
                <input type="button" class="button" id="btnResumoAntecipacao" value="Avançar" />&nbsp;
                    <div class="myInput" id="custoAntecipacaoFull" style="display:none">
                        <label for="custoAntecipacao">Custo Antecipação</label>
                        <input type="text" id="custoAntecipacao" readonly placeholder="R$ 0,00">
                    </div>
                <br />
                    <div class="myInput" id="valorAntecipacaoFull" style="display:none">
                        <label for="valorAntecipacao">Valor Antecipação</label>
                        <input type="text" id="valorAntecipacao" readonly placeholder="R$ 0,00">
                    </div>
            </fieldset>
        </form>
    </div>
    <div id="fakeForm" style="display:none">
        <p class="validateTips"></p>
        <form id="antecipacaoFake" name="antecipacaoFake" action="" method="POST">
            <fieldset id="fsComoDesejaAnteciparFake">
                <legend>Como deseja antecipar? </legend>
                <label for="radio-1" style="display:inline"><input type="radio" name="periodoFake" checked id="periodo-1Fake" class="radio" style="display:inline" value="start"> Do início do saldo</label>
                <label for="radio-2" style="display:inline"><input type="radio" name="periodoFake" id="periodo-2Fake" class="radio" style="display:inline" value="end">Do final do saldo</label>
            </fieldset>
            <br />
            <fieldset id="fsQuandoDesejaReceberFake">
                <legend>Quando deseja receber? </legend>
                <input type="text" name="dataFake" id="dataFake" placeholder="25/05/2018" class="text ui-widget-content ui-corner-all" />
            </fieldset>
            <br />
            <fieldset id="fsValorFake">
                <legend>Escolha o valor </legend>
                <div id="slider-amountFake"></div>
                <input type="text" name="valorShowFake" readonly id="valorShowFake" class="text ui-widget-content ui-corner-all" />
            </fieldset>

            <fieldset id="fsResumoFake">
                <legend>Resumo da antecipação</legend>
                <input type="button" class="button" id="btnResumoAntecipacaoFake" value="Avançar" />&nbsp;
                    <div class="myInput" id="custoAntecipacaoFullFake">
                        <label for="custoAntecipacaoFake">Custo Antecipação</label>
                        <input type="text" id="custoAntecipacaoFake" readonly placeholder="R$ 2,00">
                    </div>
                <br />
                    <div class="myInput" id="valorAntecipacaoFullFake">
                        <label for="valorAntecipacaoFake">Valor Antecipação</label>
                        <input type="text" id="valorAntecipacaoFake" readonly placeholder="R$ 300,00">
                    </div>
            </fieldset>
        </form>
    </div>
</div> 
<?php } ?>

<?php if ($bit_saque) { ?>
<div id="dialog-form-saque" title="Saque">
	<p class="validateTips"></p>
	<form id="saque" name="saque" action="?p=extrato" method="POST">
        <fieldset id="fsValorSaque">
            <legend>Escolha o valor </legend>
            <div id="slider-amount-saque"></div>
            <input type="text" name="valor-saque" style="display:none" readonly id="valor-saque" class="text ui-widget-content ui-corner-all" />
            <input type="text" name="valorShow-saque" readonly id="valorShow-saque" class="text ui-widget-content ui-corner-all" />
        </fieldset>
        <fieldset>
            <div class="myInput" id="valoresSaque">
                <label for="valorSaque">Valor a ser sacado menos a taxa para saque: </label>
                <input type="text" id="valorSaque" class="text ui-widget-content ui-corner-all" readonly placeholder="R$ 0,00">
            </div>
        </fieldset>
	</form>
</div>
<?php } ?>

<form id="dados" name="dados" method="post">
    <div class="fields">
        <label>Organizador:</label>
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
        <br>

        <label>Recebedor:</label>
        <select id="recebedor" name="recebedor">
            <option value="-1">Selecione</option>
        </select>

        <label>Evento:</label>
        <select id="evento" name="evento">
            <option value="-1">Escolha um organizador</option>
        </select>

        <label>Status:</label>
        <select id="status" name="status">
            <option value="playables">Saldo a Receber</option>
            <option value="available">Saldo Disponível</option>
            <!--option value="transferred">Saldo transferido</option-->
            <option value="transfers">Transferências</option>
            <?php if ($bit_antecipacao) { ?>
            <option value="antecipations">Antecipações</option>
            <?php } ?>
        </select>
        <span id="spanPeriodo" style="display:none">
            <label>Periodo:</label>
            <input type="text" id="start_date" name="start_date" class="datepicker extratoSearch" />
            <input type="text" id="end_date" name="end_date" class="datepicker extratoSearch" />
        </span>
        <input type="hidden" id="count" name="count" value="1000" />
        <input type="button" class="button" id="btnBuscarExtrato" value="Buscar historico" />&nbsp;
    </div>

    <div class="actions">
        <div class="saldo">
            <div class="disponivel">
                <label>Saldo total disponível</label>
                <span>R$ 0,00</span>
            </div>
            <div class="receber">
                <label>Saldo total a receber</label>
                <span>R$ 0,00</span>
            </div>
        </div> 

        <div class="produtor">
            <?php if ($bit_saque) { ?>
            <input type="button" id="btn-saque" class="button" value="Realizar Saque">
            <?php } ?> 
            <?php if ($bit_antecipacao) { ?>
            <input type="button" id="btn-antecipacao" class="button" value="Criar Antecipação">
            <?php } ?>
        </div>
    </div>
</form>

<table id="table-first" class="ui-widget ui-widget-content">
	<thead>
		<tr class="ui-widget-header">
            <th width="100">Data da venda</th>
            <th width="100">Data de pagamento</th>
            <th width="100">Tipo</th>
            <th width="100">Composição do valor</th>
			<th class="text-right">Valor</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan="5">Nenhum registro no momento.</td>
		</tr>
    </tbody>
    <tfoot>
    </tfoot>
</table>

<table id="table-extrato" class="ui-widget ui-widget-content" style="display:none">
	<thead>
		<tr class="ui-widget-header">
            <th width="100">Data da venda</th>
            <th width="100">ID Transação do Gateway</th>
            <th width="300">Evento</th>
            <th width="100">Data de pagamento</th>
            <th width="100">Entrada/Saída</th>
            <th width="100">Tipo da Transação</th>
            <th width="200">Composição do valor</th>
			<th class="text-right">Valor</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan="8">Nenhum registro no momento.</td>
		</tr>
    </tbody>
    <tfoot>
    </tfoot>
</table>
<table id="table-extrato-available" class="ui-widget ui-widget-content" style="display:none">
	<thead>
		<tr class="ui-widget-header">
            <th width="100">Data da disponibilização</th> <!--date_create-->
            <th width="100">Data da venda</th><!-- accrual_date-->
            <th width="100">Dias antecipados</th>
            <th width="100">ID Transação do Gateway</th>
            <th width="300">Evento</th>
            <th width="50">Entrada / Saída</th>
            <th width="100">Tipo da Transação</th>
            <th width="100">Valor venda Split</th>
            <th width="100">Desconto Tarifa</th>
			<th class="text-right">Valor</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan="10">Nenhum registro no momento.</td>
		</tr>
    </tbody>
    <tfoot>
    </tfoot>
</table>
<table id="table-transfer" class="ui-widget ui-widget-content" style="display:none">
	<thead>
		<tr class="ui-widget-header">
            <th width="100">Data de requisição</th>
            <th width="100">Status</th>
            <th width="100">Tipo</th>
            <th width="100">Solicitante</th>
            <th width="100">Valor</th>
            <th width="100">Taxa</th>
			<th width="100">Dt Estimada da Transf.</th>
			<th width="100">Dt da Transf.</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan="7">Nenhum registro no momento.</td>
		</tr>
    </tbody>
    <tfoot>
    </tfoot>
</table>
<table id="table-antecipavel" class="ui-widget ui-widget-content" style="display:none">
	<thead>
		<tr class="ui-widget-header">
            <th width="100">Data de requisição</th>
            <th width="100">Status</th>
            <th width="100">Valor</th>
            <th width="100">Taxa</th>
			<th width="100">Dt Pagamento</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan="5">Nenhum registro no momento.</td>
		</tr>
    </tbody>
    <tfoot>
    </tfoot>
</table>

<br/>
<?php
	}
}
?>
