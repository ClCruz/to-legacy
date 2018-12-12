<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 400, true)) {

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
                    $cboTeatro = $('#cboTeatro');

                $('#txtTemporada').onlyNumbers();

                $('#exportar').button().on('click', function(e){
                    e.preventDefault();

                    if (validacao()) {
                        document.location = pagina + '?excel=1&' + $('#dados').serialize();
                    }
                });

                $.ajax({
                    url: pagina + '?action=cboTeatro&cboTeatro=<?php echo $_GET['cboTeatro']; ?>'
                }).done(function(html){
                    $cboTeatro.html(html).trigger('change');
                });

                function validacao() {
                    var valido = true,
                        campos = $('#dados :input');

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
            header("Content-Disposition: attachment; filename=informacoesAssinantes.xls");
            ?><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><?php
        }
        ?>
        <style>
            table tr td {mso-number-format:"\@";/*force text*/}
        </style>
        <h2>Assinantes-Exportação de dados</h2>
        <form id="dados" name="dados" method="post">
            <table style="width:50%;">
                <tr>
                    <td>
                        <b>Local:</b><br/>
                        <?php
                            if ($_GET['excel']) {
                                $_GET['action'] = 'cboTeatro';
                                require('actions/' . $pagina);
                            } else {
                                ?><select name="cboTeatro" id="cboTeatro"><option value="">Carregando...</option></select><?php
                            }
                        ?>
                    </td>
                    <td>
                        <b>Temporada:</b><br/>
                        <?php
                            if ($_GET['excel']) {
                                echo $_GET['txtTemporada'];
                            } else {
                                ?><input name="txtTemporada" id="txtTemporada" maxlength="4" value="<?php echo $_GET['txtTemporada']; ?>" /><?php
                            }
                        ?>
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
                        <th align="left">NOME</th>
                        <th align="left">ENDERECO</th>
                        <th align="left">COMPL_ENDERECO</th>
                        <th align="left">BAIRRO</th>
                        <th align="left">CIDADE</th>
                        <th align="left">ESTADO</th>
                        <th align="left">CEP</th>
                        <th align="left">DDD_TELEFONE</th>
                        <th align="left">TELEFONE</th>
                        <th align="left">DDD_CELULAR</th>
                        <th align="left">CELULAR</th>
                        <th align="left">EMAIL</th>
                        <th align="left">PACOTE</th>
                        <th align="left">SETOR</th>
                        <th align="left">LOCALIZACAO</th>
                        <th align="left">TIPO_BILHETE</th>
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