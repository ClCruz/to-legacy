/**
 * @author BlackLotus
 */
jQuery.noConflict();
jQuery(document).ready(function($){
});

function changeAba(target) {
	jQuery('.aba_interna').hide();
	jQuery("#"+target).fadeIn('fast');
	jQuery(".aba_minha_conta").removeClass("aba_down");
	jQuery("#aba_"+target).addClass('aba_down')
}
