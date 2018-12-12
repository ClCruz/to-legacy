<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 420, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);

    } else {

        if (!$_GET['excel']) {
?>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script type="text/javascript">
            $(function() {
                var pagina = '<?php echo $pagina; ?>';

                $('#exportar').button().on('click', function(e){
                    e.preventDefault();

                    if (validacao()) {
                        document.location = pagina + '?excel=1&' + $('#dados').serialize();
                    }
                });

                $('#situacao option:first').attr('value', 'TODOS').text('< TODOS >');
                
                setDatePickers();
                $('input[name="dtInicial"]').datepicker("option", "minDate", undefined).on('change', function() {
                    $('input[name="dtFinal"]').datepicker("option", "minDate", $(this).val()).trigger('change');
                });

                function validacao() {
                    var valido = true,
                        campos = $(':input');

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
        <?php
        } else {
            header("Content-type: application/vnd.ms-excel");
            header("Content-type: application/force-download");
            header("Content-Disposition: attachment; filename=relatorioItemPedidos.xls");
            ?>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <style>
                .text {mso-number-format:"\@";/*force text*/}
                .money {mso-number-format:"0\.00";/*force 2 decimals*/}
            </style>
            <?php
        }
        ?>
        <h2>Exportação de Pedidos</h2>
        <form id="dados" name="dados" method="post">
            <table>
                <tr>
                    <td>
                        <b>Data Inicial:</b><br/>
                        <?php
                            if ($_GET['excel']) {
                                echo $_GET['dtInicial'];
                            } else {
                                ?><input type="text" name="dtInicial" class="datePicker" value="<?php echo $_GET['dtInicial']; ?>" /><?php
                            }
                        ?>
                    </td>
                    <td>
                        <b>Data Final:</b><br/>
                        <?php
                            if ($_GET['excel']) {
                                echo $_GET['dtFinal'];
                            } else {
                                ?><input type="text" name="dtFinal" class="datePicker" value="<?php echo $_GET['dtFinal']; ?>" /><?php
                            }
                        ?>
                    </td>
                    <td>
                        <b>Situação:</b><br/>
                        <?php echo $_GET["situacao"] == 'TODOS' ? $_GET["situacao"] : combosituacao('situacao', $_GET["situacao"], !$_GET['excel']); ?>
                    </td>
                    <td style="vertical-align: bottom;">
                        <?php if (!$_GET['excel']) { ?>
                            <a id="exportar" class="button" href="<?php echo $pagina; ?>?action=exportar">Exportar para Excel</a>
                        <?php } ?>
                    </td>
                </tr>
            </table>
            <br/>
            <?php if ($_GET['excel']) { ?>
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header">
                        <th align="left">Nº Pedido</th>
                        <th align="left">Canal de Venda</th>
                        <th align="left">Operador</th>
                        <th align="left">Local do Evento</th>
                        <th align="left">Evento</th>
                        <th align="left">Data do Evento</th>
                        <th align="left">Horário</th>
                        <th align="left">Data da Compra</th>
                        <th align="left">Horário</th>
                        <th align="left">Status/Situação da Compra</th>
                        <th align="left">Rede Cartao</th>
                        <th align="left">Bandeira</th>
                        <th align="left">Código de Autorização</th>
                        <th align="left">Forma de Pagamento</th>
                        <th align="left">Parcelas</th>
                        <th align="left">Qtde. Lugares</th>
                        <th align="left">Valor</th>
                        <th align="left">Valor Serviço</th>
                        <th align="left">Valor Total da Compra</th>
                        <th align="left">Cliente</th>
                        <th align="left">CPF</th>
                        <th align="left">Cartão  Cred.</th>
                        <th align="left">Gênero</th>
                        <th align="left">Nome do Titular do Cartão</th>
                    </tr>
                </thead>
                <tbody id="registros">
                    <?php
                        $_GET['action'] = 'busca';
                        require('actions/' . $pagina);
                    ?>
                </tbody>
            </table>
            <?php } ?>
</form>
<?php
        }
    }
?>