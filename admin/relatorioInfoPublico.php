<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 390, true)) {

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
                    $cboSetor = $('#cboSetor');

                $('#exportar').button().on('click', function(e){
                    e.preventDefault();

                    if (validacao()) {
                        document.location = pagina + '?excel=1&' + $('#dados').serialize();
                    }
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
                    var valido = true,
                        campos = $('select');

                    $.each(campos, function() {
                        var $this = $(this);
                        
                        if ($this.val() == '') {
                            $this.parent().addClass('ui-state-error');
                            valido = false;
                        } else {
                            $this.parent().removeClass('ui-state-error');
                        }
                    });

                    return valido;
                }
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
        <h2>Informações do público por evento</h2>
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
                                ?><select name="cboTeatro" id="cboTeatro"><option value="">Carregando...</option></select><?php
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
                                ?><select name="cboPeca" id="cboPeca"><option value="">Selecione um Local...</option></select><?php
                            }
                        ?>
                    </td>
                    <td>
                        <b>Data:</b><br/>
                        <?php
                            if ($_GET['excel']) {
                                $_GET['action'] = 'cboData';
                                require('actions/' . $pagina);
                            } else {
                                ?><select name="cboData" id="cboData"><option value="">Selecione um Evento...</option></select><?php
                            }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Hora:</b><br/>
                        <?php
                            if ($_GET['excel']) {
                                $_GET['action'] = 'cboHora';
                                require('actions/' . $pagina);
                            } else {
                                ?><select name="cboHora" id="cboHora"><option value="">Selecione um Data...</option></select><?php
                            }
                        ?>
                    </td>
                    <td>
                        <b>Setor:</b><br/>
                        <?php
                            if ($_GET['excel']) {
                                $_GET['action'] = 'cboSetor';
                                require('actions/' . $pagina);
                            } else {
                                ?><select name="cboSetor" id="cboSetor"><option value="">Selecione um Hora...</option></select><?php
                            }
                        ?>
                    </td>
                    <td style="vertical-align: bottom;">
                        <?php if (!$_GET['excel']) { ?>
                            <a id="exportar" class="button" href="<?php echo $pagina; ?>?action=exportar">Exportar para Excel</a>
                        <?php } ?>
                    </td>
                </tr>
            </table>
            <br/>
            <?php if ($_GET['excel']) { ?>
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header">
                        <th align="left">Nome do Cliente</th>
                        <th align="left">Sala</th>
                        <th align="left">Setor</th>
                        <th align="left">Lugar</th>
                        <th align="left">Data/Hora Entrada</th>
                        <th align="left">Assinante do Evento</th>
                        <th align="left">Assinante</th>
                        <th align="left">Tipo de Ingresso</th>
                    </tr>
                </thead>
                <tbody id="registros">
                    <?php
                        $_GET['action'] = 'busca';
                        require('actions/' . $pagina);
                    ?>
                </tbody>
            </table>
            <?php } ?>
</form>
<?php
        }
    }
?>