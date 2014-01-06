<?php
defined('_SECURE_') or die('Forbidden');
if(!auth_isvalid()){auth_block();};

$gpid = $_REQUEST['gpid'];
$pid = $_REQUEST['pid'];
$tid = $_REQUEST['tid'];

if ($tid = $_REQUEST['tid']) {
	if (! ($tid = dba_valid(_DB_PREF_.'_toolsMsgtemplate', 'tid', $tid))) {
		auth_block();
	}
}

switch ($op) {
	case "list":
		$fm_name = "fm_smstemp";
		$content = "
			<h2>"._('Message template')."</h2>
			<form id=$fm_name name=$fm_name action='index.php?app=menu&inc=tools_msgtemplate&op=actions' method=POST>
			"._CSRF_FORM_."
			<input type=hidden name=go value=delete>
			<div class=actions_box>
			<div class=pull-left><a href='index.php?app=menu&inc=tools_msgtemplate&op=add'>".$core_config['icon']['add']."</a></div>
			<div class=pull-right>
				<a href='#' onClick=\"return SubmitConfirm('"._('Are you sure you want to delete these items ?')."', '".$fm_name."');\">".$core_config['icon']['delete']."</a>
			</div>
			</div>
			<div class=table-responsive>
			<table class=playsms-table-list>
			<thead><tr>
				<th width=30%>"._('Name')."</th>
				<th width=65%>"._('Content')."</th>
				<th width=5%><input type=checkbox onclick=CheckUncheckAll(document.".$fm_name.")></th>
			</tr></thead>
			<tbody>";
		$db_query = "SELECT * FROM "._DB_PREF_."_toolsMsgtemplate WHERE uid='$uid' ORDER BY t_title";
		$db_result = dba_query($db_query);
		$i = 0;
		while ($db_row = dba_fetch_array($db_result)) {
			$tid = $db_row['tid'];
			$temp_title = $db_row['t_title'];
			$temp_text = $db_row['t_text'];
			$i++;
			$content .= "
				<tr>
					<td><a href='index.php?app=menu&inc=tools_msgtemplate&op=edit&tid=$tid'>$temp_title</a></td>
					<td>$temp_text</td>
					<td><input type=checkbox name=chkid".$i."></td>
					<input type=hidden name=chkid_value".$i." value='".$db_row['tid']."'>
				</tr>";
		}
		$content .= "
			</tbody>
			</table>
			</div>
			<input type='hidden' name='item_count' value='$i'>
			</form>
			<div class=text-info>
				<p>"._('Notes')."</p>
				<ul>
					<li>#NAME# "._('will be replaced with the name listed in phonebook')."</li>
					<li>#NUM# "._('will be replaced with the phone number listed in phonebook')."</li>
				</ul>
			</div>
		";
		if ($err = $_SESSION['error_string']) {
			echo "<div class=error_string>$err</div>";
		}
		echo $content;
		break;
	case "add":
		if ($err = $_SESSION['error_string']) {
			$content = "<div class=error_string>$err</div>";
		}
		$content .= "
			<h2>"._('Message template')."</h2>
			<h3>"._('Add message template')."</h3>
			<form action='index.php?app=menu&inc=tools_msgtemplate&op=actions&go=add' method=POST>
			"._CSRF_FORM_."
			<table class=playsms-table>
			<tr>
				<td class=label-sizer>"._('Message template name')."</td><td><input type=text size=30 maxlength=100 name=t_title></td>
			</tr>
			<tr>
				<td>"._('Message template content')."</td><td><input type=text name=t_text size=30></td>
			</tr>	
			</table>	
			<p><input type='submit' class='button' value='"._('Save')."'>
			</form>
			<p>"._back('index.php?app=menu&inc=tools_msgtemplate&op=list');
			echo $content;
		break;
	case "edit":
		$db_query = "SELECT * FROM "._DB_PREF_."_toolsMsgtemplate WHERE tid='$tid'";
		$db_result = dba_query($db_query);
		$db_row = dba_fetch_array($db_result);
		if ($err = $_SESSION['error_string']) {
			$content = "<div class=error_string>$err</div>";
		}
		$content .= "
			<h2>"._('Message template')."</h2>
			<h3>"._('Edit message template')."</h3>
			<form action='index.php?app=menu&inc=tools_msgtemplate&op=actions&go=edit' method=POST>
			"._CSRF_FORM_."
			<input type=hidden name=tid value='$tid'>
			<table class=playsms-table>
			<tr>
				<td class=label-sizer>"._('Message template name')."</td><td><input type=text size=30 maxlength=100 name=t_title value='".$db_row['t_title']."'></td>
			</tr>
			<tr>
				<td>"._('Message template content')."</td><td><input type=text name=t_text size=30 value='".$db_row['t_text']."'></td>
			</tr>	
			</table>
			<p><input type='submit' class='button' value='"._('Save')."'>
			<input type='hidden' name='item_count' value='$i'>
			</form>
			<p>"._back('index.php?app=menu&inc=tools_msgtemplate&op=list');
		echo $content;
		break;
	case "actions":
		$go = $_REQUEST['go'];
		switch ($go) {
			case "add":
				$t_title = $_POST['t_title'];
				$t_text = $_POST['t_text'];
				if ($t_title && $t_text) {
					$db_query = "INSERT INTO "._DB_PREF_."_toolsMsgtemplate (uid,t_title,t_text) VALUES ('$uid','$t_title','$t_text')";
					$db_result = dba_insert_id($db_query);
					if ($db_result > 0) {
						$_SESSION['error_string'] = _('Message template has been saved');
					} else {
						$_SESSION['error_string'] = _('Fail to add message template');
					}
				} else {
					$_SESSION['error_string'] = _('You must fill all fields');
				}
				header("Location: index.php?app=menu&inc=tools_msgtemplate&op=add");
				exit();
				break;
			case "edit":
				$t_title = $_POST['t_title'];
				$t_text = $_POST['t_text'];
				if ($t_title && $t_text) {
					$db_query = "UPDATE "._DB_PREF_."_toolsMsgtemplate SET c_timestamp='".mktime()."',t_title='$t_title', t_text='$t_text' WHERE tid='$tid'";
					$db_result = dba_affected_rows($db_query);
					if ($db_result > 0) {
						$_SESSION['error_string'] = _('Message template has been edited');
					} else {
						$_SESSION['error_string'] = _('Fail to edit message template');
					}
				} else {
					$_SESSION['error_string'] = _('You must fill all fields');
				}
				header("Location: index.php?app=menu&inc=tools_msgtemplate&op=list");
				exit();
				break;
			case "delete":
				$item_count = $_POST['item_count'];
				for ($i=1;$i<=$item_count;$i++) {
					$chkid[$i] = $_POST['chkid'.$i];
					$chkid_value[$i] = $_POST['chkid_value'.$i];
				}
				for ($i=1;$i<=$item_count;$i++) {
					if (($chkid[$i] == 'on') && $chkid_value[$i]) {
						$db_query = "DELETE FROM "._DB_PREF_."_toolsMsgtemplate WHERE tid='".$chkid_value[$i]."'";
						$db_result = dba_affected_rows($db_query);
					}
				}
				$_SESSION['error_string'] = _('Selected message template has been deleted');
				header("Location: index.php?app=menu&inc=tools_msgtemplate&op=list");
				exit();
				break;
		}
}
