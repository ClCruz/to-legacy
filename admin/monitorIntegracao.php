<?php
require_once('../settings/functions.php');

$mainConnection = mainConnection();

session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 530, true)) {

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
             
            $('input.datepicker').datepicker({
                changeMonth: true,
                changeYear: true,
                onSelect: function(date, e) {
                    if ($(this).is('#dt_inicial')) {
                        $('#dt_final').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                    }
                }
            }).datepicker('option', $.datepicker.regional['pt-BR']);


            $('.situacaoSala').click(function(){
                situacaoSalaDiaCinematografico(this);
            });




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

            function exibirGrid(){
               $('#gridListagem').show();
            }
            function esconderGrid(){
               $('#gridListagem').hide();
            }

            function buscarSalasDiasCinema(){
              console.log("buscarSalasDiasCinema()");
                // alert($('#cboSala').find(':selected').val());
                var local = $('#cboTeatro').find(':selected').val();
                var sala  = $('#cboSala').find(':selected').val(); 



                    $.ajax({
                        url: 'monitorIntegracaoActions.php',
                        type: 'post',
                        data: 'Acao=3&idBase='+local+'&CodSala='+sala+'&DatIni=' + $("#dt_inicial").val() + '&DatFim='+ $("#dt_final").val(),
                        success: function(data){
                          destroyDatatable();
                            $('#salasDiasCinema').html(data);
                            $('.button').button();
                            exibirGrid();
                          initDatatable();  
                        },
                        error: function(){
                            $.dialog({
                                title: 'Erro...',
                                text: 'Erro na chamada dos dados.'
                            });
                        }
                    });

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
                                url: 'monitorIntegracaoActions.php',
                                type: 'post',
                                data: 'NomeBase='+ NmDB +'&Proc='+ Procedure,
                                success: function(data){
                                    $('#divSala').html(data);
                                   // buscarSalasDiasCinema();
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
                            document.getElementById("divSala").innerHTML = '<SELECT disabled id="cboSala" name="cboSala" style="width: 250px;"><option value="">Não Selecionado</option></select>';
                            //buscarSalasDiasCinema();
                            break;
                    }
                }
            };
            function PreencheDescricao(){
                var_descTeatro = $('#cboTeatro').val();
                var_descPeca = $('#cboPeca').val();
                //buscarSalasDiasCinema();
            };

            function validarBusca(){
              if($('#dt_inicial').val()=='' || $('#dt_final').val()=='')
                return false;
              return true;
            }

            function buscar(){

              if(!validarBusca()){
                $.dialog({
                    title: "Atenção",
                    text: "Preencha todos os campos para realizar a pesquisa!"
                });
                return;
              }
              initDatatable();
              buscarSalasDiasCinema();
            }

            
            function situacaoSalaDiaCinematografico(input){
              var msg = "";
              var cod = $(input).data("cod-situacao");
              var protoc = $(input).data("cod-protocolo");
               console.log(cod+' '+protoc); 
                  switch(cod){
                    case 'N':
                        msg = "<p>Cód. do Protocolo: "+protoc+"</p><p>Não acatado (Os dados não foram acatados por não passarem pela validação inicial que faz verificações básicas como completude e tipo dos dados, não gerando protocolo)</p>";
                      break;
                    case 'A':
                        msg = "<p>Cód. do Protocolo: "+protoc+"</p></p>Em Análise (Os dados foram enviados obedecendo à estrutura especificada neste manual e está aguardando processamento mais detalhado da consistência dos dados, validade de código, etc)</p>";
                      break; 
                    case 'E':
                        msg = "<p>Cód. do Protocolo: "+protoc+"</p></p>Com Erro (Os dados enviados foram processados e apresentaram inconsistências, incorreções, e deverão ser retificados pelo exibidor)</p>";
                      break;
                    case 'V':
                        msg = "<p>Cód. do Protocolo: "+protoc+"</p></p>Validado (Os dados enviados foram processados com sucesso e acatados pela ANCINE)</p>";
                      break;
                    case 'R':
                        msg = "<p>Cód. do Protocolo: "+protoc+"</p></p>– Recusado (Durante auditoria dos dados enviados e em princípio validados, foram encontrados indícios de irregularidades, tornando-se uma situação de inadimplência)</p>";
                      break;   
                                                                                        
                  }

               $.dialog({
                  title: "Situação da Sala dia Cinematográfico",
                   text: msg
               });   
            }

            var enviando = false;

            function enviarParaAncine(input){
               var codSala = $(input).data("codsala");
               var dtApresentacao = $(input).data("dt-apresentacao");
               var id = $(input).data("id-arquivo");
               var retificar = $(input).data("retificar");
               
               var idBase = $('#cboTeatro').val();
               var textOld = $(input).val();


               $(input).prop('disabled',true);


                 $.ajax({
                      url: 'monitorIntegracaoActions.php',
                      type: 'post',
                      data: 'Acao=6&CodBase='+ idBase + '&CodSala='+ codSala +'&dtApresentacao='+ dtApresentacao + '&idArquivo=' + id +'&retificar=' + retificar ,
                      success: function(data){
                          $.dialog({
                              title: 'Monitor Integração',
                              text: data,
                              iconClass: '',
                              uiOptions:{
                                  buttons:{
                                      'Ok': function(){
                                          $(input).prop('disabled',false);           
                                          $(input).val(textOld);
                                          $(this).dialog('close');
                                      },
                                      'Enviar': function(){
                                            enviarParaAncineOk(input);
                                          $(this).dialog('close');
                                      },
                                      'Cancelar': function(){
                                          $(input).prop('disabled',false);           
                                          $(input).val(textOld);  
                                          $(this).dialog('close');
                                      }
                                  },
                                  close: function() {
                                    if(!enviando){
                                      $(input).prop('disabled',false);           
                                      $(input).val(textOld);  
                                    }
                                    $(this).dialog('destroy').remove();

                                  }
                               }
                          }); 
                          $('.button').button();
                      },
                      beforeSend: function(){
                           $(input).val('Enviando..');
                      },
                      error: function(){
                          $.dialog({
                              title: 'Erro...',
                              text: 'Erro na chamada dos dados.'
                          });
                      }
                  });
            }

            function enviarParaAncineOk(input){
               enviando = true;
               var codSala = $(input).data("codsala");
               var dtApresentacao = $(input).data("dt-apresentacao");
               var id = $(input).data("id-arquivo");
                var retificar = $(input).data("retificar");

               var idBase = $('#cboTeatro').val();

               $(input).prop('disabled',true);


               // console.log('Acao=5&CodBase='+ idBase + '&CodSala='+ codSala +'&dtApresentacao='+ dtApresentacao + '&idArquivo=' + id );

                 $.ajax({
                      url: 'monitorIntegracaoActions.php',
                      type: 'post',
                      data: 'Acao=5&CodBase='+ idBase + '&CodSala='+ codSala +'&dtApresentacao='+ dtApresentacao + '&idArquivo=' + id  +'&retificar=' + retificar  ,
                      success: function(data){
                           $.dialog({
                              title: 'Monitor Integração',
                              text: data
                          });
                          
                          buscarSalasDiasCinema();
                          enviando = false;
                      },
                      beforeSend: function(){
                           $(input).val('Enviando..');
                      },
                      error: function(){
                          $.dialog({
                              title: 'Erro...',
                              text: 'Erro na chamada dos dados.'
                          });
                          enviando = false;
                      }
                  });

            }

            function exibirMensagem(input){
                var msg = $(input).data('mensagem');
                          
                          $.dialog({
                              title: 'Monitor Integração',
                              text: msg

                          });

            }

            function showDiv(div){
              $(div).toggle();
            }
            function showObra(id){
              var qtdObra = $('#countObras').val();
              for(var i=0; i < qtdObra; i++){
                if(i==id){
                  $('#obra_'+i).show();
                }else{
                  $('#obra_'+i).hide();
                }
              }

            }
            function showTipoAssento(id){
              var qtdTipoAssento = $('#countTipoAssento_'+id).val();
              for(var i=0; i < qtdTipoAssento; i++){
                if(i==id){
                  $('#tipoAssento_'+i).show();
                }else{
                  $('#tipoAssento_'+i).hide();
                }
              }

            }

            function consultarProtocolo(input){
               var protocolo = $(input).data("cod-protocolo");
               var id = $(input).data("id-arquivo");
                
               var idBase = $('#cboTeatro').val();

               $(input).prop('disabled',true);


               // console.log('Acao=5&CodBase='+ idBase + '&CodSala='+ codSala +'&dtApresentacao='+ dtApresentacao + '&idArquivo=' + id );

                 $.ajax({
                      url: 'monitorIntegracaoActions.php',
                      type: 'post',
                      data: 'Acao=2&CodBase='+ idBase + '&idArquivo=' + id  + '&CodProtocolo=' + protocolo,
                      success: function(data){
                           $.dialog({
                              title: 'Monitor Integração',
                              text: data
                          });
                          buscarSalasDiasCinema();
                      },
                      beforeSend: function(){
                           $(input).val('Enviando..');
                      },
                      error: function(){
                          $.dialog({
                              title: 'Erro...',
                              text: 'Erro na chamada dos dados.'
                          });
                      }
                  });
            }
    </script>
    <style type="text/css">
        form table{width: 600px !important;}
    </style>
   
   
</head>
<body>
    <h2>Monitor de Integração</h2>
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
                  <strong>Sala:</strong><br>
                  <div id="divSala"></div>
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

      <div><strong id="msgResultado"></strong></div>


      <!-- Listagem --> 
      <table id="gridListagem" class="ui-widget ui-widget-content datatable" style="display: none;">
      
      
        <br>
        <br>
          <thead>
              <tr class="ui-widget-header " style="text-align: center;">
                  <th style="">Sala</th>
                  <th style="">Dia Cinematográfico</th>
                  <th style="">Situação Perante Ancine</th>
                  <th style="">Ações</th>
              </tr>
          </thead>
         
          <tbody border="1" id="salasDiasCinema">
           <tr>
              <td style="text-align: center;">
                <label id="nomeSalaTx_1">Sala 1</label>
              </td>
              <td style="text-align: center;">
                <label id="nrRegExibTx_1">18/05/2017</label> 
              </td>
              <td style="text-align: center;">
                <label id="nrRegSalaTx_1"><a href="javascript:void(0);" class="situacaoSala" data-cod-protocolo="12345956458" data-cod-situacao="0">Em análise</a></label>
              </td>
              <td style="text-align: center;">
                <input type="button" class="button btEditar" data-codsala="1" data-item="1" value="Retificar" />
              </td>
            </tr>       
            <tr>
              <td style="text-align: center;">
                <label id="nomeSalaTx_1">Sala 1</label>
              </td>
              <td style="text-align: center;">
                <label id="nrRegExibTx_1">20/05/2017</label> 
              </td>
              <td style="text-align: center;">

              </td>
              <td style="text-align: center;">
                <input type="button" class="button btEditar" data-codsala="1" data-item="1" value="Enviar para ANCINE" />
              </td>
            </tr> 
            <tr>
              <td style="text-align: center;">
                <label id="nomeSalaTx_1">Sala 2</label>
              </td>
              <td style="text-align: center;">
                <label id="nrRegExibTx_1">10/05/2017</label> 
              </td>
              <td style="text-align: center;">
                <label id="nrRegSalaTx_1" data-codsala="1" ><a href="javascript:void(0);" class="situacaoSala" data-cod-protocolo="12345956458" data-cod-situacao="1">Adimplente</a></label>
              </td>
              <td style="text-align: center;">
                
              </td>
            </tr>  
          </tbody>
         
      
     </table>
     <!--Fim Listagem-->
    



<script>
    ExibePeca('','Peca','');
</script>
</body>
</html>
<?php
        if (sqlErrors ())
            echo sqlErrosr();
    }
}
?>
