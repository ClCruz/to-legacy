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
  if (NmDB != "")
  {
    switch(Tipo)
    {
      case 'Peca':
        $.ajax({
          url: '../admin/getEventos.php',
          type: 'post',
          data: 'NomeBase='+ NmDB +'&Proc='+ Procedure,
          success: function(data){
            $('#cboPeca').html(data);
          },
          error: function(){
            $.dialog({
              title: 'Erro...',
              text: 'Houve um erro ao carregar os Eventos!'
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
}

function PreencheDescricao(){
  var_descTeatro = $('#cboTeatro').val();
  var_descPeca = $('#cboPeca').val();
}

var Janela

function getSetor(){
  var CodPeca = $('#cboPeca').val();
  $.ajax({
    url: '../admin/getSetor.php',
    mehotd: 'post',
    data: 'CodPeca='+ CodPeca,
    success: function(data){
      $('#cboSala').html(data);
      getPeriodo();
    },
    error: function(){
      $.dialog({
        title: 'Erro...',
        text: 'Houve um erro ao obter os Setores!'
      });
    }
  });
}

function getPeriodo(){
  var codPeca = $('#cboPeca').val();
  $.ajax({
    url: 'getPeriodo.php',
    type: 'post',
    data: 'CodPeca='+ codPeca,
    dataType: 'json',
    success: function(data){
      $('input[name="txtData1"]').val(data.inicial);
      $('input[name="txtData2"]').val(data.fim);
      $('input[name="txtData1"]').datepicker('option', 'minDate', data.inicial);
      $('input[name="txtData2"]').datepicker('option', 'maxDate', data.fim);
    },
    error: function(){
      $.dialog({
        title: 'Erro...',
        text: 'Houve um erro ao obter as Datas Disponíveis!'
      });
    }
  });
}

function validar()
{
  if(document.fPeca.cboPeca.value == "")
  {
    $.dialog({
      title: 'Alerta...',
      text: 'Selecione o evento'
    });
    document.fPeca.cboPeca.focus();
    return;
  }

  if(document.fPeca.cboSala.value == ""
    && !document.fPeca.chkSmall.checked)
    {
    $.dialog({
      title: 'Alerta...',
      text: 'Selecione a setor'
    });
    document.fPeca.cboSala.focus();
    return;
  }

  if((document.fPeca.txtData1.value == ""
    || document.fPeca.txtData2.value == "")
  && document.fPeca.chkSmall.checked)
  {
    $.dialog({
      title: 'Alerta...',
      text: 'Selecione um intervalo de datas válido'
    });
    document.fPeca.cboSala.focus();
    return;
  }
  
  var url = "relVendasFuturas.php";
  url += "?CodPeca=" + document.fPeca.cboPeca.value;
  url += "&logo=imagem";  
  url += "&DataIni=" + document.fPeca.txtData1.value;
  url += "&DataFim=" + document.fPeca.txtData2.value;
  url += "&Sala=" + document.fPeca.cboSala.value;

  $("#loading").ajaxStart(function(){
    $(this).show();
  });

  Janela = window.open ('../admin/esperaProcesso.php?redirect=' + escape(url), "", "width=720, height=600, scrollbars=yes", "");
}

function limpar()
{
  document.fPeca.cboPeca.value = "";
  document.fPeca.cboTeatro.value = "";
  document.fPeca.cboSala.value = "";
}


