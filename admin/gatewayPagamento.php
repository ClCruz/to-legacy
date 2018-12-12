<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 215, true)) {
    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);
    } else {
        $result = executeSQL($mainConnection, 'SELECT ID_GATEWAY_PAGAMENTO, DS_GATEWAY_PAGAMENTO, DS_URL,
                                                        CD_GATEWAY_PAGAMENTO, IN_ATIVO, DS_URL_CONSULTA,
                                                        DS_URL_RETORNO, CD_KEY_GATEWAY_PAGAMENTO
                                                FROM MW_GATEWAY_PAGAMENTO
                                                WHERE ID_GATEWAY = ?', array($_GET['gateway']));
?>

        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script>
            $(function() {
                var pagina = '<?php echo $pagina; ?>';

                $('button').button();

                $('#app table').delegate('a', 'click', function(event) {
                    event.preventDefault();

                    var $this = $(this),
                    href = $this.attr('href'),
                    id = 'id=' + $.getUrlVar('id', href),
                    tr = $this.closest('tr');

                    if (href.indexOf('?action=add') != -1 || href.indexOf('?action=update') != -1) {
                        if (!validateFields()) return false;

                        if($('#ativo').is(':checked') && !$('#ativo').is(':disabled')){
                            $.confirmDialog({
                                text: 'Todas as outras contas serão "INATIVADAS" para que esta se torne "ATIVA". Efetuar a alteração?',
                                uiOptions: {
                                    buttons: {
                                        'Sim': function() {
                                            $(this).dialog('close');
                                            enviar_dados($this, tr, href);
                                        }
                                    }
                                }
                            });
                        }
                        else
                        {
                            enviar_dados($this, tr, href);
                        }
                    } else if (href.indexOf('?action=edit') != -1) {
                        if(!hasNewLine()) return false;

                        var values = new Array();

                        tr.attr('id', 'newLine');

                        $.each(tr.find('td:not(.button)'), function() {
                            values.push($(this).text());
                        });

                        tr.find('td:not(.button):eq(0)').html('<input name="nome" type="text" class="inputStyle required" id="nome" maxlength="50" value="' + values[0] + '" />');
                        tr.find('td:not(.button):eq(2)').html('<input name="url" type="text" class="inputStyle required" id="url" maxlength="100" value="' + values[2] + '" />');
                        tr.find('td:not(.button):eq(3)').html('<input name="url_consulta" type="text" class="inputStyle" id="url_consulta" maxlength="100" value="' + values[3] + '" />');
                        tr.find('td:not(.button):eq(4)').html('<input name="url_retorno" type="text" class="inputStyle" id="url_retorno" maxlength="100" value="' + values[4] + '" />');
                        tr.find('td:not(.button):eq(5)').html('<input name="codigo" type="text" class="inputStyle required" id="codigo" maxlength="50" value="' + values[5] + '" />');
                        tr.find('td:not(.button):eq(6)').html('<input name="chave" type="text" class="inputStyle" id="chave" maxlength="50" value="' + values[6] + '" />');
                        tr.find('td:not(.button):eq(7)').html('<input name="ativo" type="checkbox" class="inputStyle" id="ativo" ' + (values[7] == 'sim' ? 'checked readonly disabled' : ''  )+ ' />');

                        $this.text('Salvar').attr('href', pagina + '?action=update&' + id);

                        setDatePickers();
                    } else if (href == '#delete') {
                        tr.remove();
                    }
                });

                $('#gateway').on('change', function(){
                    document.location = './?p='+pagina.slice(0, -4)+'&gateway='+$(this).val();
                });

                $('#show_hide').on('click', function(e) {
                    e.preventDefault();

                    var $this = $(this);

                    if ($this.parent().attr('colspan') == 5) {
                        $this.parent().attr('colspan', 0);
                        $this.button('option', 'label', 'Exibir');
                    } else {
                        $this.parent().attr('colspan', 5);
                        $this.button('option', 'label', 'Esconder');
                    }

                    $('.params').toggle();
                }).trigger('click');

                function validateFields() {
                    var campos = $('.required:not(#ativo)'),
                    valido = true;

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

                function enviar_dados($this, tr, href) {
                    $.ajax({
                        url: href,
                        type: 'post',
                        data: $('#dados').serialize(),
                        success: function(data) {
                            if (data.substr(0, 4) == 'true') {
                                var id = $.serializeUrlVars(data);

                                tr.find('td:not(.button):eq(0)').html($('#nome').val());
                                tr.find('td:not(.button):eq(2)').html($('#url').val());
                                tr.find('td:not(.button):eq(3)').html($('#url_consulta').val());
                                tr.find('td:not(.button):eq(4)').html($('#url_retorno').val());
                                tr.find('td:not(.button):eq(5)').html($('#codigo').val());
                                tr.find('td:not(.button):eq(6)').html($('#chave').val());
                                tr.find('td:not(.button):eq(7)').html($('#ativo').is(':checked') ? 'sim' : 'n&atilde;o');

                                $this.text('Editar').attr('href', pagina + '?action=edit&' + id);
                                tr.removeAttr('id');
                                document.location = document.location;
                            } else {        
                                $.dialog({text: data});
                            }
                        }
                    });
                }
            });
        </script>
        <h2>Contas do Gateway</h2>
        <form id="dados" name="dados" method="post">
            <div style="text-align: left;">
                <?php echo comboGateway('gateway', $_GET['gateway']); ?>
            </div>
            <br>
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header">
                        <th rowspan="2">Nome da Conta</th>
                        <th class="params" style="display: none">Parâmetros</th>
                        <th class="params">URL de Ações</th>
                        <th class="params">URL de Consultas</th>
                        <th class="params">URL de Retorno</th>
                        <th class="params">Código do Estabelecimento</th>
                        <th class="params">Chave do Estabelecimento</th>
                        <th rowspan="2">Ativo</th>
                        <th rowspan="2">A&ccedil;&otilde;es</th>
                    </tr>
                    <tr class="ui-widget-header">
                        <th colspan="5" style="text-align: center">
                            <button id="show_hide" class="button">Exibir/Esconder</button>
                        </th>
                    </tr>
                </thead>
                <tbody>
            <?php
            while ($rs = fetchResult($result)) {
                $id = $rs['ID_GATEWAY_PAGAMENTO'];
            ?>
                <tr>
                    <td><?php echo utf8_encode2($rs['DS_GATEWAY_PAGAMENTO']); ?></td>
                    <td class="params" style="display: none"> - </td>
                    <td class="params"><?php echo$rs['DS_URL']; ?></td>
                    <td class="params"><?php echo$rs['DS_URL_CONSULTA']; ?></td>
                    <td class="params"><?php echo$rs['DS_URL_RETORNO']; ?></td>
                    <td class="params"><?php echo$rs['CD_GATEWAY_PAGAMENTO']; ?></td>
                    <td class="params"><?php echo$rs['CD_KEY_GATEWAY_PAGAMENTO']; ?></td>
                    <td><?php echo $rs['IN_ATIVO'] ? 'sim' : 'n&atilde;o'; ?></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=edit&id=<?php echo $id; ?>">Editar</a></td>
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