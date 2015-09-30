<?php
defined('_JEXEC') or die;
?>

<div class="ulogin_form">

	<?php if(!empty($add_str)) { ?>
		<div class="add_str"><?php echo $add_str; ?></div>
	<?php } ?>

	<?php if(!empty($uloginid)) { ?>
		<div data-uloginid="<?php echo $uloginid; ?>"
		     data-ulogin="redirect_uri=<?php echo $redirect; ?>;callback=<?php echo $callback; ?>"></div>
	<?php } else { ?>
		<div data-uloginid="<?php echo $uloginid; ?>"
		     data-ulogin="display=small;fields=first_name,last_name,email;providers=vkontakte,odnoklassniki,mailru,facebook;hidden=other;redirect_uri=<?php echo $redirect; ?>;callback=<?php echo $callback; ?>"></div>
	<?php } ?>

	<?php if(!empty($delete_str)) { ?>
		<div class="delete_str"<?php echo $hide_delete_str; ?>><?php echo $delete_str; ?></div>
	<?php } ?>

	<div class="ulogin_accounts can_delete">
		<?php if(!empty($networks) && is_array($networks)) { ?>

			<?php foreach($networks as $network) { ?>
				<div data-ulogin-network='<?php echo $network; ?>'
				     class="ulogin_provider big_provider <?php echo $network; ?>_big"
				     onclick="uloginDeleteAccount('<?php echo $network; ?>')"></div>
			<?php } ?>

		<?php } ?>
	</div>
	<div style="clear:both"></div>

</div>
