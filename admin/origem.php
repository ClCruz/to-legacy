<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 470, true)) {
    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);
    } else {

        $result = executeSQL($mainConnection, "SELECT ID_ORIGEM, CD_ORIGEM, DS_ORIGEM FROM MW_ORIGEM ORDER BY CD_ORIGEM");
?>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script>
            $(function() {
                var pagina = '<?php echo $pagina; ?>';

                $('#app table').delegate('a', 'click', function(event) {
                    event.preventDefault();

                    var $this = $(this),
                    href = $this.attr('href'),
                    id = 'id=' + $.getUrlVar('id', href),
                    tr = $this.closest('tr');

                    if (href.indexOf('?action=add') != -1 || href.indexOf('?action=update') != -1) {
                        if (!validateFields()) return false;

                        $.ajax({
                            url: href,
                            type: 'post',
                            data: $('#dados').serialize(),
                            success: function(data) {                                
                                if (trim(data).substr(0, 4) == 'true') {
                                    var id = $.serializeUrlVars(data);

                                    tr.find('td:not(.button):eq(1)').html($('#descricao').val());

                                    $this.text('Editar').attr('href', pagina + '?action=edit&' + id);
                                    tr.find('td.button a:last').attr('href', pagina + '?action=delete&' + id);
                                    tr.removeAttr('id');
                                } else {
                                    $.dialog({text: data});
                                }
                            }
                        });
                    } else if (href.indexOf('?action=edit') != -1) {
                        if(!hasNewLine()) return false;

                        var values = new Array();

                        tr.attr('id', 'newLine');

                        $.each(tr.find('td:not(.button)'), function() {
                            values.push($(this).text());
                        });

                        tr.find('td:not(.button):eq(1)').html('<input name="descricao" type="text" class="inputStyle" id="descricao" maxlength="100" value="' + values[1] + '" />');

                        $this.text('Salvar').attr('href', pagina + '?action=update&' + id);

                        setDatePickers();
                    } else if (href == '#delete') {
                        tr.remove();
                    } else if (href.indexOf('?action=delete') != -1) {
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

                $('tr:not(.ui-widget-header)').hover(function() {
                    $(this).addClass('ui-state-hover');
                }, function() {
                    $(this).removeClass('ui-state-hover');
                });

                function validateFields() {
                    var campos = $(':text'),
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
            });
        </script>
        <h2>Origem URL Parceiros</h2>
        <form id="dados" name="dados" method="post">
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header ">
                        <th>Código</th>
                        <th>Descrição</th>
                        <th colspan="2">A&ccedil;&otilde;es</th>
                    </tr>
                </thead>
                <tbody>
<?php
        while ($rs = fetchResult($result)) {
            $id = $rs['ID_ORIGEM'];
?>
            <tr>
                <td><?php echo $rs['CD_ORIGEM']; ?></td>
                <td><?php echo utf8_encode2($rs['DS_ORIGEM']); ?></td>
                <td class="button"><a href="<?php echo $pagina; ?>?action=edit&id=<?php echo $id; ?>">Editar</a></td>
                <td class="button"><a href="<?php echo $pagina; ?>?action=delete&id=<?php echo $id; ?>">Apagar</a></td>
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