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

function rate_setusercredit($uid, $remaining=0) {
	$ret = core_call_hook();
	return $ret;
}

function rate_getusercredit($username) {
	$ret = core_call_hook();
	return $ret;
}

function rate_cansend($username, $sms_to) {
	$ret = core_call_hook();
	return $ret;
}

function rate_deduct($smslog_id) {
	$ret = core_call_hook();
	return $ret;
}

function rate_refund($smslog_id) {
	$ret = core_call_hook();
	return $ret;
}
