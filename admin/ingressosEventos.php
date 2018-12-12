<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 217, true)) {
    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);
    } else {
        $result = executeSQL($mainConnection, 'SELECT ID_EVENTO, DS_EVENTO, IN_ENTREGA_INGRESSO FROM MW_EVENTO WHERE IN_ATIVO = 1 ORDER BY DS_EVENTO');
?>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script type="text/javascript">
            $(function() {
                var pagina = '<?php echo $pagina; ?>';

                $('#app table').delegate('a', 'click', function(event) {
                    event.preventDefault();

                    var $this = $(this),
                    href = $this.attr('href'),
                    id = 'codevento=' + $.getUrlVar('codevento', href),
                    tr = $this.closest('tr');

                    if (href.indexOf('?action=add') != -1 || href.indexOf('?action=update') != -1) {                        
                        var ativo = false;
                        if($('#ativo').is(':checked')){ ativo = 1; } else{ ativo = 0; }
                        
                        $.ajax({
                            url: href,        
                            type: 'post',        
                            data: {'ativo': ativo},
                            success: function(data) {        
                                if (data.substr(0, 4) == 'true') {        
                                    var id = $.serializeUrlVars(data);                                                   
                                    tr.find('td:not(.button):eq(1)').html($('#ativo').is(':checked') ? 'Sim' : 'N&atilde;o');

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
                        tr.find('td:not(.button):eq(1)').html('<input name="ativo" type="checkbox" class="inputStyle" id="ativo" ' + (values[1] == 'Sim' ? 'checked' : ''  )+ ' />');

                        $this.text('Salvar').attr('href', pagina + '?action=update&' + id);                        
                    }
                });

                $('tr:not(.ui-widget-header)').hover(function() {
                    $(this).addClass('ui-state-hover');
                }, function() {
                    $(this).removeClass('ui-state-hover');
                });
            });
        </script>
        <h2>Habilitar o Serviço de Entrega por Evento</h2>
        <form id="dados" name="dados" method="post" action="">
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header ">
                        <th width="30%">Eventos</th>
                        <th width="50%">Entrega de Ingresso Permitida</th>                        
                        <th width="10%">A&ccedil;&otilde;es</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            while ($rs = fetchResult($result)) {
                $id = $rs['ID_EVENTO'];
            ?>
                <tr>
                    <td><?php echo utf8_encode2($rs['DS_EVENTO']); ?></td>
                    <td><?php echo ($rs['IN_ENTREGA_INGRESSO'] != 1 || is_null($rs['IN_ENTREGA_INGRESSO']))  ? 'N&atilde;o' : 'Sim'; ?></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=edit&codevento=<?php echo $id; ?>">Editar</a></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table><br />
</form>
<?php
        }
    }
?>