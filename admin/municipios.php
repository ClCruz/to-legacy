<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 28, true)) {
    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);
    } else {
        if ($_GET["idestado"]) {
            $query = "SELECT ID_MUNICIPIO, DS_MUNICIPIO, ID_ESTADO FROM MW_MUNICIPIO WHERE ID_ESTADO = ? ORDER BY DS_MUNICIPIO";
            $params = array($_GET["idestado"]);
            $result = executeSQL($mainConnection, $query, $params);
        }
?>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script type="text/javascript">
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

                                    tr.find('td:not(.button):eq(0)').html($('#nome').val());
                                    tr.find('td:not(.button):eq(1)').html($('#idestado option:selected').text());

                                    $this.text('Editar').attr('href', pagina + '?action=edit&' + id);
                                    tr.find('td.button a:last').attr('href', pagina + '?action=delete&' + id);
                                    tr.removeAttr('id');
                                    var idestado = "'"+<?php echo $_GET["idestado"]; ?>+"'";
                                    if(idestado != ""){
                                        window.location.href="?p=municipios&idestado="+<?php echo $_GET["idestado"]; ?>+"";
                                    }
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

                        tr.find('td:not(.button):eq(0)').html('<input name="nome" type="text" class="inputStyle" id="nome" maxlength="20" value="' + values[0] + '" />');
                        tr.find('td:not(.button):eq(1)').html('<?php echo comboEstado('idestado', $_GET["idestado"], true); ?>');
                        $('#idestado').find('option[text="' + values[1] + '"]').attr('selected', 'selected');
                        $this.text('Salvar').attr('href', pagina + '?action=update&' + id +'&idestado='+ <?php echo $_GET["idestado"]; ?> );

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
                        '<td><input name="nome" type="text" class="inputStyle" id="nome" maxlength="50" /></td>' +
                        '<td>' + '<?php echo comboEstado("idestado", $_GET["idestado"], true); ?>' + '</td>' +
                        '<td class="button"><a href="' + pagina + '?action=add&idestado='+ <?php echo $_GET["idestado"]; ?> +'">Salvar</a></td>' +
                        '<td class="button"><a href="#delete">Apagar</a></td>' +
                        '</tr>';
                    $(newLine).appendTo('#app table tbody');
                    setDatePickers();
                });

                function validateFields() {
                    var campos = $(':input:not(button)'),
                    idestado = $('#idestado'),
                    valido = true;

                    if (idestado.val() == '') {
                        idestado.parent().addClass('ui-state-error');
                        valido = false;
                    } else {
                        idestado.parent().removeClass('ui-state-error');
                    }

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

                $('#idestado').change(function(){
                    if(this.value != ""){
                        window.location.href="?p=municipios&idestado="+this.value;
                    }
                })
            });
        </script>

        <h2>Municípios (para BI)</h2>
        <form id="dados" name="dados" method="post">
            <p style="width:200px;"><?php echo comboEstado("idestado", $_GET["idestado"], true) ?></p>

            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header ">
                        <th width="40%">Descrição</th>
                        <th width="40%">Estado</th>
                        <th style="text-align: center;" colspan="2" width="20%">Ações</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            while ($rs = fetchResult($result)) {
                $id = $rs["ID_MUNICIPIO"];
            ?>
                <tr>
                    <td><?php echo utf8_encode2($rs["DS_MUNICIPIO"]); ?></td>
                    <td><?php echo comboEstado("idestado", $rs["ID_ESTADO"], true, false); ?></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=edit&id=<?php echo $id; ?>">Editar</a></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=delete&id=<?php echo $id; ?>">Apagar</a></td>
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
