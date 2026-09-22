<html>
<head>
    <title>VNPT SignService - Ký số</title>
</head>

<body>
<label style="color: red;">
    
</label>
</br>
</br>

<?php

require_once("models/OAuth2Config.php");

$config = new OAuth2Config();
?>

<?php

function getGUID(){
    mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
    $charid = strtolower(md5(uniqid(rand(), true)));
    $hyphen = chr(45);// "-"
    $uuid = substr($charid, 0, 8).$hyphen
        .substr($charid, 8, 4).$hyphen
        .substr($charid,12, 4).$hyphen
        .substr($charid,16, 4).$hyphen
        .substr($charid,20,12);
    return $uuid;
}

function api_get_tranInfo_curl($url){

    $curl = curl_init();	
    curl_setopt_array($curl,[
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => [            
            'Accept: application/json',
            'Content-Type: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{}'
    ]);	
    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);	
    $msg = json_decode($response);	
    curl_close($curl);
    if($httpcode != 200){
        print_r('<pre>');
        print_r($response);
        print_r('</pre>');
        exit();
    }
    return $msg;
}

function api_service_get_hash($url,$data){        
    $curl = curl_init();	
    curl_setopt_array($curl,[
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => [            
            'Accept: application/json',
            'Content-Type: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);	
    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $msg = json_decode($response);	
    curl_close($curl);
    if($httpcode != 200){
        print_r('<pre>');
        print_r($response);
        print_r('</pre>');
        exit();
    }
    return $msg;
}


// 4. Lấy thông tin ky ve
$msg = api_get_tranInfo_curl("https://gwsca.vnpt.vn/sca/sp769/v1/signatures/sign/".$_GET["tranId"]."/status");

if($msg->message != "SUCCESS"){
	echo("Bạn chưa thực hiện đầy đủ các thao tác. Mời thực hiện lại");
	exit();
}
$hashSigned = $msg->data->signatures[0]->signature_value;


// 5. signExternal
$data_signExternal = [
	"tranId" => $_GET["transIDHash"],
    "sp_id" => $config->client_id,
	"sp_password" => $config->client_secret,
	"signatures" => [
		[
			"fileID" => $_GET["fileId"],
			"signature" => $hashSigned
		]
	]
];
$msg_signExternal = api_service_get_hash("https://gwsca.vnpt.vn/rest/v2/signature/signExternal",$data_signExternal);

?>
<label>KÝ số thành công </label></br>
<label>Dữ liệu đã ký:</label></br>
<textarea style="width: 600px; margin-left: 50px" rows="10">
<?php 
        echo $msg_signExternal->signResps[0]->signedData;
?>

</body>
</html>