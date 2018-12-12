<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Metzli/autoload.php');
    die("ddd222");
//    use Metzli\Encoder\Encoder;
//    use Metzli\Renderer\PngRenderer;
/*
    function getQRCodeAztec($code) {
        $code = Encoder::encode($code);
        $renderer = new PngRenderer();
        $render = $renderer->render($code);

        $png = imagecreatefromstring($renderer->render($code));
        ob_start();
        imagepng($png);
        $imagedata = ob_get_contents();
        ob_end_clean();
        imagedestroy($png);
        return $imagedata;
    }
    */
?>