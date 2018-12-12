$(function() {
    PagarMe.encryption_key = $(':input[name=codCartao]:first').val() == '997' ? "ek_test_pXZAXrPly8fZro9eFKG3C26LmHzvpw" : "ek_live_QSMMW6WD1Bgio5K9aB0IIPL656ctjE";

    var $form = $("#dadosPagamento"),
        $inputs = $(":input[name=numCartao], :input[name=cardBrand], :input[name=codSeguranca], :input[name=validadeMes], :input[name=validadeAno]"),
        $cardNumber = $(":input[name=numCartao]");

    $('input[name=codCartao]').on('change', function(){
        var $this = $(this);
        if ($this.is(':radio[value=910]')) {
            $inputs.on('pagarmeToken', pagarmeToken);
            $cardNumber.on('keyup', changeBrandImage);
            $('.botao_pagamento').addClass('disabled');
        } else {
            $inputs.off('pagarmeToken');
            $cardNumber.off('keyup');
            $('.botao_pagamento').removeClass('disabled');
        }
    });
    // $(":input[name=numCartao]").on('change', function(){$(this).trigger('pagseguroBrand')});
    $inputs.on('change', function(){
        var valido = true;
        
        $inputs.each(function(){
            if ($(this).val() == '') valido = false;
        });
        
        if (valido) $(this).trigger('pagarmeToken');
    });

    function pagarmeToken() {
        console.log("pagarmeToken");
        var creditCard = new PagarMe.creditCard();
        creditCard.cardHolderName = $form.find(":input[name=nomeCartao]").val();
        creditCard.cardExpirationMonth = $form.find(":input[name=validadeMes]").val();
        creditCard.cardExpirationYear = $form.find(":input[name=validadeAno]").val();
        creditCard.cardNumber = $form.find(":input[name=numCartao]").val();
        creditCard.cardCVV = $form.find(":input[name=codSeguranca]").val();

        // pega os erros de validação nos campos do form
        var fieldErrors = creditCard.fieldErrors();

        //Verifica se há erros
        var hasErrors = false;
        for(var field in fieldErrors) { hasErrors = true; break; }

        if(hasErrors) {
            $.dialog({text: fieldErrors[Object.keys(fieldErrors)[0]]});
        } else {
            creditCard.generateHash(function(cardHash) {
                var $card_hash = $(':input[name=card_hash]').length == 1
                        ? $(':input[name=card_hash]')
                        : $('<input type="hidden" name="card_hash" class="pagseguro card_hash" />').appendTo('#dadosPagamento');

                $card_hash.val(cardHash);
                $('.botao_pagamento').removeClass('disabled');
            });

            return true;
        }

        return false;
    }

    function getBrand() {
        var creditCard = new PagarMe.creditCard();
        creditCard.cardNumber = $cardNumber.val();
        return creditCard.brand();
    }

    function changeBrandImage() {

        if ($cardNumber.val().length < 16) return false;

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