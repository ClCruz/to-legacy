<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 480, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);

    } else {

        $result_selecionados = executeSQL($mainConnection,
                                "SELECT E.ID_EVENTO, E.DS_EVENTO, B.DS_NOME_TEATRO, MIN(A.DT_APRESENTACAO) DT_INICIO, MAX(A.DT_APRESENTACAO) DT_FIM
                                    FROM MW_EVENTO E
                                    INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
                                    INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
                                    INNER JOIN MW_EXCECAO_PAGAMENTO EP ON EP.ID_EVENTO = E.ID_EVENTO
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
                    if ($cboLocal.val()) {
                        $('#loading').dialog('open');

                        $.ajax({
                            url: pagina + '?action=getEventos&cboLocal=' + $cboLocal.val()
                        }).done(function(html){
                            var ids_selecionados = [];

                            $('#registros').html(html);
                            $('#loading').dialog('close');
                        });
                    }
                }

            $("#dados").on('submit', function(ev){
                ev.preventDefault();
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
    <h2>Meio de Pagamento Alternativo - Eventos</h2>
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
            <br/><br/>
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header">
                        <th class="nm_evento">Eventos</th>
                        <th class="nm_local">Local</th>
                        <th class="data">Data Início</th>
                        <th class="data">Data Término</th>
                        <th class="combo_pagamento">Meio de Pagamento</th>
                    </tr>
                </thead>
            </table>
            <div class="disponiveis">
                <table class="ui-widget ui-widget-content">
                    <thead>
                        <tr>
                            <th class="nm_evento"></th>
                            <th class="nm_local">
                                <th class="data"></th>
                                <th class="data"></th>
                            </th>
                            <th class="combo_pagamento"></th>
                        </tr>
                    </thead>
                    <tbody id="registros"></tbody>
                </table>
            </div><br/>
            <input type="hidden" name="eventos_atuais" value="<?php echo implode(' ', $eventos_atuais); ?>" />

            <input type="submit" class="button" value="Salvar" />
    </form>

    <div id="resposta"></div>
<?php
    }
}
?>