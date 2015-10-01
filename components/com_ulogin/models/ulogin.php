<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;

// Подключаем библиотеку modelitem Joomla.
jimport('joomla.application.component.modelitem');

/**
 * Модель сообщения компонента uLogin.
 */
class UloginModelUlogin extends JModelItem
{
	public function __construct($config = array()) {
		parent::__construct($config);
	}


//--------------------
	/**
	 * Проверка, есть ли пользователь с указанным id в базе
	 * @param $u_id
	 * @return bool
	 */
	public function checkUserId ($u_id = 0) {
		$params = array(
			'conditions' => "id = {$u_id}",
			'columns' => 'id',
		);
		$result = self::query('users', $params)->execute();
		return @$result->num_rows > 0 ? true : false;
	}


//--------------------
	/**
	 * Проверка, есть ли пользователь с указанным username в базе
	 * @param string $email
	 * @return int|bool
	 */
	public function checkUserName ($username = '') {
		$params = array(
			'conditions' => "username LIKE '{$username}'",
			'columns' => 'id',
		);
		$result = self::query('users', $params)->execute();
		return @$result->num_rows > 0 ? true : false;
	}


//--------------------
	/**
	 * Получение id пользователя по email
	 * @param string $email
	 * @return int|bool
	 */
	public function getUserIdByEmail ($email = '') {
		$params = array(
			'conditions' => "email LIKE '{$email}'",
			'columns' => 'id',
			'limit' => 1,
		);
		$result = self::query('users', $params)->loadAssoc();
		return isset($result['id']) ? $result['id'] : false;
	}


//--------------------
	/**
	 * Получение данных о пользователе из таблицы ulogin_users
	 * @param array $fields
	 * @return bool|mixed
	 */
	public function getUloginUserItem ($fields = array()) {
		$params = array(
			'conditions' => $fields,
			'limit' => 1,
		);

		$result = self::query('ulogin_users', $params)->loadAssoc();
		return $result;
	}


//--------------------
	/**
	 * Получение массива соцсетей пользователя по значению поля $user_id
	 * @param int $user_id
	 * @return array
	 */
	public function getUloginUserNetworks ($user_id = 0) {
		$params = array(
			'conditions' => "user_id = {$user_id}",
			'columns' => 'network',
		);

		$result = self::query('ulogin_users', $params)->loadAssocList();

		$networks = array();

		foreach ($result as $value) {
			$networks[] = $value['network'];
		}

		return $networks;
	}


//--------------------
	/**
	 * Удаление данных о пользователе из таблицы ulogin_users
	 * @param array $data
	 * @return mixed
	 */
	public function deleteUloginUser ($data = array()) {
		return self::delete('ulogin_users', $data);
	}


//--------------------
	/**
	 * Добавление данных о пользователе в таблицы ulogin_users
	 * @param array $data
	 * @return bool|mixed
	 */
	public function addUloginAccount ($data = array()) {
		return self::insert('ulogin_users', $data);
	}


//--------------------
	/**
	 * ID записи в таблице компонента K2
	 * @param int $u_id
	 * @return mixed
	 */
	public function getK2UserID ($u_id = 0) {
		$params = array(
			'conditions' => "userID = {$u_id}",
			'columns' => 'id',
		);
		$result = self::query('k2_users', $params)->loadResult();
		return $result;
	}

//--------------------
	/**
	 * Проверка, подключен ли компонент
	 * @param $component_name
	 * @return bool
	 */
	public function checkComponent ($component_name) {
		$params = array(
			'conditions' => "name = '{$component_name}'",
			'columns' => 'enabled',
		);
		$result = self::query('extensions', $params)->loadResult();
		return $result == 1 ? true : false;
	}

//------------------------------------------------------------------------
	/**
	 * Получение аватара для профиля пользователя K2
	 * @param string $file_url
	 * @return string
	 */
	public function uploadAvatarK2 ($file_url = '') {
		if (empty($file_url)) return '';

		$upload_dir = JPATH_ROOT.DS.'media'.DS.'k2'.DS.'users'.DS;
		$size = getimagesize( $file_url );

		switch ( $size[2] ) {
			case IMAGETYPE_GIF:
				$file_ext = 'gif';
				break;
			case IMAGETYPE_JPEG:
				$file_ext = 'jpg';
				break;
			case IMAGETYPE_PNG:
				$file_ext = 'png';
				break;
			default:
				$file_ext = 'jpg';
				break;
		}

		$filename = substr( md5( $file_url . microtime( true ) ), 0, 12 ) . '.' . $file_ext;
		$path = $upload_dir . $filename;
		$result = @copy( $file_url, $path );

		return $result ? $filename : '';
	}

//========================================================================

	static private function query($table, $params = array()){
		$conditions = isset($params['conditions']) ? $params['conditions'] : false;
		$columns    = isset($params['columns']) ? $params['columns'] : '*';
		$order      = isset($params['order']) ? $params['order'] : false;
		$limit      = isset($params['limit']) ? $params['limit'] : 0;
		$offset     = isset($params['offset']) ? $params['offset'] : 0;
		
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query
			->select($columns)
			->from($db->quoteName('#__'.$table));

		if( $conditions ) {
			if (is_array($conditions)
			    && (bool)count(array_filter(array_keys($conditions), 'is_string'))) {
				foreach ($conditions as $key => $condition){
					$conditions[$key] = "$key = '$condition'";
				}
			}
			$query->where($conditions);
		}

		if( $order )
			$query->order($order);

		$db->setQuery($query,$offset,$limit);
		return $db;
	}


	static private function escapeKeys($array){
		$db = JFactory::getDBO();
		$keys = array_values($array);
		foreach($keys as $id=>$key)
			$keys[$id] = $db->quote($key);
		return $keys;
	}


	static private function insert($table, $data = array()){
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query
			->insert($db->quoteName('#__'.$table))
			->columns($db->quoteName(array_keys($data)))
			->values(implode(',', self::escapeKeys($data)));

		$db->setQuery($query);
		return $db->execute() ? $db->insertid() : false;
	}



	static private function delete($table, $conditions){
		if (!isset($conditions)) { return false; }

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		if (is_array($conditions)
		    && (bool)count(array_filter(array_keys($conditions), 'is_string'))) {
			foreach ($conditions as $key => $condition){
				$conditions[$key] = "$key = '$condition'";
			}
		}

		$query
			->delete($db->quoteName('#__'.$table))
			->where($conditions);

		$db->setQuery($query);
		return $db->execute();
	}


}
