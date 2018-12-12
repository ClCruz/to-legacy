<?php
require_once('../settings/functions.php');
require_once('../settings/Paginator.php');
require_once('../settings/Utils.php');
include('../settings/Log.class.php');

$mainConnection = mainConnection();
session_start();

function getTotal($conn, $params) {
    $query = "SELECT PR.ID_PACOTE
                        ,E.DS_EVENTO COLLATE SQL_Latin1_General_CP1_CI_AS AS DS_PACOTE
                        ,PR.IN_ANO_TEMPORADA
                        ,PR.ID_CADEIRA
                        ,ISNULL(PR.DS_LOCALIZACAO,'') COLLATE SQL_Latin1_General_CP1_CI_AS AS DS_CADEIRA
                        ,TA.VALPECA AS VL_PACOTE
                        ,TS.NOMSETOR COLLATE SQL_Latin1_General_CP1_CI_AS AS DS_SETOR
                        ,PR.IN_STATUS_RESERVA
                        ,PR.ID_CLIENTE
                        ,C.DS_NOME + ' ' + C.DS_SOBRENOME AS DS_NOME_SOBRENOME
                FROM CI_MIDDLEWAY..MW_PACOTE_RESERVA PR
                INNER JOIN CI_MIDDLEWAY..MW_PACOTE P ON P.ID_PACOTE = PR.ID_PACOTE
                INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
                INNER JOIN CI_MIDDLEWAY..MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                INNER JOIN CI_MIDDLEWAY..MW_CLIENTE C ON C.ID_CLIENTE  = PR.ID_CLIENTE
                INNER JOIN TABSALDETALHE TSD ON TSD.INDICE = PR.ID_CADEIRA
                INNER JOIN TABSETOR TS ON TS.CODSALA = TSD.CODSALA AND TS.CODSETOR = TSD.CODSETOR
                INNER JOIN TABAPRESENTACAO TA ON TA.CODAPRESENTACAO = A2.CODAPRESENTACAO AND TA.CODSALA= TS.CODSALA
                WHERE PR.ID_PACOTE = ? AND PR.IN_ANO_TEMPORADA = ?
                AND IN_STATUS_RESERVA IN ('A', 'S')";

    $total = numRows($conn, $query, $params);
    if (!sqlErrors())
        return $total;
    else {
        return 0;
    }
}

function getCliente($conn) {
    $query = "SELECT CODIGO, NOME FROM TABCLIENTE WHERE STACLIENTE = 'A' AND ASSINATURA = 1";
    $cliente = executeSQL($conn, $query, array(), true);
    return $cliente;
}

function setCliente($conn) {
    $query = "SP_CLI_INS002 'Cliente Assinatura Reserva', NULL, NULL, NULL, NULL, NULL, NULL, 1";
    $cliente = executeSQL($conn, $query, array(), true);
    return $cliente;
}

if (acessoPermitido($mainConnection, $_SESSION['admin'], 381, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {
        require('actions/' . $pagina);
    } else {
        $conn = getConnection($_GET['local']);
        $params = array($_GET['pacote_combo'], $_GET['ano']);
        $total = getTotal($conn, $params);
        $total_reg = (!isset($_GET["controle"])) ? 10 : $_GET["controle"];
        $offset = (isset($_GET["offset"])) ? $_GET["offset"] : 1;
        $final = ($offset + $total_reg) - 1;
        $between = "WHERE LINHA BETWEEN " . $offset . " AND " . $final . "";
        $row_number = "ROW_NUMBER() OVER(ORDER BY E.DS_EVENTO) AS LINHA";

        $query = "WITH RESULTADO AS (
                    SELECT
                        PR.ID_PACOTE
                        ,E.DS_EVENTO COLLATE SQL_Latin1_General_CP1_CI_AS AS DS_PACOTE
                        ," . $row_number . "
                        ,PR.IN_ANO_TEMPORADA
                        ,PR.ID_CADEIRA
                        ,ISNULL(PR.DS_LOCALIZACAO,'') COLLATE SQL_Latin1_General_CP1_CI_AS AS DS_CADEIRA
                        ,TA.VALPECA AS VL_PACOTE
                        ,TS.NOMSETOR COLLATE SQL_Latin1_General_CP1_CI_AS AS DS_SETOR
                        ,PR.IN_STATUS_RESERVA
                        ,PR.ID_CLIENTE
                        ,C.DS_NOME + ' ' + C.DS_SOBRENOME AS DS_NOME_SOBRENOME
                    FROM CI_MIDDLEWAY..MW_PACOTE_RESERVA PR
                    INNER JOIN CI_MIDDLEWAY..MW_PACOTE P ON P.ID_PACOTE = PR.ID_PACOTE
                    INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                    INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
                    INNER JOIN CI_MIDDLEWAY..MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                    INNER JOIN CI_MIDDLEWAY..MW_CLIENTE C ON C.ID_CLIENTE  = PR.ID_CLIENTE
                    INNER JOIN TABSALDETALHE TSD ON TSD.INDICE = PR.ID_CADEIRA
                    INNER JOIN TABSETOR TS ON TS.CODSALA = TSD.CODSALA AND TS.CODSETOR = TSD.CODSETOR
                    INNER JOIN TABAPRESENTACAO TA ON TA.CODAPRESENTACAO = A2.CODAPRESENTACAO AND TA.CODSALA= TS.CODSALA
                    WHERE PR.ID_PACOTE = ? AND PR.IN_ANO_TEMPORADA = ?
                    AND IN_STATUS_RESERVA IN ('A', 'S')                    
                   )
                   SELECT * FROM RESULTADO " . $between . " ORDER BY 1, 7, 5";
        $result = executeSQL($conn, $query, $params);
        $hasRows = hasRows($result);

        $situacao = array(
            'A' => "Aguardando ação do Assinante",
            'S' => "Solicitado troca",
            'T' => "Troca efetuada",
            'C' => "Assinatura cancelada",
            'R' => "Assinatura renovada"
        );

        $rsAux = executeSQL($mainConnection, "SELECT DS_NOME + ' ' + DS_SOBRENOME as DS_NOME_SOBRENOME FROM MW_CLIENTE WHERE CD_CPF = ?", array($_GET['cpf']), true);

        $cliente = getCliente($conn);
        if ($cliente == "") {
            setCliente($conn);
            $cliente = getCliente($conn);
        }
?>
        <style type="text/css">
            .coluna-header{width: 20%;}
            .tb-form{margin-left: 40px; width: 609px; padding: 0px;}
            form select{min-width: 200px;}
            table.ui-widget tbody tr td input {width: 100%;}
            #paginacao{width: 100%; text-align: center; margin-top: 10px;}
            #cliente{cursor: help;}
        </style>
        <script type="text/javascript">
            var pagina = '<?php echo $pagina; ?>';

            $(document).ajaxStart(function () {
                $("#dados").find(':input:not(:disabled)').prop('disabled',true);
                $.busyCursor();
                $("#loadingIcon").fadeIn('fast');
            });

            $(document).ajaxComplete(function () {
                $("#dados").find(':input:disabled').prop('disabled',false);
                $('#loadingIcon').fadeOut('slow');
            });

            $(document).ready(function(){
                $('#enviar').button();
                $('#ano').onlyNumbers();

                $('#container_pacotes').delegate('select', 'change', function(){
                    var link = "?p="+pagina.replace(".php","")+"&"+$('#dados').serialize();
                    document.location.href=link;
                });

                $('#enviar').button().on('click', function(e){
                    if(validar() == true){
                        var link = "?p="+pagina.replace(".php","")+"&"+$('#dados').serialize();
                        document.location.href=link;
                    }
                });

                $("#controle").change(function(){
                    document.location = '?p=' + pagina.replace('.php', '') + '&controle=' + $("#controle").val() + '&local=' + $("#local").val() + '&ano=' + $("#ano").val() + '&pacote_combo='+ $("#pacote_combo").val() +'&usuario='+ $('#usuario').val() +'';
                });

                $('#efetivar').button().on('click', function(e){
                    if($('#usuario').val() == -1){
                        $.dialog({text: 'Nenhum usuário selecionado.'});
                        exit();
                    }
                    if ($('input[type=checkbox]:checked').length > 0) {
                        var msg = 'Tem certeza que deseja efetivar o(s) ' +
                            $('table.ui-widget tbody input[type=checkbox]:checked').parent('td').parent('tr').length +
                            ' lugar(es) selecionado(s)?<br/><br/>Atenção: essa operação não poderá ser desfeita.'

                        $.dialog({
                            title: 'Confirmação...',
                            text: msg,
                            uiOptions: {
                                buttons: {
                                    'Ok': function() {

                                        $.ajax({
                                            url: pagina + '?action=efetivar',
                                            type: 'post',
                                            data: $('#dados').serialize(),
                                            success: function(data) {
                                                if (data == 'ok') {
                                                    $.dialog({
                                                        title: 'Sucesso',
                                                        text: 'Lugares efetivados com sucesso.',
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

                $('#local').on('change', function(){
                    $.ajax({
                        url: pagina + '?action=load_pacotes',
                        type: 'post',
                        data: $('#dados').serialize()+'&pacote_combo=<?php echo $_GET['pacote_combo']; ?>',
                        success: function(data) {
                            $('#container_pacotes').html(data);
                        }
                    });

                    $.ajax({
                        url: pagina + '?action=load_usuario',
                        type: 'post',
                        data: $('#dados').serialize()+'&local='+$('#local').val()+'&usuario=<?php echo $_GET['usuario']; ?>',
                        success: function(data) {
                            $('#coluna-usuario').html(data);
                        }
                    });

                    $.ajax({
                        url: pagina + '?action=load_cliente',
                        type: 'post',
                        data: $('#dados').serialize()+'&local='+$('#local').val(),
                        success: function(data) {
                            cliente = data.split(";");
                            $('#codcliente').val(cliente[1]);
                            $('#cliente').val(cliente[0]);
                        }
                    });
                }).trigger('change');

                function validar(){
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
                };

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
        <h2>Efetivar a reserva nas apresentações dos pacotes na bilheteria</h2>
        <form id="dados" name="dados">
            <input type="hidden" name="codcliente" id="codcliente" value="<?php echo $cliente["CODIGO"]; ?>" />
            <table class="tb-form">
                <tr>
                    <td class="coluna-header"><strong>Local:</strong></td>
                    <td>
                <?php echo comboTeatroPorUsuario('local', $_SESSION['admin'], $_GET['local']); ?>
            </td>
            <td class="coluna-header"><strong>Cliente padrão para efetivar a reserva:</strong></td>
            <td><input type="text" id="cliente" readonly="true" title="Alteração somente pelo módulo Administrativo."  name="cliente" value="<?php echo $cliente["NOME"]; ?>" /></td>
        </tr>
        <tr>
            <td class="coluna-header"><strong>Pacote:</strong></td>
            <td id="container_pacotes">
                <select id="pacote_combo"><option>Selecione um local...</option></select>
            </td>
            <td class="coluna-header"><strong>Temporada (Ano):</strong></td>
            <td>
                <input type="text" id="ano"  name="ano" maxlength="4" value="<?php echo $_GET['ano']; ?>" />
            </td>
        </tr>
        <tr>
            <td class="coluna-header"><strong>Usuário responsável pela reserva:</strong></td>
            <td colspan="3" id="coluna-usuario">
                <select id="usuario" name="usuario"><option>Selecione um usuário...</option></select>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <br />
                <input id="enviar" type="button" class="button" value="Exibir Lugares" />&nbsp;
                <input id="efetivar" type="button" class="button" value="Efetivar Reserva" />
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
                <th>Cliente</th>
            </tr>
        </thead>
        <tbody>
            <?php
                while ($rs = fetchResult($result)) {
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
                        <td><?php echo utf8_encode2($rs['DS_NOME_SOBRENOME']); ?></td>
                    </tr>
            <?php
                }
            ?>
            </tbody>
        </table>
    </form>

    <div id="paginacao">
    <?php
                if ($hasRows) {
                    $link = "?p=" . basename($pagina, '.php') . "&local=" . $_GET["local"] . "&ano=" . $_GET["ano"] . "&pacote_combo=" . $_GET["pacote_combo"] . "&usuario=" . $_GET["usuario"] . "&controle=" . $total_reg . "&bar=2&baz=3&offset=";
                    Paginator::paginate($offset, $total, $total_reg, $link, true);
                }
    ?>
            </div>
            <br/>
<?php
            }
        }
?>