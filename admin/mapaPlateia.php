<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 11, true)) {

  $pagina = basename(__FILE__);

  if (isset($_GET['action'])) {

    require('actions/' . $pagina);
  } else {
?>
    <link rel="stylesheet" href="../stylesheets/annotations.css"/>
    <link rel="stylesheet" href="../stylesheets/ajustes.css"/>
    <link rel="stylesheet" href="../stylesheets/plateiaEdicao.css"/>
    <link rel="stylesheet" href="../javascripts/jQuery-File-Upload-9.28.0/css/jquery.fileupload.css">

    <script type="text/javascript" src="../javascripts/jquery.utils.js"></script>
    <script type="text/javascript" src="../javascripts/jquery.annotate.js"></script>
    <script type="text/javascript" src="../javascripts/plateiaEdicao.js"></script>
    <h2>Mapeamento de Plateia</h2>
    <div id="containerDados">
      <form id="dados" name="dados" method="post">
        <table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td><h3>Local</h3></td>
          </tr>
          <tr>
            <td><?php echo comboTeatro('teatroID'); ?></td>
          </tr>
          <tr>
            <td><h3>Sala</h3></td>
          </tr>
          <tr>
            <td><span id="celSala"><select><option>Selecione um teatro...</option></select></span></td>
          </tr>
          <tr class="opcoes_numerados">
            <td><h3>Espa&ccedil;amento entre lugares:</h3></td>
          </tr>
          <tr class="opcoes_numerados">
            <td>
    					Horizontal:
              <span style="float:left">(+)</span><span style="float:right">(-)</span>
              <div id="xMargin"></div>
            </td>
          </tr>
          <tr class="opcoes_numerados">
            <td>
    					Vertical:
              <span style="float:left">(+)</span><span style="float:right">(-)</span>
              <div id="yMargin"></div>
            </td>
          </tr>
          <tr class="opcoes_numerados">
            <td align="center" valign="middle">
              <input type="button" id="resetEvento" class="button" value="Recalcular" />
            </td>
          </tr>
        </table>
        <table border="0" cellspacing="0" cellpadding="0" class="opcoes_numerados">
          <tr>
            <td><h3>Tamanho do Lugar:</h3></td>
          </tr>
          <tr>
            <td>
              Tamanho:<input class="readonly" type="text" id="Size" value="10px" readonly /> <a href="#" id="sizeReset">reset</a>
              <span style="float:left">(-)</span><span style="float:right">(+)</span>
              <div id="ScaleSize"></div>
            </td>
          </tr>
          <tr>
            <td><h3>Visão do Lugar (foto):</h3></td>
          </tr>
          <tr>
            <td>
              <div id="lista_fotos"></div>
            </td>
          </tr>
          <tr>
            <td>
              <div id="areaUploadFotos" style="width:300px; height:16px;">
                Enviar fotos panorâmicas dos lugares para o servidor<br/>
                <div style="width:300px; height:16px; position:absolute; top:auto; z-index:1;"><input type="button" class="button" value="Enviar Fotos" /></div>
                <div style="width:300px; height:16px; position:absolute; top:auto; z-index:100; opacity:0; filter:Alpha(Opacity=0);"><input style="width:300px;" type="file" name="fotos" id="fotos" /></div>
              </div>
              &nbsp;
            </td>
          </tr>
        </table>
        <table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td><h3>Tamanho da imagem de fundo:</h3></td>
          </tr>
          <tr>
            <td>
    					Horizontal:<input class="readonly" type="text" id="xScaleAmount" value="510px" readonly /> <a href="#" id="xReset">reset</a>
              <span style="float:left">(-)</span><span style="float:right">(+)</span>
              <div id="xScale"></div>
            </td>
          </tr>
          <tr>
            <td>
    					Vertical:<input class="readonly" type="text" id="yScaleAmount" value="630px" readonly /> <a href="#" id="yReset">reset</a>
              <span style="float:left">(-)</span><span style="float:right">(+)</span>
              <div id="yScale"></div>
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
          </tr>
          <tr>
          <td>
          <div>
                <div style="">
                <br />
                  <label>Selecione a imagem de fundo</label>
                  <input type="file" id="selectFiles" />
                    <input type="button" id="cmdUpload" value="Upload" />
                    <div id="preview">
                    </div>
                  <hr />
                  <div id="results"></div>
                  <br />
                </div>
                <div style="width:97px; height:16px; position:absolute; top:auto; z-index:100; opacity:0; filter:Alpha(Opacity=0);"><input type="file" name="background" id="background" /></div>
              </div>
          </td>
          </tr>
          <tr>
            <td align="left" valign="middle">
              <input type="button" id="removerImagem" class="button" value="Remover Imagem" style="display:inline-block" />
          </tr>
          <tr>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td align="center" valign="middle">
              <input type="button" id="carregaEvento" class="button" value="Carregar" />
              <input type="button" id="salvarEvento" class="button" value="Salvar" />
            </td>
          </tr>
        </table>
      </form>
    </div>
    <div id="mapa_de_plateia" class="mapa_de_plateia" class="edicao">
      <img src="../images/palco.png" width="630" height="500">
    </div>

    <div id="dialog-confirm">
      <span class="img"></span>
      <span class="text">Deseja aplicar a imagem aos itens selecionados?</span>
    </div>
    <script src="../javascripts/jQuery-File-Upload-9.28.0/js/jquery.iframe-transport.js"></script>
    <script src="../javascripts/jQuery-File-Upload-9.28.0/js/jquery.fileupload.js"></script>

    <script>
$(function () {
    if (window.File && window.FileList && window.FileReader) {
        $("#cmdUpload").click(function () {
            var files = $("#selectFiles").prop("files");
            for (var i = 0; i < files.length; i++) {
                (function (file) {
                    if (file.type.indexOf("image") == 0) {
                        var fileReader = new FileReader();
                        fileReader.onload = function (f) {
                            $("![]()", {
                                src: f.target.result,
                                width: 200,
                                height: 200,
                                title: file.name
                            }).appendTo("#preview");
                            $.ajax({
                                type: "POST",
                                url: "/admin/mapaPlateia.upload.php?action=upload",
                                data: {
                                    'file': f.target.result,
                                    'name': file.name,
                                    "teatroID": $("#teatroID").val(),
                                    "salaID": $("#salaID").val()
                                },
                                success: function (result) {
                                    alert(result);
                                    $("#carregaEvento").click();
                                    //loadAnnotations();
                                }
                            });
                        };

                        fileReader.readAsDataURL(file);
                    }
                })(files[i]);
            }
        });
    }
    else {
        alert('Sorry! you\'re browser does not support HTML5 File APIs.');
    }
});
</script>

<?php
  }
}
?>