/**
 * Created by Intuiti02 on 23/06/2016.
 */
var ciPopup = {

    status:         'off', //on, off, hidden

    //Elemntos HTML
    screen:         null,   //Container principal que engloba BG + Conteudo e todos os itens HTML, o popup por completo
    content:        null,   //div em branco para carregar conteúdo dinamicamente
    bg:             null,   //bg escuro para "esconder" site
    xHide:          true,   //botão X no canto superioe direito para fechar popup, default[false]
    buttons:        [],     //Botões de ação

    //outros
    newContent:     null,   //Conteúdo que será carregado em this.content
    refContent:     null,   //Conteúdo do HTML original que será clonado e deletado
    refSibling:     null,   //Sibling de referência para devolver o conteudo de onde ele foi retirado quando fechar o popup
    refParent:      null,   //Caso não exista sibling de referência, PARENT será o elemento de referência.

    //Opções
    options: {
        closeOnClick:   false   //Permite fechar ao clicar no bgblack, default[false]
    },

    init: function (elementId, options)
    {
        if (ciPopup.status == 'off') {
            this.config();
        }

        if (elementId != undefined) { this.show(elementId) }
        else{ alert('Favor informar o ID do elemento de conteúdo. Exibição de popup interrompida.'); return false; }

        ciPopup.setDefaultOptions(ciPopup, options);
    },

    reset: function ()
    {
        //Devolver conteudo de onde ele veio e zerar html do popup
        if( ciPopup.refSibling != null )
        {
            if (ciPopup.refSibling.isBefore)
                {
                    $(ciPopup.newContent).insertBefore(ciPopup.refSibling);
                }
            else
                { $(ciPopup.newContent).insertAfter(ciPopup.refSibling); }
        }
        else
        {
            $(ciPopup.refParent).append(ciPopup.newContent);
        }

        ciPopup.newContent  = null;
        ciPopup.refContent  = null;
        ciPopup.refSibling  = null;
        ciPopup.refParent   = null;
    },

    hide: function (paramns, callback, timeToCallback)
    {
        if (typeof paramns == 'function' || typeof callback == 'function')
        {
            var func = (typeof paramns == 'function') ? paramns : callback;

            //Veriricar se existe tempo de timeout para a function, default = 0
            time = 0;
            if ( simples.isInt(callback) ) { time = callback; }
            if ( simples.isInt(timeToCallback) ) { time = timeToCallback; }

            var go = setTimeout(function () {

                func();
            },time);
        }

        if (ciPopup.dialog.status) {
            ciPopup.dialog.hide();
        }

        $(ciPopup.screen).fadeOut('slow', function () {
            ciPopup.reset();
        });
    },

    show: function (id)
    {
        if ( ciPopup.options.closeOnClick )
        {
            $(ciPopup.bg).click(function () {
                ciPopup.hide();
            })
        }

        ciPopup.refContent = $('#'+id);
        ciPopup.newContent = $(ciPopup.refContent).clone(true); //pegar o conteudo em HTML

        ciPopup.getRefSibling(); //referência de onde o conteudo vem em relação ao html
        ciPopup.insertAndConfig(); //inserir HTML do conteudo, configirar e Exibir popup
    },

    //Pegar referência para onde deverá ser devolvido o conteúdo quando fechar o popup. De onde ele foi retirado?
    getRefSibling: function ()
    {
        var content = ciPopup.refContent;
        var parent = $(content).parent();

        //Se não houver referências de siblings, definir pai como referência
        var totalSiblings = $(parent).children().length;

        if (totalSiblings == 1)
        {
            ciPopup.refParent = parent;

            //Remover elemnto para não duplicar IDs e não pegar ele mesmo como REF
            $(content).remove();
        }
        else
        {
            var index = $(content).index();
            //Remover elemnto para não duplicar IDs e não pegar ele mesmo como REF
            $(content).remove();

            var isBefore = false; //Flag para inserir ANTEs ou depois do REF

            if ( index == 0 ) { isBefore = true; index = 1; }
            ciPopup.refSibling = $(parent).children().eq(index -1);
            ciPopup.refSibling.isBefore = isBefore;
        }

    },

    //Verificar forma de exibição. Se o popup ja foi gerado HTML, ou se esta ativo na tela e etc...
    insertAndConfig: function ()
    {
        //Verifica se o DIALOG interno aparece abaixo ou acima do conteúdo que será exibido no popup
        if ( ciPopup.dialog.options.insideDialogPosition == 'after' ) {
            $(ciPopup.newContent).insertBefore(ciPopup.dialog.in.container); //insert
        }else{
            $(ciPopup.content).append(ciPopup.newContent); //insert
        }

        function cfgDimensoes()
        {
            //Configs necessárias para conseguir pegar o WIDTH das coisas antes de exibir
            $(ciPopup.screen).css('display','block');
            $(ciPopup.screen).css('visibility','hidden');

            //configurar largura
            $content = $(ciPopup.content);
            var w = $content.outerWidth();
            var h = $content.outerHeight();

            var newMarginW = w / 2;
            var newMarginH = h / 2;

            $content.css('margin-left', newMarginW*-1);
            $content.css('margin-top', newMarginH*-1);

            //Resetar configurações do inicio da função
            $(ciPopup.screen).css('display','none');
            $(ciPopup.screen).css('visibility','visible');
        } cfgDimensoes();

        if ( ciPopup.xHide )
        {
            $(ciPopup.content).append(ciPopup.xHide);
        }

        //Qualquer elemento com esta class pode fechar o popup
        $('.popup_hide').click(function () {
            ciPopup.hide();
        });

        //Exibir...
        $(ciPopup.screen).fadeIn(500);
        ciPopup.status = 'on';
    },

    //Criar HTML dinamicamente e popular objeto
    config: function ()
    {
        //Carregar o simple functions dinamicamente
        if ( typeof simples == 'undefined' ) {
            $.getScript('../javascripts/simpleFunctions.js', function () {
                simples.init();
            });
        }

        //Container principal com o POPUP
        var screen = simples.createElement('div', {
            attrs: { id: 'cipopup' }
        });
        ciPopup.screen = $(screen);

        //BG escuro
        var bg = simples.createElement('div', {
           attrs: { class: 'bg' }
        });
        ciPopup.bg = $(bg);

        //Conteudo Central
        var content = simples.createElement('div', {
            attrs: { class: 'content' }
        });
        ciPopup.content = $(content);

        // botão X default do topo
        var xHide = simples.createElement('div',{
            attrs: { class: 'default popup_hide' },
            html: 'fechar'
        });
        ciPopup.xHide = $(xHide);

        //Div de botoes de ações diferentes
        var divButtons = simples.createElement('div',{
            attrs: { class: 'popup_buttons' }
        });
        ciPopup.divButtons = $(divButtons);

        //Criar dialog de mensagem do popup
        ciPopup.dialogCreate();

        $(ciPopup.screen).append(ciPopup.bg);
        $(ciPopup.screen).append(ciPopup.content);

        $('body').append(ciPopup.screen);
    },

    /*
    * Caixa de DIALOG do POPUP
    * */
    dialog: {
        status: false, //verifica se esta exibindo algo na tela ou escondido

        out: {
            container:  null,
            content:    null,
            xHide:      null
        },

        in: {
            container:  null,
            content:    null,
            xHide:      null,
        },

        //Configs Default
        options: {
            autoHide: { set: true, time: 4000 },
            side:       'in', //informa qual dialog será exibida, dentro ou fora do box de content do popup
            insideDialogPosition: 'after' //define se o Dialog INSIDE aparece antes ou depois do conteúdo do POPUP. Default[depois]
        },

        hide: function (options)
        {
            var obj = ciPopup.dialog;
            var DialogAtivo = ( obj.options.side == 'in' ) ? obj.in : obj.out;

            var time = (options != undefined) ? options.autoHide.time : 1;

            if (options == undefined) { $(DialogAtivo.container).clearQueue(); }

            if (obj.options.side == 'in')
            {
                $(DialogAtivo.container).delay(time).slideUp(function (){
                    obj.status = false;
                })
            }
            else if(obj.options.side == 'out')
            {
                $(DialogAtivo.container).delay(time).animate({ right: '-370px' }, function () {
                    obj.status = false;
                });
            }
        }
    },

    //Criar HTMLs dos DIALOGS e popular objetos
    dialogCreate: function ()
    {
        var Dialog;

        Dialog = create('out');
        $(ciPopup.screen).append(Dialog.container);

        Dialog = create('in');
        $(ciPopup.content).append(Dialog.container);

        function create(objName)
        {
            var container;
            var content;
            var xDialog;

            var Dialog = ciPopup.dialog[objName];

            container = simples.createElement('div', {
                attrs: { id: 'popup_dialog_'+objName, class: 'dialog' }
            });
            Dialog.container = $(container);

            content = simples.createElement('div', {
                attrs: { class: 'content_dialog' }
            });
            Dialog.content = $(content);

            xDialog = simples.createElement('div', {
                attrs: { class: 'x_dialog' },
                html: 'fechar'
            });
            Dialog.xHide = $(xDialog);

            $(container).append(Dialog.xHide);
            $(container).append(Dialog.content);

            return Dialog;
        }
    },

    msgDialog: function (text, options)
    {
        var obj = ciPopup.dialog;
        ciPopup.setDefaultOptions(obj, options);
        var DialogAtivo = ( obj.options.side == 'in' ) ? obj.in : obj.out;

        //Hide
        if ( obj.status )
        {
            $(DialogAtivo.container).fadeOut(500, function ()
            {
                reset();
                show();
            })
        }
        else
        {
            show();
        }

        function reset()
        {
            if ( obj.options.side == 'in' )
            {
                $(DialogAtivo.container).css('display', 'none');
            }
            else if ( obj.options.side == 'out' )
            {
                $(DialogAtivo.container).css('right', '-370px');
                $(DialogAtivo.container).css('display', 'block');
            }
        }

        function show()
        {
            $(DialogAtivo.content).html(text);

            if ( obj.options.side == 'in' )
            {
                $(DialogAtivo.container).slideDown('slow');
            }
            else if ( obj.options.side == 'out' )
            {
                $(DialogAtivo.container).animate({ right: '10px' });
            }


            if ( obj.options.autoHide.set ) {
                obj.hide(obj.options);
            }

            $(DialogAtivo.container).find('.x_dialog').click(function () {
                obj.hide();
            });

            DialogAtivo.status = true;
        }
    },

    //CORE - Setar opções defaults caso não exista a propriedade no OPTIONS do objeto
    setDefaultOptions: function (obj, options)
    {
        if (options == undefined) { options = obj.options; }

        for ( var param in obj.options )
        {
            //Exemple: if ( options.autoHide == undefined ) { options.autoHide = obj.options.autoHide }
            if ( options[param] == undefined ) { options[param] = obj.options[param] }

            for ( value in obj.options[param] )
            {
                //Exemple: if ( options.autoHide.time == undefined ) { options.autoHide.time = obj.options.time }
                if ( options[param][value] == undefined ) { options[param][value] = obj.options[param][value]; }
                else { obj.options[param][value] = options[param][value] }
            }
        }
    }
};
