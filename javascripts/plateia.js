$(function() {
  var scriptVars = $.serializeUrlVars($("script[src*='plateia']").attr('src')),
  opennedClass = 'open',
  standbyClass = 'standby',
  closedClass = 'closed',
  $mapa_de_plateia = $('#mapa_de_plateia');
	
  if ($mapa_de_plateia.length == 0) {
		
    //$('#numIngressos').change(function() {
    $('.botao_avancar').click(function(event) {
      event.preventDefault();

      if ($('#numIngressos').val() == 0) {
        document.location = $('#botao_avancar').attr('href');

      } else {
        $.ajax({
          url: 'atualizarPedido.php?action=noNum',
          data: scriptVars + '&numIngressos=' + $('#numIngressos').val() + '&' + $.serializeUrlVars(),
          type: 'post',
          success: function(data) {
            if (data != 'true') {
              tratarResposta(data);
            } else {
              document.location = $('#botao_avancar').attr('href');
            }
          }
        });
      }
    });

    // ingressos esgotados?
    if ($('#numIngressos').length == 0) {
      $('.botao_avancar').hide();
      $('.container_ingressos .container_ingresso .ingresso').hide();
    }
		
    $('#numIngressos').on('change', function(){
      setQuantidadeResumo($(this).val());
    }).trigger('change');
		
  } else {
		
    function annotation(obj) {
      return $(document.createElement('span'))
      .attr('id', obj.id)
      //.text("\u25CF")
      .addClass('annotation')
      .addClass('diametro')
      .addClass((obj.status == 'O') ? opennedClass : (obj.status == 'C') ? closedClass : standbyClass);
    }
				
    function refreshCadeiras(refreshTime) {
      $.ajax({
        url: 'annotations.php',
        data: scriptVars,
        dataType: 'json',
        success: function(data) {
          var annotations = data.cadeiras;

          $mapa_de_plateia.removeAnnotations();
          $mapa_de_plateia.addAnnotations(annotation, annotations);

          // ingressos esgotados?
          if ($(annotations).filter(function(){return this.status !== 'C'}).length == 0) {
            $('.botao_avancar').hide();
            $('.container_ingressos .container_ingresso .ingresso').hide();
            $.dialog({text:'Não há lugares disponíveis no momento para este setor.'});
          }

          if (Modernizr.touch) {
            // setup_with_touch();
          } else {
            $mapa_de_plateia.find('span').tooltip({
              track: true,
              fade: 250,
              content: function() {
                var dados = $(this).attr('title').split(' // ');

                return '<div class="tooltip tooltip__legado">'+
                          '<table><tbody><tr><td>'+dados[0]+'</td></tr></tbody></table>'+
                          '<div class="informacoes">'+
                            '<p class="local">'+dados[1]+'</p>'+
                            '<p class="descricao">clique apenas uma vez e aguarde<br>a reserva do lugar escolhido</p>'+
                          '</div>'+
                          ($(this).data('img') ? '<span>Visão aproximada do palco</span><img src="annotations.php?'+$.serializeUrlVars()+'&cadeira='+$(this).data('id')+'" class="foto-plateia">' : '')+
                        '</div>';
              }
            });
          }
          setup_without_touch();

          mapaDePlateia();

          setQuantidadeResumo($('.annotation.standby').length);
        }
      });
			
      if (refreshTime == undefined) {
        setTimeout(refreshCadeiras, 300000);
      }
    }
    refreshCadeiras();

    if ($mapa_de_plateia.width() > $mapa_de_plateia.parent().width()) {
      $mapa_de_plateia.css({'margin-left': (($mapa_de_plateia.width()-$mapa_de_plateia.parent().width())/2)*-1+'px'}).width();
    }
		
    function statusCadeira(indice, status) {
      if (status != undefined) {
        indice.data('status', status);
      } else {
        (indice.data('status') == 'O') ? indice.data('status', 'S') : indice.data('status', 'O');
      }
			
      indice
      .removeClass(opennedClass)
      .removeClass(standbyClass)
      .removeClass(closedClass)
      .addClass((indice.data('status') == 'C') ? closedClass : (indice.data('status') == 'O') ? opennedClass : standbyClass);

      setQuantidadeResumo($('.annotation.standby').length);
    }

    function setup_with_touch() {
      $('#mapa_de_plateia span:not(.' + closedClass + ')').off('click')
      .on('click', function(e) {
        var $this = $(this),
            id = 'ttip_'+$this.attr('id'),
            title,
            $ttip = $('#'+id);
        
        if ($ttip.length == 0) {
          title = $this.attr('title').split(' // ');
          $('<div class="tooltip hidden" id="'+id+'">'+
              '<table><tbody><tr><td>'+title[0]+'</td></tr></tbody></table>'+
              '<div class="informacoes">'+
                '<p class="local">'+title[1]+'</p>'+
                '<p class="descricao">clique em selecionar para<br>reservar o lugar escolhido</p>'+
                '<a href="#" class="botao selecionar_tooltip"></a>'+
              '</div>'+
            '</div>').appendTo('body');
          $ttip = $('#'+id);
        }

        $ttip.css({position:"absolute", left:e.pageX-10,top:e.pageY-10}).fadeIn()
          .find('.selecionar_tooltip').off('click').on('click', function(e2){
            e2.preventDefault();
            span_click(e);
          });
        $this.off('mouseleave').on('mouseleave', function(){
          $ttip.fadeOut();
        });
      });
    }

    function setup_without_touch() {
      // $('#mapa_de_plateia span:not(.' + closedClass + ')').off('mouseenter mouseleave')
      // .on('mouseenter mouseleave', function() {
      //   if (!$(this).hasClass('annotationHover') && !$(this).hasClass('annotationSelected')) {
      //     $(this).addClass('annotationHover');
      //   } else {
      //     $(this).removeClass('annotationHover');
      //   }
      // });

      $('#mapa_de_plateia span:not(.' + closedClass + ')').off('click').on('click', span_click);
    }

    function span_click(e) {
      
      var $this = $(e.target),
          objSerialized = '',
          action = ($this.hasClass(standbyClass)) ? 'delete' : 'add',
          quantidade;
        
      $.each($this.data(), function(key, val) {
        var exceptions = 'tooltip events handle x y status';
        if (exceptions.indexOf(key) == -1) {
          objSerialized += key + '=' + escape(val) + '&';
        }
      });
        
      $.ajax({
        url: 'atualizarPedido.php?action=' + action,
        data: objSerialized + $.serializeUrlVars(),
        type: 'post',
        success: function(data) {
          if (data.substr(0, 4) != 'true') {
            if (data.indexOf('?') != -1 && data.length != data.indexOf('?') + 1) {
              $.dialog({
                title: 'Aviso...',
                text: data.split('?')[1]
                });
                
              var ids = data.split('?');
              ids = ids[0].split('|');
                
              for (i = 0; i < ids.length; i++) {
                var $this = $('#' + ids[i]);
                statusCadeira($this, 'C');
              }
            } else {
              tratarResposta(data);
            }
          } else {
            var ids = data.split('?');
            ids = ids[1].split('|');
              
            for (i = 0; i < ids.length; i++) {
              var $this = $('#' + ids[i]);
              statusCadeira($this);
            }
          }
        //refreshCadeiras(false);
        }
      });
    }
		
    $('.botao_avancar').click(function(event) {
      event.preventDefault();

      var href = $(this).attr('href');
			
      if ($('#mapa_de_plateia span.standby').length > 0) {
        document.location = href;
      } else {
        $.ajax({
          url: 'atualizarPedido.php',
          data: 'action=add&checking=1',
          type: 'get',
          success: function(data) {
            if (data == 'true') {
              document.location = href;
            } else {
              $.dialog({
                title:'Aviso',
                text:'Selecione um lugar/quantidade de ingressos antes de avançar.'
              });
            }
          }
        });
      }
    });

    $mapa_de_plateia.on('click', function(e){
      $('div.links a:last').remove();
      $mapa_de_plateia.off(e);
    });
  }
	
  if ($('.container_setores .container_setor').length > 1) {
    $('.container_setores').slideDown();
  }
});