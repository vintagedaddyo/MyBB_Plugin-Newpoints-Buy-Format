<?php

/***************************************************************************
 *
 *   Newpoints Buy Format plugin.
 *	 Author: Sama34 (Omar Gonzalez) & Edzon Ordaz
 *   
 *   Website: https://github.com/Sama34/Newpoints-Buy-Format
 *
 *   Allow users to buy username format styles predefined by adminsitratos.
 *
 ***************************************************************************/

/****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/
 
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

/**
 * DEFINE PLUGINLIBRARY
 *
 *   Define the path to the plugin library, if it isn't defined yet.
 */
if(!defined("PLUGINLIBRARY"))
{
    define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}

// Cache our template.
if(my_strpos($_SERVER['PHP_SELF'], 'newpoints.php'))
{
	global $templatelist;
	if(isset($templatelist))
	{
		$templatelist .= ', ';
	}
	$templatelist .= '';
}

// Run the hooks.
if(defined('IN_ADMINCP') && newpoints_buyformat_is_installed())
{
	$plugins->add_hook('newpoints_admin_load', 'newpoints_buyformat_admin');
	$plugins->add_hook('newpoints_admin_plugins_start', 'newpoints_buyformat_edit');
	$plugins->add_hook('newpoints_admin_newpoints_menu', 'newpoints_buyformat_admin_menu');
	$plugins->add_hook('newpoints_admin_newpoints_action_handler', 'newpoints_buyformat_admin_action_handler');
	$plugins->add_hook("newpoints_admin_grouprules_add", "newpoints_buyformat_grouprules");
	$plugins->add_hook("newpoints_admin_grouprules_edit", "newpoints_buyformat_grouprules");
	$plugins->add_hook("newpoints_admin_grouprules_add_insert", "newpoints_buyformat_grouprules_add_commit");
	$plugins->add_hook("newpoints_admin_grouprules_edit_update", "newpoints_buyformat_grouprules_edit_commit");
}
elseif(newpoints_buyformat_is_installed())
{
	$plugins->add_hook("newpoints_start", "newpoints_buyformat");
	$plugins->add_hook("newpoints_default_menu", "newpoints_buyformat_menu");
}
$plugins->add_hook("newpoints_task_backup_tables", "newpoints_buyformat_backup");

// Necessary plugin information for the ACP plugin manager.
function newpoints_buyformat_info()
{
	global $lang, $cache, $mybb;

	newpoints_lang_load("newpoints_buyformat");

	$info = array(
		'name'			=> $lang->npbf_title,
		'description'	=> $lang->npbf_title_desc,
		'website'		=> 'https://github.com/Sama34/Newpoints-Buy-Format',
		'author'		=> $lang->npbf_author,
		'authorsite'	=> 'https://github.com/Sama34/Newpoints-Buy-Format',
		'version'		=> '1.1',
		'compatibility'	=> '*'
	);
	$plugins = $cache->read('newpoints_plugins');
    if(newpoints_buyformat_is_installed() && is_array($plugins) && is_array($plugins['active']) && $plugins['active']['newpoints_buyformat'] && $mybb->input['module'] != 'newpoints-settings')
    {
        $editurl = "index.php?module=newpoints-plugins&amp;newpoints_buyformat=edit&amp;my_post_key=".$mybb->post_code;
        $undourl = "index.php?module=newpoints-plugins&amp;newpoints_buyformat=undo&amp;my_post_key=".$mybb->post_code;

        $info["description"] .= "<br /><a href=\"{$editurl}\">{$lang->npbf_apply_edits}</a>. | <a href=\"{$undourl}\">{$lang->npbf_undo_edits}</a>.";
    }

	return $info;
}

// Activate the plugin.
function newpoints_buyformat_activate()
{
	global $lang;
	newpoints_lang_load("newpoints_buyformat");

	// Add our settings.
	newpoints_add_setting('newpoints_buyformat_power', 'newpoints_buyformat', $lang->npbf_s_power, $lang->npbf_s_power_d, 'yesno', '1', 1);
	newpoints_add_setting('newpoints_buyformat_groups', 'newpoints_buyformat', $lang->npbf_s_groups, $lang->npbf_s_groups_d, 'text', '', 2);

	// Add our templates.
	newpoints_add_template('newpoints_buyformat', '<html>
	<head>
		<title>{$lang->newpoints} {$lang->npbf_title} -{$mybb->settings[\'bbname\']}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<table width="100%" border="0" align="center">
		<tr>
		<td valign="top" width="180">
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<tr>
		<td class="thead"><strong>{$lang->newpoints_menu}</strong></td>
		</tr>
		{$options}
		</table>
		</td>
		<td valign="top">
		<table width="100%" border="0" align="center">
		<tr>
		<td valign="top" width="40%">
		{$errors}
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<tr>
			<td class="thead" colspan="5"><strong>{$lang->npbf_title}</strong></td>
		</tr>
		<tr align="center">
			<td class="tcat" width="20%" align="left"><strong>{$lang->npbf_name}</strong></td>
			<td class="tcat" width="15%"><strong>{$lang->npbf_style}</strong></td>
			<td class="tcat" width="20%"><strong>{$lang->npbf_points}</strong></td>
			<td class="tcat" width="15%"><strong>{$lang->npbf_stock}</strong></td>
			<td class="tcat"><strong>{$lang->npbf_buy}</strong></td>
		</tr>
		{$buyformat_rows}
		</table>
		</td>
		</tr>
		</table>
		</td>
		</tr>
		</table>
		{$footer}
	</body>
</html>', '-1');
	newpoints_add_template('newpoints_buyformat_row', '<tr align="center">
	<td class="{$trow}" align="left">{$style[\'name\']}</td>
	<td class="{$trow}">{$style[\'style\']}</td>
	<td class="{$trow}">{$style[\'points\']}</td>
	<td class="{$trow}">{$style[\'stock\']}</td>
	<td class="{$trow}">

<form action="{$mybb->settings[\'bburl\']}/newpoints.php" method="post">
<input type="hidden" name="action" value="buyformat" />
<input type="hidden" name="my_post_code" value="{$mybb->post_code}" />
<input type="hidden" name="sid" value="{$style[\'sid\']}" />
<input type="submit" name="submit" value="{$lang->npbf_buy}" />
</form>
</td>
</tr>', '-1');
	newpoints_add_template('newpoints_buyformat_row_empty', '<tr>
	<td class="{$trow}" align="center" colspan="5">{$lang->npbf_row_empty}</td>
</tr>', '-1');
}

// Deactivate the plugin.
function newpoints_buyformat_deactivate()
{
	global $lang;
	newpoints_lang_load("newpoints_buyformat");

	// Delete our settings.
	newpoints_remove_settings("'newpoints_buyformat_power', 'newpoints_buyformat_groups'");

	// Delete our templates.
	newpoints_remove_templates("'newpoints_buyformat', 'newpoints_buyformat_row', 'newpoints_buyformat_row_empty'");
}

// Install the plugin.
function newpoints_buyformat_install()
{
	global $db, $lang;
	newpoints_lang_load("newpoints_buyformat");

	// Lets check if PluginLibrary is installed, if not, show friendly error.
	if(!file_exists(PLUGINLIBRARY))
    {
        flash_message('PluginLibrary is not installed.', "error");
        admin_redirect("index.php?module=newpoints-plugins");
    }

	global $db, $PL;
	$PL or require_once PLUGINLIBRARY;

	// Lets check PluginLibrary version, if less than 6, show friendly error.
	if($PL->version < 6)
	{
		flash_message('PluginLibrary Version too old.', "error");
		admin_redirect("index.php?module=newpoints-plugins");
	}
	
	// Everything seems to be OK, so continue with installation.

	if(!$db->table_exists("newpoints_buyformat"))
	{
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."newpoints_buyformat` (
			`sid` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NOT NULL,
			`description` text NOT NULL,
			`style` varchar(200) NOT NULL DEFAULT '{username}',
			`points` DECIMAL(16,2) NOT NULL DEFAULT '0',
			`stock` int(10) NOT NULL DEFAULT '0',
			`visible` smallint(1) NOT NULL DEFAULT '1',
			`usergroups` text NOT NULL,
			PRIMARY KEY (`sid`)
			) ENGINE=MyISAM;"
		);
	}
	if(!$db->field_exists("buyformat", "users")) 
	{
		$db->add_column("users", "buyformat", "bigint(30) UNSIGNED NOT NULL DEFAULT '0'");
	}
	if(!$db->field_exists("buyformat_rate", "newpoints_grouprules")) 
	{
		$db->add_column("newpoints_grouprules", "buyformat_rate", "float NOT NULL default '1'");
	}
}

// Check if installed.
function newpoints_buyformat_is_installed()
{
	global $db;
	if($db->table_exists("newpoints_buyformat"))
	{
		return true;
	}
	elseif($db->field_exists("buyformat", "users")) 
	{
		return true;
	}
	elseif($db->field_exists("buyformat_rate", "newpoints_grouprules")) 
	{
		return true;
	}
	else
	{
		return false;
	}
}

// Unnstall the plugin.
function newpoints_buyformat_uninstall()
{
	global $db;
	if($db->table_exists("newpoints_buyformat"))
	{
		$db->drop_table("newpoints_buyformat"); 
	}
	if($db->field_exists("buyformat", "users")) 
	{
		$db->drop_column("users", "buyformat"); 
	}
	if($db->field_exists("buyformat_rate", "newpoints_grouprules")) 
	{	
		$db->drop_column("newpoints_grouprules", "buyformat_rate"); 
	}
	newpoints_remove_log(array("buyformat"));
}





//\\ ::ACP Section:: //\\
// Lets apply/undo the core file edits.
function newpoints_buyformat_edit()
{
    global $mybb;

    // Only perform edits if we were given the correct post key.
    if($mybb->input['my_post_key'] != $mybb->post_code)
    {
        return;
    }

    global $PL;
    $PL or require_once PLUGINLIBRARY;
	
	// Edit the core depending in input.
    if($mybb->input['newpoints_buyformat'] == 'edit')
    {
		$result = $PL->edit_core("newpoints_buyformat", "inc/functions.php",
			array(
				'search' => array("if(\$displaygroup != 0)"),
				'before' => array(
					'if(function_exists(\'newpoints_buyformat_format_name\'))',
					'{',
					'	$do_format = newpoints_buyformat_format_name($username);',
					'	if($do_format[\'status\'])',
					'{',
					'	return $do_format[\'format\'];',
					'}',
					'}',
				),
			),
			true
		);
	}
    elseif($mybb->input['newpoints_buyformat'] == 'undo')
    {
		$result = $PL->edit_core(
			"newpoints_buyformat", "inc/functions.php",
			array(),
			true
		);
    }
    else
    {
		return;
    }

	global $lang;
	newpoints_lang_load("newpoints_buyformat");

	// Apply the edit...
    if($result === true)
    {
        // redirect with success
        flash_message('Succeful edited.', "success");
        admin_redirect("index.php?module=newpoints-plugins");
    }

    else
    {
        // redirect with failure (could offer the result string for download instead)
        flash_message('File wasn\'t edited.', "error");
        admin_redirect("index.php?module=newpoints-plugins");
    }
}

// If the plugin is installed, lets run the format_name function to format usernames.
function newpoints_buyformat_format_name($username, $ACP=false, $id='')
{
	global $cache;
	$style = $cache->read('newpoints_buyformat');

	if($ACP && $id != '')
	{
		$style = $style[$id];
		if(substr_count($style['style'], "{username}") == 0)
		{
			$style['style'] = "{username}";
		}
		$style['style'] = stripslashes($style['style']);
		$info = array(
			'format' => str_replace("{username}", $username, $style['style'])
		);
	}
	else
	{
		global $db;
		$username = $db->escape_string($username);
		$q = $db->simple_select('users', 'buyformat, usergroup, additionalgroups', "username='{$username}'");
		$user = $db->fetch_array($q);

		$style = $style[$user['buyformat']];

		$info = array(
			'status' => false
		);

		// Check group permissions.
		if(!empty($user['additionalgroups']))
		{
			$usergroups = explode(',', $user['additionalgroups']);
		}
		if(!is_array($usergroups))
		{
			$usergroups = array();
		}
		$usergroups[] = $user['usergroup'];
		$groups = explode(',', $style['usergroups']);
		$perm = false;
		foreach($usergroups as $gid)
		{
			if(in_array($gid, $groups))
			{
				$perm = true;
			}
		}

		// Format if we has to.
		if($user['buyformat'] > 0 && newpoints_buyformat_is_installed() && ($perm || empty($style['usergroups'])))
		{
			if(substr_count($style['style'], "{username}") == 0)
			{
				$style['style'] = "{username}";
			}
			$style['style'] = stripslashes($style['style']);
			$info['status'] = true;
			$info['format'] = str_replace("{username}", $username, $style['style']);
		}
	}
	return $info;
}

// Show out menu link.
function newpoints_buyformat_admin_menu(&$sub_menu)
{
	global $lang;
	newpoints_lang_load("newpoints_buyformat");

	$sub_menu[] = array('id' => 'buyformat', 'title' => $lang->npbf_title, 'link' => 'index.php?module=newpoints-buyformat');
}

// Insert our ACP page information.
function newpoints_buyformat_admin_action_handler(&$actions)
{
	$actions['buyformat'] = array('active' => 'buyformat', 'file' => 'newpoints_buyformat');
}

// This is the actual ACP page.
function newpoints_buyformat_admin()
{
	global $run_module, $action_file;
	
	if($run_module == 'newpoints' && $action_file == 'newpoints_buyformat')
	{
		global $db, $lang, $mybb, $page, $mybbadmin, $plugins;
		newpoints_lang_load('newpoints_buyformat');
		$page->add_breadcrumb_item($lang->npbf_title, 'index.php?module=newpoints-buyformat');
		$page->output_header($lang->npbf_title);

		if(!$mybb->input['action'] || in_array($mybb->input['action'], array('add', 'edit')))
		{
			$sub_tabs['newpoints_buyformat_view'] = array(
				'title'			=> $lang->npbf_view,
				'link'			=> 'index.php?module=newpoints-buyformat',
				'description'	=> $lang->npbf_view_desc
			);
			$sub_tabs['newpoints_buyformat_add'] = array(
				'title'			=> $lang->npbf_add,
				'link'			=> 'index.php?module=newpoints-buyformat&amp;action=add',
				'description'	=> $lang->npbf_add_desc
			);
			if($mybb->input['action'] == 'edit')
			{
				$sub_tabs['newpoints_buyformat_edit'] = array(
					'title'			=> $lang->npbf_edit,
					'link'			=> 'index.php?module=newpoints-buyformat&amp;action=edit&amp;sid='.$mybb->input['sid'],
					'description'	=> $lang->npbf_edit_desc
				);
			}
		}
		
		if(!$mybb->input['action'])
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_buyformat_view');

			$table = new Table;
			$table->construct_header($lang->npbf_table_name, array('width' => '20%'));
			$table->construct_header($lang->npbf_table_desc, array('width' => '60%'));
			$table->construct_header($lang->npbf_table_action, array('width' => '20%', 'class' => 'align_center'));

			$q = $db->simple_select('newpoints_buyformat', '*', "sid!='0'");
			if($db->num_rows($q) < 1)
			{
				$table->construct_cell('<div align="center">'.$lang->npbf_table_empty.'</div>', array('colspan' => 4));
				$table->construct_row();
			}
			else
			{
				while($style = $db->fetch_array($q))
				{
					$name = newpoints_buyformat_format_name(htmlspecialchars_uni($style['name']), true, $style['sid']);
					$table->construct_cell($name['format']);
					$table->construct_cell(htmlspecialchars_uni($style['description']));
					$table->construct_cell("<a href=\"index.php?module=newpoints-buyformat&amp;action=edit&amp;sid={$style['sid']}\">".$lang->npbf_table_edit."</a> - <a href=\"index.php?module=newpoints-buyformat&amp;action=delete&amp;sid={$style['sid']}\">".$lang->npbf_table_delete."</a>", array('class' => 'align_center'));
					$table->construct_row();
				}
			}
			$table->output($lang->npbf_table_title);
		}
		elseif($mybb->input['action'] == 'delete')
		{
			if($mybb->input['no'])
			{
				flash_message($lang->npbf_edit_error, 'error');
				admin_redirect("index.php?module=newpoints-buyformat");
			}
			$sid = intval($mybb->input['sid']);
			$q = $db->simple_select('newpoints_buyformat', '*', "sid='{$sid}'");
			if($db->num_rows($q) < 1)
			{
				flash_message($lang->npbf_delete_error, 'error');
				admin_redirect("index.php?module=newpoints-buyformat");
			}
			if($mybb->request_method == 'post')
			{
				if($mybb->input['my_post_key'] != $mybb->post_code)
				{
					flash_message($lang->npbf_delete_error, 'error');
					admin_redirect("index.php?module=newpoints-buyformat");
				}
				$db->delete_query('newpoints_buyformat', "sid='{$sid}'");
				newpoints_buyformat_cache();
				flash_message($lang->npbf_delete_success, 'success');
				admin_redirect("index.php?module=newpoints-buyformat");
			}
			$form = new Form("index.php?module=newpoints-buyformat&amp;action=delete&amp;sid={$sid}&amp;my_post_key={$mybb->post_code}", 'post');
			echo("
				<div class=\"confirm_action\">\n
				<p>{$lang->newpoints_shop_confirm_deletecat}</p><br />\n
				<p class=\"buttons\">
				{$form->generate_submit_button($lang->yes, array('class' => 'button_yes'))}
				{$form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'))}
				</p>\n
				</div>
			");
			$form->end();
		}
		elseif($mybb->input['action'] == 'add')
		{
			if($mybb->request_method == 'post')
			{
				if($mybb->input['name'] == '')
				{
					flash_message($lang->npbf_add_error, 'error');
					admin_redirect("index.php?module=newpoints-buyformat&amp;action=add");
				}
				if(is_array($mybb->input['usergroups']))
				{
					foreach($mybb->input['usergroups'] as $gid)
					{
						if($gid == $mybb->input['usergroups'])
						{
							unset($mybb->input['usergroups'][$gid]);
						}
					}
					$usergroups = implode(",", $mybb->input['usergroups']);
				}
				else
				{
					$usergroups = '';
				}
				$insert_data = array(
						'name' => $db->escape_string($mybb->input['name']),
						'description' => $db->escape_string($mybb->input['description']),
						'style' => $db->escape_string($mybb->input['style']),
						'points' => (intval($mybb->input['points']) < 1 ? 0 : intval($mybb->input['points'])),
						'stock' => (intval($mybb->input['stock']) < 1 && intval($mybb->input['stock']) != -1 ? 0 : intval($mybb->input['stock'])),
						'visible' => ($mybb->input['visible'] == 1 ? 1 : 0),
						'usergroups' => $usergroups
				);
				$db->insert_query('newpoints_buyformat', $insert_data);
				newpoints_buyformat_cache();
				flash_message($lang->npbf_add_success, 'success');
				admin_redirect("index.php?module=newpoints-buyformat");
			}

			$page->output_nav_tabs($sub_tabs, 'newpoints_buyformat_add');
			$query = $db->simple_select("usergroups", "gid, title", "gid!='1'", array('order_by' => 'title'));
			while($usergroup = $db->fetch_array($query))
			{
				$options[$usergroup['gid']] = $usergroup['title'];
			}
			$form = new Form("index.php?module=newpoints-buyformat&amp;action=add", "post", "newpoints_buyformat");
			$form_container = new FormContainer($lang->npbf_add_info);
			
			$form_container->output_row($lang->npbf_name, $lang->npbf_name_desc, $form->generate_text_box('name', '', array('id' => 'name')), 'name');
			$form_container->output_row($lang->npbf_desc, $lang->npbf_desc_desc, $form->generate_text_box('description', '', array('id' => 'description')), 'description');
			$form_container->output_row($lang->npbf_code, $lang->npbf_code_desc, $form->generate_text_box('style', '{username}', array('id' => 'style')), 'style');
			$form_container->output_row($lang->npbf_points, $lang->npbf_points_desc, $form->generate_text_box('points', '0', array('id' => 'points')), 'points');
			$form_container->output_row($lang->npbf_stock, $lang->npbf_stock_desc, $form->generate_text_box('stock', '-1', array('id' => 'stock')), 'stock');
			$form_container->output_row($lang->npbf_visible, $lang->npbf_visible_desc, $form->generate_yes_no_radio('visible', 1, true), 'visible');
			$form_container->output_row($lang->npbf_groups, $lang->npbf_groups_desc, $form->generate_select_box('usergroups[]', $options, '', array('id' => 'usergroups', 'multiple' => true, 'size' => 5)), 'usergroups');

			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->npbf_submit);
			$buttons[] = $form->generate_reset_button($lang->npbf_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif($mybb->input['action'] == 'edit')
		{
			$sid = intval($mybb->input['sid']);
			$q = $db->simple_select('newpoints_buyformat', '*', "sid='{$sid}'");
			if($db->num_rows($q) < 1)
			{
				flash_message($lang->npbf_edit_error, 'error');
				admin_redirect("index.php?module=newpoints-buyformat");
			}
			$style = $db->fetch_array($q);

			if($mybb->request_method == 'post')
			{
				if($mybb->input['name'] == '')
				{
					flash_message($lang->npbf_add_error, 'error');
					admin_redirect("index.php?module=newpoints-buyformat&amp;action=edit&amp;sid={$style['sid']}");
				}
				if(is_array($mybb->input['usergroups']))
				{
					foreach($mybb->input['usergroups'] as $gid)
					{
						if($gid == $mybb->input['usergroups'])
						{
							unset($mybb->input['usergroups'][$gid]);
						}
					}
					$usergroups = implode(",", $mybb->input['usergroups']);
				}
				else
				{
					$usergroups = '';
				}
				$insert_data = array(
						'name' => $db->escape_string($mybb->input['name']),
						'description' => $db->escape_string($mybb->input['description']),
						'style' => $db->escape_string($mybb->input['style']),
						'points' => (intval($mybb->input['points']) < 1 ? 0 : intval($mybb->input['points'])),
						'stock' => (intval($mybb->input['stock']) < 1 && intval($mybb->input['stock']) != -1 ? 0 : intval($mybb->input['stock'])),
						'visible' => ($mybb->input['visible'] == 1 ? 1 : 0),
						'usergroups' => $usergroups
				);
				$db->update_query('newpoints_buyformat', $insert_data, "sid='{$style['sid']}'");
				newpoints_buyformat_cache();
				flash_message($lang->npbf_edit_success, 'success');
				admin_redirect("index.php?module=newpoints-buyformat");
			}

			$page->output_nav_tabs($sub_tabs, 'newpoints_buyformat_edit');
			$query = $db->simple_select("usergroups", "gid, title", "gid!='1'", array('order_by' => 'title'));
			while($usergroup = $db->fetch_array($query))
			{
				$options[$usergroup['gid']] = $usergroup['title'];
			}
			$form = new Form("index.php?module=newpoints-buyformat&amp;action=edit&amp;sid={$style['sid']}", "post", "newpoints_buyformat");
			$form_container = new FormContainer($lang->npbf_add_info);
			
			$form_container->output_row($lang->npbf_name, $lang->npbf_name_desc, $form->generate_text_box('name', $style['name'], ''), 'name');
			$form_container->output_row($lang->npbf_desc, $lang->npbf_desc_desc, $form->generate_text_box('description', $style['description'], ''), 'description');
			$form_container->output_row($lang->npbf_code, $lang->npbf_code_desc, $form->generate_text_box('style', $style['style'], ''), 'style');
			$form_container->output_row($lang->npbf_points, $lang->npbf_points_desc, $form->generate_text_box('points', $style['points'], array('id' => 'points')), 'points');
			$form_container->output_row($lang->npbf_stock, $lang->npbf_stock_desc, $form->generate_text_box('stock', intval($style['stock']), array('id' => 'stock')), 'stock');
			$form_container->output_row($lang->npbf_visible, $lang->npbf_visible_desc, $form->generate_yes_no_radio('visible', intval($style['visible']), true), 'visible');

			$style['usergroups'] = explode(',', $style['usergroups']);
			if(!is_array($style['usergroups'])){$style['usergroups'] = array();}
			$form_container->output_row($lang->npbf_groups, $lang->npbf_groups_desc, $form->generate_select_box('usergroups[]', $options, $style['usergroups'], array('id' => 'usergroups', 'multiple' => true, 'size' => 5)), 'usergroups');

			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->npbf_submit);
			$buttons[] = $form->generate_reset_button($lang->npbf_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		$page->output_footer();
	}
}

// Newpoints Group Rules part.
function newpoints_buyformat_grouprules(&$form_container)
{
	global $mybb, $db, $lang, $form, $rule;
	newpoints_lang_load("newpoints_buyformat");

	// If adding a group rule..
	if($mybb->input['action'] == 'add')
	{
		$form_container->output_row($lang->npbf_acp_rules, $lang->npbf_acp_rules_d, $form->generate_text_box('buyformat_rate', '1', ''), 'buyformat_rate');
	}
	// If editing a group rule..
	elseif($mybb->input['action'] == 'edit')
	{
		$form_container->output_row($lang->npbf_acp_rules, $lang->npbf_acp_rules_d, $form->generate_text_box('buyformat_rate', $rule['buyformat_rate'], ''), 'buyformat_rate');
	}
}

function newpoints_buyformat_grouprules_edit_commit(&$update_query)
{
	global $mybb;

	$update_query['buyformat_rate'] = intval($mybb->input['buyformat_rate']);
}

function newpoints_buyformat_grouprules_add_commit(&$insert_query)
{
	global $mybb;

	$insert_query['buyformat_rate'] = intval($mybb->input['buyformat_rate']);
}

//\\ ::Forum Section:: //\\
// Show up our page in newpoints section.
function newpoints_buyformat_menu(&$menu)
{
	global $mybb;
	if((ougc_check_groups($mybb->settings['newpoints_buyformat_groups']) || empty($mybb->settings['newpoints_buyformat_groups'])) && $mybb->settings['newpoints_buyformat_power'] == 1)
	{
		global $lang;
		newpoints_lang_load("newpoints_buyformat");

		$i = "";
		if($mybb->input['action'] == 'buyformat')
		{
			$i = "&raquo; ";
		}
		$menu[] = "{$i}<a href=\"{$mybb->settings['bburl']}/newpoints.php?action=buyformat\">{$lang->npbf_title_menu}</a>";
	}
}

function newpoints_buyformat()
{
	global $mybb;

	if($mybb->input['action'] == 'buyformat' && (ougc_check_groups($mybb->settings['newpoints_buyformat_groups']) || empty($mybb->settings['newpoints_buyformat_groups'])) && $mybb->settings['newpoints_buyformat_power'] == 1)
	{
		global $db, $lang, $theme, $header, $templates, $headerinclude, $footer, $options, $cache;
		$trow = alt_trow();
		$groupsrules = newpoints_getrules('group', $mybb->user['usergroup']);
		if(!$groupsrules)
		{
			$groupsrules['buyformat_rate'] = 1;
		}
		if($mybb->request_method == "post")
		{
			verify_post_check($mybb->input['my_post_code']);
			$mybb->input['sid'] = intval($mybb->input['sid']);

			$style = $cache->read('newpoints_buyformat');
			$style = $style[$mybb->input['sid']];
			$error = array();
			if(!$style['name'] || $style['visible'] != 1)
			{
				$error[] = $lang->npbf_error_incorrectstyle;
			}
			if($style['stock'] < 1 && $style['stock'] != "-1")
			{
				$error[] = $lang->npbf_error_nostock;
			}
			if(!ougc_check_groups($style['usergroups']))
			{
				$error[] = $lang->npbf_error_invalidgroup;
			}
			if(intval($mybb->user['buyformat']) == $mybb->input['sid'])
			{
				$error[] = $lang->npbf_error_alreadyusing;
			}
			if(intval($mybb->user['newpoints']) < intval($style['points']))
			{
				$error[] = $lang->npbf_error_nopoints;
			}
			if($error)
			{
				$errors = inline_error($error);
			}
			else
			{
				if($style['points'] < 0)
				{
					$style['points'] = 0;
				}
				newpoints_addpoints($mybb->user['uid'], -$style['points'], 1, $groupsrules['buyformat_rate']);
				if($style['stock'] != "-1")
				{
					$db->update_query("newpoints_buyformat", array('stock' => intval($style['stock'])-1), "sid='{$mybb->input['sid']}'", 1);
				}
				$db->update_query("users", array("buyformat" => $mybb->input['sid']), "uid='{$mybb->user['uid']}'", 1);
				newpoints_buyformat_cache();
				redirect($mybb->settings['bburl'].'/newpoints.php?action=buyformat', $lang->npbf_redirect);
			}
		}
		$q = $db->simple_select('newpoints_buyformat', '*', "sid!='0'");
		if($db->num_rows($q) < 1)
		{
			eval("\$buyformat_rows = \"".$templates->get("newpoints_buyformat_row_empty")."\";");
		}
		else
		{
			while($style = $db->fetch_array($q))
			{
				if(ougc_check_groups($style['usergroups']) && $style['visible'] == 1 && ($style['stock'] >= 1 || $style['stock'] == -1))
				{
					$style['sid'] = intval($style['sid']);
					$style['name'] = htmlspecialchars_uni($style['name']);
					$style['description'] = htmlspecialchars_uni($style['description']);
					$style['style'] = newpoints_buyformat_format_name(htmlspecialchars_uni($mybb->user['username']), true, $style['sid']);
					$style['style'] = $style['style']['format'];
					$style['points'] = newpoints_format_points($style['points']*floatval($groupsrules['buyformat_rate']));
					$style['stock'] = intval($style['stock']);
					$style['stock'] = ($style['stock'] == -1 ? $lang->npbf_infinite : $style['stock']);
					$style['visible'] = intval($style['visible']);
					eval("\$buyformat_rows .= \"".$templates->get("newpoints_buyformat_row")."\";");
				}
			}
		}
		eval("\$page = \"".$templates->get("newpoints_buyformat")."\";");
		output_page($page);
		exit;
	}
	elseif($mybb->input['action'] == 'buyformat')
	{
		error_no_permission();
	}
}

// Add our table to the Newpoints Backup task list.
function newpoints_buyformat_backup(&$backup_fields)
{
	$backup_fields[] = 'newpoints_buyformat';
}

// This will cache/update our format styles
function newpoints_buyformat_cache()
{
	global $cache, $db;
	$set_data = array();
	$q = $db->simple_select('newpoints_buyformat', '*', "sid!='0'", array('order_by' => 'sid'));
	while($style = $db->fetch_array($q))
	{
		$id = $style['sid'];
		unset($style['sid']);
		$set_data[$id] = $style;
	}
	$cache->update('newpoints_buyformat', $set_data);
	$db->free_result($q);
}

// This will check current user's groups.
if(!function_exists('ougc_check_groups'))
{
	function ougc_check_groups($groups, $empty=true)
	{
		global $mybb;
		if(empty($groups) && $empty == true)
		{
			return true;
		}
		if(!empty($mybb->user['additionalgroups']))
		{
			$usergroups = explode(',', $mybb->user['additionalgroups']);
		}
		if(!is_array($usergroups))
		{
			$usergroups = array();
		}
		$usergroups[] = $mybb->user['usergroup'];
		$groups = explode(',', $groups);
		foreach($usergroups as $gid)
		{
			if(in_array($gid, $groups))
			{
				return true;
			}
		}
		return false;
	}
}
?>