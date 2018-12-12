<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 460, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);

    } else {

        $result_selecionados = executeSQL($mainConnection,
                                "SELECT E.ID_EVENTO, E.DS_EVENTO, B.DS_NOME_TEATRO, MIN(A.DT_APRESENTACAO) DT_INICIO, MAX(A.DT_APRESENTACAO) DT_FIM
                                    FROM MW_EVENTO E
                                    INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
                                    INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
                                    WHERE E.IN_ANTI_FRAUDE = 1
                                    GROUP BY E.ID_EVENTO, E.DS_EVENTO, B.DS_NOME_TEATRO
                                    ORDER BY DS_EVENTO, DS_NOME_TEATRO");
?>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script type="text/javascript">
            $(function() {
                var pagina = '<?php echo $pagina; ?>',
                    $cboLocal = $('#cboLocal');

                $('.button').button();

                $("#loading").dialog({
                    autoOpen: false,
                    modal: true,
                    buttons: {},
                    closeOnEscape: false,
                    open: function(event) { $(".ui-dialog-titlebar-close", $(event.target).parent()).hide(); }
                    //open: function(event) { $(event.target).parent().hide(); }
                });

                $cboLocal.on('change', listar_eventos);

                function listar_eventos() {
                    var select_all;

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

                $('.selecionar_todos').on('click', function(){
                    $(this).parents('table').next('div').find('table').find(':input').prop('checked', $(this).prop('checked'));

                    sendSelected();

                    $(this).prop('checked', !$(this).prop('checked'));
                });

                $('#registros, #selecionados').on('click', 'tr, :checkbox', function(e){
                    if ($(this).is(':checkbox')) {
                        e.stopPropagation();
                    } else {
                        $(this).find(':checkbox').trigger('click');
                    }
                    sendSelected();
                });

                function sendSelected() {
                    var selecionados = $('#registros :checkbox:checked').parents('tr').remove();
                    var removidos = $('#selecionados :checkbox:not(:checked)').parents('tr').remove();

                    $('#selecionados').append(selecionados);
                    $('#registros').append(removidos);

                    sortColumn($('#selecionados tr td:first'), true);
                    sortColumn($('#registros tr td:first'), true);
                }

                $('table.ui-widget').on('mouseenter mouseleave', 'tr:not(.ui-widget-header)', function() {
                    $(this).toggleClass('ui-state-hover');
                });

                $('#dados').on('submit', function(e){
                    e.preventDefault();

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
                            $.dialog({text: 'Dados alterados com sucesso.'});

                            var eventos_atuais = [];

                            $('#selecionados :checkbox').each(function() {
                                eventos_atuais.push($(this).val());
                            });

                            $(':input[name=eventos_atuais]').val(eventos_atuais.join());
                        }
                    });
                });
            });
        </script>
        <style type="text/css">
        .disponiveis, .selecionados {
            max-height: 300px;
            overflow-y: scroll;
        }
        .nm_evento {
            width: 34%;
        }
        .nm_local {
            width: 33%;
        }
        .data {
            width: 10%;
        }
        .chk_evento {
            width: 13%;
            text-align: right;
        }
        .nm_evento, .nm_local {
            text-align: left;
        }
        </style>
        <div title="Processando..." id="loading">
            Aguarde, este processamento poderá levar alguns minutos. Não saia da tela até a
            finalização do processamento.
        </div>
        <h2>Anti-fraude - Eventos</h2>
        <form id="dados" name="dados" method="post">
            <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>" />
            <table>
                <tr>
                    <td>
                        <b>Local:</b><br/>
                        <?php echo comboTeatroPorUsuario('cboLocal', $_SESSION['admin'], $_GET['local']); ?>
                    </td>
                </tr>
            </table>
            <br/>

            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header">
                        <th class="nm_evento">Eventos Disponíveis / Fora do Anti-fraude</th>
                        <th class="nm_local">Local</th>
                        <th class="data">Data Início</th>
                        <th class="data">Data Término</th>
                        <th class="chk_evento"><label>Selecionar Todos <input type="checkbox" class="selecionar_todos" /></label></th>
                    </tr>
                </thead>
            </table>
            <div class="disponiveis">
                <table class="ui-widget ui-widget-content">
                    <thead><tr><th class="nm_evento"></th><th class="nm_local"><th class="data"></th><th class="data"></th></th><th class="chk_evento"></th></tr></thead>
                    <tbody id="registros"></tbody>
                </table>
            </div>
            <br/>

            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header">
                        <th class="nm_evento">Eventos Selecionados</th>
                        <th class="nm_local">Local</th>
                        <th class="data">Data Início</th>
                        <th class="data">Data Término</th>
                        <th class="chk_evento"><label>Selecionar Todos <input type="checkbox" class="selecionar_todos" checked="true" /></label></th>
                    </tr>
                </thead>
            </table>
            <div class="selecionados">
                <table class="ui-widget ui-widget-content">
                    <thead><tr><th class="nm_evento"></th><th class="nm_local"></th><th class="data"></th><th class="data"></th><th class="chk_evento"></th></tr></thead>
                    <tbody id="selecionados">
                        <?php
                            $eventos_atuais = array();
                            while ($rs = fetchResult($result_selecionados)) {
                                $eventos_atuais[] = $rs['ID_EVENTO'];
                        ?>
                        <tr>
                            <td><?php echo utf8_encode2($rs['DS_EVENTO']); ?></td>
                            <td><?php echo utf8_encode2($rs['DS_NOME_TEATRO']); ?></td>
                            <td><?php echo $rs['DT_INICIO']->format('d/m/Y'); ?></td>
                            <td><?php echo $rs['DT_FIM']->format('d/m/Y'); ?></td>
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