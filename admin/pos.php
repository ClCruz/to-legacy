<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 450, true)) {
    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);
    } else {

        $result = executeSQL($mainConnection,
                "SELECT ID, SERIAL, DESCRICAO,
                        CONVERT(VARCHAR, LAST_ACCESS, 103) + ' ' + CONVERT(VARCHAR, LAST_ACCESS, 108) LAST_ACCESS,
                        CONVERT(VARCHAR, LAST_CONFIG, 103) + ' ' + CONVERT(VARCHAR, LAST_CONFIG, 108) LAST_CONFIG,
                        VENDA_DINHEIRO,
                        VENDA_PROMO_CONVITE,
                        CONVERT(VARCHAR, LAST_ACCESS, 126) LAST_ACCESS_ORDER
                FROM MW_POS ORDER BY DESCRICAO, SERIAL");
?>
        <style type="text/css">
            .center{
                text-align: center;
            }

            th.sortable div {
                width: 0;
                height: 0;
                border-left: 5px solid transparent;
                border-right: 5px solid transparent;
                margin-left: 6px;
                display: inline-block;
            }

            th.sortable.asc div {
                border-bottom: 5px solid white;
            }

            th.sortable.desc div {
                border-top: 5px solid white;
            }

            th.sortable:hover {
                text-decoration: underline;
                cursor: pointer;
            }
        </style>
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
                                    tr.find('td:not(.button):eq(2)').html($('#venda_dinheiro').is(':checked') ? 'sim' : 'n&atilde;o');
                                    tr.find('td:not(.button):eq(3)').html($('#venda_promo_convite').is(':checked') ? 'sim' : 'n&atilde;o');


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
                        tr.find('td:not(.button):eq(2)').html('<input name="venda_dinheiro" type="checkbox" class="inputStyle" id="venda_dinheiro" ' + (values[2] == 'sim' ? 'checked' : ''  )+ ' />');
                        tr.find('td:not(.button):eq(3)').html('<input name="venda_promo_convite" type="checkbox" class="inputStyle" id="venda_promo_convite" ' + (values[3] == 'sim' ? 'checked' : ''  )+ ' />');

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

                $('.sortable').on('click', function(){
                    var $this = $(this);

                    if ($this.is('.asc')) {
                        $this.removeClass('asc').addClass('desc');
                    } else if ($this.is('.desc')) {
                        $this.removeClass('desc').addClass('asc');
                    } else {
                        $('.sortable').removeClass('asc').removeClass('desc');
                        $this.addClass('asc');
                    }

                    sortColumn($this[0], true);
                }).eq(1).trigger('click');
            });
        </script>
        <h2>Máquinas POS</h2>
        <form id="dados" name="dados" method="post">
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header ">
                        <th class="sortable">Serial <div/></th>
                        <th class="sortable">Descrição <div/></th>
                        <th>Venda em Dinheiro</th>
                        <th>Venda de Promoção Convite</th>
                        <th class="sortable">Último Acesso <div/></th>
                        <th>Última Atualização</th>
                        <th colspan="2">A&ccedil;&otilde;es</th>
                    </tr>
                </thead>
                <tbody>
<?php
        while ($rs = fetchResult($result)) {
            $id = $rs['ID'];
?>
            <tr>
                <td><?php echo substr(chunk_split($rs['SERIAL'], 3, '-'), 0, -1); ?></td>
                <td><?php echo utf8_encode2($rs['DESCRICAO']); ?></td>
                <td><?php echo $rs['VENDA_DINHEIRO'] ? 'sim' : 'não'; ?></td>
                <td><?php echo $rs['VENDA_PROMO_CONVITE'] ? 'sim' : 'não'; ?></td>
                <td data-tosort="<?php echo $rs['LAST_ACCESS_ORDER']; ?>"><?php echo $rs['LAST_ACCESS']; ?></td>
                <td><?php echo $rs['LAST_CONFIG']; ?></td>
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