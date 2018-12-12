jQuery.noConflict();

jQuery(document).ready(function(){
	jQuery('#imagemVisor1').fadeIn();
	//jQuery('#botaoVisor1').addClass('selected')
});

var intervalo = window.setInterval(visorSlideShow, 4000);

function changeVisor(imagem,button) {
	jQuery('.botaoVisor').removeClass('selected');
	jQuery(button).addClass('selected');
	jQuery(button).blur();
	clearInterval(intervalo);
	jQuery('.imagemVisor').hide();
	jQuery(imagem).fadeIn('slow');
}

function visorSlideShow() {
	if (typeof visorSlideShow.imagemAtiva == 'undefined') {
		//visorSlideShow.imagemAtiva = jQuery('#imagemVisor1');
		visorSlideShow.imagemAtiva = 0;
	}

	var selector = '#imagemVisor'+visorSlideShow.imagemAtiva;
	jQuery(selector).hide();
	//jQuery('.botaoVisor').removeClass('selected');
	//visorSlideShow.imagemAtiva = jQuery(visorSlideShow.imagemAtiva).next();
	visorSlideShow.imagemAtiva++;
	selector = '#imagemVisor'+visorSlideShow.imagemAtiva;
	
	if (jQuery(selector).length == 0) {
		visorSlideShow.imagemAtiva = 0;
		selector = '#imagemVisor'+visorSlideShow.imagemAtiva;
	}
	//var botao = '#botaoVisor'+ visorSlideShow.imagemAtiva;
	//jQuery(botao).addClass('selected');
	jQuery(selector).fadeIn('slow');
	/*
	if (visorSlideShow.imagemAtiva.length == 0) {
		visorSlideShow.imagemAtiva = jQuery('#imagemVisor1');
	}
	*/
	/*
	if (typeof visorSlideShow.imagemAtiva == 'undefined' ||
		visorSlideShow.imagemAtiva == null) {
		visorSlideShow.imagemAtiva = jQuery('#imagemVisor1');
	}
	*/
	//visorSlideShow.imagemAtiva.fadeIn('slow');
}