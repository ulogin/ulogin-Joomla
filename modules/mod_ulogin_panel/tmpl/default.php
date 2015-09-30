<?php
defined('_JEXEC') or die;
?>

<div class="ulogin_form">
	<?php if(!empty($uloginid)) { ?>
		<div data-uloginid="<?php echo $uloginid; ?>"
		     data-ulogin="redirect_uri=<?php echo $redirect; ?>;callback=<?php echo $callback; ?>"></div>
	<?php } else { ?>
		<div data-uloginid="<?php echo $uloginid; ?>"
		     data-ulogin="display=small;fields=first_name,last_name,email;providers=vkontakte,odnoklassniki,mailru,facebook;hidden=other;redirect_uri=<?php echo $redirect; ?>;callback=<?php echo $callback; ?>"></div>
	<?php } ?>
</div>
