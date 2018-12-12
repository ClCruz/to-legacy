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
                    "retrieve":true
                    // "destroy":true
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
                            url: 'associarTipoBilheteActions.php',
                            method: 'post',
                            data: actionData,
                            success: function(data){
                                destroyDatatable();
                                $('#associarTipoBilhete').html(data);
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
                var codigoCategoriaIngressoOld = $(inputBt).data('codigocategoriaingressoold');
                var CodTipBilheteOld = $(inputBt).data('codtipbilheteold');



               var codigoCategoriaIngresso  = $('#codigoCategoriaIngressoIn_' + item);
                var CodTipBilhete = $('#CodTipBilheteIn_' + item);
            

                var codigoCategoriaIngressoTx = $('#codigoCategoriaIngressoTx_' + item);
                var CodTipBilheteTx = $('#CodTipBilheteTx_' + item);
            
                

                if(codigoCategoriaIngresso.is(':hidden') || cancel){
                    
                    if(cancel)
                         $('.btEditar[data-item='+item+']').val('Editar');
                    else
                        $(inputBt).val('Salvar');


                    $('.btCancelar[data-item='+item+']').toggle();
                }else{

                    if(codigoCategoriaIngresso.val()=='' || codigoCategoriaIngresso.val() <= 0
                       || CodTipBilhete.val()=='' || CodTipBilhete.val() <= 0
                      
                    ){
                        
                            $.dialog({
                                title:'Associar Tipo Bilhete',
                                text:'Todas as opções precisam serem selecionadas!'
                            });
                        return;
                    }

                    var actionData = 'Acao=2&codigoCategoriaIngresso='+codigoCategoriaIngresso.val()+'&CodTipBilhete='+CodTipBilhete.val()+'&idBase='+idBase+'&codigoCategoriaIngressoOld='+codigoCategoriaIngressoOld+'&CodTipBilheteOld='+CodTipBilheteOld;

                    if(codigoCategoriaIngresso.val()==codigoCategoriaIngressoOld 
                      && CodTipBilhete.val() == CodTipBilheteOld ){
                         $.dialog({
                                title:'Associar Tipo Bilhete',
                                text:'Escolha valores diferentes para realizar alteração!'
                            });
                        return;
                    } 

                    $.ajax({
                        url: 'associarTipoBilheteActions.php',
                        method: 'post',
                        data: actionData,
                        success: function(data){

                            // codigoCategoriaIngressoTx.html(codigoCategoriaIngresso.text());
                            // CodTipBilheteTx.html(CodTipBilhete.text());

                                  //$('#msgResultado').html(data);

                                  $.dialog({
                                      title:'Nova Associação Tipo do Bilhete',
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

                codigoCategoriaIngresso.toggle();
                CodTipBilhete.toggle();
           
                codigoCategoriaIngressoTx.toggle();
                CodTipBilheteTx.toggle();



                // console.log(codSala);
        }

        function clickCancel(inputBt){
            clickEdit(inputBt,true);
        }

        function carregarComboModalidade(){
                 var actionData = 'Acao=3';


                    $.ajax({
                        url: 'associarTipoBilheteActions.php',
                        method: 'post',
                        data: actionData,
                        success: function(data){

                            $('#selcodigoCategoriaIngresso').html(data);

                        },
                        beforeSend: function(){
                           // $('#msgResultado').html('Salvando...');
                        }
                    });
        }
        function carregarComboCodTipBilhete(){
                   var idBase = $('#cboTeatro').val();
  
                   var actionData = 'Acao=4&idBase='+idBase;


                    $.ajax({
                        url: 'associarTipoBilheteActions.php',
                        method: 'post',
                        data: actionData,
                        success: function(data){

                            $('#selCodTipBilhete').html(data);

                        },
                        beforeSend: function(){
                            //$('#msgResultado').html('Salvando...');
                        }
                    });
        }

        function loadCombos(){
            carregarComboModalidade();
            carregarComboCodTipBilhete();
            $('#btSalvar').show();
            $('#btCancelar').show();
            $('.cbohidden').show();
            $('#btNovo').hide();
        }

        function salvarNovo(){
             var idBase = $('#cboTeatro').val();
             var codigoCategoriaIngresso = $('#cbocodigoCategoriaIngresso').val();
             var CodTipBilhete = $('#cboCodTipBilhete').val();


             var actionData = 'Acao=2&codigoCategoriaIngresso='+codigoCategoriaIngresso+'&CodTipBilhete='+CodTipBilhete+'&idBase='+idBase;
             //console.log(actionData);

              $.ajax({
                  url: 'associarTipoBilheteActions.php',
                  method: 'post',
                  data: actionData,
                  success: function(data){

                      esconderNovo();

                       $.dialog({
                                      title:'Nova Associação Tipo do Bilhete',
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
            var codigocodigoCategoriaIngresso = $(input).data('codigocategoriaingresso');
            var codigoTipoCodTipBilhete = $(input).data('codtipbilhete');
            var idBase = $('#cboTeatro').val();

            var actionData = 'Acao=5&codigoCategoriaIngresso='+codigocodigoCategoriaIngresso+'&CodTipBilhete='+codigoTipoCodTipBilhete+'&idBase='+idBase;
             // console.log(actionData);

              $.ajax({
                  url: 'associarTipoBilheteActions.php',
                  method: 'post',
                  data: actionData,
                  success: function(data){

                      esconderNovo();

                       $.dialog({
                                      title:'Apagar Associação Tipo do Bilhete',
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
    <h2>Associar Tipo do Bilhete</h2>
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
                  <th>Categoria do Ingresso (ANCINE)</th>
                  <th>Tipo do Bilhete</th>
                  <th>Ações</th>
              </tr>
          </thead>
         
          <tbody border="1" id="associarTipoBilhete"></tbody>
         <tfoot>
             <tr>
               <td id="selcodigoCategoriaIngresso">
               <!--    <select>
                    <option>Meio Pagamento</option>
                    <option>Meio PagamentoB</option>
                  </select> -->
               </td>
               <td id="selCodTipBilhete">
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
