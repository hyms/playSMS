<?php
defined('_SECURE_') or die('Forbidden');

// hook_sendsms
// called by main sms sender
// return true for success delivery
// $sms_sender	: sender mobile number
// $sms_footer	: sender sms footer or sms sender ID
// $sms_to	: destination sms number
// $sms_msg	: sms message tobe delivered
// $uid		: sender User ID
// $gpid	: group phonebook id (optional)
// $smslog_id	: sms ID
// $sms_type : type of the message (defaults to text)
// $unicode : send as unicode (boolean)
function kannel_hook_sendsms($sms_sender,$sms_footer,$sms_to,$sms_msg,$uid='',$gpid=0,$smslog_id=0,$sms_type='text',$unicode=0) {
	global $kannel_param;
	global $http_path;
	logger_print("start smslog_id:".$smslog_id." uid:".$uid." to:".$sms_to, 3, "kannel outgoing");
	$sms_sender = stripslashes($sms_sender);
	$sms_footer = stripslashes($sms_footer);
	$sms_msg = stripslashes($sms_msg);
	$ok = false;
	$account = user_uid2username($uid);
	$msg_type = 1;

	if ($sms_footer) {
		$sms_msg = $sms_msg.$sms_footer;
	}

	if ($sms_type=='flash') {
		$msg_type = 0; //flash
	} else {
		$msg_type = 1; // text, default
	}

	// this doesn't work properly if kannel is not on the same server with playSMS
	// $dlr_url = $http_path['base'] . "/plugin/gateway/kannel/dlr.php?type=%d&smslog_id=$smslog_id&uid=$uid";

	// prior to 0.9.5.1
	// $dlr_url = $kannel_param['playsms_web'] . "/plugin/gateway/kannel/dlr.php?type=%d&smslog_id=".$smslog_id."&uid=".$uid;
	// since 0.9.5.1
	$dlr_url = $kannel_param['playsms_web'] . "/index.php?app=call&cat=gateway&plugin=kannel&access=dlr&type=%d&smslog_id=".$smslog_id."&uid=".$uid;

	$URL = "/cgi-bin/sendsms?username=".urlencode($kannel_param['username'])."&password=".urlencode($kannel_param['password']);
	$URL .= "&from=".urlencode($sms_sender)."&to=".urlencode($sms_to);
	// Handle DLR options config (emmanuel)
	//$URL .= "&dlr-mask=31&dlr-url=".urlencode($dlr_url);
	$URL .= "&dlr-mask=".$kannel_param['dlr']."&dlr-url=".urlencode($dlr_url);
	// end of Handle DLR options config (emmanuel)

	if ($sms_type=='flash') {
		$URL .= "&mclass=".$msg_type;
	}

	//Automatically setting the unicode flag if necessary
	if (!$unicode)
		$unicode=core_detect_unicode($sms_msg);

	if ($unicode) {
		if (function_exists('mb_convert_encoding')) {
			$sms_msg = mb_convert_encoding($sms_msg, "UCS-2BE", "auto");
			$URL .= "&charset=UTF-16BE";
		}
		$URL .= "&coding=2";
	}

	$URL .= "&account=".$account;
	$URL .= "&text=".urlencode($sms_msg);

	// fixme anton - patch 1.4.3, dlr requries smsc-id, you should add at least smsc=<your smsc-id in kannel.conf> from web
	if ($additional_param = $kannel_param['additional_param']) {
		$additional_param = "&".$additional_param;
	}
	$URL .= $additional_param;
	$URL = str_replace("&&", "&", $URL);

	logger_print("http://".$kannel_param['sendsms_host'].":".$kannel_param['sendsms_port'].$URL, 3, "kannel outgoing");

	// srosa 20100531: Due to improper http response from Kannel, file_get_contents cannot be used.
	// One issue is that Kannel responds with HTTP 202 whereas file_get_contents expect HTTP 200
	// The other is that a missing CRLF at the end of Kannel's message forces file_get_contents to wait forever.
	// reverting to previous way of doing things which works fine.
	/*
	if ($rv = trim(file_get_contents("$URL"))) {
	// old kannel responsed with Sent.
	// new kannel with the other 2
	if (($rv == "Sent.") || ($rv == "0: Accepted for delivery") || ($rv == "3: Queued for later delivery")) {
	$ok = true;
	// set pending
	$p_status = 0;
	dlr($smslog_id, $uid, $p_status);
	}
	}
	*/
	// fixme anton - deprecated when using PHP5
	//$connection = fsockopen($kannel_param['sendsms_host'],$kannel_param['sendsms_port'],&$error_number,&$error_description,60);
	$connection = fsockopen($kannel_param['sendsms_host'],$kannel_param['sendsms_port'],$error_number,$error_description,60);
	if ($connection) {
		socket_set_blocking($connection, false);
		fputs($connection, "GET ".$URL." HTTP/1.0\r\n\r\n");
		while (!feof($connection)) {
			$rv = fgets($connection, 128);
			if (($rv == "Sent.") || ($rv == "0: Accepted for delivery") || ($rv == "3: Queued for later delivery")) {
				logger_print("smslog_id:".$smslog_id." response:".$rv, 3, "kannel outgoing");
				// set pending
				$p_status = 0;
				$ok = true;
			}
		}
		fclose ($connection);
	}
	if (!$ok) {
		// set failed
		$p_status = 2;
	}
	dlr($smslog_id,$uid,$p_status);
	logger_print("end smslog_id:".$smslog_id." p_status:".$p_status, 3, "kannel outgoing");
	// good or bad, print it on the log
	return $ok;
}

function kannel_hook_call($requests) {
	global $apps_path, $http_path, $core_config, $kannel_param;
	$called_from_hook_call = true;
	$access = $requests['access'];
	if ($access == 'dlr') {
		$fn = $apps_path['plug'].'/gateway/kannel/dlr.php';
		logger_print("start load:".$fn, 2, "kannel call");
		include $fn;
		logger_print("end load dlr", 2, "kannel call");
	}
	if ($access == 'geturl') {
		$fn = $apps_path['plug'].'/gateway/kannel/geturl.php';
		logger_print("start load:".$fn, 2, "kannel call");
		include $fn;
		logger_print("end load geturl", 2, "kannel call");
	}
}

?>
