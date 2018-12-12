<?php
require_once('../settings/functions.php');

$mainConnection = mainConnection();

session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 520, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action']) or isset($_POST['codigo'])) {
        
        require('actions/'.$pagina);

    } else {
?>

<html>
   <head>
       <meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1" >
       <link rel="stylesheet" href="https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css" >
       <script type="text/javascript" src="https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js"></script>
     <script>
        var table ;
        function initDatatable(){
                table = $('#datatable').DataTable( {
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
                    },
                    "destroy":true
                } );
        }
        function destroyDatatable(){
          table.destroy();
        }

        $(document).ready(function(){
            var pagina = '<?php echo $pagina; ?>';
             $('.button').button();
          
              initDatatable();

             
             $('#cboTeatro').on('change',function(){
                if($(this).find(':selected').val() == ''){
                  $('#btNovo').hide();    
                }
                else
                  $('#btNovo').show();    


                buscar();
                    
             });
        

            

        });

        function buscar(){
           var idBase = $('#cboTeatro').find(":selected").val();
                    
                    idBase = (idBase == '') ? 'null': idBase;

                    var actionData = 'Acao=1&idBase='+idBase;

                        $.ajax({
                            url: 'associarFormaPagamentoActions.php',
                            method: 'post',
                            data: actionData,
                            success: function(data){
                                destroyDatatable();
                                $('#associarFormaPagamentos').html(data);
                                $('.button').button();
                                $('#msgResultado').html('');
                                initDatatable();
                            },
                            beforeSend: function(){
                                $('#salas').html('<tr><td colspan="4">Carregando...</td></tr>');
                            }
                        });
        }

        function clickEdit(inputBt,cancel){
             $('#msgResultado').html('');
             cancel = (cancel == 'undefined') ? false : cancel;

             var idBase = $('#cboTeatro').val();
                var item = $(inputBt).data('item');


               var modalidadePagamento  = $('#modalidadePagamentoIn_' + item);
                var formaPagamento = $('#formaPagamentoIn_' + item);
            

                var modalidadePagamentoTx = $('#modalidadePagamentoTx_' + item);
                var formaPagamentoTx = $('#formaPagamentoTx_' + item);
            
                

                if(modalidadePagamento.is(':hidden') || cancel){
                    
                    if(cancel)
                         $('.btEditar[data-item='+item+']').val('Editar');
                    else
                        $(inputBt).val('Salvar');


                    $('.btCancelar[data-item='+item+']').toggle();
                }else{

                    if(modalidadePagamento.val()=='' || modalidadePagamento.val() <= 0
                       || formaPagamento.val()=='' || formaPagamento.val() <= 0
                      
                    ){
                        alert("Os valores para Nr. Reg.Exibidor na Ancine e Nr. Reg.da Sala na Ancine precisam ser maior que zero!");
                        return;
                    }

                    var actionData = 'Acao=2&modalidadePagamento='+modalidadePagamento.val()+'&formaPagamento='+formaPagamento.val()+'&idBase='+idBase;


                    $.ajax({
                        url: 'associarFormaPagamentoActions.php',
                        method: 'post',
                        data: actionData,
                        success: function(data){

                            // modalidadePagamentoTx.html(modalidadePagamento.text());
                            // formaPagamentoTx.html(formaPagamento.text());

                                  //$('#msgResultado').html(data);

                                  $.dialog({
                                      title:'Nova Associação Forma de Pagamento',
                                      text: data
                                  });
                            buscar();

                            
                        },
                        beforeSend: function(){
                            $('#msgResultado').html('Salvando...');
                        }
                    });

                    $(inputBt).val('Editar');
                    $('.btCancelar[data-item='+item+']').toggle();
                }

                modalidadePagamento.toggle();
                formaPagamento.toggle();
           
                modalidadePagamentoTx.toggle();
                formaPagamentoTx.toggle();



                // console.log(codSala);
        }

        function clickCancel(inputBt){
            clickEdit(inputBt,true);
        }

        function carregarComboModalidade(){
                 var actionData = 'Acao=3';


                    $.ajax({
                        url: 'associarFormaPagamentoActions.php',
                        method: 'post',
                        data: actionData,
                        success: function(data){

                            $('#selModalidadePagamento').html(data);

                        },
                        beforeSend: function(){
                           // $('#msgResultado').html('Salvando...');
                        }
                    });
        }
        function carregarComboFormaPagamento(){
                   var idBase = $('#cboTeatro').val();
  
                   var actionData = 'Acao=4&idBase='+idBase;


                    $.ajax({
                        url: 'associarFormaPagamentoActions.php',
                        method: 'post',
                        data: actionData,
                        success: function(data){

                            $('#selFormaPagamento').html(data);

                        },
                        beforeSend: function(){
                            //$('#msgResultado').html('Salvando...');
                        }
                    });
        }

        function loadCombos(){
            carregarComboModalidade();
            carregarComboFormaPagamento();
            $('#btSalvar').show();
            $('#btCancelar').show();
            $('.cbohidden').show();
            $('#btNovo').hide();
        }

        function salvarNovo(){
             var idBase = $('#cboTeatro').val();
             var modalidadePagamento = $('#cboModalidadePagamento').val();
             var formaPagamento = $('#cboFormaPagamento').val();


             var actionData = 'Acao=2&modalidadePagamento='+modalidadePagamento+'&formaPagamento='+formaPagamento+
             '&idBase='+idBase;
             //console.log(actionData);

              $.ajax({
                  url: 'associarFormaPagamentoActions.php',
                  method: 'post',
                  data: actionData,
                  success: function(data){

                      esconderNovo();

                       $.dialog({
                                      title:'Nova Associação Forma de Pagamento',
                                      text: data
                                  });
                        buscar();
                    //  buscar();
                  },
                  beforeSend: function(){
                      $('#msgResultado').html('Salvando...');
                  }
              });

        }

        function esconderNovo(){

                $('.cbohidden').hide();
                $('#btSalvar').hide();
                $('#btCancelar').hide();
                $('#btNovo').show();
        }

        function clickExcluir(input){
            var codigoModalidadePagamento = $(input).data('codigomodalidadepagamento');
            var codigoTipoFormaPagamento = $(input).data('codtipforpagto');
            var idBase = $('#cboTeatro').val();

            var actionData = 'Acao=5&modalidadePagamento='+codigoModalidadePagamento+'&formaPagamento='+codigoTipoFormaPagamento+'&idBase='+idBase;
             // console.log(actionData);

              $.ajax({
                  url: 'associarFormaPagamentoActions.php',
                  method: 'post',
                  data: actionData,
                  success: function(data){

                      esconderNovo();

                       $.dialog({
                                      title:'Apagar Associação Forma de Pagamento',
                                      text: data
                                  });
                        buscar();
                    //  buscar();
                  },
                  beforeSend: function(){
                      $('#msgResultado').html('Excluindo...');
                  }
              });


        }

    </script>
    <style type="text/css">
        .inputhidden{
            display: none;
        }
    </style>
   
   
</head>
<body>
    <h2>Associar Forma de Pagamento</h2>
       <form>
      <table border="0" cellpadding="2" cellspacing="2">
          <tr>
               <td><strong>Local:</strong><br>
                    <?php
                    $funcJavascript = '';
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
        
                 
          </tr>
      </table>
      </form><br>

      <div><strong id="msgResultado"></strong></div>
       
      <table class="ui-widget ui-widget-content" id="datatable">
      
      
        <br>
        <br>
          <thead>
              <tr class="ui-widget-header " style="text-align: center;">
                  <th>Modalidade de Pagamento (ANCINE)</th>
                  <th>Forma de Pagamento</th>
                  <th>Ações</th>
              </tr>
          </thead>
         
          <tbody border="1" id="associarFormaPagamentos"></tbody>
         <tfoot>
             <tr>
               <td id="selModalidadePagamento">
               <!--    <select>
                    <option>Meio Pagamento</option>
                    <option>Meio PagamentoB</option>
                  </select> -->
               </td>
               <td id="selFormaPagamento">
                  <!-- <select>
                    <option>Meio Pagamento</option>
                    <option>Meio PagamentoB</option>
                  </select> -->
               </td>
               <td>
                  <input type="button" id="btSalvar" class="button" name="" value="Salvar" style="display: none;" onclick="salvarNovo();">
                  <input type="button" id="btNovo" class="button" name="" value="Novo" style="display: none;" onclick="loadCombos();">
                  <input type="button" id="btCancelar" class="button" name="" value="Cancelar" style="display: none;" onclick="esconderNovo();">
               </td>
             </tr>
         </tfoot>
      
     </table>

</body>
</html>
<?php
    }
}
?>
