<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;

$app = JFactory::getApplication();
$app->redirect('administrator/index.php?option=com_config&view=component&component=com_ulogin');
