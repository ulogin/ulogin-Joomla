<?php

defined('_JEXEC') or die;

class Com_UloginInstallerScript {

	public function postflight($type, $parent) {
		if($type != 'install')
			return;
		$db = JFactory::getDBO();
		$status = new stdClass;
		$status->modules = array ();
		$status->plugins = array ();
		$src = $parent->getParent()->getPath('source');
		$manifest = $parent->getParent()->manifest;
		$plugins = $manifest->xpath('plugins/plugin');
		foreach($plugins as $plugin) {
			$name = (string)$plugin->attributes()->plugin;
			$group = (string)$plugin->attributes()->group;
			$path = $src . '/plugins/' . $group;
			if(JFolder::exists($src . '/plugins/' . $group . '/' . $name)) {
				$path = $src . '/plugins/' . $group . '/' . $name;
			}
			$installer = new JInstaller;
			$result = $installer->install($path);
			$query = "UPDATE #__extensions SET enabled=1 WHERE type='plugin' AND element=" . $db->Quote($name) . " AND folder=" . $db->Quote($group);
			$db->setQuery($query);
			$db->execute();
			$status->plugins[] = array ( 'name' => $name, 'group' => $group, 'result' => $result );
		}
		$modules = $manifest->xpath('modules/module');
		foreach($modules as $module) {
			$name = (string)$module->attributes()->module;
			$client = (string)$module->attributes()->client;
			if(is_null($client)) {
				$client = 'site';
			}
			($client == 'administrator') ? $path = $src . '/administrator/modules/' . $name : $path = $src . '/modules/' . $name;
			$db->setQuery("SELECT id FROM #__modules WHERE `module` = " . $db->quote($name));
			$isUpdate = (int)$db->loadResult();
			$installer = new JInstaller;
			$result = $installer->install($path);
			$language = JFactory::getLanguage();
			$language->load($name);
			$status->modules[] = array ( 'name' => $name, 'client' => $client, 'result' => $result );
			if($client == 'site' && !$isUpdate) {
				$db->setQuery("SELECT params FROM #__extensions WHERE `type`='module' AND element = " . $db->Quote($name) . "");
				$params = $db->loadResult();
//				$position = 'position-7';
				$db->setQuery("UPDATE #__modules SET " . //				              "`position`=".$db->quote($position)."," .
//				              "`published`='1'," .
					"`params`=" . $db->quote($params) . "," . "`title`=" . $db->quote(str_replace('uLogin - ', '', JText::_($name))) . " WHERE `module`=" . $db->quote($name));
				$db->execute();
				$db->setQuery("SELECT id FROM #__modules WHERE `module` = " . $db->quote($name));
				$id = (int)$db->loadResult();
				$db->setQuery("INSERT IGNORE INTO #__modules_menu (`moduleid`,`menuid`) VALUES (" . $id . ", 0)");
				$db->execute();
			}
		}
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_users/models/', 'UsersModel');
		// set 'uLogin' user group
		jimport('joomla.application.component.helper');
		$groupModel = JModelLegacy::getInstance('Group', 'UsersModel');
		$groupData = array (
			'title' => 'uLogin', 'parent_id' => JComponentHelper::getParams('com_users')->get('new_usertype'), 'id' => 0
		);
		$res = $groupModel->save($groupData);
		if($res) {
			$db->setQuery("SELECT id FROM #__usergroups WHERE `title` = 'uLogin'");
			$groupId = $db->loadResult();
			$params = JComponentHelper::getParams('com_ulogin');
			$params->set('group', $groupId);
			$query = "UPDATE #__extensions SET params=" . $db->quote((string)$params) . " WHERE type='component' AND element='com_ulogin'";
			$db->setQuery($query);
			$db->execute();
		}
		$this->installationResults($status);
	}

	public function uninstall($parent) {
		$db = JFactory::getDBO();
		$status = new stdClass;
		$status->modules = array ();
		$status->plugins = array ();
		$manifest = $parent->getParent()->manifest;
		$plugins = $manifest->xpath('plugins/plugin');
		foreach($plugins as $plugin) {
			$name = (string)$plugin->attributes()->plugin;
			$group = (string)$plugin->attributes()->group;
			$query = "SELECT `extension_id` FROM #__extensions WHERE `type`='plugin' AND element = " . $db->Quote($name) . " AND folder = " . $db->Quote($group);
			$db->setQuery($query);
			$extensions = $db->loadColumn();
			if(count($extensions)) {
				foreach($extensions as $id) {
					$installer = new JInstaller;
					$result = $installer->uninstall('plugin', $id);
				}
				$status->plugins[] = array ( 'name' => $name, 'group' => $group, 'result' => $result );
			}
		}
		$modules = $manifest->xpath('modules/module');
		foreach($modules as $module) {
			$name = (string)$module->attributes()->module;
			$client = (string)$module->attributes()->client;
			$db = JFactory::getDBO();
			$query = "SELECT `extension_id` FROM `#__extensions` WHERE `type`='module' AND element = " . $db->Quote($name) . "";
			$db->setQuery($query);
			$extensions = $db->loadColumn();
			if(count($extensions)) {
				foreach($extensions as $id) {
					$installer = new JInstaller;
					$result = $installer->uninstall('module', $id);
				}
				$status->modules[] = array ( 'name' => $name, 'client' => $client, 'result' => $result );
			}
		}
		$this->uninstallationResults($status);
	}

	private function installationResults($status) {
		$language = JFactory::getLanguage();
		$language->load('com_ulogin');
		$rows = 0; ?>
		<h2><?php echo JText::_('ULOGIN_INSTALLATION_STATUS'); ?></h2>
		<table class="adminlist table table-striped">
			<thead>
			<tr>
				<th class="title" colspan="2"><?php echo JText::_('ULOGIN_EXTENSION'); ?></th>
				<th width="30%"><?php echo JText::_('ULOGIN_STATUS'); ?></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="3"></td>
			</tr>
			</tfoot>
			<tbody>
			<tr class="row0">
				<td class="key" colspan="2"><?php echo JText::_('ULOGIN_COMPONENT'); ?></td>
				<td><strong><?php echo JText::_('ULOGIN_INSTALLED'); ?></strong></td>
			</tr>
			<?php if(count($status->modules)): ?>
				<tr>
					<th><?php echo JText::_('ULOGIN_MODULE'); ?></th>
					<th><?php echo JText::_('ULOGIN_CLIENT'); ?></th>
					<th></th>
				</tr>
				<?php foreach($status->modules as $module): ?>
					<tr class="row<?php echo(++$rows % 2); ?>">
						<td class="key"><?php echo $module['name']; ?></td>
						<td class="key"><?php echo ucfirst($module['client']); ?></td>
						<td>
							<strong><?php echo ($module['result']) ? JText::_('ULOGIN_INSTALLED') : JText::_('ULOGIN_NOT_INSTALLED'); ?></strong>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php if(count($status->plugins)): ?>
				<tr>
					<th><?php echo JText::_('ULOGIN_PLUGIN'); ?></th>
					<th><?php echo JText::_('ULOGIN_GROUP'); ?></th>
					<th></th>
				</tr>
				<?php foreach($status->plugins as $plugin): ?>
					<tr class="row<?php echo(++$rows % 2); ?>">
						<td class="key"><?php echo ucfirst($plugin['name']); ?></td>
						<td class="key"><?php echo ucfirst($plugin['group']); ?></td>
						<td>
							<strong><?php echo ($plugin['result']) ? JText::_('ULOGIN_INSTALLED') : JText::_('ULOGIN_NOT_INSTALLED'); ?></strong>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	<?php
	}

	private function uninstallationResults($status) {
		$language = JFactory::getLanguage();
		$language->load('com_ulogin');
		$rows = 0;
		?>
		<h2><?php echo JText::_('ULOGIN_REMOVAL_STATUS'); ?></h2>
		<table class="adminlist table table-striped">
			<thead>
			<tr>
				<th class="title" colspan="2"><?php echo JText::_('ULOGIN_EXTENSION'); ?></th>
				<th width="30%"><?php echo JText::_('ULOGIN_STATUS'); ?></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="3"></td>
			</tr>
			</tfoot>
			<tbody>
			<tr class="row0">
				<td class="key" colspan="2"><?php echo JText::_('ULOGIN_COMPONENT'); ?></td>
				<td><strong><?php echo JText::_('ULOGIN_REMOVED'); ?></strong></td>
			</tr>
			<?php if(count($status->modules)): ?>
				<tr>
					<th><?php echo JText::_('ULOGIN_MODULE'); ?></th>
					<th><?php echo JText::_('ULOGIN_CLIENT'); ?></th>
					<th></th>
				</tr>
				<?php foreach($status->modules as $module): ?>
					<tr class="row<?php echo(++$rows % 2); ?>">
						<td class="key"><?php echo $module['name']; ?></td>
						<td class="key"><?php echo ucfirst($module['client']); ?></td>
						<td>
							<strong><?php echo ($module['result']) ? JText::_('ULOGIN_REMOVED') : JText::_('ULOGIN_NOT_REMOVED'); ?></strong>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>

			<?php if(count($status->plugins)): ?>
				<tr>
					<th><?php echo JText::_('ULOGIN_PLUGIN'); ?></th>
					<th><?php echo JText::_('ULOGIN_GROUP'); ?></th>
					<th></th>
				</tr>
				<?php foreach($status->plugins as $plugin): ?>
					<tr class="row<?php echo(++$rows % 2); ?>">
						<td class="key"><?php echo ucfirst($plugin['name']); ?></td>
						<td class="key"><?php echo ucfirst($plugin['group']); ?></td>
						<td>
							<strong><?php echo ($plugin['result']) ? JText::_('ULOGIN_REMOVED') : JText::_('ULOGIN_NOT_REMOVED'); ?></strong>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	<?php
	}
}