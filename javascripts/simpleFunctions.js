// Avoid `console` errors in browsers that lack a console.
(function() {
    var method;
    var noop = function () {};
    var methods = [
        'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
        'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
        'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
        'timeline', 'timelineEnd', 'timeStamp', 'trace', 'warn'
    ];
    var length = methods.length;
    var console = (window.console = window.console || {});

    while (length--) {
        method = methods[length];

        // Only stub undefined methods.
        if (!console[method]) {
            console[method] = noop;
        }
    }
}());

$(document).ready(function () {
    simples.init();
});

function hasNewLine() {
    if ($('#newLine').length == 0) {
        return true;
    } else {
        $.dialog({
            title: 'Atenção...',
            text: 'Já existe uma linha em edição!<br><br>Favor salvá-la antes de continuar.'
        });
        return false;
    }
}

function setDatePickers() {
    $('input.datePicker').datepicker({
        minDate: +0,
        changeMonth: true,
        changeYear: true
    });
    $('input.datePicker').datepicker('option', $.datepicker.regional['pt-BR']);
}

function verificaCPF(cpf) {
    if(isNaN(cpf) == true) {
        return false;
    } else {
        if((cpf == '11111111111') || (cpf == '22222222222') ||
            (cpf == '33333333333') || (cpf == '44444444444') ||
            (cpf == '55555555555') || (cpf == '66666666666') ||
            (cpf == '77777777777') || (cpf == '88888888888') ||
            (cpf == '99999999999') || (cpf == '00000000000')) {
            return false;
        } else {
            //PEGA O DIGITO VERIFIACADOR
            var dv_informado = cpf.substr(9, 2);
            var digito = [];
            for(var i=0; i <= 8; i++) {
                digito[i] = cpf.substr(i, 1);
            }

            //CALCULA O VALOR DO 10º DIGITO DE VERIFICAÇÂO
            var posicao = 10;
            var soma = 0;

            for(i = 0; i <= 8; i++) {
                soma += digito[i] * posicao;
                posicao--;
            }

            digito[9] = soma % 11;

            if (digito[9] < 2) {
                digito[9] = 0;
            } else {
                digito[9] = 11 - digito[9];
            }

            //CALCULA O VALOR DO 11º DIGITO DE VERIFICAÇÃO
            posicao = 11;
            soma = 0;

            for (i = 0; i <= 9; i++) {
                soma += digito[i] * posicao;
                posicao--;
            }

            digito[10] = soma % 11;

            if (digito[10] < 2) {
                digito[10] = 0;
            } else {
                digito[10] = 11 - digito[10];
            }

            //VERIFICA SE O DV CALCULADO É IGUAL AO INFORMADO
            var dv = digito[9] * 10 + digito[10];

            if (dv != dv_informado) {
                return false;
            } else {
                return true;
            }
        }
    }
}

function verificaCNPJ(str){
    str = str.replace('.','');
    str = str.replace('.','');
    str = str.replace('.','');
    str = str.replace('-','');
    str = str.replace('/','');
    cnpj = str;
    var numeros, digitos, soma, i, resultado, pos, tamanho, digitos_iguais;
    digitos_iguais = 1;
    if (cnpj.length < 14 && cnpj.length < 15)
        return false;
    for (i = 0; i < cnpj.length - 1; i++)
        if (cnpj.charAt(i) != cnpj.charAt(i + 1))
        {
            digitos_iguais = 0;
            break;
        }
    if (!digitos_iguais)
    {
        tamanho = cnpj.length - 2
        numeros = cnpj.substring(0,tamanho);
        digitos = cnpj.substring(tamanho);
        soma = 0;
        pos = tamanho - 7;
        for (i = tamanho; i >= 1; i--)
        {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2)
                pos = 9;
        }
        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != digitos.charAt(0))
            return false;
        tamanho = tamanho + 1;
        numeros = cnpj.substring(0,tamanho);
        soma = 0;
        pos = tamanho - 7;
        for (i = tamanho; i >= 1; i--)
        {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2)
                pos = 9;
        }
        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != digitos.charAt(1))
            return false;
        return true;
    }
    else
        return false;
}

var simples =
{
    urlParamns: null,

    init: function () {
        this.setVars();
        this.getUrlParamns();
    },

    serverURL: function ()
    {
        from = location.pathname.split('/');
        from = from[from.length-1];
        return from;
    },

    setVars: function ()
    {

    },

    isInt: function (n){
        return Number(n) === n && n % 1 === 0;
    },

    isFloat: function(n) {
        return Number(n) === n && n % 1 !== 0;
    },

    /*
     * Pega os parametros GET e envia para um objeto Javascript
     * */
    getUrlParamns: function()
    {
        var paramns = document.location.search;
        paramns = paramns.replace('?','&');
        paramns = paramns.split('&');
        var obj = {};
        var i =0;
        for(x in paramns)
        {
            var item = paramns[x];
            if (item != '')
            {
                item = item.split('=');
                eval("obj['"+item[0]+"'] = {}");
                eval("obj['"+item[0]+"'].value = '"+item[1]+"';");
                i++;
            }
        }
        simples.urlParamns = obj;
    },

    /*
     * Cria ou altera novos parametros GET que serão utilizados depois em goTo()
     * */
    setParamns: function(paramn, value, paramns)
    {
        paramns = ( typeof paramns == 'object' ) ? paramns : {};

        //Caso não envie value, apenas PARAMN e PARAMNS...
        if ( typeof value == 'object') { paramns = value }

        paramns.action = ( !paramns.action ) ? 'new' : paramns.action ;

        console.log(paramns);

        if (paramns.action == 'new')
        {
            if ( simples.urlParamns[paramn] )
            {
                simples.urlParamns[paramn].value = value;
            }
            else
            {
                simples.urlParamns[paramn] = {};
                simples.urlParamns[paramn].value = value;
            }
        }
        else if (paramns.action == 'delete')
        {
            simples.deleteParam(paramn)
        }

        if ( paramns.reload == true ) { simples.reloadWithParamns(); }
    },

    deleteParam: function (param)
    {
        console.log(simples.urlParamns);
        if ( simples.urlParamns[param] )
        {
            console.log(param+" deletado");
            delete simples.urlParamns[param];
        }
    },

    reloadWithParamns: function()
    {
        var i 	= 0;
        var str = '';
        for(x in simples.urlParamns)
        {
            str += ( i == 0 ) ? '?' : '&';
            str += x+'='+simples.urlParamns[x].value;
            i++;
        }

        document.location = document.location.origin + document.location.pathname + str;
    },

    /*
    * Função para selecionar página de navegação via SELECT
    * utilização em Paginator::__paginate()
    * */
    selectPage: function (e)
    {
        this.setParamns('page', e.value);
        this.reloadWithParamns();
    },

    replaceSpecialChars: function (str)
    {
        str = str.replace(/[ÀÁÂÃÄÅ]/,"A");
        str = str.replace(/[àáâãäå]/,"a");
        str = str.replace(/[ÈÉÊË]/,"E");
        str = str.replace(/[éèëê]/,"e");
        str = str.replace(/[ÍÌÏÎ]/,"I");
        str = str.replace(/[íìïî]/,"i");
        str = str.replace(/[ÓÒÖÔÕ]/,"O");
        str = str.replace(/[óòöôõ]/,"o");
        str = str.replace(/[ÚÙÜÛ]/,"u");
        str = str.replace(/[úùüû]/,"u");
        str = str.replace(/[Ç]/,"C");
        str = str.replace(/[ç]/,"c");

        //return str;
        // o resto 
        return str.replace(/[^a-z0-9]/gi,'');
    },

    preventGetCEP: false, //Qdo a opção é EXTERIOR (estrangeiro) não pegar cep

    getCEP: function (element, paramns)
    {
        if ( typeof paramns != 'object' ) { paramns = {} }

        if ( !paramns.prefix ) { paramns.prefix = ''; }
        if ( !paramns.getnow ) { paramns.getnow = false; }

        var prefix = paramns.prefix;

        if ( !paramns.getnow ) {
            $(element).keyup(function (){

                var leng = this.value.length;
                if (leng == this.maxLength && !simples.preventGetCEP)
                {
                    __get(this);
                }else{
                    SetFormEndereco('reset');
                }
            });
        }else{
            __get($(element)[0]);
        }
        
        function setData(data, param, from) {
            
        }

        function __get(input){
            $('.alert').hide();
            var cep = input.value.replace('-', '');

            $.ajax({
                url: 'https://api.postmon.com.br/v1/cep/'+cep,
                dataType: 'json',
                success: function (data) {
                    try{
                        SetFormEndereco(data);
                    } catch (e){
                        console.log({e:e});
                        $.dialog({ text: 'O CEP foi encontrado, mas ocorreu algum erro ao preencher o formulário de endereço. Favor entrar em contato com o administrador do Sistema e preencher o endereço manualmente.' });
                    }
                },
                error: function (error) {
                    console.log(error);
                    SetFormEndereco('reset');
                    $.dialog({ text: 'CEP não encontrado. Por favor, verifique se foi digitado corretamente.', autoHide: { set: true, time: 6000 } });
                }
            });
        }
        
        function verificaEstadoSigla(sigla)
        {
            sigla = sigla.toLowerCase();

            var estados = {
                ac: 'Acre',
                al: 'Alagoas',
                ap: 'Amapá',
                am: 'Amazonas',
                ba: 'Bahia',
                ce: 'Ceará',
                df: 'Distrito Federal',
                es: 'Espírito Santo',
                go: 'Goiás',
                ma: 'Maranhão',
                mt: 'Mato Grosso',
                ms: 'Mato Grosso do Sul',
                mg: 'Minas Gerais',
                pa: 'Pará',
                pb: 'Paraíba',
                pr: 'Paraná',
                pe: 'Pernambuco',
                pi: 'Piauí',
                rj: 'Rio de Janeiro',
                rn: 'Rio Grande do Norte',
                rs: 'Rio Grande do Sul',
                ro: 'Rondônia',
                rr: 'Roraima',
                sc: 'Santa Catarina',
                sp: 'São Paulo',
                se: 'Sergipe',
                to: 'Tocantins'
            };

            return estados[sigla];
        }

        function SetFormEndereco(data)
        {
            var cidade      = document.getElementById(prefix+"cidade");
            var bairro      = document.getElementById(prefix+"bairro");
            var endereco    = document.getElementById(prefix+"endereco");
            var estado      = document.getElementById(prefix+'estado');

            if ( typeof data == 'string' &&  data == 'reset')
            {
                estado.options.selectedIndex = ( estado.options.selectedIndex == 28 ) ? 28 : 0;
                $(estado).selectbox('detach');
                $(estado).selectbox('attach');

                $(cidade).val('');
                $(bairro).val('');
                $(endereco).val('');

                return;
            }

            var opts = estado.getElementsByTagName('option');

            for(var x = 0; x < opts.length; x++)
            {
                var opt = opts[x];
                var optValue 	= simples.replaceSpecialChars(opt.text.toLocaleLowerCase());

                /*Em alguns casos, estado info não é trazido. Pegar a SIGLA e substituir por nome para encontrar no form*/
                if (data.estado_info == undefined || data.estado_info.nome == undefined) {
                    data.estado_info = ( !data.estado_info ) ? {} : data.estado_info;
                    data.estado_info.nome = verificaEstadoSigla(data.estado);
                }
                
                var estadoNome 	= simples.replaceSpecialChars(data.estado_info.nome.toLowerCase());
                if (optValue == estadoNome)  { opt.selected = true; }
            }

            $(estado).selectbox('detach');
            $(estado).selectbox('attach');

            $(cidade).val(data.cidade);
            $(bairro).val(data.bairro);
            $(endereco).val(data.logradouro);

            nextFocus = ( data.logradouro != undefined ) ? 'numero_endereco' : 'bairro';
            $('#'+prefix+nextFocus).focus();

        }
    },

    createElement: function (tag, paramns)
    {
        var element = document.createElement(tag);


        if ( paramns.attrs != undefined )
        {
            for(attr in paramns.attrs)
            {
                element.setAttribute(attr, paramns.attrs[attr]);
            }
        }

        if ( paramns.html != undefined )
        {
            element.innerHTML = paramns.html;
        }

        if ( paramns.style != undefined )
        {
            element.setAttribute('style', paramns.style);
        }

        return element;
    }

};

function verificaNumero(e) 
{
    if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) 
    {
        return false;
    }
}