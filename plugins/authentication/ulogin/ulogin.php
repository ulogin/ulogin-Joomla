<?php

defined('_JEXEC') or die;

class PlgAuthenticationUlogin extends JPlugin {
	public function onUserAuthenticate($credentials, $options, &$response) {
		$lang = JFactory::getLanguage();
		$lang->load('plg_authentication_ulogin', __DIR__);
		$response->type = 'uLogin';
		$user_id = $options['user_id'];
		// если не uLogin
		if(!array_key_exists('ulogin_auth', $options)) {
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_EMPTY_PASS_NOT_ALLOWED');

			return;
		}
		require_once JPATH_ADMINISTRATOR . '/components/com_users/helpers/users.php';
		$methods = UsersHelper::getTwoFactorMethods();
		// если включена двухфакторная авторизация
		if(count($methods) > 1) {
			require_once JPATH_ADMINISTRATOR . '/components/com_users/models/user.php';
			$model = new UsersModelUser;
			// Load the user's OTP (one time password, a.k.a. two factor auth) configuration
			$otpConfig = $model->getOtpConfig($user_id);
			$options['otp_config'] = $otpConfig;
			// Check if the user has enabled two factor authentication
			if(isset($otpConfig->method) && ($otpConfig->method != 'none')) {
				$response->status = JAuthentication::STATUS_FAILURE;
				$response->error_message = JText::_('PLG_AUTH_ULOGIN_ERR_TFA_USED');

				return;
			}
		}
		$result = JFactory::getUser($user_id);
		// Bring this in line with the rest of the system
		$user = JUser::getInstance($result->id);
		$response->email = $user->email;
		$response->fullname = $user->name;
		$response->language = $user->getParam('language');
		$response->status = JAuthentication::STATUS_SUCCESS;
		$response->error_message = '';

		return;
	}
}