$(function(){
  $('.outras_datas').on('click', function(){
    $overlay = $('#overlay');

    if ($overlay.find('.datas').is(':empty')) {
      $.ajax({
        url: 'timeTable.php',
        cache: false,
        data: {'evento': $.getUrlVar('evento', $("script[src*='overlay_datas']").attr('src'))}
      }).done(function(data) {
        var horarios = '';

        data.evento.nome = data.evento.nome ? data.evento.nome : '';
        data.evento.genero = data.evento.genero ? data.evento.genero : '';
        data.evento.classificacao = data.evento.classificacao ? data.evento.classificacao : '';
        data.evento.duracao = data.evento.duracao ? data.evento.duracao : '';
        data.evento.local = data.evento.local ? data.evento.local : '';
        data.evento.bairro = data.evento.bairro ? data.evento.bairro : '';
        data.evento.cidade = data.evento.cidade ? data.evento.cidade : '';
        data.evento.sigla_estado = data.evento.sigla_estado ? data.evento.sigla_estado : '';

        $overlay.find('h1').text(data.evento.nome);
        $overlay.find('.genero').text(data.evento.genero);
        $overlay.find('.classificacao').addClass('c'+data.evento.classificacao).text(data.evento.classificacao);
        $overlay.find('.duracao').text(data.evento.duracao);
        $overlay.find('.teatro').text(data.evento.local);
        $overlay.find('.teatro_info').text(data.evento.bairro+' - '+data.evento.cidade+' - '+data.evento.sigla_estado);

        $.each(data.horarios, function(i,v) {
          horarios += '<a href="etapa1.php?apresentacao='+v.idApresentacao+'&eventoDS='+encodeURIComponent(data.evento.nome)+'" class="botao data">' +
                  '<span class="data">' +
                    '<span class="numero">'+v.nDia+'</span>' +
                    '<span class="mes">'+v.tMes+'</span>' +
                  '</span>' +
                  '<span class="dia_hora">'+v.tSemana+' '+v.nHora+'h'+v.nMinuto+'</span>' +
                '</a>';
        });

        $overlay.find('.datas').html(horarios);

        $overlay.find('.datas a.botao.data').on('click', function(){document.location = $(this).attr('href')})
      });
    }
  });
});