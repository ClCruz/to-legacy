<?php
require_once('../settings/functions.php');
require_once('../settings/multisite/unique.php');


function callapi_boleto($id,$id_pedido_venda) {
    
    $transaction_data = array("id" => $id, "id_pedido_venda"=>$id_pedido_venda);

    $url = getconf()["api_internal_uri"]."/v1/purchase/site/doafter?imthebossofme=".gethost();     
    die(json_encode(array($id, $id_pedido_venda,$url)));

    $post_data = $transaction_data;
    // $out = fopen('php://output', 'w');
    $curl = curl_init(); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                                                                      
    // curl_setopt($curl, CURLOPT_VERBOSE, true);
    // curl_setopt($curl, CURLOPT_STDERR, $out);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));   

    $response = curl_exec($curl);
    // fclose($out);
    $errno = curl_errno($curl);
    
    $json = json_decode($response);
    
    // $data = ob_get_clean();
    // $data .= PHP_EOL . $response . PHP_EOL;
    //die(print_r($response,true)."|".$errno);
    
    curl_close($curl);
}


$mainConnection = mainConnection();

$transactionid = $_REQUEST["trann"];
$id_pedido = 0;

        $rs = executeSQL(
            $mainConnection,
            "SELECT P.IN_SITUACAO, M.CD_MEIO_PAGAMENTO, p.id_pedido_venda
                FROM MW_PEDIDO_VENDA P
                INNER JOIN MW_MEIO_PAGAMENTO M ON M.ID_MEIO_PAGAMENTO = P.ID_MEIO_PAGAMENTO
                WHERE P.cd_numero_transacao = ? AND id_pedido_ipagare='pagarme'",
            array($transactionid),
            true
        );

        $id_pedido = $rs["id_pedido_venda"];

        // die(json_encode($id_pedido));

        if ($rs["IN_SITUACAO"] == 'P') {
            callapi_boleto($transactionid,$id_pedido);
        }
?>