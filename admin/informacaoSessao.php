<?php

require_once('../settings/functions.php');

$mainConnection = mainConnection();

session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 540, true)) {

    $pagina = basename(__FILE__);
     $mes = date("m") + 0;
    if($mes < 10)
        $mes = "0".$mes;  

    if (isset($_GET['action']) or isset($_POST['codigo'])) {
        
        require('actions/'.$pagina);

    } else {
?>

<html>
    <script type="text/javascript" src="../javascripts/jquery.ui.datepicker-pt-BR.js"></script>
    <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css" >
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js"></script>
    <script>
     var table;
        function initDatatable(){
                table = $('.datatable').DataTable( {
                    "language": {
                        "lengthMenu": "Exibir _MENU_ registros por página",
                        "zeroRecords": "Nada encontrado!",
                        // "info": "Exibindo pagina _PAGE_ de _PAGES_",
                        "info": "Mostrando _START_ para _END_ de _TOTAL_ entradas",
                        "infoEmpty": "Nenhum registro encontrado",
                        "infoFiltered": "(filtrado de _MAX_ registros totais)",
                        "infoSearching": "Pesquisar:",

                        "emptyTable":     "Sem dados disponíveis",
                        "infoPostFix":    "",
                        "thousands":      ",",
                        "loadingRecords": "Carregando...",
                        "processing":     "Processando...",
                        "search":         "Pesquisar:",
                        "zeroRecords":    "Nenhum registro correspondente encontrado",
                        "paginate": {
                            "first":      "Primeira",
                            "last":       "Última",
                            "next":       "Próxima",
                            "previous":   "Voltar"
                        },
                        "aria": {
                            "sortAscending":  ": ativado para ordernação crescente",
                            "sortDescending": ": ativado para ordernação decrescente"
                        }
                    }  
                } );

                console.log("datatable On!");
        }
        function destroyDatatable(){
          table.destroy();
          table = '';
          console.log("datatable Off!");
        }

        function toggleDatatable(){
          if(typeof table == 'undefined' || table == '' )
              initDatatable();
          else 
              destroyDatatable();  
        }

        $(document).ready(function(){
            var pagina = '<?php echo $pagina; ?>';
             
            $('input.datepicker').datepicker({
                changeMonth: true,
                changeYear: true,
                onSelect: function(date, e) {
                    if ($(this).is('#dt_inicial')) {
                        $('#dt_final').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                    }
                }
            }).datepicker('option', $.datepicker.regional['pt-BR']);

            toggleDatatable();
        });

        function formatar(src, mask){
            var i = src.value.length;
            var saida = mask.substring(0,1);
            var texto = mask.substring(i)
            if (texto.substring(0,1) != saida)
            {
                src.value += texto.substring(0,1);
            }
        }

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
                                url: 'informacaoSessaoActions.php',
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
            function CarregaApresentacao()
            {
                var CodPeca = $('#cboPeca').val();
                $.ajax({
                    url: 'informacaoSessaoActions.php',
                    type: 'post',
                    data: 'Acao=1&CodPeca='+ CodPeca,
                    success: function(data){
                        $('#cboApresentacao').html(data);
                        CarregaHorario();
                        //document.fPeca.cboSala.value = "";
                    }
                });
                $.ajax({
                    url: 'informacaoSessaoActions.php',
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
                    url: 'informacaoSessaoActions.php',
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
                    url: 'informacaoSessaoActions.php',
                    method: 'post',
                    data: 'Acao=3&CodPeca='+ CodPeca + '&DatApresentacao=' + $("#cboApresentacao").val() + '&Horario='+ $("#cboHorario").val(),
                    success: function(data){
                       // $('#cboSala').html(data);
                    }
                });
            };

            function buscar(offset){

                if($('#cboTeatro').val()=='')
                {

                  $.dialog({
                                title: 'Atenção',
                                text: 'Selecione o local!'
                            });
                  return;
                }
                if($('#cboPeca').val()=='' || $('#cboPeca').val() =='null')
                {
                  $.dialog({
                                title: 'Atenção',
                                text: 'Selecione o evento!'
                            });
                  return;
                }

               var CodPeca = $('#cboPeca').val();

               var actionData = 'Acao=4&CodPeca='+ CodPeca + '&DatIni=' + $("#dt_inicial").val() + '&DatFim='+ $("#dt_final").val();
                  
                   console.log("buscar():toggleDatatable");
                   toggleDatatable();

                       
                $.ajax({
                    url: 'informacaoSessaoActions.php',
                    method: 'post',
                    data: actionData,
                    success: function(data){
                       
                        $('#apresentacoes').html(data);
                        console.log("buscar():toggleDatatable");
                        toggleDatatable();
                    },
                    beforeSend: function(){
                        $('#apresentacoes').html('<tr><td>Carregando...</td></tr>');
                    }
                });
            }

            function alterarInfo(){

                var qtdAssento = $('#qtdAssento').val();
                var formaExib = $('#formaExib').val();
                var formatoExib = $('#formatoExib').val();
                var formSerialize = [];
                $("input[name=optApresentacao]:checked").each(function(){ 
                   var objSerialize = {};

                   objSerialize.codsala = $(this).data('codsala');
                   objSerialize.idevento = $(this).val();

                   formSerialize.push(JSON.stringify(objSerialize));
                })

                var actionData = "Acao=5&Apresentacao=["+formSerialize+"]&formaExib="+formaExib+"&formatoExib="+formatoExib;

                $.ajax({
                    url: 'informacaoSessaoActions.php',
                    method: 'post',
                    data: actionData,
                    success: function(data){
                         $('#msgResultado').html('');
                         $.dialog({
                                title: 'Alteração de Informação de Sessão',
                                text: data
                            });
                    },
                    beforeSend: function(){
                        $('#msgResultado').html('Salvando...</td></tr>');
                    }
                });
            }
       
          $(function(){
             $('.button').button();

             $('#btSave').click(function(){
                
                if($('#qtdAssento').val() == '' ||
                    $('#formaExib').val() == '' ||
                    $('#formatoExib').val() == ''
                    ){
                  
                      $.dialog({
                                title: 'Atenção',
                                text: 'Os seguintes campos precisam serem preenchidos: Qtde de Assentos, Forma Exibição e Formato Exibição!'
                            });
                  return;
                }
                if(!$("input[name=optApresentacao]").is(':checked')) {
                    $.dialog({
                                title: 'Atenção',
                                text: 'Selecione alguma apresentação!'
                            });
                    return;
                }


                alterarInfo();
                
             });
          });




        function clickEdit(inputBt,cancel){
             $('#msgResultado').html('');
             cancel = (cancel == 'undefined') ? false : cancel;

             var idBase = $('#cboTeatro').val();
                var item = $(inputBt).data('item');
                var codSala = $(inputBt).data('codsala');


                var modalidade  = $('#modalidadeIn_' + item);
       


                var modalidadeTx  = $('#modalidadeTx_' + item);
    

                

                if(modalidade.is(':hidden') || cancel){
                    
                    if(cancel)
                         $('.btEditar[data-item='+item+']').val('Editar');
                    else
                        $(inputBt).val('Salvar');


                    $('.btCancelar[data-item='+item+']').toggle();
                }else{

                    if(modalidade.val()==''){
                        alert("A modalidade precisa ser selecionada!");
                        return;
                    }

                    var actionData = 'Acao=5&modalidade='+modalidade.val()+'&idBase='+idBase+'&codSala='+codSala;


                    $.ajax({
                        url: 'informacaoSessaoActions.php',
                        method: 'post',
                        data: actionData,
                        success: function(data){
                            buscar();
                            //modalidadeTx.html(modalidade.text());
                           
                            $('#msgResultado').html(data);
                        },
                        beforeSend: function(){
                            $('#msgResultado').html('Salvando...');
                        }
                    });

                    $(inputBt).val('Editar');
                    $('.btCancelar[data-item='+item+']').toggle();
                }

                modalidade.toggle();
               
                modalidadeTx.toggle();
                
               


                // console.log(codSala);
        }

        function clickCancel(inputBt){
            clickEdit(inputBt,true);
        }
        </script>
        <head>


       <style type="text/css">
             form table{width: 600px !important;}
            .inputhidden{
                display: none;
            }
        </style>
    <head>
</head>
<body>
    <h2>Informação Sobre Sessão</h2>
      <form>
      <table border="0" cellpadding="2" cellspacing="2">
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
              <td>
                  <strong>Dt. Inicial da Apresentação</strong><br>
                  <input type="text" title="Data inicial da venda" onkeypress="formatar(this, '##/##/####');" value="<?php echo (isset($_GET["dt_inicial"])) ? $_GET["dt_inicial"] : date("d") . "/" . $mes . "/" . date("Y") ?>" class="datepicker" id="dt_inicial" name="dt_inicial" />     
              </td>
              <td>
                   <strong>Dt. Final da Apresentação</strong><br>
                   <input type="text" title="Data final da venda" class="datepicker" value="<?php echo (isset($_GET["dt_final"])) ? $_GET["dt_final"] : date("d/m/Y") ?>" id="dt_final" name="dt_final" />
              </td>
          </tr>
          <tr>
            <td colspan="4" align="center">
              <input type="button" class="button" id="btnRelatorio" onclick="buscar()" value="Buscar" />
            </td>
          </tr>
      </table>
      </form><br>
      
      <form>
      <table border="0" cellpadding="2"  cellspacing="1">
        <tr>
          <label id="msgResultado"></label></td>
        </tr>
      </table>
      </form>
 
      <table class="ui-widget ui-widget-content datatable">
      <form id="formApresentacao" >
     

        <br>
        <br>
          <thead>
              <tr class="ui-widget-header " style="text-align: center;">
                  <th>Sala</th>
                  <th>Modalidade</th>
                  <th>Selecione</th>
              </tr>
          </thead>
          <tbody border="1" id="apresentacoes">
            <!-- <tr>
              <td>30/04/2017 19:00</td>
              <td>Sala 1</td>
              <td>O Estudante</td>
              <td>500</td>
              <td>Original</td>
              <td>Digital</td>
              <td><input type="checkbox" name="optApresentacao[]" value="1" /></td>
            </tr> -->
      
          </tbody>

       </form> 
     </table>
  
</BODY>
</html>
<script>
    ExibePeca('','Peca','');
</script>
<?php
      if (sqlErrors ())
            echo sqlErrosr();
    }
}
?>
