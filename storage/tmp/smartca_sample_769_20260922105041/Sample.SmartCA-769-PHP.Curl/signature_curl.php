<html>
<head>
    <title>SmartCA Tích hợp</title>
</head>

<body>
<label style="color: red;">
    Lưu ý (*): Dữ liệu test, thông tin chứng thư xem thêm trong source code
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


function api_smartca($link,$data){
	$curl = curl_init();	
    curl_setopt_array($curl,[
        CURLOPT_URL => $link,
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

// 1. Lấy thông tin certificate
$data_getCertificate = [
	"sp_id" => $config->client_id,
	"sp_password" => $config->client_secret,
	"user_id" => $config->user_id,
	"transaction_id" => getGUID()
];

$msg_getCertificate = api_smartca("https://gwsca.vnpt.vn/sca/sp769/v1/credentials/get_certificate",$data_getCertificate);
/*
print_r("<pre>");
print_r($msg_getCertificate);
print_r("</pre>");
exit();
*/

$certBase64 = $msg_getCertificate->data->user_certificates[0]->cert_data;
$certBase64 = str_replace("\r\n","",$certBase64);
$serialNumber = $msg_getCertificate->data->user_certificates[0]->serial_number;

// 2.CalculateHash
$unsignDataBase64 = file_get_contents("base64_file.txt");

$data_calculate_hash=[
	"transaction_id" => getGUID(),
	"sp_id" => $config->client_id,
	"sp_password" => $config->client_secret,
	"signerCert" => $certBase64,
	"digestAlgorithm" => "sha256",
	"sign_files" => [
		[
			"storage_file_name"=> "",
			"name"=> "test.pdf",
			"pdfContent" => $unsignDataBase64,
			"sigOptions" => [			
				"renderMode" => 4, //0: TextOnly, 1:TEXT_WITH_LOGO_LEFT, 2:LOGO_ONLY, 3:TEXT_WITH_LOGO_TOP,4:TEXT_WITH_BACKGROUND 
				"customImage" => "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", //base64 của anh gửi
				"fontSize" => 13, //kcihs thước chữ
				"fontColor" => "#000000", //màu chữ							
				"signatureText"=> "Ký bởi: Quỳnh Anh test ca\nThời gian ký: 17/05/2023 09:43:23",
				"signatures" => [
					[
						"page" => 1,
						"rectangle" => "0,581,200,657"
					]				
				]
			]
		]
	]
	
];
$msg_calculateHash = api_smartca("https://gwsca.vnpt.vn/rest/v2/signature/calculateHash",$data_calculate_hash); 

if($msg_calculateHash->hashResps[0]->code != "sigSuccess"){
	echo("Không thể calculateHash đc");
	exit();
}
$hashData = $msg_calculateHash->hashResps[0]->hash;
$fileID = $msg_calculateHash->hashResps[0]->fileID;
$transIdHash = $msg_calculateHash->tranId;

// 3.SignHash
$data_signhash = [	
	"sp_id" => $config->client_id,
	"sp_password" => $config->client_secret,
	"user_id" => $config->user_id,		
	"transaction_id" => getGUID(),	
	"sign_files" => [
		[
			"data_to_be_signed" => bin2hex(base64_decode($hashData)),
            "doc_id" => "doc_id",
            "file_type" => "pdf",
            "sign_type" => "hash"
		]
	],
	"serial_number" => $serialNumber,
];
$msg_signHash = api_smartca("https://gwsca.vnpt.vn/sca/sp769/v1/signatures/sign",$data_signhash);

if($msg_signHash->status_code != 200 || $msg_signHash->message != "sig_wait_for_user_confirm"){
	echo("Ký số thất bại <br/>");
	exit();
}
?>

<label>KÝ số thành công, transaction id là : <?php echo($msg_signHash->data->transaction_id); ?></label></br>
<label>Mời bạn vào App Mobile confirm việc ký này</label></br>
<label>Khi confirm thành công <a href="get_tranInfo.php?tranId=<?php echo($msg_signHash->data->transaction_id) ?>&fileId=<?php echo($fileID) ?>&transIDHash=<?php echo($transIdHash) ?>">Click vào đây</a> để xem thông tin ký</label></br>
</body>
</html>