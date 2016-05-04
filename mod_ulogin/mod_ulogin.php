<?php
/**
* @package uLogin
* @copyright (c) 2011-2012 uLogin
* @license GNU/GPL3
*/
defined('_JEXEC') or die('Restricted access');
jimport('joomla.user.helper');


//====================Get default user fields===========================//
function getDefaultUserData(){
    
        $data	= new stdClass();
	$app	= JFactory::getApplication();
	$params	= JComponentHelper::getParams('com_users');

        $temp = (array)$app->getUserState('com_users.registration.data', array());
        foreach ($temp as $k => $v) {
            $data->$k = $v;
        }

        $data->groups = array();

        $system	= $params->get('new_usertype', 2);
        $data->groups[] = $system;

        unset($data->password1);
	unset($data->password2);

        $dispatcher	= JDispatcher::getInstance();
	JPluginHelper::importPlugin('user');

        $results = $dispatcher->trigger('onContentPrepareData', array('com_users.registration', $data));

	return $data;
}

//====================K2 user photo upload===========================//

function uploadPhoto($url, $filename){
    
    $file = array();
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_HEADER, 1); 
    $result = curl_exec($ch);
    if (!$result)
        return false;
    
    $savepath = JPATH_ROOT.DS.'media'.DS.'k2'.DS.'users'.DS;
    
    $value = array();
    preg_match('/Content-Type: (?<value>\w+(\/)\w+)/', $result, $value);
    $file['type'] = $value['value'];
    preg_match('/Content-Type: \w+(\/)(?<value>\w+)/', $result, $value);
    $file['ext'] = $value['value'] == 'jpeg' ? 'jpg' : $value['value'];
    $file['tmp_name'] = $filename.'.'.$file['ext'];
    $from = fopen($url,'rb'); 
    $to = fopen($savepath.$file['tmp_name'], "wb");
    $size = 0;
    if ($from && $to){
        while(!feof($from)) {
            $size += fwrite($to, fread($from, 1024 * 8 ), 1024 * 8 );
        }
    } else 
        return false;
    
    fclose($from); 
    fclose($to);
    $file['size'] = $size;
    $file['tmp_name'] = basename($file['tmp_name']);
    return $file;
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
			$user_temp->password = JUserHelper::getCryptedPassword(JUserHelper::genRandomPassword());
			$user_temp->registerDate = $date->toMySQL();
                        
                        $user_data = array();
                        
                        foreach ($user_temp as $k => $v) {
                            $user_data[$k] = $v;
                        }
                        $user_data['email'] = $user_data['email1'];
                        $user_data['password'] = $user_data['password1'];    
                        
                        //========================K2 user registration parameters=========================//
                        if (JComponentHelper::isEnabled('com_k2' , true)){
                            $avatar =  !empty($data['photo']) ? $data['photo'] : $data['photo_big'];
                            JRequest::setVar('K2UserForm', 1);
                            JRequest::setVar('gender',$data['sex'] == '2' ? 'm' : 'f');
                            JRequest::setVar('url',$data['identity']);
                            JRequest::setVar('del_image', false);
                            $photo = uploadPhoto($avatar, md5($data['identity']));
                            if ($photo)
                                JRequest::set(array('image' => array('tmp_name' => $photo['tmp_name'],
                                                                     'name' => $photo['tmp_name'],
                                                                     'type' => $photo['type'],
                                                                     'size' => $photo['size'],
                                                                     'error' => UPLOAD_ERR_OK,
                                                                )

                                                   ),
                                        'FILES');
                        }
                        //========================K2 user registration parameters=========================//
                        
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
	$user = &JFactory::getUser();
	global $ulogin_counter;
	$ulogin_counter++;
	if ($user->get('guest')) {
		$instance = &JURI::getInstance();
        if($ulogin_counter==1)
            echo '<script src="http://ulogin.ru/js/ulogin.js"></script>';
            echo '<a href="#" id="uLogin_'.$ulogin_counter.'" x-ulogin-params="display=window&'.
                'fields=first_name,last_name,nickname,photo,email,sex&'.
                'redirect_uri='.urlencode($instance->toString()).'">'.
                '<img src="http://ulogin.ru/img/button.png" style = "width:187;height:30" /></a>';
	}
}