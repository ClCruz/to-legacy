<?php
require_once('../settings/functions.php');

$mainConnection = mainConnection();

session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 220, true)) {

    $pagina = basename(__FILE__);

    if ( isset($_GET['action']) ) {

        require('actions/' . $pagina);

    } else {
?>
    <html>
        <script>
            $(document).ready(function(){
                $('.button').button();
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
                    }
                });
            };

            function validar()
            {
                if(document.fPeca.cboTeatro.value == "")
                {
                    $.dialog({title: 'Alerta...',text: 'Selecione o local'});
                    document.fPeca.cboTeatro.focus();
                    return false;
                }

                if(document.fPeca.cboPeca.value == "" || document.fPeca.cboPeca.value == "null")
                {
                    $.dialog({title: 'Alerta...',text: 'Selecione o evento'});
                    document.fPeca.cboPeca.focus();
                    return false;
                }

                if(document.fPeca.cboApresentacao.value == "")
                {
                    $.dialog({title: 'Alerta...', text: 'Selecione a apresentação'});
                    document.fPeca.cboApresentacao.focus();
                    return false;
                }

                if(document.fPeca.cboHorario.value == "")
                {
                    $.dialog({title: 'Alerta...', text: 'Selecione o horário'});
                    document.fPeca.cboHorario.focus();
                    return false;
                }

//                $("#loading").ajaxStart(function(){
//                    $(this).show();
//                });
                var tipoExport = $('select[name="tipoExport"]').val();

                var action;
                switch (tipoExport)
                {
                    case '01':
                        action = 'csv';
                        break;

                    case '02':
                        action = 'defaultcsv';
                        break;

                    case '03':
                        action = 'vendidos';
                        break;
                }

                var url = "gerarCSVLeitor.php?action=" + action +
                    "&CodPeca=" + document.fPeca.cboPeca.value +
                    "&CodTeatro=" + document.fPeca.cboTeatro.value +
                    "&DatApresentacao=" + document.fPeca.cboApresentacao.value +
                    "&HorSessao=" + document.fPeca.cboHorario.value;

                
                if (tipoExport) {
                    document.location = url;
                } else {
                    alert("Selecione um tipo de exportação válido");
                }

            }

            function limpar()
            {
                $(document.fPeca.cboTeatro).val('');
                $(document.fPeca.cboPeca).val('').find('option:not(:selected)').remove();
                $(document.fPeca.cboApresentacao).val('').find('option:not(:selected)').remove();
                $(document.fPeca.cboHorario).val('').find('option:not(:selected)').remove();
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
        <h2>Gerar arquivo CSV de Código de Barras</h2>
    </head>
    <body>
        <form name="fPeca" id="fPeca" method="POST">
            <table cellpadding='0' border='0' width='609' cellspacing='0'>
                <tr>
                    <td width="30%"><strong>Local:</strong><br>
                    <?php
                    $funcJavascript = 'onChange="ExibePeca(this.value, \'Peca\', \'SP_PEC_CON009;5\');PreencheDescricao()"';
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
                    <td>
                        <strong>tipo de exportação</strong><br>
                        <label>
                            <select name="tipoExport">
                                <option value="01">01-CSV-Modelo Específico</option>
                                <option value="02">02-CSV-Modelo Padrão</option>
                                <option value="03">03-CSV-Só Ingressos Vendidos</option>
                            </select>
                        </label>
                    </td>
                </tr>
                <tr>
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
                            <select name="cboHorario" id="cboHorario">
                                <option value="">Selecione...</option>
                            </select>
                        </div>
                    </td>
                    <td></td>
                </tr>
            <tr>
                <td ALIGN="CENTER" COLSPAN="3">
                    <br>
                    <input type="button" class="button enviar" value="Gerar Arq. CSV" onclick="validar()" />&nbsp;
                    <input type="button" class="button limpar" value="Limpar Campos" onclick="limpar()" />&nbsp;
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
      if (sqlErrors ()) echo sqlErrosr();
  }
}