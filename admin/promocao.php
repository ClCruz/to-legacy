<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 430, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);

    } else {

        if (isset($_GET['id'])) {

            $rs = executeSQL($mainConnection,
                                "SELECT
                                    PC.DS_PROMOCAO,
                                    PC.PERC_DESCONTO_VR_NORMAL,
                                    PC.VL_PRECO_FIXO,
                                    PC.DS_NOME_SITE,
                                    PC.DS_TIPO_BILHETE,
                                    PC.IMAG1PROMOCAO,
                                    PC.IMAG2PROMOCAO,
                                    PC.DT_INICIO_PROMOCAO,
                                    PC.DT_FIM_PROMOCAO,
                                    PC.CODTIPPROMOCAO,
                                    PC.ID_BASE,
                                    PC.IN_HOT_SITE,
                                    PC.IN_EXIBICAO,
                                    PC.IN_VALOR_SERVICO,
                                    PC.ID_PATROCINADOR,
                                    PC.QT_PROMO_POR_CPF,
                                    (SELECT TOP 1 P.CD_PROMOCIONAL FROM MW_PROMOCAO P WHERE P.ID_PROMOCAO_CONTROLE = PC.ID_PROMOCAO_CONTROLE) AS CD_PROMOCIONAL,
                                    (SELECT COUNT(1) FROM MW_PROMOCAO P WHERE P.ID_PROMOCAO_CONTROLE = PC.ID_PROMOCAO_CONTROLE) AS QTD_CUPONS,
                                    (SELECT COUNT(1) FROM MW_PROMOCAO P WHERE P.ID_PROMOCAO_CONTROLE = PC.ID_PROMOCAO_CONTROLE AND (P.ID_SESSION IS NOT NULL OR P.ID_PEDIDO_VENDA IS NOT NULL)) AS QTD_CUPONS_EM_USO,
                                    CASE 
                                        WHEN PC.ID_BASE IS NOT NULL THEN 'teatro'
                                        WHEN PC.IN_TODOS_EVENTOS = 1 THEN 'geral'
                                        ELSE 'especifico'
                                    END AS ABRANGENCIA,
                                    XY.ID_PROMOCAO_CONTROLE_FILHA,
                                    XY.QT_INGRESSOS
                                FROM MW_PROMOCAO_CONTROLE PC
                                LEFT JOIN MW_PROMOCAO_COMPREXLEVEY XY ON XY.ID_PROMOCAO_CONTROLE_PAI = PC.ID_PROMOCAO_CONTROLE
                                WHERE PC.ID_PROMOCAO_CONTROLE = ?
                                ORDER BY PC.DS_PROMOCAO",
                                array($_GET['id']), true);

            $result_selecionados = executeSQL($mainConnection,
                                                "SELECT CE.ID_EVENTO, E.DS_EVENTO, CE.QT_PROMO_POR_CPF
                                                FROM MW_CONTROLE_EVENTO CE
                                                INNER JOIN MW_EVENTO E ON E.ID_EVENTO = CE.ID_EVENTO
                                                WHERE ID_PROMOCAO_CONTROLE = ?
                                                ORDER BY E.DS_EVENTO",
                                                array($_GET['id']));

            $result_assinaturasSelecionadas = executeSQL($mainConnection,
                                                            "SELECT AP.ID_ASSINATURA
                                                            FROM MW_ASSINATURA_PROMOCAO AP
                                                            INNER JOIN MW_ASSINATURA A ON A.ID_ASSINATURA = AP.ID_ASSINATURA
                                                            WHERE AP.ID_PROMOCAO_CONTROLE = ?",
                                                            array($_GET['id']));

            $assinaturasSelecionadas = array();
            while ($rsAux = fetchResult($result_assinaturasSelecionadas)) {
                $assinaturasSelecionadas[] = $rsAux['ID_ASSINATURA'];
            }

        }
?>
        <link rel="stylesheet" href="../javascripts/uploadify/uploadify.css"/>
        <link rel="stylesheet" href="../stylesheets/chosen.min.css"/>

        <style type="text/css"> div#ui-datepicker-div { z-index: 9999 !important; } </style>

        <script type="text/javascript" src="../javascripts/uploadify/swfobject.js"></script>
        <script type="text/javascript" src="../javascripts/uploadify/jquery.uploadify.v2.1.0.min.js"></script>

        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script type="text/javascript" src="../javascripts/jquery.mask.min.js"></script>

        <script type="text/javascript" src="../javascripts/chosen.jquery.min.js"></script>

        <script type="text/javascript">
            $(function() {
                var pagina = '<?php echo $pagina; ?>',
                    abrangencia = '<?php echo $rs['ABRANGENCIA']; ?>',
                    $cboLocal = $('#cboLocal'),
                    $cboPromo = $('#cboPromo'),
                    $csv = $('[type=file]'),
                    $eventos = $('[name=eventos]');

                $('.button').button();

                $("#loading").dialog({
                    autoOpen: false,
                    modal: true,
                    buttons: {},
                    closeOnEscape: false,
                    open: function(event) { $(".ui-dialog-titlebar-close", $(event.target).parent()).hide(); }
                    //open: function(event) { $(event.target).parent().hide(); }
                });

                $('.numbersOnly.int').onlyNumbers();
                $('.numbersOnly.float').onlyNumbers(true);
                $('.numbersOnly.negative').onlyNumbers(false, true);

                $cboLocal.find('option:first').attr('value', 'TODOS').text('< TODOS >');
                $('[name=ds_bilhete]').mask('MW_AAAAAAAAAAAAAAAAA');

                $('input.datePicker').prop('readonly', true).datepicker({
                    changeMonth: true,
                    changeYear: true
                }).datepicker('option', $.datepicker.regional['pt-BR']);

                $('#dt_inicio').on('change', function() {
                    $("#dt_fim").datepicker("option", "minDate", $(this).val()).trigger('change');
                });

                $cboLocal.on('change', listar_eventos);
                $eventos.on('change', listar_eventos);

                function listar_eventos() {
                    var select_all;

                    if ($('[name=eventos]:checked').val() == 'especificos' ) {
                        select_all = false;
                        $('#dados').addClass('especificos');
                        $('#dados').removeClass('geral');
                    } else {
                        select_all = true;
                        $('#dados').addClass('geral');
                        $('#dados').removeClass('especificos');

                        if ($('[name=id]').val() == '') {
                            $('#selecionados').html('');
                        }
                    }

                    if ($cboLocal.val()) {
                        $('#loading').dialog('open');

                        $.ajax({
                            url: pagina + '?action=getEventos&cboLocal=' + $cboLocal.val()
                        }).done(function(html){
                            var ids_selecionados = [];

                            $('#registros').html(html);

                            $('#selecionados :checkbox').each(function(){
                                ids_selecionados.push(~~($(this).val()));
                            });

                            if (ids_selecionados.length > 0) {
                                $('#registros').find('tr').filter(function(){
                                    return $.inArray(~~($(this).find(':checkbox').val()), ids_selecionados) != -1;
                                }).remove();
                            }

                            if (select_all) {
                                $('#registros :checkbox').prop('checked', true);
                                sendSelected();
                            }

                            $('#loading').dialog('close');
                        });
                    }
                }


                if ($('[name=id]').val() != '') {
                    if ($('[name=eventos]:checked').val() == 'especificos') {
                        $('table.ui-widget').show();
                    }
                    
                    $(":input:not(.datePicker, [name=qt_limite_cpf], [name=limite_cpf\\[\\]], [name=cboExibicao], [name=ds_codigo], [name=qt_codigo], [type=checkbox], [type=hidden], [type=submit]), :input[name=in_hotsite]").prop('disabled', true).prop('readonly', true);

                    if (abrangencia == 'especifico') {
                        $("#cboLocal").prop('disabled', false).prop('readonly', false);
                        $cboLocal.prepend('<option value="" selected="selected">Selecione...</option>');
                    }
                }

                $('.selecionar_todos').on('click', function(){
                    $(this).parents('table').next('div').find('table').find(':input').prop('checked', $(this).prop('checked'));

                    sendSelected();

                    $(this).prop('checked', !$(this).prop('checked'));
                });

                $('#registros, #selecionados').on('click', ':checkbox', sendSelected);

                function sendSelected() {
                    var selecionados = $('#registros :checkbox:checked').parents('tr').remove();
                    var removidos = $('#selecionados :checkbox:not(:checked)').parents('tr').remove();

                    $('#selecionados').append(selecionados)
                        .find('input[name=limite_cpf\\[\\]]').prop('disabled', false).prop('readonly', false);
                    $('#registros').append(removidos)
                        .find('input[name=limite_cpf\\[\\]]').val('').prop('disabled', true).prop('readonly', true);

                    sortColumn($('#selecionados tr td:first'));
                    sortColumn($('#registros tr td:first'));
                }

                $('table.ui-widget').on('mouseenter mouseleave', 'tr:not(.ui-widget-header)', function() {
                    $(this).toggleClass('ui-state-hover');
                });

                $cboPromo.on('change', function(){
                    var mostrar;
                    $('.ui-state-error').removeClass('ui-state-error');

                    $('[class*=promo_]').hide();
                    if ($('[name=id]').val() == '') {
                        $('[class*=promo_]').find(':input:not([type=hidden])').val('');
                    }

                    switch ($cboPromo.val()) {
                        // Código Fixo
                        case '1':
                            mostrar = 'fixo';
                        break;
                        // Código Aleatório
                        case '2':
                            mostrar = 'aleatorio';
                        break;
                        // Código de Arquivo CSV
                        case '3':
                            mostrar = 'csv';

                            $('a.button.importar span').text('Importar Arq. CSV');
                        break;
                        // BINs CSV
                        case '4': case '7':
                            mostrar = 'bin';

                            $('a.button.importar span').text('Importar Arq. BIN - CSV');
                        break;
                        // Convite
                        case '5':
                            mostrar = 'convite';

                            $('[name=ds_codigo]').val('CONVITE');
                        break;
                        // Assinatura
                        case '8': case '9':
                            mostrar = 'assinatura';
                        break;
                        // Compre X Leve Y
                        case '10':
                            mostrar = 'xy';
                        break;
                    }

                    $('[class*=promo_'+mostrar+'], .promo_geral').show();

                    if (mostrar == 'csv' || mostrar == 'bin' || mostrar == 'xy') {
                        if ($('[name=diretorio_temp]').val() == '') {
                            $.get(pagina, {
                                'action': 'diretorio_temp'
                            }, function(data) {
                                var erro = $.getUrlVar('erro', data),
                                    diretorio_temp = $.getUrlVar('diretorio_temp', data);

                                if (erro) {
                                    $.dialog({text: erro});
                                } else {
                                    $('[name=diretorio_temp]').val((diretorio_temp.split('/'))[diretorio_temp.split('/').length-1]);

                                    $('#csv').uploadify({
                                        uploader: '../javascripts/uploadify/uploadify.swf',
                                        checkScript: '../javascripts/uploadify/check.php',
                                        script: '../javascripts/uploadify/uploadify.php',
                                        cancelImg: '../javascripts/uploadify/cancel.png',
                                        auto: true,
                                        multi: true,
                                        folder: diretorio_temp,
                                        fileDesc: 'Apenas CSV',
                                        fileExt: '*.csv;',
                                        queueID:'uploadifyQueue',
                                        width: 300,
                                        onComplete: function(event, queueID, fileObj, response, data) {
                                            if (response.substr(0, 4) == 'true') {
                                                var byteSize = Math.round(fileObj.size / 1024 * 100) * .01;
                                                var suffix = 'KB';
                                                if (byteSize > 1000) {
                                                    byteSize = Math.round(byteSize *.001 * 100) * .01;
                                                    suffix = 'MB';
                                                }
                                                var sizeParts = byteSize.toString().split('.');
                                                if (sizeParts.length > 1) {
                                                    byteSize = sizeParts[0] + '.' + sizeParts[1].substr(0,2);
                                                } else {
                                                    byteSize = sizeParts[0];
                                                }

                                                $('#uploadifyQueue').find('.uploadifyQueueItem .fileName')
                                                    .filter(function(){return $(this).text() == fileObj.name+' ('+byteSize + suffix+')'})
                                                    .first().parents('.uploadifyQueueItem').remove()

                                                $('#uploadifyQueue').append('<div class="uploadifyQueueItem"><span class="fileName">'+fileObj.name+' ('+byteSize + suffix+')</span></div>');
                                            } else {
                                                $.dialog({
                                                    text: response
                                                });
                                            }
                                        }
                                    });
                                }   
                            });
                        }
                    }

                    if (mostrar == 'bin') {
                        $('option[value=TODOS]').prop('disabled', true);

                        if ($('option[value=TODOS]').is(':selected'))
                            $('option[value=TODOS]').next().prop('selected', true);
                    } else {
                        $('option[value=TODOS]').prop('disabled', false);
                    }

                    if (mostrar == 'assinatura') {
                        if ($cboPromo.val() == 9) {
                            $('#cboAssinatura').prop('multiple', true)
                                .find('option[value=""]').text('').end()
                                .chosen();
                        } else {
                            if ($('[name=id]').val() != '') {
                                $('#cboAssinatura').prop('multiple', false);
                            } else {
                                $('#cboAssinatura').prop('multiple', false).chosen("destroy")
                                    .find('option[value=""]').remove().end().find('option:first').before('<option value="" selected>Selecione...</option>');
                            }
                        }
                    }

                    if ($cboPromo.val() != '') listar_eventos();
                }).trigger('change');

                $csv.on('change', function(e){
                    // nao deixar upload maior que 5mb passar
                    if (parseFloat(((e.currentTarget.files[0].size/1024)/1024).toFixed(4)) > 5) {
                        $('[type=file]').val('');
                        $.dialog({text: "Não é possível importar arquivos maiores que 5MB; Caso o arquivo a ser importado seja maior que este tamanho, divida-o em arquivos menores para efetuar a importação."});
                        return false;
                    }

                    $('#loading').dialog('open');

                    $.ajax({
                        url: pagina+'?action=saveFile',
                        type: 'post',
                        data: new FormData($('#dados')[0]),
                        cache: false,
                        contentType: false,
                        processData: false
                    }).done(function(data){
                        var erro = $.getUrlVar('erro', data),
                            diretorio_temp = $.getUrlVar('diretorio_temp', data);
                        
                        if (erro) {
                            $.dialog({text: erro});
                        } else {
                            if (diretorio_temp) {
                                $('[name=diretorio_temp]').val(diretorio_temp);
                            }

                            $.dialog({text: 'Upload finalizado.<br/><br/>Selecione mais arquivos ou finalize o processe para iniciar a importação.'});

                            atualizarArquivos();
                        }

                        $csv.val('');

                        $('#loading').dialog('close');
                    });
                });

                $('#dados').on('submit', function(e){
                    e.preventDefault();

                    if (!validacao()) return false;

                    $('#loading').dialog('open');

                    $.ajax({
                        url: pagina+'?action=save',
                        type: 'post',
                        data: $(this).serialize()
                    }).done(function(data){
                        var erro = $.getUrlVar('erro', data),
                            id = $.getUrlVar('id', data),
                            msg = $.getUrlVar('msg', data);

                        $('#loading').dialog('close');

                        if (erro) {
                            $.dialog({text: erro});
                        } else {
                            $.dialog({text: msg,
                                uiOptions: {
                                    buttons: {
                                        'Ok': function() {
                                            document.location = './?p=promocao&id='+id;
                                        },
                                        'Voltar': function() {
                                            document.location = './?p=promocoes';
                                        },
                                        'Exibir Códigos Gerados': function(){
                                            if ($.inArray($cboPromo.val(), ['4', '7']) != -1 /*BINs*/) {
                                                document.location = './?p=cartaoPatrocinado&idPatrocinador='+$('#cboPatrocinador').val();
                                            } else {
                                                document.location = './?p=codigosPromocionais&id='+id;
                                            }
                                        }
                                    },
                                    open: function(event) { $(".ui-dialog-titlebar-close", $(event.target).parent()).hide(); }
                                }
                            });
                        }
                    });
                });

                $('a.ui-icon-clipboard').on('click', function(){
                    $.dialog({
                        text: 'Copiar o limite por CPF para...',
                        uiOptions: {
                            buttons: {
                                'Todos os eventos': function() {
                                    $('#selecionados input[name=limite_cpf\\[\\]]').val($('input[name=qt_limite_cpf]').val());
                                    $(this).dialog('close');
                                },
                                'Apenas eventos sem limite por CPF definido': function() {
                                    $('#selecionados input[name=limite_cpf\\[\\]]').filter(function(){return $(this).val() == '';}).val($('input[name=qt_limite_cpf]').val());
                                    $(this).dialog('close');
                                },
                                'Cancelar': function() {
                                    $(this).dialog('close');
                                }
                            },
                            open: function(event) {
                                $(".ui-dialog-titlebar-close, .ui-dialog-buttonset button:first", $(event.target).parent()).hide();
                            }
                        }
                    });
                });

                function validacao() {
                    var valido = true,
                        campos;

                    $cboPromo.parent().removeClass('ui-state-error');

                    switch ($cboPromo.val().toString()) {
                        // Código Fixo
                        case '1':
                            campos = $('#dados :input:not(button, [type=file], [type=hidden], [type=radio], [name=limite_cpf\\[\\]], .chosen-container *, #cboAssinatura, [name=cboPatrocinador], [name=promoMonitorada], [name=qt_ingressos])');
                        break;
                        // Código Aleatório
                        case '2':
                            campos = $('#dados :input:not(button, [type=file], [type=hidden], [type=radio], [name=limite_cpf\\[\\]], .chosen-container *, #cboAssinatura, [name=cboPatrocinador], [name=ds_codigo], [name=promoMonitorada], [name=qt_ingressos])');
                        break;
                        // Código de Arquivo CSV
                        case '3':
                            campos = $('#dados :input:not(button, [type=file], [type=hidden], [type=radio], [name=limite_cpf\\[\\]], .chosen-container *, #cboAssinatura, [name=cboPatrocinador], [name=ds_codigo], [name=qt_codigo], [name=promoMonitorada], [name=qt_ingressos])');
                        break;
                        // BINs CSV
                        case '4': case '7':
                            campos = $('#dados :input:not(button, [type=file], [type=hidden], [type=radio], [name=limite_cpf\\[\\]], .chosen-container *, #cboAssinatura, [name=ds_codigo], [name=qt_codigo], [name=promoMonitorada], [name=qt_ingressos])');
                        break;
                        // Convite
                        case '5':
                            campos = $('#dados :input:not(button, [type=file], [type=hidden], [type=radio], [name=limite_cpf\\[\\]], .chosen-container *, #cboAssinatura, [name=cboPatrocinador], [name=ds_codigo], [name=qt_codigo], [name=ds_img1], [name=ds_img2], [name=promoMonitorada], [name=qt_ingressos])');
                        break;
                        // Assinatura
                        case '8': case '9':
                            campos = $('#dados :input:not(button, [type=file], [type=hidden], [type=radio], [name=limite_cpf\\[\\]], .chosen-container *, [name=cboPatrocinador], [name=ds_codigo], [name=qt_codigo], [name=ds_img1], [name=ds_img2], [name=promoMonitorada], [name=qt_ingressos])');
                        break;
                        // Compre X Leve Y
                        case '10':
                            campos = $('#dados :input:not(button, [type=file], [type=hidden], [type=radio], [name=limite_cpf\\[\\]], .chosen-container *, [name=cboPatrocinador], #cboAssinatura, [name=ds_codigo], [name=qt_codigo])');
                        break;
                        // inválido
                        default:
                            $cboPromo.parent().addClass('ui-state-error');
                            return false;
                        break;
                    }

                    if ($('[name=id]').val() != '') {
                        campos = campos.find(':input:not([name=qt_codigo])');
                    }

                    $.each(campos, function() {
                        var $this = $(this);

                        if ($this.is('.numbersOnly')) {
                    
                            if (!$.isNumeric($this.val().replace('.', '').replace(',', '.'))) {
                                $this.parent().addClass('ui-state-error');
                                valido = false;
                                console.log($this);
                            } else {
                                $this.parent().removeClass('ui-state-error');
                            }

                        } else {
                    
                            if ($this.val() == '' || $this.val() == null) {
                                $this.parent().addClass('ui-state-error');
                                valido = false;
                                console.log($this);
                            } else {
                                $this.parent().removeClass('ui-state-error');
                            }

                        }
                    });

                    if ($('[name=ds_bilhete]').val().length < 4) {
                        $('[name=ds_bilhete]').parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        $('[name=ds_bilhete]').parent().removeClass('ui-state-error');
                    }

                    return valido;
                }
            });
        </script>
        <style type="text/css">
        .ui-widget .chk_evento {
            width: 150px;
            text-align: right;
        }
        .ui-widget .limite_cpf {
            width: 100px;
        }
        td.limite_cpf input {
            width: 80px;
            text-align: right;
        }

        div.disponiveis, div.selecionados {
            width: 100%;
            max-height: 300px;
            overflow-y: scroll;
        }
        .disponiveis .limite_cpf,
        #dados.geral .disponiveis,
        #dados.geral .ui-widget .chk_evento {
            display: none;
        }
        #cboAssinatura {
            width: 100%;
        }
        </style>
        <div title="Processando..." id="loading">
            Aguarde, este processamento poderá levar alguns minutos. Não saia da tela até a
            finalização do processamento.
        </div>
        <h2><?php echo $_GET['id'] ? 'Editar' : 'Nova'; ?> Promoção</h2>
        <form id="dados" name="dados" method="post">
            <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>" />
            <table>
                <tr>
                    <td>
                        <b>Descrição da Promoção:</b><br/>
                        <input type="text" name="ds_promo" size="60" maxlength="60" value="<?php echo utf8_encode2($rs['DS_PROMOCAO']); ?>" />
                    </td>
                    <td>
                        <b>% Desconto:</b><br/>
                        <input type="text" name="vl_desconto" class="numbersOnly float" value="<?php echo number_format($rs['PERC_DESCONTO_VR_NORMAL'], 2, ',', ''); ?>" />
                    </td>
                    <td>
                        <b>Valor Fixo:</b><br/>
                        <input type="text" name="vl_fixo" class="numbersOnly float" value="<?php echo number_format($rs['VL_PRECO_FIXO'], 2, ',', ''); ?>" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Descrição do Bilhete:</b><br/>
                        <input type="text" name="ds_bilhete" size="30" maxlength="20" value="<?php echo $rs['DS_TIPO_BILHETE'] ? $rs['DS_TIPO_BILHETE'] : 'MW_'; ?>" />
                    </td>
                    <td>
                        <b>Descrição do Bilhete para o Site:</b><br/>
                        <input type="text" name="ds_site" size="30" maxlength="20" value="<?php echo utf8_encode2($rs['DS_NOME_SITE']); ?>" />
                    </td>
                    <td>
                        <input type="checkbox" name="in_hotsite" <?php echo $rs['IN_HOT_SITE'] ? 'checked' : ''; ?> />
                        <b>Bilhete de Hotsite</b>
                        <br/>
                        <input type="checkbox" name="in_servico" <?php echo $rs['IN_VALOR_SERVICO'] ? 'checked' : ''; ?> />
                        <b>Cobrar Valor de Serviço</b>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Nome da Imagem Promocional 1:</b><br/>
                        <input type="text" name="ds_img1" size="50" maxlength="100" value="<?php echo $rs['IMAG1PROMOCAO']; ?>" />
                    </td>
                    <td>
                        <b>Nome da Imagem Promocional 2:</b><br/>
                        <input type="text" name="ds_img2" size="50" maxlength="100" value="<?php echo $rs['IMAG2PROMOCAO']; ?>" />
                    </td>
                    <td>
                        <b>Exibição:</b><br/>
                        <?php echo comboExibicaoPromocao('cboExibicao', $rs['IN_EXIBICAO']);?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Data Início:</b><br/>
                        <input type="text" name="dt_inicio" id="dt_inicio" class="datePicker" value="<?php echo $rs['DT_INICIO_PROMOCAO'] ? $rs['DT_INICIO_PROMOCAO']->format('d/m/Y') : ''; ?>" />
                    </td>
                    <td>
                        <b>Data Fim:</b><br/>
                        <input type="text" name="dt_fim" id="dt_fim" class="datePicker" value="<?php echo $rs['DT_FIM_PROMOCAO'] ? $rs['DT_FIM_PROMOCAO']->format('d/m/Y') : ''; ?>" />
                    </td>
                    <td>
                        <b>Limite por CPF:</b><br/>
                        <input type="text" name="qt_limite_cpf" class="numbersOnly int" maxlength="2" value="<?php echo $rs['QT_PROMO_POR_CPF']; ?>" />
                        <a class="button ui-icon ui-icon-clipboard" href="#"></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Tipo da Promoção:</b><br/>
                        <?php echo comboTipoPromocao('cboPromo', $rs['CODTIPPROMOCAO']);?>
                    </td>
                    <td>
                        <div class="promo_fixo promo_convite">
                            <b>Código Fixo:</b><br/>
                            <input type="text" name="ds_codigo" size="30" maxlength="30" value="<?php echo utf8_encode2($rs['CD_PROMOCIONAL']); ?>" />
                            <a class="promo_convite button ui-icon ui-icon-help" href="#"
                                title="Quando o código fixo for igual à 'CONVITE' o cliente não terá que efetuar a validação no momento da compra. Qualquer outro código fixo será necessário a digitação no momento da compra."></a>
                        </div>
                        <div class="promo_csv promo_bin promo_xy">
                            <input type="hidden" name="diretorio_temp" value="" />
                            
                            <div style="width:300px; height:16px;">
                                <div style="width:300px; height:16px; position:absolute; top:auto; z-index:1;"><a class="button importar" href="#">Importar Arq. CSV</a></div>
                                <div style="width:300px; height:16px; position:absolute; top:auto; z-index:100; opacity:0; filter:Alpha(Opacity=0);"><input style="width:300px;" type="file" name="csv" id="csv" /></div>
                            </div>
                            <br/>

                            <div id="uploadifyQueue" class="uploadifyQueue"></div>
                            <div id="arquivos"></div>
                        </div>
                        <div class="promo_assinatura">
                            <b>Assinatura:</b><br/>
                            <?php echo comboAssinatura('cboAssinatura[]', $assinaturasSelecionadas, true); ?>
                        </div>
                    </td>
                    <td>
                        <div class="promo_fixo promo_aleatorio promo_convite">
                            <b>Qtde. de Códigos para Gerar:</b><br/>
                            <input type="text" name="qt_codigo" class="numbersOnly negative" value="" /><br/>
                            <?php if ($_GET['id']) { ?>
                            Em uso: <?php echo $rs['QTD_CUPONS_EM_USO']; ?> / <?php echo $rs['QTD_CUPONS']; ?>
                            <?php } ?>
                        </div>
                        <div class="promo_bin">
                            <b>Patrocinador:</b><br/>
                            <?php echo comboPatrocinador('cboPatrocinador', $rs['ID_PATROCINADOR']); ?>
                        </div>
                        <div class="promo_xy">
                            <b>Promoção Monitorada:</b><br/>
                            <?php echo comboPromocoes('promoMonitorada', $rs['ID_PROMOCAO_CONTROLE_FILHA']); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Local:</b><br/>
                        <?php echo comboTeatroPorUsuario('cboLocal', $_SESSION['admin'], $rs['ID_BASE']); ?>
                    </td>
                    <td>
                        <b>Evento:</b><br/>
                        <div id="radio">
                            <input type="radio" id="radio1" name="eventos" value="todos" checked="checked"><label for="radio1">Todos</label>
                            <input type="radio" id="radio2" name="eventos" value="especificos" <?php echo $rs['ABRANGENCIA'] == 'especifico' ? 'checked' : ''; ?>><label for="radio2">Específicos</label>
                        </div>
                    </td>
                    <td>
                        <div class="promo_xy">
                            <b>Quantidade mínima de ingressos da promoção monitorada:</b><br/>
                            <input type="text" name="qt_ingressos" class="numbersOnly int" size="10" maxlength="2" value="<?php echo $rs['QT_INGRESSOS']; ?>" />
                        </div>
                    </td>
                </tr>
            </table>
            <br/>

            <table class="ui-widget ui-widget-content disponiveis">
                <thead>
                    <tr class="ui-widget-header">
                        <th align="left">Eventos Disponíveis / Fora da Promoção</th>
                        <th class="chk_evento"><label>Selecionar Todos <input type="checkbox" class="selecionar_todos" /></label></th>
                    </tr>
                </thead>
            </table>
            <div class="disponiveis">
                <table class="ui-widget ui-widget-content">
                    <tbody id="registros"></tbody>
                </table>
            </div>
            <br/>

            <table class="ui-widget ui-widget-content selecionados">
                <thead>
                    <tr class="ui-widget-header">
                        <th align="left">Eventos Selecionados</th>
                        <th class="limite_cpf" align="left">Limite por CPF</th>
                        <th class="chk_evento"><label>Selecionar Todos <input type="checkbox" class="selecionar_todos" checked="true" /></label></th>
                    </tr>
                </thead>
            </table>
            <div class="selecionados">
                <table class="ui-widget ui-widget-content promo_geral">
                    <tbody id="selecionados">
                        <?php
                            $eventos_atuais = array();
                            while ($rs = fetchResult($result_selecionados)) {
                                $eventos_atuais[] = $rs['ID_EVENTO'];
                        ?>
                        <tr>
                            <td><?php echo utf8_encode2($rs['DS_EVENTO']); ?></td>
                            <td class="limite_cpf"><input type="text" name="limite_cpf[]" value="<?php echo $rs['QT_PROMO_POR_CPF']; ?>" /></td>
                            <td class="chk_evento"><input type="checkbox" name="evento[]" value="<?php echo $rs['ID_EVENTO']; ?>" checked="true" /></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <br/>

            <input type="hidden" name="eventos_atuais" value="<?php echo implode(' ', $eventos_atuais); ?>" />

            <input type="submit" class="button" value="Salvar" />
        </form>
<?php
    }
}
?>