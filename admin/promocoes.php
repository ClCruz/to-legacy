<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 430, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);

    } else {

        $result = executeSQL($mainConnection,
                            "SELECT PC.ID_PROMOCAO_CONTROLE,
                                PC.DS_PROMOCAO,
                                CASE 
                                    WHEN PC.ID_BASE IS NOT NULL THEN B.DS_NOME_TEATRO
                                    WHEN PC.IN_TODOS_EVENTOS = 1 THEN 'Geral'
                                    ELSE '".utf8_decode('Eventos Específicos')."'
                                END AS ABRANGENCIA,
                                PC.DT_INICIO_PROMOCAO,
                                PC.DT_FIM_PROMOCAO
                            FROM MW_PROMOCAO_CONTROLE PC
                            LEFT JOIN MW_BASE B ON B.ID_BASE = PC.ID_BASE
                            WHERE PC.IN_ATIVO = 1
                            ORDER BY PC.DS_PROMOCAO");
?>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script type="text/javascript">
            $(function() {
                var pagina = '<?php echo $pagina; ?>';

                $('.button').button();

                $('#app table').on('click', 'a', function(event) {
                    event.preventDefault();

                    var $this = $(this),
                        href = $this.attr('href'),
                        id = 'id=' + $.getUrlVar('id', href),
                        tr = $this.closest('tr');

                    if (href.indexOf('?action=delete') != -1) {

                        $.confirmDialog({
                            text: 'Deseja apagar a promoção e todos os códigos restantes relacionados a ela?',
                            uiOptions: {
                                buttons: {
                                    'Sim': function() {
                                        $(this).dialog('close');
                                        $.get(href, function(data) {
                                            if (data.trim() == 'true') {
                                                tr.remove();
                                            } else {
                                                $.dialog({text: data});
                                            }
                                        });
                                    }
                                }
                            }
                        });

                    } else {

                        document.location = href;

                    }
                });

                $('tr:not(.ui-widget-header)').hover(function() {
                    $(this).addClass('ui-state-hover');
                }, function() {
                    $(this).removeClass('ui-state-hover');
                });
            });
        </script>
        <h2>Gerar Promocões</h2>
        <form id="dados" name="dados" method="post">
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header">
                        <th align="left">Descrição da Promoção</th>
                        <th align="left">Abrangência</th>
                        <th align="left">Data Início</th>
                        <th align="left">Data Fim</th>
                        <th colspan="3">A&ccedil;&otilde;es</th>
                    </tr>
                </thead>
                <tbody id="registros">
                    <?php
                        while($rs = fetchResult($result)) {
                            $id = $rs['ID_PROMOCAO_CONTROLE'];
                    ?>
                    <tr>
                        <td><?php echo utf8_encode2($rs['DS_PROMOCAO']); ?></td>
                        <td><?php echo utf8_encode2($rs['ABRANGENCIA']); ?></td>
                        <td><?php echo $rs['DT_INICIO_PROMOCAO']->format('d/m/Y'); ?></td>
                        <td><?php echo $rs['DT_FIM_PROMOCAO']->format('d/m/Y');; ?></td>

                        <td><a href="./?p=promocao&id=<?php echo $id; ?>">Editar</a></td>
                        <td><a href="./?p=codigosPromocionais&id=<?php echo $id; ?>">Consultar</a></td>
                        <td><a href="<?php echo $pagina; ?>?action=delete&id=<?php echo $id; ?>">Apagar</a></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <br/>

            <a href='./?p=promocao' class='button'>Novo</a>
        </form>
<?php
        }
    }
?>