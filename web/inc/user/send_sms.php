<?php

/**
 * This file is part of playSMS.
 *
 * playSMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * playSMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with playSMS.  If not, see <http://www.gnu.org/licenses/>.
 */

defined('_SECURE_') or die('Forbidden');
if(!auth_isvalid()){auth_block();};

switch ($op) {
	case "send_sms":
		$to = $_REQUEST['to'];
		$message = stripslashes($_REQUEST['message']);

		// sender ID
		$sms_from = sendsms_get_sender($username);
		if (!$allow_custom_sender) {
			$allow_custom_sender = 'readonly';
		}

		// SMS footer
		$sms_footer = $core_config['user']['footer'];
		if (!$allow_custom_footer) {
			$allow_custom_footer = 'readonly';
		}

		// message template
		$option_values = "<option value=\"\" default>--"._('Please select template')."--</option>";
		$c_templates = sendsms_get_template();
		for ($i=0;$i<count($c_templates);$i++) {
			$option_values .= "<option value=\"".$c_templates[$i]['text']."\" title=\"".$c_templates[$i]['text']."\">".$c_templates[$i]['title']."</option>";
			$input_values .= "<input type=\"hidden\" name=\"content_".$i."\" value=\"".$c_templates[$i]['text']."\">";
		}
		if ($c_templates[0]) {
			$sms_template = "<div id=msg_template><select name=smstemplate id=msg_template_select style='width: 100%' onClick=\"SetSmsTemplate();\">$option_values</select></div>";
		}

		// unicode option
		if ($core_config['user']['send_as_unicode']) {
			$option_msg_unicode = 'checked';
		}

		$content = '';
		if ($err = $_SESSION['error_string']) {
			$error_content = "<div class=error_string>$err</div>";
		}

		// build form
		unset($tpl);
		$tpl = array(
		    'name' => 'send_sms',
		    'var' => array(
			'Send message' => _('Send message'),
			'SMS sender ID' => _('SMS sender ID'),
			'SMS footer' => _('SMS footer'),
			'Send to' => _('Send to'),
			'Message' => _('Message'),
			'Flash message' => _('Flash message'),
			'Unicode message' => _('Unicode message'),
			'Send' => _('Send'),
			'Schedule' => _('Schedule'),
			'Options' => _('Options'),
			'ERROR' => $error_content,
			'HTTP_PATH_BASE' => _HTTP_PATH_BASE_,
			'HTTP_PATH_THEMES' => _HTTP_PATH_THEMES_,
			'HINT_SEND_TO' => _hint(_('prefix with # for groups and @ for users')),
			'HINT_SCHEDULE' => _hint(_('format YYYY-MM-DD hh:mm')),
			'sms_from' => $sms_from,
			'sms_footer' => $sms_footer,
			'allow_custom_sender' => $allow_custom_sender,
			'allow_custom_footer' => $allow_custom_footer,
			'to' => $to,
			'sms_template' => $sms_template,
			// 'sms_schedule' => core_display_datetime(core_get_datetime()),
			'sms_schedule' => '',
			'message' => $message,
			'sms_footer_length' => $core_config['user']['opt']['sms_footer_length'],
			'per_sms_length' => $core_config['user']['opt']['per_sms_length'],
			'per_sms_length_unicode' => $core_config['user']['opt']['per_sms_length_unicode'],
			'max_sms_length' => $core_config['user']['opt']['max_sms_length'],
			'max_sms_length_unicode' => $core_config['user']['opt']['max_sms_length_unicode'],
			'option_msg_unicode' => $option_msg_unicode,
			'lang' => substr($core_config['user']['language_module'], 0, 2),
			'chars' => _('chars'),
			'SMS' => _('SMS')
		    ),
		    'if' => array(
			'calendar' => file_exists($apps_path['themes'].'/common/jscss/bootstrap-datetimepicker/bootstrap-datetimepicker.'.substr($core_config['user']['language_module'], 0, 2).'.js'),
			'combobox' => file_exists($apps_path['themes'].'/common/jscss/combobox/select2_locale_'.substr($core_config['user']['language_module'], 0, 2).'.js')
		    )
		);
		echo tpl_apply($tpl);
		break;
	case "send_sms_yes":
		if ($sms_to = trim($_REQUEST['p_num_text'])) {
			$sms_to = explode(',', $sms_to);
		}
		$sms_sender = trim($_REQUEST['sms_sender']);
		$sms_footer = trim($_REQUEST['sms_footer']);
		$sms_schedule = trim($_REQUEST['sms_schedule']);
		$msg_flash = $_REQUEST['msg_flash'];
		$msg_unicode = $_REQUEST['msg_unicode'];
		$message = $_REQUEST['message'];
		if ($sms_to[0] && $message) {
			$nofooter = true;
			if ($sms_footer) {
				$nofooter = false;
			}
			$sms_type = "text";
			if ($msg_flash == "on") {
				$sms_type = "flash";
			}
			$unicode = "0";
			if ($msg_unicode == "on") {
				$unicode = "1";
			}

			// get destinations
			for ($i=0;$i<count($sms_to);$i++) {
				if (substr(trim($sms_to[$i]), 0, 1) == '#') {
					if ($c_group_code = substr(trim($sms_to[$i]), 1)) {
						$c_gpid = phonebook_groupcode2id($core_config['user']['uid'], $c_group_code);
						$members = phonebook_getdatabyid($c_gpid);
						foreach ($members as $member) {
							if (trim($member['p_num'])) {
								$array_sms_to[] = trim($member['p_num']);
							}
						}
					}
				} else if (substr(trim($sms_to[$i]), 0, 1) == '@') {
					if ($c_username = substr(trim($sms_to[$i]), 1)) {
						$array_username[] = $c_username;
					}
				} else {
					$array_sms_to[] = trim($sms_to[$i]);
				}
			}
			
			// remove duplicates destinations
			array_unique($array_sms_to);
			
			$sms_queued = 0;
			$sms_failed = 0;

			// sendsms_im
			if (is_array($array_username) && $array_username[0]) {
				$im_sender = '@'.$core_config['user']['username'];
				foreach ($array_username as $target_user) {
					$im_sender = '@'.$core_config['user']['username'];
					if (recvsms_inbox_add(core_get_datetime(), $im_sender, $target_user, $message)) {
						$ok_im[] = $target_user;
					}
				}
			}
			for ($i=0;$i<count($ok_im);$i++) {
				if ($ok_im[$i]) {
					$sms_queued++;
				} else {
					$sms_failed++;
				}
			}

			/* fixme anton - soon sendsms_bc will be removed
			// sendsms_bc
			if (is_array($array_gpid) && $array_gpid[0]) {
				list($ok_bc,$to_bc,$smslog_id_bc,$queue_bc) = sendsms_bc($username,$array_gpid,$message,$sms_type,$unicode,$nofooter,$sms_footer,$sms_sender,$sms_schedule);
			}
			for ($i=0;$i<count($ok_bc);$i++) {
				if ($ok_bc[$i]) {
					$sms_queued++;
				} else {
					$sms_failed++;
				}
			}
			*/

			// sendsms
			if (is_array($array_sms_to) && $array_sms_to[0]) {
				list($ok,$to,$smslog_id,$queue) = sendsms($username,$array_sms_to,$message,$sms_type,$unicode,$nofooter,$sms_footer,$sms_sender,$sms_schedule);
			}
			for ($i=0;$i<count($ok);$i++) {
				if ($ok[$i]) {
					$sms_queued++;
				} else {
					$sms_failed++;
				}
			}
			
			$_SESSION['error_string'] = _('Your message has been delivered to queue')." ("._('queued').": ".$sms_queued.", "._('failed').": ".$sms_failed.")";
		} else {
			$_SESSION['error_string'] = _('You must select receiver and your message should not be empty');
		}
		header("Location: index.php?app=menu&inc=send_sms&op=send_sms&message=".urlencode(stripslashes($message)));
		exit();
		break;
}
