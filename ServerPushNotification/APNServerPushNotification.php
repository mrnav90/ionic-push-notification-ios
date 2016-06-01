<?php

// Just a note: please make sure all device tokens from the same application

// Put your device token here (without spaces):
$tokens = !empty($_REQUEST['token']) ? trim($_REQUEST['token']) : null;
$pushLive = !empty($_REQUEST['type']) && $_REQUEST['type'] == 'live' ? ucfirst($_REQUEST['type']) : "";

if (empty($deviceTokens)) {
	jsonOut(array(), 'Device token is required!', true, 400);
}

$deviceTokens = explode(",", $tokens);
				
// Put your private key's passphrase here:
$passphrase = 'yesconoidung';

// Put your alert message here:
$message = 'Ionic Hybrid App Push Notification!';

$serverPush = $pushLive == 'Live' ? 'ssl://gateway.push.apple.com:2195' : 'ssl://gateway.sandbox.push.apple.com:2195';

////////////////////////////////////////////////////////////////////////////////

$ctx = stream_context_create();
stream_context_set_option($ctx, 'ssl', 'local_cert', 'ionicPushNotification{$pushLive}.pem');
stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
$fp = stream_socket_client($serverPush, $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

if (!$fp) {
	jsonOut(array(), "Failed to connect: ".$err." ".$errstr, true, 500);
}

$messageConnectSuccess = 'Connected to APNS Successfully!!!';
$sendSuccess = array();
$sendError = array();

foreach ($deviceTokens as $token) {
	// Create the payload body
	$body['aps'] = array('alert' => $message,'sound' => 'default');

	// Encode the payload as JSON
	$payload = json_encode($body);

	// Build the binary notification
	$msg = chr(0) . pack('n', 32) . pack('H*', $token) . pack('n', strlen($payload)) . $payload;

	// Send it to the server
	$result = fwrite($fp, $msg, strlen($msg));

	if (!$result) {
		$sendError[] = 'Message not delivered - device token: '.$token;
	} else {
		$sendSuccess[] = 'Message successfully delivered - device token: '.$token;
	}
}

// Close the connection to the server
fclose($fp);

jsonOut(array('pushSuccess' => $sendSuccess, 'pushError' => $sendError), $messageConnectSuccess, false, 200);

function jsonOut($data = array(), $msg = "Bad Request", $error = true, $code = null) {
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Headers: Authorization");
	header("Content-type: application/json; charset=utf-8");
	$str = @json_encode(array('error' => $error,'code'=>$code, 'message' => $msg, 'data' => $data),JSON_PRETTY_PRINT);
	echo $str;
	exit;
}
