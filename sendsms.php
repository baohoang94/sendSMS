<?php
// Company		: SAMI-S
// Description	: Demo Send Sms API
// Created By	: Doan Quoc Tuan
// Create On	: 2019-12-18

// Tested PHP 4 >= 4.0.2, PHP 5, PHP 7
// Required extension=openssl, extension=curl

// Class for sms item
class SmsOut {
	// Properties
	public $cooperateMsgId;
	public $destAddr;
	public $message;
	public $cdrIndicator;
	public $mtType;
}

// Class for sms transaction
class SmsTransaction {
	public $transactionId;
	public $coopereateId;
	public $createTime;
	public $smsOuts;
}

// Class for transaction request
class SmsTransactionRequest {
	public $payload;
	public $signature;
}


// CONSTANT
$cooperateId = 46044;
$username = "sms@sab.com.vn";
$password = "SabBrandname@531";
$x_client_id = "efa66179-1eb9-4187-9c0f-52fc99388492";

$tokenUrl = "https://auth.sami.vn:8443/api/authenticate/token";

$sendSmsUrl = "https://sms.sami.vn:8558/api/sms/send";


// You can get a simple private/public key pair using:
// openssl genrsa 512 >private_key.txt
// openssl rsa -pubout <private_key.txt >public_key.txt

// IMPORTANT: The key pair below is provided for testing only.
// For security reasons you must get a new key pair
// for production use, obviously.

$private_key = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA3re3SB+4YGTos3oHqR3OXhVfcBOR9uwj7aW00jUbaO6tzxtf
kXBpfQI2ky8Kjzu1vF2lslxzsonyI29tfGRKKhK+rROC5Vp3rqkK4zsLTneQybcR
y1BrV6oyzsP8X0d2+v954iPidhabgbsfLNCPnbNaGy2XwbzFbWP8rFj+s7G8UsDS
+A9gNxlEbeicJYtohhgtGNiJ+L7MNYuZXd+fQyIYcdHKVr4ys6GHZjzRM1r5bQLG
f3vo5pb/YlJtHZRae9bQ6pbsa7riFzHQH1aeYrh5SYTBJpgnwV6/4EKHbZGkwZQ6
1kdAWIlH3qOlK+mXxGYP8r5ERi9Knva7p8KKZwIDAQABAoIBAAmAdQVStR8HrxXZ
IvKIHwHMj6HMc/HA6Vd+NlSxh1XWuPuZA17FGPsIWSO2YhDyTzKWHDeb0iPP1tDE
NDuWW8OnwRLJPlBip7B/7cL8BeN3Hmo8sTmnWK0Iydogamf2OJFEzQJk3YiENmY2
gYH78+SafkGilFLjptif52vC8DriSfK1wyt2+CHwADcc5VNaj12NmrvU3uZjQxTp
Xy3DnSuwJQqQcjKz35gv6gim6TZUoef9+JbX2QJBwOBagYuq1kVycRMOg98THe73
SYR+k3o9clETwqFX6/rcKDA5bf7Bc9rVw0d3zJ7QNsjybUunIOIVbQdR4L2DrcU4
6QdPV8ECgYEA7vUQGey5XwoJLqQ0QHDFOonM1X47IS+/8ZUsKH5RND0aU8nZIWqb
3Bt9IqDX9qxrlR3i4+tIY85ldZufk2PZLRkXA4Xay7uJUmyn4EzaLx7SGYLduKeA
izkPf0fct4S43h9FLxqap97zqdZej8tfPq8nZyMvDszRs2942HSpYP8CgYEA7pol
YWazENdZ3jNyB3LcoyvQ/hxMlkQ4OABJF5cm3tGJtzk7Psqd6ox3sUNLC9sAN5Io
QVwgfTX42coNIyPnrkZ70UjwO0ctEvRoMt0ZL9TYZ/fnhIzGjUMUAkzN/JLU3+DJ
irRvD0NODf6jAE+nmRBFsv9FOTEtrc9Kxj8mbpkCgYEAx84UA4lkSuqqWNymcEeb
MuJsawucx5gUqB0yij1tCwAYln0N2Jo67uXxUVYqnrD3V/1gbXGb2xCG17sjyGtR
+hqjmqd/FqGeJlSCXtQEECh2Ryyc1r0Ah+lTYvskvDL3HYwKHmPhc4LCPX3pHdVQ
s6pjiSLrQzXSFnacFmoeAuMCgYEAqQ2qYoKDaPFMCTVmG4T0ct/+qayfTvBy7Kq3
HvHHZqQL8TeplGm2zZJM09mb+IBofPcfcn+1vUFaWeTgvf8Yjc/+tl/5ZeaeNwnY
MgGZcNxbn/5zmOStYTUfnimQ4N2f2ifIZHBHKAdF7IB4OSY21ypCV/qpr0X62WU/
04q2zjECgYBV+wBa3QQDmv8jXoGYMQFQoqPM4/LSK2bC1odHxCBvE4iMEDw7hW2D
EvCaq0Wpky5eYF79B8TCmSHSM6o2bE2AW7/XcfUzCg33WJhQqaiNho/W6ININ12T
b0IFJPy/SHmvY/QyLemqtGk6DJYuozOO62/7T5u+fgZAbZYWJBu+QQ==
-----END RSA PRIVATE KEY-----
EOD;

// Sms out array
$smsOuts = array();

// Sms out item
$smsOutItem = new SmsOut();
// Chuan bi du lieu
$smsOutItem->cooperateMsgId =  uniqid();
// In test mode, destAdrr must be 84912324296 or 84912656901
$smsOutItem->destAddr = "84912324296";

$smsOutItem->message = "Test sms message from S&B";
$smsOutItem->shortCode = "8150";
$smsOutItem->cdrIndicator = "FREE";
$smsOutItem->mtType = "AN";

// Add sms out item to smsOuts
array_push($smsOuts, $smsOutItem);

// prepare smsTransaction
$smsTransaction = new SmsTransaction();

// Generate unique id for transaction
$smsTransaction->transactionId = uniqid();
// Mã định danh của đối tác
$smsTransaction->coopereateId = $cooperateId;
// create transaction datetime (ISO format)
$smsTransaction->createTime = date("c");;
// attach smsOut to $smsTransaction
$smsTransaction->smsOuts = $smsOuts;

// This file code must be encoding UTF-8
$smsTransactionJSON = json_encode($smsTransaction, JSON_UNESCAPED_UNICODE);

// Declare signature
$binary_signature = "";

// At least with PHP 5.2.2 / OpenSSL 0.9.8b (Fedora 7)
// there seems to be no need to call openssl_get_privatekey or similar.
// Just pass the key as defined above
openssl_sign($smsTransactionJSON, $binary_signature, $private_key, OPENSSL_ALGO_SHA256);

// Encode Base 64 for signature
$signature = base64_encode($binary_signature);

// Encode Base 64 for payload
$payload = base64_encode($smsTransactionJSON);


// Prepare transaction request
$smsTransactionRequest = new SmsTransactionRequest();
$smsTransactionRequest->payload = $payload;
$smsTransactionRequest->signature = $signature;

// Convert transaction request to JSON
$smsTransactionRequestJSON = json_encode($smsTransactionRequest);

// PRINT Transaction Request JSON
echo "smsTransactionRequestJSON \n";
echo $smsTransactionRequestJSON;
echo "\n";


$basicAuth = base64_encode($username . ":" . $password);

// Prepare POST token
$ch = curl_init();

$headers = [
	'Authorization: Basic ' . $basicAuth,
	'x-client-id: ' .$x_client_id,
    'Content-Type: application/json; charset=utf-8',
    'Content-Length: 0'
];

curl_setopt($ch, CURLOPT_URL, $tokenUrl);                                                                      
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                                                                      
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
curl_setopt($ch, CURLOPT_POST, true);                                                                      
curl_setopt($ch, CURLOPT_TIMEOUT,20);

echo "==== START get token from " . $tokenUrl . "====\n";

// POST to get token
$responseToken = curl_exec($ch);
// Get Response Status
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// http code 200 OK
if ($httpcode == 200 ) {
	echo "HTTP Status code" . $httpcode . "\n";
	echo "Response Token\n";
	echo $responseToken . "\n";
}
else {
	echo "HTTP Status code: " . $httpcode . "\n";
	$responseToken = null;
}
echo "==== END get token from " . $tokenUrl . "====\n";

// If get token success, continue sending sms
if ($responseToken != null)
{
	echo "==== START send sms to " . $sendSmsUrl . "====\n";

	$headers = [
		'Authorization: Bearer ' . $responseToken,
		'Content-Type: application/json; charset=utf-8',
		'Content-Length: ' . strlen($smsTransactionRequestJSON)
	];

	curl_setopt($ch, CURLOPT_URL, $sendSmsUrl);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
	curl_setopt($ch, CURLOPT_POSTFIELDS, $smsTransactionRequestJSON);                                                                  
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                                                                      
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	
	$responseSms = curl_exec($ch);

	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	echo "HTTP Status code: " . $httpcode . "\n";
	
	// 200 OK
	if ($httpcode == 200 ) {
		echo "Response SMS\n";
		echo json_encode($responseSms, JSON_PRETTY_PRINT);
	} else {
		echo "Response Error\n";
		echo json_encode($responseSms, JSON_PRETTY_PRINT);
	}
	echo "==== END send sms to " . $sendSmsUrl . "====\n";
}

// Close http connection
curl_close($ch);

?>
