$(function() {
    var $form = $("#dadosPagamento"),
        $inputs = $(":input[name=numCartao], :input[name=cardBrand], :input[name=codSeguranca], :input[name=validadeMes], :input[name=validadeAno]"),
        $cardNumber = $(":input[name=numCartao]"),
        $brand = ($(':input[name=cardBrand]').length ? $(':input[name=cardBrand]') : $('<input type="hidden" name="cardBrand" />').appendTo($form));

    $cardNumber.on('change', function(){
        var brand = getBrand();

        $brand.val(brand == 'mastercard' ? 'master' : brand);

        changeBrandImage();

        if ($brand.val() != 'unknown' && $brand.val() != "")
            $('.botao_pagamento').removeClass('disabled');
        else
            $('.botao_pagamento').addClass('disabled');
    });

    function getBrand() {
        var creditCard = new PagarMe.creditCard();
        creditCard.cardNumber = $cardNumber.val();
        return creditCard.brand();
    }

    function changeBrandImage() {

        if ($cardNumber.val().length < 14) return false;

        var brand = getBrand(),
            img_path = '../images/cartoes/ico_'+brand+'.png';

        $.get(img_path)
        .done(function(){
            $('input[name=codCartao]:checked').next('label').find('img').attr('src', img_path);
        })
        .fail(function() {
            img_path = '../images/cartoes/ico_default.png';
            $('input[name=codCartao]:checked').next('label').find('img').attr('src', img_path);
        });
    }
});