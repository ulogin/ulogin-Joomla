<?php
defined('_JEXEC') or die;
// Подключаем логирование.
JLog::addLogger(array ( 'text_file' => 'com_ulogin.php' ), JLog::ALL, array ( 'com_ulogin' ));
// Устанавливаем обработку ошибок в режим использования Exception.
JError::$legacy = false;
jimport('joomla.application.component.controller');
$controller = JControllerLegacy::getInstance('Ulogin');
$input = JFactory::getApplication()->input;
$controller->execute($input->getCmd('task', 'display'));
$controller->redirect();
