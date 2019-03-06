<?php
require_once('../settings/functions.php');
require_once('../settings/Log.class.php');

$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 382, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {
        require('actions/' . $pagina);
    } else {
?>
        <style type="text/css">
            .coluna-header{width: 20%;}
            .tb-form{margin-left: 40px; width: 100% !important; padding: 0px;}
            form select{min-width: 200px;}
            input{padding-left: 4px;}
            table.ui-widget tbody tr td input {width: 100%;}
            #center{width: 1124px !important;
                    margin-right: -562px;
                    float: left;
                    text-align: center !important;
                    display: table;
            }
            #content{float: left;}
            div.tooltip{
              width:248px;
              border:1px solid #B2B2B2;
              margin:100px auto;
              overflow:hidden;
            }

            div.tooltip table{
              margin:10px;
              float:left;
              border-collapse:collapse;
              border:1px solid #B2B2B2;
            }

            div.tooltip table tr{
              margin:0;
              padding:0;
              border:0;
            }

            div.tooltip table td{
              width:55px;
                    height:52px;
              padding:3px 0 0;
              margin:0;
                    line-height:15px;
                    text-align:center;
                    font-size:18px;
                    text-transform:uppercase;
              vertical-align:middle;
              border:0;
            }

            div.tooltip div.informacoes{
              float:left;
              width:163px;
              margin:10px 0;
              text-align:left;
            }

            div.tooltip div.informacoes p.local{
              float:left;
              font-size:21px;
            }

            div.tooltip div.informacoes p.descricao{
              float:left;
              font-size:11px;
              margin-bottom:10px;
            }

            div.tooltip div.informacoes a.botao{
              margin:0;
            }
        </style>

        <!-- Extraido do mapaPlateia -->
        <link rel="stylesheet" href="../stylesheets/annotations.css"/>
        <link rel="stylesheet" href="../stylesheets/ajustes.css"/>
        <link rel="stylesheet" href="../stylesheets/plateiaEdicao.css"/>
        <link rel="stylesheet" href="../stylesheets/ajustes2.css"/>

        <script src="../javascripts/modernizr.js" type="text/javascript"></script>
        <script type="text/javascript" src="../javascripts/jquery.annotate.js"></script>
        <!-- / Extraido do mapaPlateia -->

        <script type="text/javascript">
            var pagina = '<?php echo $pagina; ?>';

            $(document).ajaxStart(function () {
                $("#dados").find(':input:not(:disabled)').prop('disabled',true);
                $.busyCursor();
                $("#loadingIcon").fadeIn('fast');
            });

            $(document).ajaxComplete(function () {
                $("#dados").find(':input:disabled').prop('disabled',false);
                $('#loadingIcon').fadeOut('slow');
            });

            $(document).ready(function(){
                $('#exibir').button();
                $('#bloquear').button();
                $('#ano').onlyNumbers();

                var opennedClass = 'open',
                standbyClass = 'standby',
                closedClass = 'closed',
                $mapa_de_plateia = $('#mapa_de_plateia');

                defaultImage = '../images/palco.png',
                uploadPath = '../images/uploads/';

                $("#exibir").click(function(){
                    if(validar()){
                        loadAnnotations('');
                        refreshCadeiras();
                    }
                });

                $("#bloquear").click(function(){
                    $.ajax({
                        url: pagina + '?action=bloquear',
                        data: 'apresentacao='+$('#setor').val() +'&pacote='+$('#pacote_combo').val()+'&ano='+$('#ano').val()+'&local='+$('#local').val(),
                        type: 'post',
                        success: function(data) {
                            if (data.substr(0, 4) != 'true') {
                                if (data.indexOf('?') != -1 && data.length != data.indexOf('?') + 1) {
                                    $.dialog({
                                        title: 'Aviso...',
                                        text: data.split('?')[1]
                                    });

                                    var ids = data.split('?');
                                    ids = ids[0].split('|');

                                    for (i = 0; i < ids.length; i++) {
                                        var $this = $('#' + ids[i]);
                                        statusCadeira($this, 'C');
                                    }
                                } else {
                                    tratarResposta(data);
                                }
                            } else {
                                var ids = data.split('?');
                                ids = ids[1].split('|');

                                for (i = 0; i < ids.length; i++) {
                                    var $this = $('#' + ids[i]);
                                    statusCadeira($this);
                                }
                            }
                            refreshCadeiras(false);
                        }
                    });
                });

                $('#setor').on('change', function(){
                    $("#exibir").click();                    
                });

                $('#local').on('change', function(){
                    $("#mapa_de_plateia").html("<img src='../images/palco.png' width='630' height='500' alt=''>");
                    $.ajax({
                        url: pagina + '?action=load_pacotes',
                        type: 'post',
                        data: $('#dados').serialize(),
                        success: function(data) {
                            $('#container_pacotes').html(data);
                        }
                    });
                }).trigger('change');

                $('#container_pacotes').delegate('select', 'change', function(){
                    $("#mapa_de_plateia").html("<img src='../images/palco.png' width='630' height='500' alt=''>");
                });

                $('#ano').on('change', function(){
                    $("#mapa_de_plateia").html("<img src='../images/palco.png' width='630' height='500' alt=''>");
                    $.ajax({
                        url: pagina + '?action=load_setor',
                        type: 'post',
                        data: $('#dados').serialize()+'&pacote='+$('#pacote_combo').val(),
                        success: function(data) {
                            $('#setor').html(data);
                        }
                    });
                });

                function loadAnnotations(dados) {
                    if ($('#local').val() != '' && $('#setor').val() != '') {
                        dados = 'ano='+ $('#ano').val() +'&teatro='+$('#local').val()+'&sala='+$('#setor').val()+'&xmargin=0.1&ymargin=0.1' + dados;

                        size = 10;

                        $('#loadingIcon').fadeIn('fast');
                        $.ajax({
                            url: pagina + '?action=load',
                            type: 'post',
                            data: dados,
                            success: function(data) {
                                data = data.split('||');
                                $('#mapa_de_plateia').removeAnnotations();

                                changeScale(data[2], data[3]);
                                size = data[4];

                                $('#mapa_de_plateia').addAnnotations(annotation, eval(data[0]));
                                $('#mapa_de_plateia span').tooltip({
                                    track: true,
                                    content: function() {
                                        var element = $(this),
                                        text = element.attr("title");
                                        var img = element.data('img');
                                        if (img != "undefined") {
                                            text += "<br /><br /><img src='"+element.data('img')+"' class='foto-plateia' />";
                                        }

                                        return text;
                                    }
                                });

                                if (data[1].length > 0) {
                                    changeImage(uploadPath + data[1]);
                                } else {
                                    changeImage(defaultImage);
                                }
                            },
                            complete: function() {
                                changeSize(size);
                                $('#loadingIcon').fadeOut('slow');
                            }
                        });
                    }
                }

                function annotation(obj) {
                    return $(document.createElement('span'))
                    .attr('id', obj.id)
                    .addClass('annotation')
                    .addClass('diametro')
                    .addClass((obj.status == 'O') ? opennedClass : (obj.status == 'C') ? closedClass : standbyClass);
                }

                function refreshCadeiras(refreshTime) {
                    dados = 'ano='+ $('#ano').val() +'&teatro='+$('#local').val()+'&sala='+$('#setor').val()+'&xmargin=0.1&ymargin=0.1';
                    $.ajax({
                        url: pagina + '?action=load',
                        type: 'post',
                        data: dados,
                        success: function(data) {
                            annotations = eval(data);
                            $mapa_de_plateia.removeAnnotations();
                            $mapa_de_plateia.addAnnotations(annotation, annotations);

                            // ingressos esgotados?
                            if ($(annotations).filter(function(){return this.status !== 'C'}).length == 0) {
                                $.dialog({text:'Não há lugares disponíveis no momento para este setor.'});
                            }

                            if (Modernizr.touch) {
                                setup_with_touch();
                            } else {
                                $mapa_de_plateia.find('span').tooltip({
                                    track: true,
                                    fade: 250,
                                    content: function() {
                                        var dados = $(this).attr('title').split(' // ');
                                        var img = ($(this).data('img') != "undefined") ? $(this).data('img') : '';

                                        return '<div class="tooltip">'+
                                            '<table><tbody><tr><td>'+dados[0]+'</td></tr></tbody></table>'+
                                            '<div class="informacoes">'+
                                            '<p class="local">'+dados[1]+'</p>'+
                                            '<p class="descricao">clique apenas uma vez e aguarde<br>a reserva do lugar escolhido</p>'+
                                            '</div>'+
                                            '<span>Visão aproximada do palco</span><img src="'+img+'" class="foto-plateia">'+
                                            '</div>';
                                    }
                                });
                                setup_without_touch();
                            }
                        }
                    });

                    if (refreshTime == undefined) {
                        setTimeout(refreshCadeiras, 300000);
                    }
                }

                function setup_with_touch() {
                    $('#mapa_de_plateia span:not(.' + closedClass + ')').off('click')
                    .on('click', function(e) {
                        var $this = $(this),
                        id = 'ttip_'+$this.attr('id'),
                        title,
                        $ttip = $('#'+id);

                        if ($ttip.length == 0) {
                            title = $this.attr('title').split(' // ');
                            $('<div class="tooltip hidden" id="'+id+'">'+
                                '<table><tbody><tr><td>'+title[0]+'</td></tr></tbody></table>'+
                                '<div class="informacoes">'+
                                '<p class="local">'+title[1]+'</p>'+
                                '<p class="descricao">clique em selecionar para<br>reservar o lugar escolhido</p>'+
                                '<a href="#" class="botao selecionar_tooltip"></a>'+
                                '</div>'+
                                '</div>').appendTo('body');
                            $ttip = $('#'+id);
                        }

                        $ttip.css({position:"absolute", left:e.pageX-10,top:e.pageY-10}).fadeIn()
                        .find('.selecionar_tooltip').off('click').on('click', function(e2){
                            e2.preventDefault();
                            span_click(e);
                        });
                        $this.off('mouseleave').on('mouseleave', function(){
                            $ttip.fadeOut();
                        });
                    });
                }

                function setup_without_touch() {
                    $('#mapa_de_plateia span:not(.' + closedClass + ')').off('mouseenter mouseleave')
                    .on('mouseenter mouseleave', function() {
                        if (!$(this).hasClass('annotationHover') && !$(this).hasClass('annotationSelected')) {
                            $(this).addClass('annotationHover');
                        } else {
                            $(this).removeClass('annotationHover');
                        }
                    });
                    $('#mapa_de_plateia span:not(.' + closedClass + ')').off('click').on('click', span_click);
                }

                function span_click(e) {
                    var $this = $(e.target),
                    objSerialized = '',
                    action = ($this.hasClass(standbyClass)) ? 'delete' : 'add';

                    $.each($this.data(), function(key, val) {
                        var exceptions = 'tooltip events handle x y status';
                        if (exceptions.indexOf(key) == -1) {
                            objSerialized += key + '=' + escape(val) + '&';
                        }
                    });

                    $.ajax({
                        url: pagina + '?action=' + action,
                        data: objSerialized + $.serializeUrlVars() + '&apresentacao='+$('#setor').val(),
                        type: 'post',
                        success: function(data) {
                            if (data.substr(0, 4) != 'true') {
                                if (data.indexOf('?') != -1 && data.length != data.indexOf('?') + 1) {
                                    $.dialog({
                                        title: 'Aviso...',
                                        text: data.split('?')[1]
                                    });

                                    var ids = data.split('?');
                                    ids = ids[0].split('|');

                                    for (i = 0; i < ids.length; i++) {
                                        var $this = $('#' + ids[i]);
                                        statusCadeira($this, 'C');
                                    }
                                } else {        
                                    tratarResposta(data);
                                }
                            } else {
                                var ids = data.split('?');
                                ids = ids[1].split('|');

                                for (i = 0; i < ids.length; i++) {
                                    var $this = $('#' + ids[i]);
                                    statusCadeira($this);
                                }
                            }
                        }
                    });
                }

                function statusCadeira(indice, status) {
                    if (status != undefined) {
                        indice.data('status', status);
                    } else {
                        (indice.data('status') == 'O') ? indice.data('status', 'S') : indice.data('status', 'O');
                    }

                    indice
                    .removeClass(opennedClass)
                    .removeClass(standbyClass)
                    .removeClass(closedClass)
                    .addClass((indice.data('status') == 'C') ? closedClass : (indice.data('status') == 'O') ? opennedClass : standbyClass);
                }

                function changeSize(size){
                    if(size.length > 0){
                        $('#ScaleSize').slider('value', parseInt(size));
                        updateSize(null, {
                            value: parseInt(size)
                        });
                    }else{
                        $('#ScaleSize').slider('value', parseInt(10));
                        updateSize(null, {
                            value: 10
                        });
                        stopSize(null, {
                            value: 10
                        });
                    }
                }

                function changeImage(image) {
                    var img = $('#mapa_de_plateia img');

                    img.fadeOut('fast', function() {
                        img.attr('src', image);
                        img.fadeIn('slow');
                    });
                }

                function changeScale(x, y) {
                    if (x.length > 0) {
                        $('#xScale').slider('value', parseInt(x));
                        updateX(null, {
                            value: parseInt(x)
                        });
                    } else {
                        $('#xScale').slider('value', 630);
                        updateX(null, {
                            value: 630
                        });
                    }
                    if (y.length > 0) {
                        $('#yScale').slider('value', parseInt(y));
                        updateY(null, {
                            value: parseInt(y)
                        });
                    } else {
                        $('#yScale').slider('value', 510);
                        updateY(null, {
                            value: 510
                        });
                        stopY(null, {
                            value: 510
                        });
                    }
                }

                function updateX(event, ui) {
                    $('#xScaleAmount').val(ui.value + 'px');
                    $('#mapa_de_plateia, #mapa_de_plateia img').width(ui.value);
                }
                function updateY(event, ui) {
                    $('#yScaleAmount').val(ui.value + 'px');
                    $('#mapa_de_plateia, #mapa_de_plateia img').height(ui.value);
                }
                function updateSize(event, ui){
                    $('#Size').val(ui.value + 'px');
                    $('.diametro').width(ui.value);
                    $('.diametro').height(ui.value);
                }
                function stopY(event, ui) {
                    if (ui.value > 1000) {
                        $('#yScale').slider('option', 'max', ui.value * 2);
                    } else {
                        $('#yScale').slider('option', 'max', 1500);
                    }
                    $('#yScale').slider('value', ui.value);
                }
                function stopX(event, ui) {
                    if (ui.value > 1000) {
                        $('#xScale').slider('option', 'max', ui.value * 2);
                    } else {
                        $('#xScale').slider('option', 'max', 1500);
                    }
                    $('#xScale').slider('value', ui.value);
                }
                function stopSize(event, ui) {
                    $('#ScaleSize').slider('value', ui.value);
                    $('.diametro').width(ui.value);
                    $('.diametro').height(ui.value);
                }

                $('#xReset').click(function(event) {
                    event.preventDefault();
                    $('#xScale').slider('value', 630);
                    updateX(event, {
                        value: 630
                    });
                });
                $('#yReset').click(function(event) {
                    event.preventDefault();
                    $('#yScale').slider('value', 510);
                    updateY(event, {
                        value: 510
                    });
                    stopY(event, {
                        value: 510
                    });
                });
                $('#sizeReset').click(function(event) {
                    event.preventDefault();
                    $('#ScaleSize').slider('value', 10);
                    updateSize(event, {
                        value: 10
                    });
                    stopSize(event, {
                        value: 10
                    });
                });                

                function validar(){
                    var valido = true;

                    if ($('#local').val() == '') {
                        $('#local').parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        $('#local').parent().removeClass('ui-state-error');
                    }

                    if ($('#pacote_combo').val() == '') {
                        $('#pacote_combo').parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        $('#pacote_combo').parent().removeClass('ui-state-error');
                    }

                    if ($('#ano').val() == '') {
                        $('#ano').parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        $('#ano').parent().removeClass('ui-state-error');
                    }

                    if ($('#setor').val() == '') {
                        $('#setor').parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        $('#setor').parent().removeClass('ui-state-error');
                    }

                    return valido;
                };
            });
        </script>

        <h2>Bloquear lugares para a Gestão do Teatro</h2>
        <form action=""  id="dados" name="dados" >
            <table class="tb-form">
                <tr>
                    <td class="coluna-header"><strong>Local:</strong></td>
                    <td><?php echo comboTeatroPorUsuario("local", $_SESSION["admin"], $_GET["local"]); ?></td>
                </tr>
                <tr>
                    <td class="coluna-header"><strong>Pacote:</strong></td>
                    <td id="container_pacotes">
                        <select id="pacote_combo"><option>Selecione um local...</option></select>
                    </td>
                </tr>
                <tr>
                    <td class="coluna-header"><strong>Temporada (Ano):</strong></td>
                    <td>
                        <input type="text" id="ano"  name="ano" maxlength="4" value="<?php echo $_GET['ano']; ?>" />
                    </td>
                </tr>
                <tr>
                    <td class="coluna-header"><strong>Setor:</strong></td>
                    <td colspan="3" id="coluna-setor">
                        <select id="setor" name="setor"><option>Selecione...</option></select>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <br />
                        <input id="exibir" type="button" class="button" value="Exibir Lugares" />&nbsp;
                        <input id="bloquear" type="button" class="button" value="Bloquear Lugares" />
                    </td>
                </tr>
            </table>

            <div id="center">
                <div id="mapa_de_plateia" class="edicao">
                    <img src="../images/palco.png" width="630" height="500" alt="">
                </div>
            </div>

        </form>
        <br/>
<?php } ?>
<?php } ?>