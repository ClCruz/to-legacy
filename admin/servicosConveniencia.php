<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

function formatNumber ($number) {
    return number_format($number, 2, ',', '');
}

function combo_tipo_valor ($name, $valor) {
    $tipos = array(
        'V' => 'R$',
        'P' => '%'
    );

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um tipo...</option>';
    foreach ($tipos as $key => $val) {
        $combo .= '<option value="' . $key . '"' . (($valor == $key) ? ' selected' : '') . '>' . (($number) ? $key : $val) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

if (acessoPermitido($mainConnection, $_SESSION['admin'], 6, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action']))
    {
        require('actions/' . $pagina);
    }
    else
    {
        if ( !isset($_GET['cartaz']) || ( isset($_GET['cartaz']) && $_GET['cartaz'] == 1 ) )
        {
            $complemento = 'HAVING CONVERT(DATE, MAX(A.dt_apresentacao), 103) >= CONVERT(DATE, GETDATE(), 103)';
            $iptCartazCheck = 'checked="checked"';
        }
        else if ( isset($_GET['cartaz']) && $_GET['cartaz'] == 0 )
        {
            $complemento = '';
            $iptCartazCheck = '';
        }

        $newResultQuery = "SELECT 
                            DISTINCT(A.id_evento), 
                            MAX(A.dt_apresentacao) AS dt_apresentacao
                            , B.ds_evento
                            , CONVERT(VARCHAR(10), C.dt_inicio_vigencia, 103) AS dt_inicio_vigencia
                            , C.in_taxa_conveniencia AS tipo
                            , C.vl_taxa_conveniencia
                            , C.vl_taxa_promocional
                            , C.vl_taxa_um_ingresso
                            , C.vl_taxa_um_ingresso_promocional
                            , C.in_taxa_por_pedido
                            , C.in_cobrar_pdv
                            , C.in_cobrar_pos
                            , CASE
                            WHEN CONVERT(CHAR(8), C.DT_INICIO_VIGENCIA, 112) >= CONVERT(CHAR(8), GETDATE(), 112)
                            THEN 1
                            ELSE 0
                            END edicao
                        FROM mw_apresentacao AS A 
                        INNER JOIN mw_evento AS B ON A.id_evento = B.id_evento
                        INNER JOIN mw_taxa_conveniencia AS C ON A.id_evento = C.id_evento
                        WHERE B.id_base = ?
                        GROUP BY A.id_evento, B.ds_evento
                            , C.dt_inicio_vigencia
                            , C.in_taxa_conveniencia
                            , C.vl_taxa_conveniencia
                            , C.vl_taxa_promocional
                            , C.vl_taxa_um_ingresso
                            , C.vl_taxa_um_ingresso_promocional
                            , C.in_taxa_por_pedido
                            , C.in_cobrar_pdv
                            , C.in_cobrar_pos 
                        $complemento ORDER BY B.ds_evento";
        
        $newResult = fetchAssoc( executeSQL($mainConnection, $newResultQuery, array($_GET['teatro'])) ) ;
//        echo  '<pre>';
//            print_r($newResult);
//        echo  '</pre>';
?>

        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script>
            var conv =
            {
                checkCartaz: null,
                urlParamns: null,

                init: function () {
                    this.setVars();
                    this.getUrlParamns();
                    this.cfgBtnEmCartaz();
                },

                setVars: function ()
                {
                    this.checkCartaz = document.getElementById('emcartaz');
                    //console.log(this.checkCartaz);
                    //console.log(this.checkCartaz.attributes.id.nodeValue);
                },

                cfgBtnEmCartaz: function ()
                {
                    conv.checkCartaz.onclick=function ()
                    {
                        var newsrc = ( this.checked ) ? '1' : '0';
                        conv.setParamns('cartaz', newsrc);
                        conv.goTo();
                    }
                },

                /*
                 * Pega os parametros GET e envia para um objeto Javascript
                 * */
                getUrlParamns: function()
                {
                    var paramns = document.location.search;
                    paramns = paramns.replace('?','&');
                    paramns = paramns.split('&');
                    var obj = {};
                    var i =0;
                    for(x in paramns)
                    {
                        var item = paramns[x];
                        if (item != '')
                        {
                            item = item.split('=');
                            eval("obj['"+item[0]+"'] = {}");
                            eval("obj['"+item[0]+"'].value = '"+item[1]+"';");
                            i++;
                        }
                    }
                    conv.urlParamns = obj;
                },

                /*
                 * Cria ou altera novos parametros GET que serão utilizados depois em goTo()
                 * */
                setParamns: function(paramn, value)
                {
                    if (eval('conv.urlParamns.'+paramn))
                    {
                        eval('conv.urlParamns.'+paramn+'.value = "'+value+'"');
                    }
                    else
                    {
                        eval("conv.urlParamns['"+paramn+"'] = {}");
                        eval("conv.urlParamns['"+paramn+"'].value = '"+value+"';");
                    }
                },

                goTo: function()
                {
                    var i 	= 0;
                    var str = '';
                    for(x in conv.urlParamns)
                    {
                        str += ( i == 0 ) ? '?' : '&';
                        str += x+'='+conv.urlParamns[x].value;
                        i++;
                    }

                    document.location = document.location.origin + document.location.pathname + str;
                }
            };


            $(function() {
                var pagina = '<?php echo $pagina; ?>';
                conv.init();

                $('#app table').delegate('a', 'click', function(event) {
                    event.preventDefault();

                    var $this = $(this),
                    href = $this.attr('href'),
                    id = 'idEvento=' + $.getUrlVar('idEvento', href) + '&data=' + $.getUrlVar('data', href),
                    tr = $this.closest('tr');

                    if (href.indexOf('?action=add') != -1 || href.indexOf('?action=update') != -1) {
                        if (!validateFields()) return false;

                        $.ajax({
                            url: href,
                            type: 'post',
                            data: $('#dados').serialize(),
                            success: function(data) {
                                if (data.substr(0, 4) == 'true') {
                                    var id = $.serializeUrlVars(data);

                                    tr.find('td:not(.button):eq(0)').html($('#idEvento option:selected').text());
                                    tr.find('td:not(.button):eq(1)').html($('#data').val());
                                    tr.find('td:not(.button):eq(2)').html($('#tipo option:selected').text());
                                    tr.find('td:not(.button):eq(3)').html($('#valor').val());
                                    tr.find('td:not(.button):eq(4)').html($('#valor2').val());
                                    tr.find('td:not(.button):eq(5)').html($('#valor3').val());
                                    tr.find('td:not(.button):eq(6)').html($('#valor4').val());
                                    tr.find('td:not(.button):eq(7)').html($('#cobrarPorPedido').is(':checked') ? 'sim' : 'n&atilde;o');
                                    tr.find('td:not(.button):eq(8)').html($('#cobrarNoPDV').is(':checked') ? 'sim' : 'n&atilde;o');
                                    tr.find('td:not(.button):eq(9)').html($('#cobrarNoPOS').is(':checked') ? 'sim' : 'n&atilde;o');

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

                        tr.find('td:not(.button):eq(0)').html('<?php echo comboEvento('idEvento', $_GET['teatro']); ?>');
			             $('#idEvento option').filter(function(){return $(this).text() == values[0]}).prop('selected', 'selected');
                        tr.find('td:not(.button):eq(1)').html('<input name="data" type="text" class="datePicker inputStyle" id="data" maxlength="10" value="' + values[1] + '" readonly>');
                        tr.find('td:not(.button):eq(2)').html('<?php echo combo_tipo_valor('tipo'); ?>');
                         $('#tipo option').filter(function(){return $(this).text() == values[2]}).prop('selected', 'selected');
                        tr.find('td:not(.button):eq(3)').html('<input name="valor" type="text" class="inputStyle" id="valor" maxlength="6" value="' + values[3] + '" >');
                        tr.find('td:not(.button):eq(4)').html('<input name="valor2" type="text" class="inputStyle" id="valor2" maxlength="6" value="' + values[4] + '" >');
                        tr.find('td:not(.button):eq(5)').html('<input name="valor3" type="text" class="inputStyle" id="valor3" maxlength="6" value="' + values[5] + '" >');
                        tr.find('td:not(.button):eq(6)').html('<input name="valor4" type="text" class="inputStyle" id="valor4" maxlength="6" value="' + values[6] + '" >');
                        tr.find('td:not(.button):eq(7)').html('<input name="cobrarPorPedido" type="checkbox" class="inputStyle" id="cobrarPorPedido" ' + (values[7] == 'sim' ? 'checked' : ''  )+ ' />');
                        tr.find('td:not(.button):eq(8)').html('<input name="cobrarNoPDV" type="checkbox" class="inputStyle" id="cobrarNoPDV" ' + (values[8] == 'sim' ? 'checked' : ''  )+ ' />');
                        tr.find('td:not(.button):eq(9)').html('<input name="cobrarNoPOS" type="checkbox" class="inputStyle" id="cobrarNoPOS" ' + (values[9] == 'sim' ? 'checked' : ''  )+ ' />');

                        $this.text('Salvar').attr('href', pagina + '?action=update&' + id);

                        setDatePickers();
                        $('#app table #cobrarPorPedido').trigger('change');

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
                        '<?php echo comboEvento('idEvento', $_GET['teatro'], "", array("emcartaz" => true)); ?>' +
                        '</td>' +
                        '<td><input name="data" type="text" class="datePicker inputStyle" id="data" maxlength="10" readonly></td>' +
                        '<td><?php echo combo_tipo_valor('tipo'); ?></td>' +
                        '<td><input name="valor" type="text" class="number inputStyle" id="valor" maxlength="6" ></td>' +
                        '<td><input name="valor2" type="text" class="number inputStyle" id="valor2" maxlength="6" ></td>' +
                        '<td><input name="valor3" type="text" class="number inputStyle" id="valor3" maxlength="6" ></td>' +
                        '<td><input name="valor4" type="text" class="number inputStyle" id="valor4" maxlength="6" ></td>' +
                        '<td><input name="cobrarPorPedido" type="checkbox" class="inputStyle" id="cobrarPorPedido" /></td>' +
                        '<td><input name="cobrarNoPDV" type="checkbox" class="inputStyle" id="cobrarNoPDV" /></td>' +
                        '<td><input name="cobrarNoPOS" type="checkbox" class="inputStyle" id="cobrarNoPOS" /></td>' +
                        '<td class="button"><a href="' + pagina + '?action=add">Salvar</a></td>' +
                        '<td class="button"><a href="#delete">Apagar</a></td>' +
                        '</tr>';
                    $(newLine).appendTo('#app table tbody');
                    setDatePickers();
                    $('.datePicker').datepicker('option', 'minDate', 0);
                });

                $('#teatro').change(function() {
                    document.location = '?p=' + pagina.replace('.php', '') + '&teatro=' + $(this).val();
                });

                $('#enviarvalorPorPedido').button().click(function(){
                    $.confirmDialog({
                        text: 'Somente os registros marcados como "Cobrar por Pedido" e que ainda não estão em vigência, ou entrarão em vigência hoje, serão alterados.</br></br>Deseja continuar?',
                        uiOptions: {
                            buttons: {
                                'Sim': function() {
                                    $.ajax({
                                        url: pagina + '?action=updateAll',
                                        type: 'post',
                                        data: $('#dados').serialize(),
                                        success: function(data) {
                                            if (data.substr(0, 4) == 'true') {
                                                document.location = document.location;
                                            } else {
                                                $.dialog({text: data});
                                            }
                                        }
                                    });
                                }
                            }
                        }
                    });
                });

                $('#app table').on('change', '#cobrarPorPedido', function(){
                    if ($(this).is(':checked')) {
                        $('#valor2').prop('disabled', true);
                        $('#valor4').prop('disabled', true);

                        $('#valor').on('change', function(){
                            $('#valor2').val($(this).val());
                        }).trigger('change');
                        $('#valor3').on('change', function(){
                            $('#valor4').val($(this).val());
                        }).trigger('change');
                    } else {
                        $('#valor, #valor3').off('change');

                        $('#valor2').prop('disabled', false);
                        $('#valor4').prop('disabled', false);
                    }
                });

                function validateFields() {
                    var idEvento = $('#idEvento'),
                    data = $('#data'),
                    valor = $('#valor'),
                    valor2 = $('#valor2'),
                    valor3 = $('#valor3'),
                    valor4 = $('#valor4'),
                    tipo = $('#tipo'),
                    valido = true;
                    if (idEvento.val() == '') {
                        idEvento.parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        idEvento.parent().removeClass('ui-state-error');
                    }
                    if (data.val() == '') {
                        data.parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        data.parent().removeClass('ui-state-error');
                    }
                    if (tipo.val() == '') {
                        tipo.parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        tipo.parent().removeClass('ui-state-error');
                    }
                    if (valor.val() == '') {
                        valor.parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        valor.parent().removeClass('ui-state-error');
                    }
                    if (valor2.val() == '') {
                        valor2.parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        valor2.parent().removeClass('ui-state-error');
                    }
                    if (valor3.val() == '') {
                        valor3.parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        valor3.parent().removeClass('ui-state-error');
                    }
                    if (valor4.val() == '') {
                        valor4.parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        valor4.parent().removeClass('ui-state-error');
                    }

                    return valido;
                }
            });
        </script>
        <style>
            div.emcartaz input {
                width: auto;
            }

            input {
                width: 75px;
            }

            div.emcartaz{
                text-align: left;
            }
        </style>
        <h2>Valor do Servi&ccedil;o</h2>
        <form id="dados" name="dados" method="post">
            <p style="width:200px;"><?php echo comboTeatro('teatro', $_GET['teatro']); ?></p>

            <div class="emcartaz">
                <label for="emcartaz">Em Cartaz</label>
                <input id="emcartaz" type="checkbox" name="eventoativo" value="1" <?php echo $iptCartazCheck ?> />
            </div>

            <table class="ui-widget ui-widget-content">
                <thead>

                    <?php if ($_GET['teatro']): ?>
                    <tr class="ui-widget-header ">
                        <th colspan="5" align="right">Valor por compra:</th>
                        <th colspan="7">
                            R$ <input type="text" name="valorPorPedido" value="<?php echo $_GET['valorPorPedido']; ?>" />
                            <input type="button" id="enviarvalorPorPedido" value="Alterar" />
                        </th>
                    </tr>
                    <?php endif; ?>

                    <tr class="ui-widget-header ">
                        <th>Evento</th>
                        <th>In&iacute;cio de<br/>Vig&ecirc;ncia</th>
                        <th>Tipo</th>
                        <th>Normal</th>
                        <th>Promocional</th>
                        <th>Um Ingresso</th>
                        <th>Um Ingresso<br/>Promocional</th>
                        <th>Cobrar<br/>por Pedido</th>
                        <th>Cobrar<br/>no PDV</th>
                        <th>Cobrar<br/>no POS</th>
                        <th colspan="2">A&ccedil;&otilde;es</th>
                    </tr>
                </thead>
                <tbody>
            <?php
              foreach ($newResult as $rs):
                  $idEvento = utf8_encode2($rs['ds_evento']);
                  $data = $rs['dt_inicio_vigencia'];
            ?>
            <tr>
                <td><?php echo $idEvento; ?></td>
                <td><?php echo $data; ?></td>
                <td><?php echo $rs['tipo'] == 'V' ? 'R$' : '%' ; ?></td>
                <td><?php echo formatNumber($rs['vl_taxa_conveniencia']); ?></td>
                <td><?php echo formatNumber($rs['vl_taxa_promocional']); ?></td>
                <td><?php echo formatNumber($rs['vl_taxa_um_ingresso']); ?></td>
                <td><?php echo formatNumber($rs['vl_taxa_um_ingresso_promocional']); ?></td>
                <td><?php echo $rs['in_taxa_por_pedido'] == 'S' ? 'sim' : 'n&atilde;o'; ?></td>
                <td><?php echo $rs['in_cobrar_pdv'] == 'S' ? 'sim' : 'não'; ?></td>
                <td><?php echo $rs['in_cobrar_pos'] == 'S' ? 'sim' : 'não'; ?></td>

            <?php if ($rs['edicao']): ?>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=edit&idEvento=<?php echo $idEvento ?>&data=<?php echo $data; ?>">Editar</a></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=delete&idEvento=<?php echo $idEvento ?>&data=<?php echo $data; ?>">Apagar</a></td>
            <?php else: ?>
                <td colspan="2">&nbsp;</td>
            <?php endif; ?>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    <a id="new" href="#new">Novo</a>
</form>
<?php
        }
    }
?>
