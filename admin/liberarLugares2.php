<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');

$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 500, true)) {

    $pagina = basename(__FILE__);    

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);

    } else {

        $conn = getConnection($_POST['local']);

        $query = "SELECT 
                        PR.ID_PACOTE
                        ,E.DS_EVENTO COLLATE SQL_Latin1_General_CP1_CI_AS AS DS_PACOTE
                        ,PR.IN_ANO_TEMPORADA
                        ,PR.ID_CADEIRA
                        ,ISNULL(PR.DS_LOCALIZACAO,'') COLLATE SQL_Latin1_General_CP1_CI_AS AS DS_CADEIRA
                        ,TA.VALPECA AS VL_PACOTE
                        ,TS.NOMSETOR COLLATE SQL_Latin1_General_CP1_CI_AS AS DS_SETOR
                        ,PR.IN_STATUS_RESERVA
                        ,PR.ID_CLIENTE
                    FROM CI_MIDDLEWAY..MW_PACOTE_RESERVA PR
                    INNER JOIN CI_MIDDLEWAY..MW_PACOTE P ON P.ID_PACOTE = PR.ID_PACOTE
                    INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                    INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
                    INNER JOIN CI_MIDDLEWAY..MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                    INNER JOIN CI_MIDDLEWAY..MW_CLIENTE C ON C.ID_CLIENTE  = PR.ID_CLIENTE
                    INNER JOIN CI_MIDDLEWAY..MW_BASE B ON B.ID_CLIENTE  = C.ID_CLIENTE AND B.ID_BASE = E.ID_BASE
                    INNER JOIN TABSALDETALHE TSD ON TSD.INDICE = PR.ID_CADEIRA
                    INNER JOIN TABSETOR TS ON TS.CODSALA = TSD.CODSALA AND TS.CODSETOR = TSD.CODSETOR
                    INNER JOIN TABAPRESENTACAO TA ON TA.CODAPRESENTACAO = A2.CODAPRESENTACAO AND TA.CODSALA= TS.CODSALA
                    WHERE PR.ID_PACOTE = ? AND PR.IN_ANO_TEMPORADA = ? AND IN_STATUS_RESERVA = 'R'
                    ORDER BY E.DS_EVENTO, TS.NOMSETOR, PR.DS_LOCALIZACAO";
        $params = array($_POST['pacote_combo'], $_POST['ano']);
        $result = executeSQL($conn, $query, $params);

        $situacao = array(
            'A' => "Aguardando ação do Assinante",
            'S' => "Solicitado troca",
            'T' => "Troca efetuada",
            'C' => "Assinatura cancelada",
            'R' => "Assinatura renovada"
        );

        $rsAux = executeSQL($mainConnection, "SELECT DS_NOME + ' ' + DS_SOBRENOME as DS_NOME_SOBRENOME FROM MW_CLIENTE WHERE CD_CPF = ?", array($_POST['cpf']), true);

?>
<style type="text/css">
    .coluna-header{width: 200px;}
    .tb-form{margin-left: 40px; width: 609px; padding: 0px;}
    form select{min-width: 200px;}
    table.ui-widget tbody tr td input {width: 100%;}
</style>
<script type="text/javascript">
    var pagina = '<?php echo $pagina; ?>';
    $(document).ready(function(){
        $('#enviar').button();
        $('#cpf').onlyNumbers();

        $('#liberar').button().on('click', function(e){
            if ($('input[type=checkbox]:checked').length > 0) {
                var msg = 'Tem certeza que deseja liberar o(s) ' + 
                            $('table.ui-widget tbody input[type=checkbox]:checked').parent('td').parent('tr').length +
                            ' lugar(es) selecionado(s)?<br/><br/>Atenção: certifique-se que os lugares selecionados ' +
                            'realmente não foram vendidos, pois os mesmos serão liberados para compra no site, deseja continuar?'

                $.dialog({
                    title: 'Confirmação...',
                    text: msg,
                    uiOptions: {
                        buttons: {
                            'Ok': function() {
                                $.ajax({
                                    url: pagina + '?action=liberar',
                                    type: 'post',
                                    data: $('#dados').serialize(),
                                    success: function(data) {
                                        if (data == 'ok') {
                                            $.dialog({
                                                title: 'Sucesso',
                                                text: 'Lugares liberados com sucesso.',
                                                uiOptions: {
                                                    buttons: {
                                                        'Ok': function() {
                                                            $('#enviar').trigger('click');
                                                        }
                                                    }
                                                }
                                            });
                                        } else {
                                            $.dialog({
                                                title: 'Aviso...',
                                                text: data,
                                                uiOptions: {
                                                    buttons: {
                                                        'Ok': function() {
                                                            $('#enviar').trigger('click');
                                                        }
                                                    }
                                                }
                                            });
                                        }
                                    }
                                });
                                $(this).dialog('close');
                            },
                            'Cancelar': function() {
                                $(this).dialog('close');
                            }
                        }
                    }
                });
            } else {
                $.dialog({text: 'Nenhum lugar selecionado.'});
            }
        });

        $('#local').on('change', obterComboPacotes);
        $('#ano').on('keyup', obterComboPacotes);

        function obterComboPacotes(){
            if ($('#ano').val().length != 4 || $('#local').val() == '') return;

            $.ajax({
                url: pagina + '?action=load_pacotes',
                type: 'post',
                data: $('#dados').serialize()+'&pacote_combo=<?php echo $_POST['pacote_combo']; ?>',
                success: function(data) {
                    $('#container_pacotes').html(data);
                }
            });
        }

        if ($('#ano').val() && $('#local').val()) obterComboPacotes();

        $('#dados').on('submit', function(){
            var valido = true;

            if ($('#local').val() == '') {
                $('#local').parent().addClass('ui-state-error');
                valido = false;
            } else {
                $('#local').parent().removeClass('ui-state-error');
            }

            if ($('#pacote_combo').val() == '') {
                $('#pacote_combo').parent().addClass('ui-state-error');
                valido = false;
            } else {
                $('#pacote_combo').parent().removeClass('ui-state-error');
            }

            if ($('#ano').val() == '') {
                $('#ano').parent().addClass('ui-state-error');
                valido = false;
            } else {
                $('#ano').parent().removeClass('ui-state-error');
            }

            return valido;
        });

        $('input[type=checkbox]:first').on('change', function(){
            $('input[type=checkbox]').prop('checked', $(this).prop('checked'));
        });

        $('input[type=checkbox]:not(:first)').on('change', function(){
            $(this).parent('td').find('input[type=checkbox]').prop('checked', $(this).prop('checked'));

            if ($('input[type=checkbox]:not(:first):not(:checked)').length) {
                $('input[type=checkbox]:first').prop('checked', false)
            } else {
                $('input[type=checkbox]:first').prop('checked', true)
            }
        });

        $('table.ui-widget tr:not(.ui-widget-header, .estorno)').hover(function() {
            $(this).addClass('ui-state-hover').next('.estorno').addClass('ui-state-hover');
        }, function() {
            $(this).removeClass('ui-state-hover').next('.estorno').removeClass('ui-state-hover');
        }).find('td:not(:first)').on('click', function(){
            $(this).parent().find('input[type=checkbox]:last').trigger('click');
        });
    });
</script>
<h2>Liberar lugar no site após período de Assinaturas</h2>
<form id="dados" name="dados" method="post">
    <table class="tb-form">
        <tr>
            <td class="coluna-header"><strong>Local:</strong></td>
            <td>
                <?php echo comboTeatroPorUsuario('local', $_SESSION['admin'], $_POST['local']); ?>
            </td>
        </tr>  
        <tr>
            <td class="coluna-header"><strong>Temporada (Ano):</strong></td>
            <td>
                <input type="text" id="ano"  name="ano" value="<?php echo $_POST['ano']; ?>" maxlength="4" />
            </td>
        </tr>
        <tr>
            <td class="coluna-header"><strong>Pacote:</strong></td>
            <td id="container_pacotes">
                <select><option>Selecione um local...</option></select>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <br />
                <input id="enviar" type="submit" class="button" value="Exibir Lugares" />&nbsp;
                <input id="liberar" type="button" class="button" value="Liberar Lugares" />
            </td>
        </tr>
        <tr>
            <td></td>
        </tr>
    </table>

    <table class="ui-widget ui-widget-content">
        <thead>
            <tr class="ui-widget-header ">
                <th><label><input type="checkbox" /> Todos</label></th>
                <th>Pacote</th>
                <th>Temporada</th>
                <th>Setor</th>
                <th>Lugar</th>
                <th>Preço</th>
                <th>Situação</th>
            </tr>
        </thead>
        <tbody>
            <?php
                while($rs = fetchResult($result)) {
            ?>
            <tr>
                <td>
                    <input type="checkbox" name="pacote[]" value="<?php echo $rs['ID_PACOTE']; ?>" />
                    <input type="checkbox" name="cliente[]" value="<?php echo $rs['ID_CLIENTE']; ?>" class="ui-helper-hidden" />
                    <input type="checkbox" name="cadeira[]" value="<?php echo $rs['ID_CADEIRA']; ?>" class="ui-helper-hidden" />
                </td>
                <td><?php echo utf8_encode2($rs['DS_PACOTE']); ?></td>
                <td><?php echo $rs['IN_ANO_TEMPORADA']; ?></td>
                <td><?php echo utf8_encode2($rs['DS_SETOR']); ?></td>
                <td><?php echo utf8_encode2($rs['DS_CADEIRA']); ?></td>
                <td><?php echo number_format($rs['VL_PACOTE'], 2, ',', ''); ?></td>
                <td><?php echo $situacao[$rs['IN_STATUS_RESERVA']]; ?></td>
            </tr>
            <?php
                }
            ?>
        </tbody>
    </table>
</form>
<br/>
<?php
    }
}
?>