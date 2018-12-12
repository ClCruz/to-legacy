<?php
    session_start();

    if (isset($_GET['desktop'])) {
        if ($_GET['desktop'] == 1) {
            $_SESSION['desktop'] = 1;
        } else {
            unset($_SESSION['desktop']);
        }
    }

    if ($_SESSION['desktop'] == 1) {
?>
    <script type="text/javascript">
        var mobileversion = 0;
    </script>
<?php
    } else {
        $scale = basename($_SERVER['SCRIPT_FILENAME']) == 'etapa1.php' ? '4.5' : '1';
?>
    <link rel="stylesheet" href="../stylesheets/cimobile.css"/>
    <script type="text/javascript">
        var mobileversion = 1;
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=<?php echo $scale; ?>"/>
<?php 
    }
?>