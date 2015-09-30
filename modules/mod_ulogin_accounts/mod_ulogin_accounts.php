<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_login
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
if(method_exists('JHtmlBehavior', 'core')) {
	JHtmlBehavior::core();
} else {
	JHtml::_('jquery.framework');
	JHtml::_('script', 'system/core.js', false, true);
}
$session = JFactory::getSession();
if($session->has('ulogin_script')) {
	$ulogin_script = $session->get('ulogin_script');
	if(isset($ulogin_script['token'])) {
		$script_params = $ulogin_script['token'];
		$script_params .= isset($ulogin_script['identity']) ? "','{$ulogin_script['identity']}" : '';
		JFactory::getDocument()->addScript('//ulogin.ru/js/ulogin.js');
		echo "<script type='text/javascript'>uLogin.mergeAccounts('{$script_params}')</script>";
	}
	$session->clear('ulogin_script');
}
$currentUserId = JFactory::getUser()->id;
if(intval($currentUserId) == 0) {
	return;
}
JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_ulogin/models/');
$model = JModelLegacy::getInstance('Ulogin', 'UloginModel');
JFactory::getDocument()->addScript('//ulogin.ru/js/ulogin.js');
JFactory::getDocument()->addScript('components/com_ulogin/js/ulogin.js');
JFactory::getDocument()->addStyleSheet('//ulogin.ru/css/providers.css');
JFactory::getDocument()->addStyleSheet('modules/mod_ulogin_accounts/css/ulogin_accounts.css');
$return = JURI::getInstance()->toString();
$redirect = JUri::base();
$redirect .= 'index.php?option=com_ulogin&task=login';
$redirect .= '&backurl=' . base64_encode($return);
$redirect = urlencode($redirect);
$callback = 'uloginCallback';
$uloginid = $params->get('uloginid', '');
if(empty($uloginid)) {
	jimport('joomla.application.component.helper');
	$uloginid = JComponentHelper::getParams('com_ulogin')->get('uloginid');
}
$uloginid = htmlspecialchars($uloginid);
$add_str = htmlspecialchars($params->get('add_str', ''));
$delete_str = htmlspecialchars($params->get('delete_str', ''));
$networks = $model->getUloginUserNetworks($currentUserId);
$hide_delete_str = empty($networks) ? ' style="display: none"' : '';
require JModuleHelper::getLayoutPath('mod_ulogin_accounts');
