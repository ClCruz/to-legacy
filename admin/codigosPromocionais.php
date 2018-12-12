<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 384, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);

    } else {

        if (!$_GET['excel']) {
?>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script type="text/javascript">
            $(function() {
                var pagina = '<?php echo $pagina; ?>',
                    $cboPromocao = $('#id');

                $('.button').button({disabled: true});

                $('#app table').on('click', 'a', function(event) {
                    event.preventDefault();

                    var $this = $(this),
                        href = $this.attr('href'),
                        id = 'id=' + $.getUrlVar('id', href),
                        tr = $this.closest('tr');

                    if (href.indexOf('?action=busca') != -1) {

                        $('input[name=offset]').val($.getUrlVar('offset', href));

                        $cboPromocao.trigger('change');

                    } else if (href.indexOf('?action=exportar') != -1) {

                        document.location = pagina + '?excel=1&' + $('#dados').serialize();

                    }
                });

                $cboPromocao.on('change', function(){
                    $('a.button').button({disabled: true});

                    $.ajax({
                        url: pagina + '?action=busca&' + $('#dados').serialize()
                    }).done(function(html){
                        $('#registros').html(html);

                        if ($('#registros tr').length > 0) {
                            $('#exportar').button({disabled: false});
                        }
                    });
                });

                if ($cboPromocao.val()) {
                    $cboPromocao.trigger('change');
                }
            });
        </script>
        <?php
        } else {
            header("Content-type: application/vnd.ms-excel");
            header("Content-type: application/force-download");
            header("Content-Disposition: attachment; filename=codigos.xls");
            ?>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <style>
                .text {mso-number-format:"\@";/*force text*/}
            </style>
            <?php
        }
        ?>
        <h2>Promoções Cadastradas</h2>
        <form id="dados" name="dados" method="post">
            <input type="hidden" name="offset" value="" />
            <table>
                <tr>
                    <td>
                        <b>Promoção:</b><br/>
                        <?php
                            if ($_GET['excel']) {
                                echo comboPromocoes('id', $_GET['id'], false);
                            } else {
                                echo comboPromocoes('id', $_GET['id']);
                                echo "</td><td align='right'><a href='$pagina?action=exportar' id='exportar' class='button'>Exportar para o Excel</a></td>";
                            }
                        ?>
                    </td>
                </tr>
            </table>
            <br/>
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header">
                        <th align="left">Descrição da Promoção</th>
                        <th align="left">Código Promocional</th>
                        <th align="left">Sessão</th>
                        <th align="left">Nº Pedido</th>
                        <th align="left">CPF</th>
                    </tr>
                </thead>
                <tbody id="registros">
                    <?php
                        if ($_GET['excel']) {
                            $_GET['action'] = 'busca';
                            require('actions/' . $pagina);
                        }
                    ?>
                </tbody>
            </table>
</form>
<?php
        }
    }
?>