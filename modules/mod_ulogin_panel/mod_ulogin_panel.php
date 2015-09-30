<?php
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
if(intval($currentUserId) > 0) {
	return;
}
JFactory::getDocument()->addScript('//ulogin.ru/js/ulogin.js');
JFactory::getDocument()->addScript('components/com_ulogin/js/ulogin.js');
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
require JModuleHelper::getLayoutPath('mod_ulogin_panel');
