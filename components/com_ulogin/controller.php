<?php
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

/**
 * Контроллер компонента uLogin.
 */
class UloginController extends JControllerLegacy
{
	protected $u_data;
	protected $currentUserId;
	protected $isUserLogined;
	protected $model;
	protected $token;

	public function __construct($config = array()) {
		parent::__construct($config);
		$this->model = $this->getModel('Ulogin');
		$this->jinput = JFactory::getApplication()->input;
	}


	public function login(){
		$title = '';
		$msg = '';

		$this->currentUserId = JFactory::getUser()->id;
		$this->isUserLogined = $this->currentUserId > 0 ? true : false;

		if ($this->isUserLogined){
			$msg = 'Аккаунт успешно добавлен';
		}

		$this->uloginLogin($title, $msg);
	}

	public function delete_account(){
		$this->deleteAccount();
	}


	//==========================================================

	protected function uloginLogin ($title = '', $msg = '') {
		$this->u_data = $this->uloginParseRequest();
		if ( !$this->u_data ) {
			return;
		}

		try {
			$u_user_db = $this->model->getUloginUserItem(array('identity' => $this->u_data['identity']));
			$user_id = 0;

			if ( $u_user_db ) {

				if ($this->model->checkUserId($u_user_db['user_id'])) {
					$user_id = $u_user_db['user_id'];
				}

				if ( intval( $user_id ) > 0 ) {
					if ( !$this->checkCurrentUserId( $user_id ) ) {
						// если $user_id != ID текущего пользователя
						return;
					}
				} else {
					// данные о пользователе есть в ulogin_table, но отсутствуют в users. Необходимо переписать запись в ulogin_table и в базе users.
					$user_id = $this->newUloginAccount( $u_user_db );
				}

			} else {
				// пользователь НЕ обнаружен в ulogin_table. Необходимо добавить запись в ulogin_table и в базе users.
				$user_id = $this->newUloginAccount();
			}

			// обновление данных и Вход
			if ( $user_id > 0 ) {
				$this->loginUser( $user_id );

				$networks = $this->model->getUloginUserNetworks( $user_id );
				$this->sendMessage( array(
					'title' => $title,
					'msg' => $msg,
					'networks' => $networks,
					'type' => 'success',
				) );
			}
			return;
		}
		catch (Exception $e){
			$this->sendMessage (array(
				'title' => "Ошибка при работе с БД.",
				'msg' => "Exception: " . $e->getMessage(),
				'type' => 'error'
			));
			return;
		}
	}


	/**
	 * Отправляет данные как ответ на ajax запрос, если код выполняется в результате вызова callback функции,
	 * либо добавляет сообщение в сессию для вывода в режиме redirect
	 * @param array $params
	 */
	protected function sendMessage ($params = array()) {
		$app = JFactory::getApplication();
		$backurl = base64_decode($app->input->get->get('backurl', '', 'BASE64'));

		$type = ($params['type'] == 'error' || $params['type'] == 'warning')
			? $params['type']
			: 'message';

		$message = (!empty($params['title']) ? $params['title']  . ' <br>' : '') . $params['msg'];

		if ($params['type'] == 'error') {
			JLog::add($params['title'] . ' ' . $params['msg'], JLog::ERROR, 'com_ulogin');
		}

		$app->enqueueMessage($message, $type);

		if (!empty($backurl)){
			
			if (!empty($params['script'])) {
				$session = JFactory::getSession();
				$session->set('ulogin_script', $params['script']);
			}
			$app->redirect(JRoute::_($backurl, false));
			
		} else {
			unset($params['title']);
			unset($params['msg']);
			unset($params['type']);
			echo new JResponseJson($params);
			jexit();
		}
	}




	/**
	 * Добавление в таблицу uLogin
	 * @param $u_user_db - при непустом значении необходимо переписать данные в таблице uLogin
	 */
	protected function newUloginAccount($u_user_db = ''){
		$u_data = $this->u_data;

		if ($u_user_db) {
			// данные о пользователе есть в ulogin_user, но отсутствуют в users => удалить их
			$this->model->deleteUloginUser(array('id' => $u_user_db['id']));
		}

		$CMSuserId = $this->model->getUserIdByEmail($u_data['email']);

		// $emailExists == true -> есть пользователь с таким email
		$user_id = 0;
		$emailExists = false;
		if ($CMSuserId) {
			$user_id = $CMSuserId; // id юзера с тем же email
			$emailExists = true;
		}

		// $isUserLogined == true -> пользователь онлайн
		$currentUserId = $this->currentUserId;
		$isUserLogined = $this->isUserLogined;

		if (!$emailExists && !$isUserLogined) {
			// отсутствует пользователь с таким email в базе -> регистрация в БД
			$user_id = $this->regUser();
			$this->addUloginAccount($user_id);
		} else {
			// существует пользователь с таким email или это текущий пользователь
			if (intval($u_data["verified_email"]) != 1){
				// Верификация аккаунта

				$this->sendMessage(
					array(
						'title' => 'Подтверждение аккаунта.',
						'msg' => 'Электронный адрес данного аккаунта совпадает с электронным адресом существующего пользователя. ' .
						         '<br>Требуется подтверждение на владение указанным email.',
						'script' => array('token' => $this->token),
					)
				);
				return false;
			}

			$user_id = $isUserLogined ? $currentUserId : $user_id;

			$other_u = $this->model->getUloginUserItem(array(
				'user_id' => $user_id,
			));

			if ($other_u) {
				// Синхронизация аккаунтов
				if(!$isUserLogined && !isset($u_data['merge_account'])){
					$this->sendMessage(
						array(
							'title' => 'Синхронизация аккаунтов.',
							'msg' => 'С данным аккаунтом уже связаны данные из другой социальной сети. ' .
							         '<br>Требуется привязка новой учётной записи социальной сети к этому аккаунту.',
							'script' => array('token' => $this->token, 'identity' => $other_u['identity']),
						)
					);
					return false;
				}
			}

			$this->addUloginAccount($user_id);
		}

		return $user_id;
	}



	/**
	 * Регистрация пользователя в БД
	 * @return mixed
	 */
	protected function regUser(){
		
		$users_model = $this->getModel('Registration');

		$u_data = $this->u_data;

		$login = $this->generateNickname($u_data['first_name'],$u_data['last_name'],$u_data['nickname'],$u_data['bdate']);

		$CMSuser = array(
			'name' => $login,
			'username' => $login,
			'email' => $u_data['email'],
			'verified_email' => isset($u_data["verified_email"]) ? $u_data["verified_email"] : -1,
		);

		jimport('joomla.application.component.helper');
		$groupId = JComponentHelper::getParams('com_ulogin')->get('group');

		if (!empty($groupId)) {
			$groups[] = JComponentHelper::getParams('com_users')->get('new_usertype');
			$groups[] = $groupId;
			$CMSuser['groups'] = $groups;
		}

		$res = $users_model->register($CMSuser);

		if (intval($res) > 0) {

			if (JComponentHelper::isEnabled('com_k2' , true)) {

				JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_k2'.DS.'tables');
				$row = JTable::getInstance('K2User', 'Table');

				$row->set('url', isset( $u_data["profile"] ) ? $u_data["profile"] : '');

				if ( isset( $u_data['sex'] ) ) {
					if ( $u_data['sex'] == 1 ) {
						$row->set('gender', 'f');
					} elseif ( $u_data['sex'] == 2 ) {
						$row->set('gender', 'm');
					}
				}

				$file_url = ( !empty( $u_data['photo_big'] ) )
					? $u_data['photo_big']
					: ( !empty( $u_data['photo'] )  ? $u_data['photo'] : '' );

				$row->set('image', $this->model->uploadAvatarK2($file_url));

				$row->store();
			}

			return $res;

		} elseif ($res === 'adminactivate') {
			$this->sendMessage (array(
				'title' => "",
				'msg' => JText::_('COM_USERS_REGISTRATION_COMPLETE_VERIFY'),
				'type' => 'warning'
			));
			return false;
		} elseif ($res === 'useractivate') {
			$this->sendMessage (array(
				'title' => "",
				'msg' => JText::_('COM_USERS_REGISTRATION_COMPLETE_ACTIVATE'),
				'type' => 'warning'
			));
			return false;
		} else {
			$this->sendMessage (array(
				'title' => "Ошибка при регистрации.",
				'msg' => "Произошла ошибка при регистрации пользователя.",
				'type' => 'error'
			));
			return false;
		}
	}




	/**
	 * Добавление записи в таблицу ulogin_user
	 * @param $user_id
	 * @return bool
	 */
	protected function addUloginAccount($user_id){
		$user = $this->model->addUloginAccount(array(
			'user_id' => $user_id,
			'identity' => strval($this->u_data['identity']),
			'network' => $this->u_data['network'],
		));

		if (!$user) {
			$this->sendMessage (array(
				'title' => "Произошла ошибка при авторизации.",
				'msg' => "Не удалось записать данные об аккаунте.",
				'type' => 'error'
			));
			return false;
		}

		return true;
	}



	/**
	 * Выполнение входа пользователя в систему по $user_id
	 * @param $u_user
	 * @param int $user_id
	 */
	protected function loginUser($user_id = 0){

		$CMSuser = JFactory::getUser($user_id);

		if(!$CMSuser) {
			$this->sendMessage(
				array(
					'title' => '',
					'msg' => 'Произошла ошибка при авторизации.',
					'type' => 'error',
				)
			);
			return false;
		}
		
		$u_data = $this->u_data;

		$app    = JFactory::getApplication();

		$credentials = array();
		$credentials['username']  = $CMSuser->username;
		$credentials['password']  = '';
		$credentials['secretkey'] = '';

		$options['ulogin_auth'] = true;
		$options['user_id'] = $CMSuser->id;

		if ($app->login($credentials, $options) === true) {

			// обновление данных для K2
			if ($this->model->checkComponent('com_k2')) {

				$k2id = $this->model->getK2UserID($CMSuser->id);

				JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_k2'.DS.'tables');
				$row = JTable::getInstance('K2User', 'Table');

				if ($k2id)
				{
					$k2exist = $row->load($k2id);
				}

				if (empty($row->url)
				    || (empty($row->gender) && isset($u_data['sex']))
				    || (empty($row->image) && (!empty($u_data['photo_big']) || !empty($u_data['photo'])))
				) {
					if ($k2exist) {
						$row->id = $k2id;
					}

					if ( !$k2exist || empty( $row->url ) ) {
						$row->url = isset( $u_data["profile"] ) ? $u_data["profile"] : '' ;
					}
					if ( !$k2exist || (empty( $row->gender ) && isset( $u_data['sex']))) {
						if ( $u_data['sex'] == 1 ) {
							$row->gender = 'f' ;
						} elseif ( $u_data['sex'] == 2 ) {
							$row->gender = 'm' ;
						}
					}
					if ( !$k2exist || empty( $row->image ) ) {
						$file_url = ( !empty( $u_data['photo_big'] ) )
							? $u_data['photo_big']
							: ( !empty( $u_data['photo'] ) ? $u_data['photo'] : '' );

						$row->image = $this->model->uploadAvatarK2( $file_url );
					}

					$row->store(true);
				}
			}

			return true;
		} else {

			// проверка на двухфакторную авторизацию
			require_once JPATH_ADMINISTRATOR . '/components/com_users/helpers/users.php';
			$methods = UsersHelper::getTwoFactorMethods();
			if (count($methods) > 1)
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_users/models/user.php';
				$user_model = new UsersModelUser;
				$otpConfig = $user_model->getOtpConfig($user_id);
				$options['otp_config'] = $otpConfig;
				if (isset($otpConfig->method) && ($otpConfig->method != 'none')){
					$this->sendMessage (array(
						'title' => "",
						'msg' => "Авторизация невозможна при включенной двухфакторной аутентификации.",
						'type' => 'warning'
					));
					return false;
				}
			}

			$this->sendMessage (array(
				'title' => "Ошибка при авторизации.",
				'msg' => "Не удалось осуществить вход.",
				'type' => 'error'
			));
			return false;
		}
	}

//todo createAvatar (для K2)
	/**
	 * Создание аватара
	 * @return string
	 */
	protected function createAvatar() {
		$u_data = $this->u_data;

		$file_url = ( !empty( $u_data['photo_big'] ) )
			? $u_data['photo_big']
			: ( !empty( $u_data['photo'] )  ? $u_data['photo'] : '' );

		$filename = '';

		$upload_dir = PATH.'/images/users/avatars/';

		$inCore = cmsCore::getInstance();
		$cfg = $inCore->loadComponentConfig('users');

		$q = isset( $file_url ) ? true : false;

		if ($q) {
			$size = getimagesize( $file_url );

			switch ( $size[2] ) {
				case IMAGETYPE_GIF:
					$dest_ext = 'gif';
					break;
				case IMAGETYPE_JPEG:
					$dest_ext = 'jpg';
					break;
				case IMAGETYPE_PNG:
					$dest_ext = 'png';
					break;
				default:
					$dest_ext = 'jpg';
					break;
			}

			$filename = substr( md5( $file_url . microtime( true ) ), 0, 16 ) . '.' . $dest_ext;

			$path = $upload_dir . $filename;
			$path_small = $upload_dir . 'small/' . $filename;

			cmsCore::includeGraphics();
			@copy( $file_url, $path );
			$q1 = @img_resize($path, $path,  $cfg['medw'], $cfg['medh']);
			@copy( $path, $path_small );
			$q2 = @img_resize($path_small, $path_small, $cfg['smallw'], $cfg['smallw']);

			$q = $q1 && $q2;
		}


		if (!$q) {
			return '';
		}

		return $filename;
	}



	/**
	 * Проверка текущего пользователя
	 * @param $user_id
	 */
	protected function checkCurrentUserId($user_id){
		$currentUserId = $this->currentUserId;
		if($this->isUserLogined) {
			if ($currentUserId == $user_id) {
				return true;
			}
			$this->sendMessage (
				array(
					'title' => '',
					'msg' => 'Данный аккаунт привязан к другому пользователю. ' .
					         '</br>Вы не можете использовать этот аккаунт',
					'type' => 'warring',
				)
			);
			return false;
		}
		return true;
	}



	/**
	 * Обработка ответа сервера авторизации
	 */
	protected function uloginParseRequest(){
		$this->token = $this->jinput->get('token');

		if (!$this->token) {
			$this->sendMessage (array(
				'title' => "Произошла ошибка при авторизации.",
				'msg' => "Не был получен токен uLogin.",
				'type' => 'error'
			));
			return false;
		}

		$s = $this->getUserFromToken();

		if (!$s){
			$this->sendMessage (array(
				'title' => "Произошла ошибка при авторизации.",
				'msg' => "Не удалось получить данные о пользователе с помощью токена.",
				'type' => 'error'
			));
			return false;
		}

		$this->u_data = json_decode($s, true);

		if (!$this->checkTokenError()){
			return false;
		}

		return $this->u_data;
	}


	/**
	 * "Обменивает" токен на пользовательские данные
	 */
	protected function getUserFromToken() {
		$response = false;
		if ($this->token){
			$request = 'http://ulogin.ru/token.php?token=' . $this->token . '&host=' . $_SERVER['HTTP_HOST'];
			if(in_array('curl', get_loaded_extensions())){
				$c = curl_init($request);
				curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
				$response = curl_exec($c);
				curl_close($c);

			}elseif (function_exists('file_get_contents') && ini_get('allow_url_fopen')){
				$response = file_get_contents($request);
			}
		}
		return $response;
	}


	/**
	 * Проверка пользовательских данных, полученных по токену
	 */
	protected function checkTokenError(){
		if (!is_array($this->u_data)){
			$this->sendMessage (array(
				'title' => "Произошла ошибка при авторизации.",
				'msg' => "Данные о пользователе содержат неверный формат.",
				'type' => 'error'
			));
			return false;
		}

		if (isset($this->u_data['error'])){
			$strpos = strpos($this->u_data['error'],'host is not');
			if ($strpos){
				$this->sendMessage (array(
					'title' => "Произошла ошибка при авторизации.",
					'msg' => "<i>ERROR</i>: адрес хоста не совпадает с оригиналом " . sub($this->u_data['error'],intval($strpos)+12),
					'type' => 'error'
				));
				return false;
			}
			switch ($this->u_data['error']){
				case 'token expired':
					$this->sendMessage (array(
						'title' => "Произошла ошибка при авторизации.",
						'msg' => "<i>ERROR</i>: время жизни токена истекло",
						'type' => 'error'
					));
					break;
				case 'invalid token':
					$this->sendMessage (array(
						'title' => "Произошла ошибка при авторизации.",
						'msg' => "<i>ERROR</i>: неверный токен",
						'type' => 'error'
					));
					break;
				default:
					$this->sendMessage (array(
						'title' => "Произошла ошибка при авторизации.",
						'msg' => "<i>ERROR</i>: " . $this->u_data['error'],
						'type' => 'error'
					));
			}
			return false;
		}
		if (!isset($this->u_data['identity'])){
			$this->sendMessage (array(
				'title' => "Произошла ошибка при авторизации.",
				'msg' => "В возвращаемых данных отсутствует переменная <b>identity</b>.",
				'type' => 'error'
			));
			return false;
		}
		if (!isset($this->u_data['email'])){
			$this->sendMessage (array(
				'title' => "Произошла ошибка при авторизации.",
				'msg' => "В возвращаемых данных отсутствует переменная <b>email</b>",
				'type' => 'error'
			));
			return false;
		}
		return true;
	}


	/**
	 * Гнерация логина пользователя
	 * в случае успешного выполнения возвращает уникальный логин пользователя
	 * @param $first_name
	 * @param string $last_name
	 * @param string $nickname
	 * @param string $bdate
	 * @param array $delimiters
	 * @return string
	 */
	protected function generateNickname($first_name, $last_name="", $nickname="", $bdate="", $delimiters=array('.', '_')) {
		$delim = array_shift($delimiters);

		$first_name = $this->translitIt($first_name);
		$first_name_s = substr($first_name, 0, 1);

		$variants = array();
		if (!empty($nickname))
			$variants[] = $nickname;
		$variants[] = $first_name;
		if (!empty($last_name)) {
			$last_name = $this->translitIt($last_name);
			$variants[] = $first_name.$delim.$last_name;
			$variants[] = $last_name.$delim.$first_name;
			$variants[] = $first_name_s.$delim.$last_name;
			$variants[] = $first_name_s.$last_name;
			$variants[] = $last_name.$delim.$first_name_s;
			$variants[] = $last_name.$first_name_s;
		}
		if (!empty($bdate)) {
			$date = explode('.', $bdate);
			$variants[] = $first_name.$date[2];
			$variants[] = $first_name.$delim.$date[2];
			$variants[] = $first_name.$date[0].$date[1];
			$variants[] = $first_name.$delim.$date[0].$date[1];
			$variants[] = $first_name.$delim.$last_name.$date[2];
			$variants[] = $first_name.$delim.$last_name.$delim.$date[2];
			$variants[] = $first_name.$delim.$last_name.$date[0].$date[1];
			$variants[] = $first_name.$delim.$last_name.$delim.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name.$date[2];
			$variants[] = $last_name.$delim.$first_name.$delim.$date[2];
			$variants[] = $last_name.$delim.$first_name.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name.$delim.$date[0].$date[1];
			$variants[] = $first_name_s.$delim.$last_name.$date[2];
			$variants[] = $first_name_s.$delim.$last_name.$delim.$date[2];
			$variants[] = $first_name_s.$delim.$last_name.$date[0].$date[1];
			$variants[] = $first_name_s.$delim.$last_name.$delim.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name_s.$date[2];
			$variants[] = $last_name.$delim.$first_name_s.$delim.$date[2];
			$variants[] = $last_name.$delim.$first_name_s.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name_s.$delim.$date[0].$date[1];
			$variants[] = $first_name_s.$last_name.$date[2];
			$variants[] = $first_name_s.$last_name.$delim.$date[2];
			$variants[] = $first_name_s.$last_name.$date[0].$date[1];
			$variants[] = $first_name_s.$last_name.$delim.$date[0].$date[1];
			$variants[] = $last_name.$first_name_s.$date[2];
			$variants[] = $last_name.$first_name_s.$delim.$date[2];
			$variants[] = $last_name.$first_name_s.$date[0].$date[1];
			$variants[] = $last_name.$first_name_s.$delim.$date[0].$date[1];
		}
		$i=0;

		$exist = true;
		while (true) {
			if ($exist = $this->userExist($variants[$i])) {
				foreach ($delimiters as $del) {
					$replaced = str_replace($delim, $del, $variants[$i]);
					if($replaced !== $variants[$i]){
						$variants[$i] = $replaced;
						if (!$exist = $this->userExist($variants[$i]))
							break;
					}
				}
			}
			if ($i >= count($variants)-1 || !$exist)
				break;
			$i++;
		}

		if ($exist) {
			while ($exist) {
				$nickname = $first_name.mt_rand(1, 100000);
				$exist = $this->userExist($nickname);
			}
			return $nickname;
		} else
			return $variants[$i];
	}


	/**
	 * Проверка существует ли пользователь с заданным логином
	 */
	protected function userExist($login){
		if (!$this->model->checkUserName($login)){
			return false;
		}
		return true;
	}


	/**
	 * Транслит
	 */
	protected function translitIt($str) {
		$tr = array(
			"А"=>"a","Б"=>"b","В"=>"v","Г"=>"g",
			"Д"=>"d","Е"=>"e","Ж"=>"j","З"=>"z","И"=>"i",
			"Й"=>"y","К"=>"k","Л"=>"l","М"=>"m","Н"=>"n",
			"О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t",
			"У"=>"u","Ф"=>"f","Х"=>"h","Ц"=>"ts","Ч"=>"ch",
			"Ш"=>"sh","Щ"=>"sch","Ъ"=>"","Ы"=>"yi","Ь"=>"",
			"Э"=>"e","Ю"=>"yu","Я"=>"ya","а"=>"a","б"=>"b",
			"в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
			"з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
			"м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
			"с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
			"ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
			"ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
		);
		if (preg_match('/[^A-Za-z0-9\_\-]/', $str)) {
			$str = strtr($str,$tr);
			$str = preg_replace('/[^A-Za-z0-9\_\-\.]/', '', $str);
		}
		return $str;
	}



	/**
	 * Удаление привязки к аккаунту соцсети в таблице ulogin_user для текущего пользователя
	 */
	protected function deleteAccount() {
		$this->currentUserId = JFactory::getUser()->id;
		$this->isUserLogined = $this->currentUserId > 0 ? true : false;

		if(!$this->isUserLogined) { jexit();}

		$user_id = $this->currentUserId;

		$network = $this->jinput->get('network');

		if ($user_id > 0 && !empty($network)) {
			try {

				$this->model->deleteUloginUser( array('user_id' => $user_id, 'network' => $network) );
				JFactory::getApplication()->enqueueMessage("Удаление аккаунта $network успешно выполнено", 'message');

			} catch (Exception $e) {

				JLog::add("Ошибка при удалении аккаунта. Exception: " . $e->getMessage(), JLog::ERROR, 'com_ulogin');
				JFactory::getApplication()->enqueueMessage("Ошибка при удалении аккаунта. Exception: " . $e->getMessage(), 'error');

			}
		}
		echo new JResponseJson();
		jexit();
	}


}
