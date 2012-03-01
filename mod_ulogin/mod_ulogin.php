<?php
/**
* @package uLogin
* @copyright (c) 2011-2012 uLogin
* @license GNU/GPL3
*/
defined('_JEXEC') or die('Restricted access');
jimport('joomla.user.helper');


if (isset($_POST['token'])) { 
	$s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']);
	$data = json_decode($s, true);
	if (isset($data['uid'])) {
		$user_id = JUserHelper::getUserId('ulogin_' . $data['network'] . '_' . $data['uid']);
		if (!$user_id) {
			$com_user = JComponentHelper::getParams('com_users');
			$group = $com_user->get('new_usertype');
			$acl = &JFactory::getACL();
			$date = JFactory::getDate();
			$instance = JUser::getInstance();
			$instance->name = $data['first_name'] . ' ' . $data['last_name'];
			$instance->username = 'ulogin_' . $data['network'] . '_' . $data['uid'];
			$instance->email = $data['email'];
			$instance->password = JUserHelper::getCryptedPassword(JUserHelper::genRandomPassword());
			$instance->usertype = $group;
			if (method_exists($acl, 'get_group_id')) $instance->gid = $acl->get_group_id('', $group);
			else $instance->groups = array($group);
			$instance->registerDate = $date->toMySQL();
			$instance->guest = false;
			$i = 0;
			$email = explode('@', $data['email']);
			$instance->save();
			while (!$instance->save()) {
				$i++;
				$instance->email = $email[0] . '+' . $i . '@' . $email[1];
			}
		} else $instance = JUser::getInstance($user_id);
		$session = &JFactory::getSession();
		$instance->guest = 0;
		$instance->aid = 1;
		$session->set('user', $instance);
		$instance->setLastVisit();
		echo '<script type=text/javascript>window.location.href=window.location.href;</script>';
	}
} else {
	$user = &JFactory::getUser();
	global $ulogin_counter;
	$ulogin_counter++;
	if ($user->get('guest')) {
		$instance = &JURI::getInstance();
		if($ulogin_counter==1) echo '<script src="http://ulogin.ru/js/ulogin.js"></script>';
		echo		'<div id="uLogin'.$ulogin_counter.'" x-ulogin-params="'.
					'display=small&'.
					'fields=first_name,last_name,photo,email&'.
					'providers=vkontakte,odnoklassniki,mailru,facebook&'.
					'hidden=twitter,google,yandex,livejournal,openid&'.
					'redirect_uri='.urlencode($instance->toString()).
					'"></div>';
	}
}