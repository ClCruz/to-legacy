$(document).ready(function(){
  $('.button').button();
  $('input[name="PARAM_CPF"]').mask("999.999.999-99");
  $('input[name="PARAM_RG"]').mask("99.999.999-9");
  $('input[name="PARAM_HR_INI"]').mask("99:99");
  $('input[name="PARAM_HR_FIM"]').mask("99:99");
  
  $('input.datePicker').datepicker({
    changeMonth: true,
    changeYear: true,
    onSelect: function(date, e) {
      if ($(this).is('input[name="PARAM_DATA_INI"]')) {
        $('input[name="PARAM_DATA_FIM"]').datepicker('option', 'minDate', $(this).datepicker('getDate'));
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
            //Adiciona a opção TODOS no select de eventos
            // $('#cboPeca').html("<option selected value=\"\">&lt; TODOS &gt;</option>" + data);
            getSetor();
            getPeriodo();
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

//DataBase, Tipo, Procedure
function ExibePeca2(NmDB, Tipo, Procedure)
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
            //$('#cboPeca').html(data);
            //Adiciona a opção TODOS no select de eventos
            $('#cboPeca').html("<option selected value=\"-1\">&lt; TODOS &gt;</option>" + data);
            getSetor();
            getPeriodo();
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
      $('input[name="PARAM_DATA_INI"]').datepicker('option', 'minDate', data.inicial);
      $('input[name="PARAM_DATA_FIM"]').datepicker('option', 'minDate', data.inicial);
      $('input[name="PARAM_DATA_FIM"]').datepicker('option', 'maxDate', data.fim);
      $('input[name="PARAM_DATA_INI"]').val(data.inicial);
      $('input[name="PARAM_DATA_FIM"]').val(data.fim);
    },
    error: function(){
      $.dialog({
        title: 'Erro...',
        text: 'Houve um erro ao obter as Datas Disponíveis!'
      });
    }
  });
}

function validar(){
  if(document.fPeca.cboTeatro.value == "-1") {
    $.dialog({
      title: 'Alerta...',
      text: 'Selecione o Local!'
    });    
    return false;
  }
  return true;
}

function limpar(){
  location.reload();
}

function validarHora(txtHora){
  var hora;
  if(txtHora.value.length == 5){
    hora = txtHora.value.replace(":","");
  }else if(txtHora.value.length > 0){
    //Hora inválida
    hora = 9999;
  }else{
    hora = 0000;    
  }

  if((hora < 0000) || (hora > 2400)){    
    $.dialog({
      title: 'Alerta...',
      text: 'Hora inválida!'
    });
    txtHora.focus();
  }
}