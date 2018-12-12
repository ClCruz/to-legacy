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
        $result = executeSQL($mainConnection, 'SELECT MPF.ID_MEIO_PAGAMENTO, CODFORPAGTO, DS_FORPAGTO, IN_TRANSACAO_PDV FROM MW_MEIO_PAGAMENTO_FORMA_PAGAMENTO MPF INNER JOIN MW_MEIO_PAGAMENTO MP ON MP.ID_MEIO_PAGAMENTO = MPF.ID_MEIO_PAGAMENTO WHERE ID_BASE = ? ORDER BY MP.DS_MEIO_PAGAMENTO ASC', array($_GET['teatro']));
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
                        var meioPgto        = formDados.idMeioPagamento;
                        var formaPgto       = formDados.idFormaPagamento;
                        var meioSelected    = meioPgto[meioPgto.selectedIndex];
                        var formaSelected   = formaPgto[formaPgto.selectedIndex];

                        var diffPgto = { status: true };
                        var preventDeleteId = false;
                        switch (meioSelected.value)
                        {
                            /*
                             #69 - Não permitir POS e PDV cadastrados com formas diferentes. Isso já é um padrão que vem do VB é deve se manter assim.
                             * */
                            case '65': case '66': case '67': case '69': case '70': case '71':
                            diffPgto = validaPgtoWebVB(meioSelected, formaSelected);
                            break;
                        }

                        if (!diffPgto.status)
                        {
                            $.dialog({ text: diffPgto.msg });
                            //alert(diffPgto.msg);
                            return false;
                        }


                        $.ajax({
                            url: href,
                            type: 'post',
                            data: $('#dados').serialize() + '&ds_forpagto=' + $('#idFormaPagamento option:selected').text(),
                            success: function(data) {
                                if (data.substr(0, 4) == 'true') {
                                    var id = $.serializeUrlVars(data);

                                    tr.find('td:not(.button):eq(0)').html($('#idMeioPagamento option:selected').text());
                                    tr.find('td:not(.button):eq(1)').html($('#idFormaPagamento option:selected').text());
                                    tr.find('td:not(.button):eq(2)').html($('#in_transacao_pdv').is(':checked') ? 'Sim' : 'N&atilde;o');

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

                        tr.find('td:not(.button):eq(0)').html('<?php echo comboMeioPagamento('idMeioPagamento'); ?>');
                        $('#idMeioPagamento option').filter(function(){return $(this).text() == values[0]}).attr('selected', 'selected');
                        tr.find('td:not(.button):eq(1)').html('<?php echo comboFormaPagamento('idFormaPagamento', $_GET['teatro']); ?>');
                        $('#idFormaPagamento option').filter(function(){return $(this).text() == values[1]}).attr('selected', 'selected');
                        tr.find('td:not(.button):eq(2)').html('<input name="in_transacao_pdv" type="checkbox" class="inputStyle" id="in_transacao_pdv" ' + (values[2] == 'Sim' ? 'checked' : ''  )+ ' />');

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

                $('#new').button().click(function(event) {
                    event.preventDefault();

                    if(!hasNewLine()) return false;

                    var newLine = '<tr id="newLine">' +
                        '<td>' +
                        '<?php echo comboMeioPagamento('idMeioPagamento'); ?>' +
                        '</td>' +
                        '<td>'+
                        '<?php echo comboFormaPagamento('idFormaPagamento', $_GET['teatro']); ?>' +
                        '</td>' +
                        '<td class="center"><input name="in_transacao_pdv" type="checkbox" class="inputStyle" id="in_transacao_pdv" /></td>' +
                        '<td class="button"><a href="' + pagina + '?action=add">Salvar</a></td>' +
                        '<td class="button"><a href="#delete">Apagar</a></td>' +
                        '</tr>';
                    $(newLine).appendTo('#app table tbody');
                    setDatePickers();
                });

                $('#teatro').change(function() {
                    document.location = '?p=' + pagina.replace('.php', '') + '&teatro=' + $(this).val();
                });

                function validateFields() {
                    var idMeioPagamento = $('#idMeioPagamento'),
                    idFormaPagamento = $('#idFormaPagamento'),
                    valido = true;
                    if (idMeioPagamento.val() == '') {
                        idMeioPagamento.parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        idMeioPagamento.parent().removeClass('ui-state-error');
                    }
                    if (idFormaPagamento.val() == '') {
                        idFormaPagamento.parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        idFormaPagamento.parent().removeClass('ui-state-error');
                    }
                    return valido;
                }

                $('tr:not(.ui-widget-header)').hover(function() {
                    $(this).addClass('ui-state-hover');
                }, function() {
                    $(this).removeClass('ui-state-hover');
                });
            });

            function validaPgtoWebVB(web, vb)
            {
                var error = false;
                var msg = '';

                var charsWeb = web.innerHTML.slice(0,3);
                var charsVB = vb.innerHTML.slice(0,3);

                if (charsWeb != charsVB)
                {
                    error = true;
                    msg = "Relacionamento POS/PDV incorreto. Em caso de dúvida, entre em contato com o administrador.";
                }

                if ( !error )
                {
                    var webHTML = simples.replaceSpecialChars(web.innerHTML).toLowerCase();
                    var vbHTML  = simples.replaceSpecialChars(vb.innerHTML).toLowerCase();

                    var tipos = ['debito', 'credito', 'dinheiro'];
                    for(var t in tipos)
                    {
                        var tip = tipos[t];
                        if ( webHTML.search(tip) >= 0 && vbHTML.search(tip) < 0 )
                        {
                            error = true;
                            msg = 'Relacionamento incorreto. Verifique tipos de pagamento (Crédito/Débito/Dinheiro)';
                        }
                    }
                }

                return { status: !error, msg: msg };
            }
        </script>
        <style type="text/css">
            .center{text-align: center;}
        </style>
        <h2>Formas de Pagamento</h2>
        <form id="dados" name="dados" method="post">
            <p style="width:200px;"><?php echo comboTeatro('teatro', $_GET['teatro']); ?></p>
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header ">
                        <th>Meio de Pagamento</th>
                        <th>Forma de Pagamento</th>
                        <th>Transação PDV</th>
                        <th colspan="2">A&ccedil;&otilde;es</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            while ($rs = fetchResult($result)) {
                $idMeioPagamento = $rs['ID_MEIO_PAGAMENTO'];
                $idFormaPagamento = $rs['CODFORPAGTO'];
                $idBase = $rs['ID_BASE'];
            ?>
                <tr>
                    <td><?php echo comboMeioPagamento('idMeioPagamento', $idMeioPagamento, false); ?></td>
                    <td><?php echo comboFormaPagamento('idFormaPagamento', $_GET['teatro'], $idFormaPagamento, false); ?></td>
                    <td class="center"><?php echo $rs['IN_TRANSACAO_PDV'] ? 'Sim' : 'N&atilde;o'; ?></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=edit&idMeioPagamento=<?php echo $idMeioPagamento; ?>&idBase=<?php echo $_GET['teatro']; ?>">Editar</a></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=delete&idMeioPagamento=<?php echo $idMeioPagamento; ?>&idBase=<?php echo $_GET['teatro']; ?>">Apagar</a></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    <a id="new" href="#new">Novo</a>
</form>
<?php
        }
    }
?>