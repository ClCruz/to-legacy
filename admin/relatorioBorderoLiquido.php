<?php
require_once('../settings/functions.php');

$mainConnection = mainConnection();

session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 251, true)) {

    $pagina = basename(__FILE__);
?>

    <html>
        <script>
            $(document).ready(function(){
                $('.button').button();

                $('#chkSmall').click(function(){
                    $('.smallHide').toggle();
                });
                $('input.datePicker').datepicker({
                    changeMonth: true,
                    changeYear: true,
                    onSelect: function(date, e) {
                        if ($(this).is('input[name="txtData1"]')) {
                            $('input[name="txtData2"]').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                        }
                    }
                }).datepicker('option', $.datepicker.regional['pt-BR']);
            });
            //DataBase, Tipo, Procedure
            function ExibePeca(NmDB, Tipo, Procedure)
            {
                //limpar();

                if (NmDB != "")
                {
                    switch(Tipo)
                    {
                        case 'Peca':
                            $.ajax({
                                url: 'relatorioBorderoActions.php',
                                type: 'post',
                                data: 'NomeBase='+ NmDB +'&Proc='+ Procedure,
                                success: function(data){
                                    $('#divPeca').html(data);
                                },
                                error: function(){
                                    $.dialog({
                                        title: 'Erro...',
                                        text: 'Erro na chamada dos dados.'
                                    });
                                }
                            });
                            break;
                    }
                }
                else
                {
                    switch(Tipo)
                    {
                        case 'Peca':
                            document.getElementById("divPeca").innerHTML = '<SELECT disabled id="cboPeca" name="cboPeca" style="width: 250px;"><option value="">Não Selecionado</option></select>';
                            break;
                    }
                }
            };
            function PreencheDescricao(){
                var_descTeatro = $('#cboTeatro').val();
                var_descPeca = $('#cboPeca').val();
            };
        </script>
        <script language="javascript">
            var Janela

            function CarregaApresentacao()
            {
                var CodPeca = $('#cboPeca').val();
                $.ajax({
                    url: 'relatorioBorderoActions.php',
                    type: 'post',
                    data: 'Acao=1&CodPeca='+ CodPeca,
                    success: function(data){
                        $('#cboApresentacao').html(data);
                        CarregaHorario();
                        document.fPeca.cboSala.value = "";
                    }
                });
                $.ajax({
                    url: 'relatorioBorderoActions.php',
                    type: 'post',
                    data: 'Acao=requestDates&CodPeca='+ CodPeca,
                    dataType: 'json',
                    success: function(data){
                        $('input[name="txtData1"]').datepicker('option', 'minDate', data.inicial);
                        $('input[name="txtData2"]').datepicker('option', 'maxDate', data.final);
                    }
                });
            };

            function CarregaHorario()
            {
                var CodPeca = $('#cboPeca').val();
                $.ajax({
                    url: 'relatorioBorderoActions.php',
                    method: 'post',
                    data: 'Acao=2&CodPeca='+ CodPeca + '&DatApresentacao='+ $("#cboApresentacao").val(),
                    success: function(data){
                        $('#cboHorario').html(data);
                        //CarregaSala();
                    }
                });
            };

            function CarregaSala()
            {
                var CodPeca = $('#cboPeca').val();
                $.ajax({
                    url: 'relatorioBorderoActions.php',
                    mehotd: 'post',
                    data: 'Acao=3&CodPeca='+ CodPeca + '&DatApresentacao=' + $("#cboApresentacao").val() + '&Horario='+ $("#cboHorario").val(),
                    success: function(data){
                        $('#cboSala').html(data);
                    }
                });
            };

            function validar()
            {
                var primeiraSala = ''; //Primeiro option do combo após "SELECIONE" e "TODOS".

                if(document.fPeca.cboPeca.value == "")
                {
                    $.dialog({title: 'Alerta...',text: 'Selecione o evento'});
                    document.fPeca.cboPeca.focus();
                    return;
                }

                if(document.fPeca.cboApresentacao.value == ""
                    && !document.fPeca.chkSmall.checked)
                {
                    $.dialog({title: 'Alerta...', text: 'Selecione a apresentação'});
                    document.fPeca.cboApresentacao.focus();
                    return;
                }

                if(document.fPeca.cboHorario.value == ""
                    && !document.fPeca.chkSmall.checked)
                {
                    $.dialog({title: 'Alerta...', text: 'Selecione o horário'});
                    document.fPeca.cboHorario.focus();
                    return;
                }

                if(document.fPeca.cboSala.value == ""
                    && !document.fPeca.chkSmall.checked)
                {
                    $.dialog({title: 'Alerta...', text: 'Selecione a setor'});
                    document.fPeca.cboSala.focus();
                    return;
                }

                if((document.fPeca.txtData1.value == ""
                    || document.fPeca.txtData2.value == "")
                    && document.fPeca.chkSmall.checked)
                {
                    $.dialog({title: 'Alerta...', text: 'Selecione um intervalo de datas válido'});
                    document.fPeca.cboSala.focus();
                    return;
                }

                if (document.fPeca.chkSmall.checked) {
                    data1 = document.fPeca.txtData1.value.split('/');
                    data1 = data1[2]+data1[1]+data1[0];
                    data2 = document.fPeca.txtData2.value.split('/');
                    data2 = data2[2]+data2[1]+data2[0];
                } else {
                    data1 = data2 = document.fPeca.cboApresentacao.value;
                }

                var url = "relBorderoVendasLiquidas.php";
                url += "?CodPeca=" + document.fPeca.cboPeca.value;
                url += "&logo=imagem";

                url += "&Resumido=0";
                url += "&Small=" + ((document.fPeca.chkSmall.checked) ? '1' : '0');
                url += "&DataIni=" + data1;
                url += "&DataFim=" + data2;
                url += "&HorSessao=" + ((document.fPeca.chkSmall.checked)
                    ? ''
                : document.fPeca.cboHorario.value
            );
                url += "&Sala=" + ((document.fPeca.chkSmall.checked)
                    ? "TODOS"
                : document.fPeca.cboSala.value
            );

                $("#loading").ajaxStart(function(){
                $(this).show();
            });
            
                Janela = window.open ('esperaProcesso.php?redirect=' + escape(url), "", "width=720, height=600, scrollbars=yes", "");
            };

            function limpar()
            {
                document.fPeca.cboPeca.value = "";
                document.fPeca.cboTeatro.value = "";
                document.fPeca.cboSala.value = "";
            };
        </script>
        <head>
            <style type="text/css">
                #paginacao{
                    width: 100%;
                    text-align: center;
                    margin-top: 10px;
                }
            </style>
        <h2>Borderô - Fechamento em Dinheiro</h2>
    </head>
    <body>
        <form action="javascript:validar();" name="fPeca" id="fPeca" method="POST">
            <table cellpadding='0' border='0' width='609' cellspacing='0'>
                <tr>
                    <td><strong>Local:</strong><br>
                    <?php
                    $funcJavascript = 'onChange="ExibePeca(this.value, \'Peca\', \'SP_PEC_CON009;8\');PreencheDescricao()"';
                    //echo comboTeatro("cboTeatro", "", $funcJavascript);

                    $result = executeSQL($mainConnection, 'SELECT DISTINCT B.ID_BASE, B.DS_NOME_TEATRO FROM MW_BASE B INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = B.ID_BASE WHERE AC.ID_USUARIO ='. $_SESSION['admin'] .'  AND B.IN_ATIVO = \'1\' ORDER BY B.DS_NOME_TEATRO');

                    $combo = '<select name="cboTeatro" ' . $funcJavascript . ' class="inputStyle" id="cboTeatro"><option value="">Selecione um local...</option>';
                    while ($rs = fetchResult($result)) {
                        $combo .= '<option value="' . $rs['ID_BASE'] . '"' . (($selected == $rs['ID_BASE']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_NOME_TEATRO']) . '</option>';
                    }
                    $combo .= '</select>';
                    
                    echo $combo;
                    ?>
                </td>
                <td>
                    <strong>Evento:</strong><br>
                    <div name="divPeca" Id="divPeca">&nbsp;
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <label><input type="checkbox" id="chkSmall" name="chkSmall" />Borderô Resumido - Selecione essa opção para fazer (visualizar) o fechamento final do evento.</label>
                </td>
            </tr>
            <tr class="smallHide">
                <td>
                    <br>
                    <div name="divApresent">
                        <strong>Apresenta&ccedil;&atilde;o:</strong><br>
                        <select name="cboApresentacao" id="cboApresentacao" onChange="CarregaHorario()">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                </td>
                <td>
                    <br>
                    <div name="divHorario">
                        <strong>Hor&aacute;rio:</strong><br>
                        <select name="cboHorario" id="cboHorario" onChange="CarregaSala()">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                </td>
            </tr>
            <tr class="smallHide">
                <td>
                    <br>
                    <div name="divSala">
                        <strong>Setor:</strong><br>
                        <select id="cboSala" name="cboSala">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                </td>
            </tr>
            <tr class="smallHide ui-helper-hidden">
                <td>
                    <div id="DataIni">
                        <strong>Dt. Apresentação Inicial</strong><br>
                        <input type="text" maxlength="10" size="15" class="datePicker" name="txtData1" readonly>
                    </div>
                </td>
                <td>
                    <div id="DataFim">
                        <strong>Dt. Apresentação Final</strong><br>
                        <input type="text" maxlength="10" size="15" class="datePicker" name="txtData2" readonly>
                    </div>
                </td>
            </tr>
            <tr>
                <td ALIGN="CENTER" COLSPAN="2">
                    <br>
                    <button type="submit" class="button" style="width:100">Visualizar</button>&nbsp;
                    <button class="button" onClick="limpar()">Limpar Campos</button>&nbsp;
                </td>
            </tr>
        </table>
    </form>

</BODY>
</html>
<script>
    ExibePeca('','Peca','');
</script>
<?php
        if (sqlErrors ())
            echo sqlErrosr();
    }
?>
