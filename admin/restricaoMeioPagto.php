<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();
if (acessoPermitido($mainConnection, $_SESSION['admin'], 7, true)) {
    $pagina = basename(__FILE__);
    if (isset($_GET['action'])) {
        require('actions/' . $pagina);
    } else {
        $qry = "SELECT	MPF.ID_MEIO_PAGAMENTO,
                        MP.DS_MEIO_PAGAMENTO,
                        CASE WHEN BSM.ID_BASE IS NOT NULL THEN 1 ELSE 0 END AS IND_RESTRICAO,
                        CASE WHEN BSM.ID_BASE IS NOT NULL THEN CONVERT(VARCHAR(10),BSM.DT_INICIO,103) ELSE ' ' END AS INI_RESTRICAO,
                        CASE WHEN BSM.ID_BASE IS NOT NULL THEN CONVERT(VARCHAR(10),BSM.DT_FIM,103)    ELSE ' ' END AS FIM_RESTRICAO
                FROM MW_MEIO_PAGAMENTO_FORMA_PAGAMENTO MPF WITH (NOLOCK)
                INNER JOIN MW_MEIO_PAGAMENTO MP WITH (NOLOCK)
                ON MP.ID_MEIO_PAGAMENTO = MPF.ID_MEIO_PAGAMENTO
                LEFT JOIN MW_BASE_MEIO_PAGAMENTO BSM WITH (NOLOCK)
                ON MPF.ID_BASE = BSM.ID_BASE
                AND MPF.ID_MEIO_PAGAMENTO = BSM.ID_MEIO_PAGAMENTO
                WHERE MPF.ID_BASE = ? AND MP.IN_ATIVO = 1
                ORDER BY MP.DS_MEIO_PAGAMENTO ASC";

        $result = executeSQL($mainConnection, $qry , array($_GET['teatro']));
        $resultTeatros = executeSQL($mainConnection, 'SELECT ID_BASE, DS_NOME_TEATRO FROM MW_BASE WHERE IN_ATIVO = \'1\'');
?>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script>
            $(function() {
                var pagina = '<?php echo $pagina; ?>'



                $('#app table').delegate('a', 'click', function(event) {
                    event.preventDefault();

                    var $this = $(this),
                    href = $this.attr('href'),
                    id = 'idMeioPagamento=' + $.getUrlVar('idMeioPagamento', href) + '&idBase=' + $.getUrlVar('idBase', href),
                    tr = $this.closest('tr');

                    if (href.indexOf('?action=add') != -1 || href.indexOf('?action=update') != -1) {
                        if (!validateFields()) return false;

                            var formDados       = document.forms.dados;


                        $.ajax({
                            url: href,
                            type: 'post',
                            data: $('#dados').serialize() + '&ds_forpagto=' + $('#idFormaPagamento option:selected').text(),
                            success: function(data) {
                                if (data.substr(0, 4) == 'true') {
                                    var id = $.serializeUrlVars(data);

                                    tr.find('td:not(.button):eq(1)').html($('#dt_Inicio').text());
                                    tr.find('td:not(.button):eq(2)').html($('#dt_Fim').text());

                                    $this.text('Editar').attr('href', pagina + '?action=edit&' + id);
                                    tr.find('td.button a:last').attr('href', pagina + '?action=delete&' + id);
                                    tr.removeAttr('id');
                                    location.reload(true);
                                } else {
                                    $.dialog({text: 'Restrição cadastrada com sucesso !', icon: 'ok' });
                                    location.reload(true);
                                }
                            }
                        });


                    } else if (href.indexOf('?action=edit') != -1 || href.indexOf('?action=add') != -1) {
                        if(!hasNewLine()) return false;

                        var values = new Array();

                        tr.attr('id', 'newLine');

                        $.each(tr.find('td:not(.button)'), function() {
                            values.push($(this).text());
                        });

                        tr.find('td:not(.button):eq(2)').html('<?php echo '<input type="text" name="dt_inicio" id="dt_inicio" class="datePicker" size="7">'; ?>');
                        tr.find('td:not(.button):eq(3)').html('<?php echo '<input type="text" name="dt_fim" id="dt_fim" class="datePicker" size="7">'; ?>');
                        $('input.datePicker').prop('readonly', true).datepicker({
                            changeMonth: true,
                            changeYear: true
                        }).datepicker('option', $.datepicker.regional['pt-BR']);

                        $('#dt_inicio').on('change', function() {
                            $("#dt_fim").datepicker("option", "minDate", $(this).val()).trigger('change');
                        });

                        $this.text('Salvar').attr('href', pagina + '?action=add&' + id);

                        setDatePickers();

                    } else if (href == '#delete') {
                        tr.remove();
                    } else if (href.indexOf('?action=delete') != -1) {
                        $.confirmDialog({
                            text: 'Tem certeza que deseja retirar a restrição de meio de pagamento ?',
                            uiOptions: {
                                buttons: {
                                    'Sim': function() {
                                        $(this).dialog('close');
                                        $.get(href, function(data) {
                                            if (data.replace(/^\s*/, "").replace(/\s*$/, "") == 'true') {
                                                //tr.remove();
                                                location.reload(true);
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



                $('#teatro').change(function() {
                    document.location = '?p=' + pagina.replace('.php', '') + '&teatro=' + $(this).val();
                });

                function validateFields() {
                    var dtInicio = $('#dt_inicio');
                    var dtFim    = $('#dt_fim');

                    valido = true;

                    if (dtInicio.val() == '') {
                        dtInicio.parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                         dtInicio.parent().removeClass('ui-state-error');
                    }

                    if (dtFim.val() == '') {
                        dtFim.parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        dtFim.parent().removeClass('ui-state-error');
                    }

                    return valido;
                }

                $('tr:not(.ui-widget-header)').hover(function() {
                    $(this).addClass('ui-state-hover');
                }, function() {
                    $(this).removeClass('ui-state-hover');
                });
            });


        </script>
        <style type="text/css">
            .center{text-align: center;}
        </style>
        <h2>Restrição a Meio de Pagamento</h2>
        <form id="dados" name="dados" method="post">
            <p style="width:200px;"><?php echo comboTeatro('teatro', $_GET['teatro']); ?></p>
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header ">
                        <th>Meio de Pagamento</th>
                        <th>Restri&ccedil;&atilde;o</th>
                        <th>Data Início</th>
                        <th>Data Fim</th>
                        <th colspan="2">A&ccedil;&otilde;es</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            while ($rs = fetchResult($result)) {
                $idMeioPagamento = $rs['ID_MEIO_PAGAMENTO'];
                $idBase = $rs['ID_BASE'];

            ?>
                <tr>
                    <td><?php echo comboMeioPagamento('idMeioPagamento', $idMeioPagamento, false); ?></td>
                    <td class="center"><?php echo ($rs['IND_RESTRICAO'] == 1 ? '<b>Sim</b>' : 'N&atilde;o'); ?></td>
                    <td class="center"><?php echo $rs['INI_RESTRICAO']; ?></td>
                    <td class="center"><?php echo $rs['FIM_RESTRICAO']; ?></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=<?php echo ($rs['IND_RESTRICAO'] == 1 ? '  ' : 'edit'); ?>&idMeioPagamento=<?php echo $idMeioPagamento; ?>&idBase=<?php echo $_GET['teatro']; ?>"><?php echo $rs['IND_RESTRICAO'] == 1 ? '   ' : 'Criar'; ?></a></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=delete&idMeioPagamento=<?php echo $idMeioPagamento; ?>&idBase=<?php echo $_GET['teatro']; ?>"><?php echo $rs['IND_RESTRICAO'] == 1 ? 'Apagar' : ''; ?></a></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>

</form>
<?php
        }
    }
?>