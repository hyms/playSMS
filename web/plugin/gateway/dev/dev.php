<?php
defined('_SECURE_') or die('Forbidden');
if(!auth_isadmin()){auth_block();};

include $apps_path['plug']."/gateway/dev/config.php";

$gw = core_gateway_get();

if ($gw == $dev_param['name']) {
	$status_active = "<span class=status_active />";
} else {
	$status_active = "<span class=status_inactive />";
}

switch ($op) {
	case "manage":
		$sender = '+629876543210';
		$receiver = '1234';
		$datetime = core_get_datetime();
		if ($err = $_SESSION['error_string']) {
			$content = "<div class=error_string>$err</div>";
		}
		$content .= "
			<h2>"._('Manage dev')."</h2>
			<table class=playsms-table>
				<tbody><tr><td class=label-sizer>"._('Gateway name')."</td><td>dev $status_active</td></tr></tbody>
			</table>
			<br />
			<h3>"._('Simulate incoming SMS')."</h3>
			<form action=\"index.php?app=menu&inc=gateway_dev&op=simulate\" method=post>
			"._CSRF_FORM_."
			<table class=playsms-table>
				<tbody>
				<tr><td class=label-sizer>"._('Message')."</td><td><input type=text name=message value=\"$message\" size=30 maxlength=250></td></tr>
				<tr><td>"._('Sender')."</td><td><input type=text name=sender value=\"$sender\" size=30 maxlength=20></td></tr>
				<tr><td>"._('Receiver')."</td><td><input type=text name=receiver value=\"$receiver\" size=30 maxlength=20></td></tr>
				<tr><td>"._('Date/Time')."</td><td><input type=text name=datetime value=\"$datetime\" size=30 maxlength=20></td></tr>
				</tbody>
			</table>
			<p><input type=submit class=button value=\""._('Submit')."\">
			</form>";
		$content .= _back('index.php?app=menu&inc=tools_gatewaymanager&op=gatewaymanager_list');
		echo $content;
		break;
	case "manage_activate":
		$db_query = "UPDATE "._DB_PREF_."_tblConfig_main SET c_timestamp='".mktime()."',cfg_gateway_module='dev'";
		$db_result = dba_query($db_query);
		$_SESSION['error_string'] = _('Gateway has been activated');
		header("Location: index.php?app=menu&inc=gateway_dev&op=manage");
		exit();
		break;
	case "simulate":
		$sms_sender = ( $_REQUEST['sender'] ? $_REQUEST['sender'] : '+629876543210' );
		$sms_receiver = ( $_REQUEST['receiver'] ? $_REQUEST['receiver'] : '1234' );
		$sms_datetime = ( $_REQUEST['datetime'] ? $_REQUEST['datetime'] : core_get_datetime() );
		$message = ( $_REQUEST['message'] ? $_REQUEST['message'] : _('This is a test incoming SMS message') );
		if (trim($sms_sender) && trim($sms_receiver) && trim($sms_datetime) && trim($message)) {
			recvsms($sms_datetime,$sms_sender,$message,$sms_receiver);
			$err = "sender:".$sms_sender." receiver:".$sms_receiver." dt:".$sms_datetime." msg:".stripslashes($message);
			logger_print($err, 3, "dev incoming");
			$_SESSION['error_string'] = $err;
		} else {
			$_SESSION['error_string'] = _('Fail to simulate incoming SMS');
		}
		header("Location: index.php?app=menu&inc=gateway_dev&op=manage");
		exit();
		break;
}
?>