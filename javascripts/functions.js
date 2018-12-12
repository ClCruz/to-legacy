/* 
 * Funções gerais de javascript
 * @author Edicarlos Barbosa <edicarlos.barbosa@cc.com.br>
 */

$(function(){
    $('#dados').delegate('#idestado','change',function(){
        $.ajax({
            async: false,
            url: "mudarMunicipio.php",
            type: 'post',
            data: 'idEstado='+$(this).val(),
            success: function(data){
                if(data != ""){
                    $('#idmunicipio').html(data);
                }
            }
        });
    });
});
