<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 410, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);

    } else {

        if (!$_GET['excel']) {
?>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script type="text/javascript">
            $(function() {
                var pagina = '<?php echo $pagina; ?>',
                    $cboTeatro = $('#cboTeatro'),
                    $cboPeca = $('#cboPeca'),
                    $cboData = $('#cboData'),
                    $cboHora = $('#cboHora'),
                    $cboSetor = $('#cboSetor'),
                    $cboPeriodo = $('#cboPeriodo'),
                    $cboDataI = $('#getDataInicio'),
                    $cboDataF = $('#getDataTermino');

                $('#visualizar').button().on('click', function(e){
                    e.preventDefault();

                    if (validacao()) {
                        $('#registros').html('<tr><td colspan="8" align="center">Carregando...</td></tr>');

                        $.ajax({
                            url: pagina + '?action=busca&' + $('#dados').serialize()
                        }).done(function(html){
                            $('#registros').html(html).show();
                        });
                    }
                });

                $('#exportar').button().on('click', function(e){
                    e.preventDefault();

                    if (validacao()) {
                         document.location = pagina + '?excel=1&' + $('#dados').serialize();

                    }
                });

                $('#limpar').button().on('click', function(e){
                    e.preventDefault();
                    $cboPeca.val('').trigger('change');
                    $('#registros').html('');
                });

                $.ajax({
                    url: pagina + '?action=cboTeatro&cboTeatro=<?php echo $_GET['cboTeatro']; ?>'
                }).done(function(html){
                    $cboTeatro.html(html).trigger('change');
                });

                $cboTeatro.on('change', function(){
                    if ($cboTeatro.val()) {
                        $.ajax({
                            url: pagina + '?action=cboPeca&cboPeca=<?php echo $_GET['cboPeca']; ?>&cboTeatro=' + $cboTeatro.val()
                        }).done(function(html){
                            $cboPeca.html(html).trigger('change');
                        });
                    } else {
                        $cboPeca.find('option:not(:first)').remove().end().trigger('change');
                    }
                });

                $cboPeca.on('change', function(){
                    if ($cboPeca.val()) {

                        $.ajax({
                            url: pagina + '?action=cboData&cboData=<?php echo $_GET['cboData']; ?>&cboTeatro=' + $cboTeatro.val() + '&cboPeca=' + $cboPeca.val()
                        }).done(function(html){
                            $cboData.html(html).trigger('change');
                        });
                    } else {
                        $cboData.find('option:not(:first)').remove().end().trigger('change');
                    }

                    if($cboPeca.val() == ""){
                        $(".dtC").attr("placeholder","Selecione uma peça");
                        $(".dtC").attr("disabled", true);
                        $(".dtC").val("");
                    }
                    else {
                        $.ajax({
                            url: pagina + '?action=getDataInicio&getDataInicio=<?php echo $_GET['getDataInicio']; ?>&cboTeatro=' + $cboTeatro.val() + '&cboPeca=' + $cboPeca.val(),
                            dataType: 'json'
                        }).done(function(html){
                            var tamanho_array = parseInt(html.length)-1;
                            $('input[name="getDataInicio"]').datepicker('option', 'minDate', html[0].data_apresentacao);
                            $('input[name="getDataInicio"]').datepicker('option', 'maxDate', html[tamanho_array].data_apresentacao);
                            $('input[name="getDataTermino"]').datepicker('option', 'maxDate', html[tamanho_array].data_apresentacao);
                            $('input[name="getDataTermino"]').datepicker('option', 'minDate', html[0].data_apresentacao);
                        }); 
                        $(".dtC").attr("placeholder","");
                        $(".dtC").attr("disabled", false);
                    }
                });

                $cboData.on('change', function(){
                    if ($cboData.val()) {
                        $.ajax({
                            url: pagina + '?action=cboHora&cboHora=<?php echo $_GET['cboHora']; ?>&cboTeatro=' + $cboTeatro.val() + '&cboPeca=' + $cboPeca.val() + '&cboData=' + $cboData.val()
                        }).done(function(html){
                            $cboHora.html(html).trigger('change');
                        });
                    } else {
                        $cboHora.find('option:not(:first)').remove().end().trigger('change');
                    }
                });

                $cboHora.on('change', function(){
                    if ($cboHora.val()) {
                        $.ajax({
                            url: pagina + '?action=cboSetor&cboSetor=<?php echo $_GET['cboSetor']; ?>&cboTeatro=' + $cboTeatro.val() + '&cboPeca=' + $cboPeca.val() + '&cboData=' + $cboData.val() + '&cboHora=' + $cboHora.val()
                        }).done(function(html){
                            $cboSetor.html(html);
                        });
                    } else {
                        $cboSetor.find('option:not(:first)').remove();
                    }
                });

                function validacao() {
                    if($("#cboPeriodo").is(':checked')){
                        var valido = true,
                        campos = $('.com, .pPeriodo');

                        $.each(campos, function() {
                            var $this = $(this);
                            
                            if ($this.val() == '') {
                                $this.parent().addClass('ui-state-error');
                                valido = false;
                            } else {
                                $this.parent().removeClass('ui-state-error');
                            }
                        });
                    }
                    else{
                        var valido = true,
                        campos = $('.com, .pData');

                        $.each(campos, function() {
                            var $this = $(this);
                            
                            if ($this.val() == '') {
                                $this.parent().addClass('ui-state-error');
                                valido = false;
                            } else {
                                $this.parent().removeClass('ui-state-error');
                            }
                        });
                    }
                    return valido;
                }

                $cboPeriodo.click(function(){
                    $('.smallH').toggle();
                    if($cboPeriodo.is(':checked')){
                        $(".pData").val("");

                    } else {
                        $(".pPeriodo").val("");
                    }
                });

                $('input.datePicker').datepicker({
                    changeMonth: true,
                    changeYear: true,
                    onSelect: function(date, e) {
                        if ($(this).is('input[name="getDataInicio"]')) {
                            $('input[name="getDataTermino"]').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                        }
                    }
                }).datepicker('option', $.datepicker.regional['pt-BR']);
            });
        </script>
        <?php
        } else {
            header("Content-type: application/vnd.ms-excel");
            header("Content-type: application/force-download");
            header("Content-Disposition: attachment; filename=informacoesPublico.xls");
            ?><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><?php
        }
        ?>
        <div id="qualquer"></div>
        <h2>Demonstrativo de Vendas – Assinaturas X Avulsas</h2>
        
        <form id="dados" name="dados" method="post">
            <table>
                <tr>
                    <td>
                        <b>Local:</b><br/>
                        <?php
                            if ($_GET['excel']) {
                                $_GET['action'] = 'cboTeatro';
                                require('actions/' . $pagina);
                            } else {
                                ?><select name="cboTeatro" id="cboTeatro" class="com"><option value="">Carregando...</option></select><?php
                            }
                        ?>
                    </td>
                    <td>
                        <b>Evento:</b><br/>
                        <?php
                            if ($_GET['excel']) {
                                $_GET['action'] = 'cboPeca';
                                require('actions/' . $pagina);
                            } else {
                                ?><select name="cboPeca" id="cboPeca" class="com"><option value="">Selecione um Local...</option></select><?php
                            }
                        ?>
                    </td>
                    <td class="smallH porData">
                        <?php
                            if ($_GET['excel']) {
                                $_GET['action'] = 'cboData';
                                require('actions/' . $pagina);
                            } else {
                                ?>
                                <b>Data:</b><br/>
                                <select name="cboData" id="cboData" class="pData"><option value="">Selecione um Evento...</option></select><?php
                            }
                        ?>
                        
                    </td>
                    
                    <td>
                        <?php 
                            if(!$_GET['excel']){
                        ?>  
                            <br><label><input type="checkbox" value="cboPeriodo" id="cboPeriodo"> Por periodo</label>
                        <?php
                            }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class='smallH porData'>
                        <?php
                            if ($_GET['excel']) {
                                $_GET['action'] = 'cboHora';
                                require('actions/' . $pagina);
                            } else {
                                ?>
                                <b>Hora:</b><br/>
                                <select name="cboHora" id="cboHora" class="pData"><option value="">Selecione uma Data...</option></select><?php
                            }
                        ?>
                    </td>
                    <td class="smallH porData">
                        <b>Setor:</b><br/>
                        <?php
                            if ($_GET['excel']) {
                                $_GET['action'] = 'cboSetor';
                                require('actions/' . $pagina);
                            } else {
                                ?><select name="cboSetor" id="cboSetor" class="pData"><option value="">Selecione uma Hora...</option></select><?php
                            }
                        ?>
                    </td>
                    <td class="smallH  ui-helper-hidden">
                        <?php
                            if ($_GET['excel']) {
                                $_GET['action'] = 'getDataInicio';
                                require('actions/' . $pagina);
                            } else {
                                ?>
                                <b>Dt. Apresentação Inicial</b><br />
                                <input type="text" maxlength="10" size="15" class="datePicker dtC pPeriodo" name="getDataInicio" id="getDataInicio" readonly /><?php
                            }
                        ?>
                    </td>
                    <td class="smallH  ui-helper-hidden">
                         <?php
                            if ($_GET['excel']) {
                                if($_GET['getDataTermino'] != ""){
                                    echo "<b>Dt. Apresentação Final</b><br />".$_GET['getDataTermino'];
                                }
                            } else {
                                ?>
                                <b>Dt. Apresentação Final</b><br />
                                <input type="text" maxlength="10" size="15" class="datePicker dtC pPeriodo" name="getDataTermino" id="getDataTermino" readonly /><?php
                            }
                        ?>
                    </td>
                    <td style="vertical-align: bottom;">
                        <?php if (!$_GET['excel']) { ?>
                            <a id="visualizar" class="button" href="<?php echo $pagina; ?>?action=visualizar">Visualizar</a>
                            <a id="exportar" class="button" href="<?php echo $pagina; ?>?action=exportar">Exportar para Excel</a>
                            <a id="limpar" class="button" href="<?php echo $pagina; ?>?action=limpar">Limpar Campos</a>
                        <?php } ?>
                    </td>
                </tr>
            </table>
            <br/>
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header">
                        <th align="left" rowspan="2">Setor</th>
                        <th align="left" rowspan="2">Tipo de Ingresso</th>

                        <th align="center" colspan="2">Assinaturas</th>
                        <th align="center" colspan="2">Avulsas</th>
                        <th align="center" colspan="2">Totais</th>
                    </tr>
                    <tr class="ui-widget-header">
                        <th align="center">Qtde.</th>
                        <th align="center">Valor</th>

                        <th align="center">Qtde.</th>
                        <th align="center">Valor</th>

                        <th align="center">Qtde.</th>
                        <th align="center">Valor</th>
                    </tr>
                </thead>
                <tbody id="registros">
                    <?php
                        if ($_GET['excel']) {
                            $_GET['action'] = 'busca';
                            require('actions/' . $pagina);
                        }
                    ?>
                </tbody>
            </table>
</form>
<?php
        }
    }
?>