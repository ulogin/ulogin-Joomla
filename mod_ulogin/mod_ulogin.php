<?php
/**
* @package uLogin
* @copyright (c) 2011-2012 uLogin
* @license GNU/GPL3
*/
defined('_JEXEC') or die('Restricted access');


//====================Get default user fields===========================//
function getDefaultUserData()
{

    $data = new stdClass();
    $app = JFactory::getApplication();
    $params = $app->getParams('com_users');

    $temp = (array)$app->getUserState('com_users.registration.data', array());
    foreach ($temp as $k => $v) {
        $data->$k = $v;
    }

    $data->groups = array();

    $system = $params->get('new_usertype', 2);
    $data->groups[] = $system;

    unset($data->password1, $data->password2);

    JPluginHelper::importPlugin('user');

    $app->triggerEvent('onContentPrepareData', array('com_users.registration', $data));

    return $data;
}


//========================main code========================//

if (isset($_POST['token'])) {
    $s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']);
    $data = json_decode($s, true);
    if (isset($data['uid'])) {
        $user_id = JUserHelper::getUserId('ulogin_' . $data['network'] . '_' . $data['uid']);
        if (!$user_id) {
            $date = JFactory::getDate();
            $com_user = JComponentHelper::getParams('com_users');

            $instance = new JUser;
            $user_temp = getDefaultUserData();
            $user_temp->name = $data['first_name'] . ' ' . $data['last_name'];
            $user_temp->username = 'ulogin_' . $data['network'] . '_' . $data['uid'];
            $user_temp->email = $data['email'];
            $user_temp->password = JUserHelper:: hashPassword(JUserHelper::genRandomPassword());
            $user_temp->registerDate = $date->toSql();

            $user_data = array();

            foreach ($user_temp as $k => $v) {
                $user_data[$k] = $v;
            }
            $user_data['email'] = $user_data['email1'];
            $user_data['password'] = $user_data['password1'];


            JPluginHelper::importPlugin('user');

            $instance->bind($user_data);
            $i = 0;
            $email = explode('@', $data['email']);
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
    $user = JFactory::getUser();

    global $ulogin_counter;
    $ulogin_counter++;
    if ($user->get('guest')) {
        $uri = JURI::getInstance();
        $instance = &$uri;
        if ($ulogin_counter == 1)
            echo '<script src="http://ulogin.ru/js/ulogin.js"></script>';
        echo '<a href="#" id="uLogin_' . $ulogin_counter . '" x-ulogin-params="display=window&' .
            'fields=first_name,last_name,nickname,photo,email,sex&' .
            'redirect_uri=' . urlencode($instance->toString()) . '">' .
            '<img src="http://ulogin.ru/img/button.png" style = "width:187px;height:30px" /></a>';
    }
}