<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 29, true)) {
    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);
    } else {
        $query = "SELECT L.ID_LOCAL_EVENTO,L.DS_LOCAL_EVENTO,L.ID_TIPO_LOCAL,L.ID_MUNICIPIO, M.ID_ESTADO,L.IN_ATIVO FROM MW_LOCAL_EVENTO L INNER JOIN MW_MUNICIPIO M ON M.ID_MUNICIPIO = L.ID_MUNICIPIO";
        $result = executeSQL($mainConnection, $query, $params);
?>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script type="text/javascript" src="../javascripts/functions.js"></script>
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
                                    tr.find('td:not(.button):eq(1)').html($('#tipolocal option:selected').text());
                                    tr.find('td:not(.button):eq(2)').html($('#idestado option:selected').text());
                                    tr.find('td:not(.button):eq(3)').html($('#idmunicipio option:selected').text());
                                    tr.find('td:not(.button):eq(4)').html($('#idativo option:selected').text());

                                    $this.text('Editar').attr('href', pagina + '?action=edit&' + id);
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

                        tr.find('td:not(.button):eq(0)').html('<input name="nome" type="text" class="inputStyle" id="nome" maxlength="50" value="' + values[0] + '" />');
                        tr.find('td:not(.button):eq(1)').html('<select id="tipolocal" name="tipolocal" class="inputStyle">'+'<?php echo comboTipoLocalOptions('tipolocal', ""); ?>'+'</select>');
                        $('#tipolocal option').filter(function(){return $(this).text() == values[1]}).prop('selected', 'selected');

                        tr.find('td:not(.button):eq(2)').html('<select id="idestado" name="idestado">'+'<?php echo comboEstadoOptions('idestado', "", true); ?>'+'</select>');
                        $('#idestado option').filter(function(){return $(this).text() == values[2]}).prop('selected', 'selected');
                        
                        tr.find('td:not(.button):eq(3)').html('<select id="idmunicipio" name="idmunicipio" class="inputStyle">'+'<?php echo comboMunicipio('idmunicipio', "", 0); ?>'+'</select>');                        
                        $('#idestado').change();
                        $('#idmunicipio option').filter(function(){return $(this).text() == values[3]}).prop('selected', 'selected');

                        tr.find('td:not(.button):eq(4)').html('<select id="idativo" name="idativo" class="inputStyle">'+'<?php echo comboAtivoOptions('idativo', "", 0); ?>'+'</select>');                        
                        $('#idativo option').filter(function(){return $(this).text() == values[4]}).prop('selected', 'selected');

                        $this.text('Salvar').attr('href', pagina + '?action=update&' + id );

                        setDatePickers();
                    }
                });

                $('#new').button().click(function(event) {
                    event.preventDefault();

                    if(!hasNewLine()) return false;

                    var newLine = '<tr id="newLine">' +
                        '<td><input name="nome" type="text" class="inputStyle" id="nome" maxlength="50" /></td>' +
                        '<td>' + '<select name="tipolocal" class="inputStyle" id="tipolocal"><?php echo comboTipoLocalOptions("tipolocal", ""); ?></select></td>' +
                        '<td>' + '<select name="idestado" class="inputStyle" id="idestado"><?php echo comboEstadoOptions("idestado", "", true); ?></select></td>' +
                        '<td>' + '<select name="idmunicipio" class="inputStyle" id="idmunicipio"><?php echo comboMunicipio("idmunicipio", $_GET["idmunicipio"], $_GET["idestado"], true); ?></select></td>' +
                        '<td>' + '<select name="idativo" class="inputStyle" id="idativo"><?php echo comboAtivo("idativo", $_GET["idativo"], true); ?></select></td>' +
                        '<td class="button"><a href="' + pagina + '?action=add">Salvar</a></td>' +
                        '</tr>';
                    $(newLine).appendTo('#app table tbody');
                    setDatePickers();
                });

                function validateFields() {
                    var campos = $(':input:not(button)'),
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
        <h2>Local do Evento (para BI)</h2>
        <form id="dados" name="dados" method="post">
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header ">
                        <th width="20%">Local</th>
                        <th width="20%">Tipo do local</th>
                        <th width="20%">Estado</th>
                        <th width="20%">Município</th>
                        <th withn="10%">Ativo</th>
                        <th style="text-align: center;" width="10%">Ações</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            while ($rs = fetchResult($result)) {
                $id = $rs["ID_LOCAL_EVENTO"];
            ?>
                <tr>
                    <td><?php echo utf8_encode2($rs["DS_LOCAL_EVENTO"]); ?></td>
                    <td><?php echo comboTipoLocal("tipolocal", $rs["ID_TIPO_LOCAL"], false); ?></td>
                    <td><?php echo comboEstado("idestado", $rs["ID_ESTADO"], true, false); ?></td>
                    <td><?php echo comboMunicipio("idmunicipio", $rs["ID_MUNICIPIO"], $rs["ID_ESTADO"], false); ?></td>
                    <td><?php echo comboAtivo("idativo", $rs["IN_ATIVO"], false); ?></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=edit&id=<?php echo $id; ?>">Editar</a></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    <a id="new" href="#new">Novo</a>
</form>
<?php
            if (sqlErrors ())
                print_r(sqlErrors());
        }
    }
?>
