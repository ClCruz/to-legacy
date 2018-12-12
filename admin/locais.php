<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 3, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {
        require('actions/' . $pagina);
    } else {
        $result = executeSQL($mainConnection, 'SELECT ID_BASE, DS_NOME_BASE_SQL, DS_NOME_TEATRO, IN_ATIVO FROM MW_BASE ORDER BY DS_NOME_TEATRO');
        $bases = executeSQL($mainConnection, "SELECT NAME FROM SYS.DATABASES D WHERE NAME LIKE 'CI_%' AND NOT EXISTS (SELECT 1 FROM MW_BASE B WHERE B.DS_NOME_BASE_SQL collate Latin1_General_CI_AS = D.NAME)");
?>
        <style>
            label, input { display:block; }
            input.text, select { margin-bottom:12px; width:95%; padding: .4em; }
            fieldset { padding:0; border:0; margin-top:25px; }
            div#users-contain { width: 350px; margin: 20px 0; }
            div#users-contain table { margin: 1em 0; border-collapse: collapse; width: 100%; }
            div#users-contain table td, div#users-contain table th { border: 1px solid #eee; padding: .6em 10px; text-align: left; }
            .ui-dialog{ padding: .3em; }
            .validateTips { border: 1px solid transparent; padding: 0.3em; }
        </style>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script src="../javascripts/jquery.maskedinput.min.js" type="text/javascript"></script>
        <script type="text/javascript">
            $(function() {
                var pagina = '<?php echo $pagina; ?>';

                $("#telefone").mask("99 9999-9999");
                $('#celular').mask("99 9999-9999?9");

                var phone, element;
                phone = $('#celular').val().replace(/\D/g, '');
                element = $('#celular');
                element.unmask();
                if(phone.length > 10) {
                    element.mask("99 99999-999?9");
                } else {
                    element.mask("99 9999-9999?9");
                }

                $("#cpf_cnpj").keypress(verificaNumero);
                $("#prazo").keypress(verificaNumero);
                $("#numero_agencia").keypress(verificaNumero);
                $("#numero_banco").keypress(verificaNumero);
                $("#taxa_cc").keypress(verificaNumeroEVirgula);
                $("#taxa_cd").keypress(verificaNumeroEVirgula);
                $("#taxa_rp").keypress(verificaNumeroEVirgula);
                $("#valor").keypress(verificaNumeroEVirgula);

                $("#msg_pos_venda").on('keypress blur', function(){
                    if ($(this).val() == '')
                        $( "#url_msg" ).prop('disabled', true).val('');
                    else
                        $( "#url_msg" ).prop('disabled', false);
                });

                var dialog, form,
                emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/,
                id_local = $( "#id_local" ),
                nome = $( "#nome" ),
                nomeSql = $( "#nomeSql" ),
                ativo = $( "#ativo" ),
                razao_social = $( "#razao_social" ),
                cpf_cnpj = $( "#cpf_cnpj" ),
                prazo = $( "#prazo" ),
                banco = $( "#banco" ),
                numero_banco = $( "#numero_banco" ),
                numero_agencia = $( "#numero_agencia" ),
                numero_conta = $( "#numero_conta" ),
                tipo_contaC = $( "#tipo_contaC" ),
                tipo_contaP = $( "#tipo_contaP" ),
                contato = $( "#contato" ),
                telefone = $( "#telefone" ),
                celular = $( "#celular" ),
                email = $( "#email" ),
                taxa_cc = $( "#taxa_cc" ),
                taxa_cd = $( "#taxa_cd" ),
                taxa_rp = $( "#taxa_rp" ),
                valor = $( "#valor" ),
                allFields = $( [] ).add( razao_social ).add( cpf_cnpj ).add( prazo ).add( ativo ).
                    add( banco ).add( numero_banco ).add( numero_agencia ).add( numero_conta ).
                    add( contato ).add( telefone ).add( celular ).add( email ).add( taxa_rp ).
                    add( taxa_cc ).add( taxa_cd ).add( valor ).add( nomeSql ).add( nome ),
                tips = $( ".validateTips" );

                $('#app table').delegate('a', 'click', function(event) {
                    event.preventDefault();

                    var $this = $(this),
                    href = $this.attr('href'),
                    id = 'id=' + $.getUrlVar('id', href),
                    tr = $this.closest('tr');

                    if (href.indexOf('?action=edit') != -1) {
                        $.get('locais.php?action=load&' + id, function(data) {
                            data = $.parseJSON(data);
                            $('#nome').val(data.nome);
                            $( "#id_local" ).val(data.id_local);
                            $( "#nomeSql option" ).filter(":not([origem='db'])").remove();
                            var o = new Option(data.nomeSql, data.nomeSql);
                            $(o).html(data.nomeSql);                            
                            $( "#nomeSql" ).append(o);
                            $( "#nomeSql" ).val(data.nomeSql);
                            if(data.ativo == 1){
                                document.getElementById("ativo").checked = true;
                            }
                            $( "#razao_social" ).val(data.razao_social);
                            $( "#cpf_cnpj" ).val(data.cpf_cnpj);
                            $( "#prazo" ).val(data.prazo);
                            $( "#banco" ).val(data.banco);
                            $( "#numero_banco" ).val(data.numero_banco);
                            $( "#numero_agencia" ).val(data.numero_agencia);
                            $( "#numero_conta" ).val(data.numero_conta);
                            if(data.tipo_conta == 'C'){
                                document.getElementById("tipo_contaC").checked = true;
                            }else{
                                document.getElementById("tipo_contaP").checked = true;
                            }
                            $( "#contato" ).val(data.contato);
                            $( "#telefone" ).val(data.telefone);
                            $( "#celular" ).val(data.celular);
                            $( "#email" ).val(data.email);
                            $( "#taxa_cc" ).val(data.taxa_cc);
                            $( "#taxa_cd" ).val(data.taxa_cd);
                            $( "#taxa_rp" ).val(data.taxa_rp);
                            $( "#valor" ).val(data.valor);

                            $( "#msg_pos_venda" ).val(data.msg_pos_venda);
                            $( "#url_msg" ).val(data.url_msg).prop('disabled', !data.msg_pos_venda);

                            dialog.dialog( "open" );
                        });
                    } else if (href == '#delete') {
                        tr.remove();
                    } else if (href.indexOf('?action=delete') != -1) {
                        $.confirmDialog({
                            text: 'Tem certeza que deseja apagar este registro?',
                            uiOptions: {
                                buttons: {
                                    'Sim': function() {
                                        $(this).dialog('close');
                                        $.get(href, function(data) {
                                            if (data.replace(/^\s*/, "").replace(/\s*$/, "") == 'true') {
                                                tr.remove();
                                            } else {
                                                $.dialog({text: data});
                                            }
                                        });
                                    }
                                }
                            }
                        });
                    }
                });

                $('#new').button().click(function(event) {
                    event.preventDefault();
                    document.getElementById("ativo").checked = true;
                    $( "#nomeSql option" ).filter(":not([origem='db'])").remove();
                    dialog.dialog( "open" );
                });

                function updateTips( t ) {
                    tips
                    .text( t )
                    .addClass( "ui-state-highlight" );
                    setTimeout(function() {
                        tips.removeClass( "ui-state-highlight", 1500 );
                    }, 500 );
                }

                function checkLength( o, n, min, max ) {
                    if ( o.val().length > max || o.val().length < min ) {
                        o.addClass( "ui-state-error" );
                        updateTips( "O tamanho do " + n + " deve ser entre " +
                            min + " e " + max + "." );
                        return false;
                    } else {
                        return true;
                    }
                }

                function checkRegexp( o, regexp, n ) {
                    if ( !( regexp.test( o.val() ) ) ) {
                        o.addClass( "ui-state-error" );
                        updateTips( n );
                        return false;
                    } else {
                        return true;
                    }
                }

                function verificaNumero(e) {
                    if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
                        return false;
                    }
                }

                function verificaNumeroEPonto( e ) {
                    var tecla = ( window.event ) ? e.keyCode : e.which;
                    if ( tecla == 8 || tecla == 0 )
                        return true;
                    if ( tecla != 46 && tecla < 48 || tecla > 57 )
                        return false;
                }

                function verificaNumeroEVirgula( e ) {
                    var tecla = ( window.event ) ? e.keyCode : e.which;
                    if ( tecla == 8 || tecla == 0 )
                        return true;
                    if ( tecla != 44 && tecla < 48 || tecla > 57 )
                        return false;
                }

                function addUser() {
                    var valid = true;
                    allFields.removeClass( 'ui-state-error' );
                    $.each(allFields, function() {
                        var $this = $(this);
                        if ($this.val() == '') {
                            $this.addClass('ui-state-error');
                            valid = false;
                        } else {
                            $this.removeClass('ui-state-error');
                        }
                    });

                    valid = valid && checkLength( nome, "Nome", 3, 150 );
                    valid = valid && checkLength( nomeSql, "Nome da Base", 3, 50 );
                    valid = valid && checkLength( email, "E-Mail", 6, 80 );
                    if( !verificaCPF( cpf_cnpj.val() ) && !verificaCNPJ( cpf_cnpj.val() ) ){
                        valid = false;
                        cpf_cnpj.addClass('ui-state-error');
                    }
                    valid = valid && checkRegexp( email, emailRegex, "E-Mail inválido!" );
                    if(document.getElementById("tipo_contaP").checked == false &&
                        document.getElementById("tipo_contaC").checked == false ){

                        valid = false;
                        $.dialog({text: "Selecione o tipo de conta!"});
                    }
                    if ( valid ) {
                        if ( id_local.val() == "" ){
                            var p = 'locais.php?action=add';
                        }else{
                            var p = 'locais.php?action=update&id='+ id_local.val();
                        }        
                        $.ajax({
                            url: p,
                            type: 'post',
                            data: $('#info-contratuais').serialize(),
                            success: function(data) {
                                if (trim(data).substr(0, 4) == 'true') {
                                    location.reload();
                                } else {
                                    $.dialog({text: data});
                                }
                            },
                            error: function(){
                                $.dialog({
                                    title: 'Erro...',
                                    text: 'Erro na chamada dos dados !!!'
                                });
                                return false;
                            }
                        });
                        dialog.dialog( "close" );
                    }
                    return valid;
                }

                dialog = $( "#dialog-form" ).dialog({
                    autoOpen: false,
                    height: 600,
                    width: 600,
                    modal: true,
                    buttons: {
                        "Salvar": addUser,
                        Cancelar: function() {
                            dialog.dialog( "close" );
                        }
                    },
                    close: function() {
                        document.forms[1].reset();
                        id_local.val("");
                        allFields.removeClass( "ui-state-error" );
                    }
                });

                form = dialog.find( "form" ).on( "submit", function( event ) {
                    event.preventDefault();
                    addUser();
                });
            });
        </script>

        <div id="dialog-form" title="Informações Contratuais">
            <p class="validateTips">Todos os campos são obrigatórios.</p>

            <form name="info-contratuais" id="info-contratuais">
                <fieldset>
                    <input type="hidden" name="id_local" id="id_local" value="" />
                    <label for="nome">Nome</label>
                    <input type="text" name="nome" id="nome" value="" maxlength="150" class="text ui-widget-content ui-corner-all">
                    <label for="nomeSql">Nome da Base</label>
                    <select name="nomeSql" id="nomeSql" class="text ui-widget-content ui-corner-all">
                    <?php while($base = fetchResult($bases)){ ?>
                        <option origem="db" value="<?php echo $base['NAME']; ?>"><?php echo $base['NAME']; ?></option>
                    <?php } ?>
                    </select>
                    <label for="ativo" style="display: inline;">Ativo</label>
                    <input type="checkbox" name="ativo" id="ativo" style="display: inline;">
                    <br/><br/>
                    <label for="razao_social">Razão Social</label>
                    <input type="text" name="razao_social" maxlength="80" id="razao_social" value="" class="text ui-widget-content ui-corner-all">
                    <label for="cpf_cnpj">CPF / CNPJ (Digite apenas números)</label>
                    <input type="text" name="cpf_cnpj" maxlength="14" id="cpf_cnpj" value="" class="text ui-widget-content ui-corner-all">                    

                    <h2>Dados bancários</h2><br/>
                    <label for="banco">Nome do Banco</label>
                    <input type="text" name="banco" id="banco" maxlength="50" value="" class="text ui-widget-content ui-corner-all">
                    <label for="numero_banco">Número do Banco</label>
                    <input type="text" name="numero_banco" maxlength="6" id="numero_banco" value="" class="text ui-widget-content ui-corner-all">
                    <label for="numero_agencia">Número da Agência</label>
                    <input type="text" name="numero_agencia" maxlength="8" id="numero_agencia" value="" class="text ui-widget-content ui-corner-all">
                    <label for="numero_conta">Número da Conta</label>
                    <input type="text" name="numero_conta" maxlength="8" id="numero_conta" value="" class="text ui-widget-content ui-corner-all">
                    <label for="tipo_conta" style="display: inline;">Tipo de conta: </label>
                    Corrente <input type="radio" name="tipo_conta" id="tipo_contaC" value="C" style="display: inline;">&nbsp;&nbsp;
                    Poupança <input type="radio" name="tipo_conta" id="tipo_contaP" value="P" style="display: inline;">
                    <br/><br/>
                    <label for="contato">Nome do contato</label>
                    <input type="text" name="contato" maxlength="60" id="contato" value="" class="text ui-widget-content ui-corner-all">
                    <label for="telefone">Telefone fixo</label>
                    <input type="text" name="telefone" id="telefone" value="" class="text ui-widget-content ui-corner-all">
                    <label for="celular">Telefone celular</label>
                    <input type="text" name="celular" id="celular" value="" class="text ui-widget-content ui-corner-all">
                    <label for="email">E-Mail</label>
                    <input type="text" name="email" id="email" value="" class="text ui-widget-content ui-corner-all">
                    <label for="prazo">Prazo para repasse em dias</label>
                    <input type="text" name="prazo" id="prazo" value="" class="text ui-widget-content ui-corner-all">
                    <label for="taxa_rp">Taxa do Repasse (%)</label>
                    <input type="text" name="taxa_rp" id="taxa_rp" value="" class="text ui-widget-content ui-corner-all">
                    <label for="taxa_cc">Taxa de Cartão de Crédito (%)</label>
                    <input type="text" name="taxa_cc" id="taxa_cc" value="" class="text ui-widget-content ui-corner-all">
                    <label for="taxa_cd">Taxa de Cartão de Débito (%)</label>
                    <input type="text" name="taxa_cd" id="taxa_cd" value="" class="text ui-widget-content ui-corner-all">                    
                    <label for="valor">Custos dos Ingressos (R$)</label>
                    <input type="text" name="valor" id="valor" value="" class="text ui-widget-content ui-corner-all">
                    
                    <label for="msg_pos_venda">Mensagem Após Venda</label>
                    <textarea name="msg_pos_venda" id="msg_pos_venda" class="text ui-widget-content ui-corner-all" style="width: 96%; height: 80px"></textarea>
                    <br/>
                    <label for="url_msg">URL para a Mensagem</label>
                    <input type="text" name="url_msg" id="url_msg" value="" class="text ui-widget-content ui-corner-all" disabled>

                    <input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
                </fieldset>
            </form>
        </div>

        <h2>Locais</h2>
        <form id="dados" name="dados" method="post">
            <table class="ui-widget ui-widget-content">
                <thead>
                    <tr class="ui-widget-header ">
                        <th>Nome</th>
                        <th>Nome da Base</th>
                        <th>Ativo</th>
                        <th colspan="3">A&ccedil;&otilde;es</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            while ($rs = fetchResult($result)) {
                $id = $rs['ID_BASE'];
            ?>
                <tr>
                    <td><?php echo utf8_encode2($rs['DS_NOME_TEATRO']); ?></td>
                    <td><?php echo utf8_encode2($rs['DS_NOME_BASE_SQL']); ?></td>
                    <td><?php echo $rs['IN_ATIVO'] ? 'Sim' : 'N&atilde;o'; ?></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=edit&id=<?php echo $id; ?>">Editar</a></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=delete&id=<?php echo $id; ?>">Apagar</a></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    <a id="new" href="#new">Novo</a>
</form>
<?php
        }
    }
?>