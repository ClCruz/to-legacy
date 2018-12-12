<?php
require_once('acessoLogadoDie.php');
require_once('../settings/functions.php');
require_once('../settings/Paginator.php');

$mainConnection = mainConnection();
session_start();

function tratarData($data) {
    $data = explode("/", $data);
    return $data[2] . $data[1] . $data[0];
}

if (acessoPermitido($mainConnection, $_SESSION['admin'], 34, true)) {
    $pagina = basename(__FILE__);
    if (isset($_GET['action'])) {
        require('actions/' . $pagina);
    } else {
        if (isset($_GET["dtInicial"]) and isset($_GET["dtFinal"])) {
            //Se foi escolhida a funcionalidade
            if ($_GET["funcionalidade"] != -1)
                $funcOpcao = "AND (DS_FUNCIONALIDADE = '" . utf8_decode($_GET['funcionalidade']) . "')";

            if (isset($_GET["offset"]))
                $offset = $_GET["offset"];
            else
                $offset = 1;

            $queryTotal = "SELECT 1
                        FROM MW_LOG_MIDDLEWAY LM
                        INNER JOIN MW_USUARIO U ON U.ID_USUARIO = LM.ID_USUARIO
                        WHERE (CONVERT(VARCHAR, DT_OCORRENCIA, 112) BETWEEN ? AND ?) " .
                        $funcOpcao . "";

            $params = array(tratarData($_GET["dtInicial"]),
                            tratarData($_GET["dtFinal"]),
                            utf8_decode($_GET["funcionalidade"]));
            
            $tr = numRows($mainConnection, $queryTotal, $params);

            $total_reg = ($_GET["controle"]) ? $_GET["controle"] : 10;
            $final = ($offset + $total_reg) - 1;

            $query = "WITH RESULTADO AS (
                        SELECT
                                DT_OCORRENCIA,
                                U.DS_NOME,
                                DS_FUNCIONALIDADE,
                                DS_LOG_MIDDLEWAY,
                                ROW_NUMBER() OVER(ORDER BY DT_OCORRENCIA DESC) AS 'LINHA'
                        FROM
                                MW_LOG_MIDDLEWAY LM
                        INNER JOIN MW_USUARIO U ON U.ID_USUARIO = LM.ID_USUARIO
                        WHERE (CONVERT(VARCHAR, DT_OCORRENCIA, 112) BETWEEN ? AND ?) " .
                        $funcOpcao . "
                     )
                     SELECT * FROM RESULTADO WHERE LINHA BETWEEN " . $offset . " AND " . $final ." ORDER BY DT_OCORRENCIA DESC";

            $result = executeSQL($mainConnection, $query, $params);            
        }

        $dataInicial = executeSQL($mainConnection,
                        "SELECT TOP 1 DT_OCORRENCIA FROM MW_LOG_MIDDLEWAY ORDER BY DT_OCORRENCIA",
                        array(), true);

        if(empty($dataInicial["DT_OCORRENCIA"])){
            $dtInicial = '';
        }else{
            $dtInicial = $dataInicial["DT_OCORRENCIA"]->format("d/m/Y");
        }

        $dataFinal = executeSQL($mainConnection,
                        "SELECT TOP 1 DT_OCORRENCIA FROM MW_LOG_MIDDLEWAY ORDER BY DT_OCORRENCIA DESC",
                        array(), true);
        if(empty($dataFinal["DT_OCORRENCIA"])){
            $dtFinal = '';
        }else{
            $dtFinal = $dataFinal["DT_OCORRENCIA"]->format("d/m/Y");
        }

        $funcionalidades = executeSQL($mainConnection,
                        "SELECT DISTINCT DS_FUNCIONALIDADE
                    FROM MW_LOG_MIDDLEWAY WHERE DS_FUNCIONALIDADE IS NOT NULL ORDER BY DS_FUNCIONALIDADE ASC");
?>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>     
        <script type="text/javascript" language="javascript">
            $(function() {
                var pagina = '<?php echo $pagina; ?>';
                //FAZER IF PARA VERIFICAR SE A VARIAVEL DTINICIAL ESTÁ VAZIA SE SIM NÃO PROSSEGUE
                var dtInicial = '<?php echo $dtInicial; ?>';
                var dtFinal = '<?php echo $dtFinal; ?>';
                var dtInicialOpc = '<?php echo $_GET["dtInicial"]; ?>';

                $('.button').button();

                $('.button').click(function(){
                   document.location.href="?p=consultaLog&dtInicial=" + $("#dtInicial").val() + "&dtFinal=" + $('#dtFinal').val() + "&funcionalidade=" + $('#funcionalidade').val();
                });

                $('#dtFinal').datepicker({
                    minDate: dtInicialOpc,
                    maxDate: ''+dtFinal+'',
                    changeYear: true,
                    changeMonth: true
                });

                $('input.datePicker').datepicker({
                    changeMonth: true,
                    changeYear: true,
                    minDate: ''+dtInicial+'',
                    maxDate: ''+dtFinal+'',
                    onSelect: function(date, e) {
                        if ($(this).is('input[name="dtInicial"]')) {
                            $('input[name="dtFinal"]').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                        }
                    }
                }).datepicker('option', $.datepicker.regional['pt-BR']);
            });
        </script>
        <h2>Consulta de Log</h2>
        <form id="dados" name="dados" action="?p=consultaLog" method="POST" style="text-align: left;">
            <label>Data Inicial:
                <input type="text" maxlength="10" size="15" id="dtInicial" name="dtInicial" class="datePicker" value="<?php echo $_GET["dtInicial"]; ?>" />
            </label>
            <label>&nbsp;&nbsp;Data Final:
                <input type="text" maxlength="10" size="15" id="dtFinal" name="dtFinal"  class="datePicker" value="<?php echo $_GET["dtFinal"]; ?>" />
            </label>
            <label>&nbsp;&nbsp;Funcionalidade:
                <select name="funcionalidade" id="funcionalidade">
                    <option value="-1">Todas</option>
<?php
        while ($funcionalidade = fetchResult($funcionalidades)) {
            if (isset($_GET["funcionalidade"]))
                $selected = "";
            if (strcmp(utf8_encode2($funcionalidade["DS_FUNCIONALIDADE"]), $_GET["funcionalidade"]) == 0)
                $selected = "selected=\"selecteded\"";
?>
            <option <?php echo $selected; ?> value="<?php echo utf8_encode2($funcionalidade["DS_FUNCIONALIDADE"]); ?>"><?php echo utf8_encode2($funcionalidade["DS_FUNCIONALIDADE"]) ?></option>
<?php
        }
?>
        </select>&nbsp;
    </label>
    <input type="button" class="button" id="btnProcurar" value="Buscar" /><br/><br/>

    <table id="tableLogs" class="ui-widget ui-widget-content">
        <thead>
            <tr class="ui-widget-header">
                <th width="140">Data da ocorrência</th>
                <th width="140">Usuário</th>
                <th width="140">Funcionalidade</th>
                <th>Atualização efetuada</th>
            </tr>
        </thead>
        <tbody>
<?php
        while ($dados = fetchResult($result)) {
?>
            <tr>
                <td><?php echo $dados["DT_OCORRENCIA"]->format("d-m-Y G:i:s"); ?></td>
                <td><?php echo $dados["DS_NOME"]; ?></td>
                <td><?php echo utf8_encode2($dados["DS_FUNCIONALIDADE"]); ?></td>
                <td><?php echo $dados["DS_LOG_MIDDLEWAY"]; ?></td>
            </tr>
<?php
        }
?>
        </tbody>
    </table>
    <div id="paginacao" style="text-align: center;">
    <?php        
        $link = "?p=consultaLog&dtInicial=" . $_GET["dtInicial"] . "&dtFinal=" . $_GET["dtFinal"] . "&funcionalidade=" . $_GET["funcionalidade"]."&controle=" . $total_reg . "&bar=2&baz=3&offset=";
        Paginator::paginate($offset, $tr, $total_reg, $link, false);
    ?>
    </div>
</form><br/>
<?php
    }
}
?>