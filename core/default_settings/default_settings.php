<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008 - 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('default_setting_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (is_array($_POST['default_settings'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$domain_uuid = $_POST['domain_uuid'];
		$default_settings = $_POST['default_settings'];
	}

//process the http post data by action
	if ($action != '' && is_array($default_settings) && @sizeof($default_settings) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('default_setting_add')) {
					$obj = new default_settings;
					$obj->domain_uuid = $domain_uuid;
					$obj->copy($default_settings);
				}
				break;
			case 'toggle':
				if (permission_exists('default_setting_edit')) {
					$obj = new default_settings;
					$obj->toggle($default_settings);
				}
				break;
			case 'delete':
				if (permission_exists('default_setting_delete')) {
					$obj = new default_settings;
					$obj->delete($default_settings);
				}
				break;
		}

		header('Location: default_settings.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search string
	if (isset($_GET["search"])) {
		$search =  strtolower($_GET["search"]);
		$sql_search = " (";
		$sql_search .= "	lower(default_setting_category) like :search ";
		$sql_search .= "	or lower(default_setting_subcategory) like :search ";
		$sql_search .= "	or lower(default_setting_name) like :search ";
		$sql_search .= "	or lower(default_setting_value) like :search ";
		$sql_search .= "	or lower(default_setting_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the count
	$sql = "select count(default_setting_uuid) from v_default_settings ";
		if (isset($sql_search)) {
			$sql .= "where ".$sql_search;
		}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//get the list
	$sql = str_replace('count(default_setting_uuid)', '*', $sql);
	$sql .= order_by($order_by, $order, 'default_setting_category', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$default_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//determine categories
	if (is_array($default_settings) && @sizeof($default_settings) != 0) {
		foreach ($default_settings as $default_setting) {
			$category = strtolower($default_setting['default_setting_category']);
			switch ($category) {
				case "api" : $category = "API"; break;
				case "cdr" : $category = "CDR"; break;
				case "ldap" : $category = "LDAP"; break;
				case "ivr_menu" : $category = "IVR Menu"; break;
				default:
					$category = str_replace("_", " ", $category);
					$category = str_replace("-", " ", $category);
					$category = ucwords($category);
			}
			$categories[$default_setting['default_setting_category']]['formatted'] = $category;
			$categories[$default_setting['default_setting_category']]['count']++;
		}
		ksort($categories);
		unset($default_setting, $category);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	require_once "resources/header.php";

//copy settings javascript
	if (permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) {
		echo "<script language='javascript' type='text/javascript'>\n";
		echo "	var fade_speed = 400;\n";
		echo "	function show_domains() {\n";
		echo "		document.getElementById('action').value = 'copy';\n";
		echo "		$('#button_copy').fadeOut(fade_speed, function() {\n";
		echo "			$('#button_back').fadeIn(fade_speed);\n";
		echo "			$('#target_domain_uuid').fadeIn(fade_speed);\n";
		echo "			$('#button_paste').fadeIn(fade_speed);\n";
		echo "		});";
		echo "	}";
		echo "	function hide_domains() {\n";
		echo "		document.getElementById('action').value = '';\n";
		echo "		$('#button_back').fadeOut(fade_speed);\n";
		echo "		$('#target_domain_uuid').fadeOut(fade_speed);\n";
		echo "		$('#button_paste').fadeOut(fade_speed, function() {\n";
		echo "			$('#button_copy').fadeIn(fade_speed);\n";
		echo "			document.getElementById('target_domain_uuid').selectedIndex = 0;\n";
		echo "		});\n";
		echo "	}\n";
		echo "</script>";
	}

//show category javascript
	if (is_array($categories) && @sizeof($categories) != 0) {
		echo "<script language='javascript' type='text/javascript'>\n";
		echo "	function show_category(category) {\n";
		echo "		var n;\n";
		echo "		var c = document.getElementsByClassName('category');\n";
		echo "		if (category != '') {\n";
		echo "			for (n = 0; n < c.length; n++) {\n";
		echo "				c[n].style.display = 'none';\n";
		echo "			}\n";
		echo "			document.getElementById('category_'+category).style.display = 'block';\n";
		echo "		}\n";
		echo "		else {\n";
		echo "			for (n = 0; n < c.length; n++) {\n";
		echo "				c[n].style.display = 'block';\n";
		echo "			}\n";
		echo "		}\n";
		echo "	}\n";
		echo "</script>";
	}

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-default_settings']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['label'=>$text['button-reload'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'button_reload','link'=>'default_settings_reload.php'.($search != '' ? '?search='.urlencode($search) : null),'style'=>'margin-right: 15px;']);
	if (permission_exists('default_setting_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'link'=>'default_setting_edit.php']);
	}
	if (permission_exists('default_setting_add') && $default_settings) {
		if (permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'id'=>'button_copy','icon'=>$_SESSION['theme']['button_icon_copy'],'onclick'=>'show_domains();']);
			echo button::create(['type'=>'button','label'=>$text['button-back'],'id'=>'button_back','icon'=>$_SESSION['theme']['button_icon_refresh'],'style'=>'display: none;','onclick'=>'hide_domains();']);
			echo 		"<select class='formfld' style='display: none; width: auto;' id='target_domain_uuid' onchange=\"document.getElementById('domain_uuid').value = this.options[this.selectedIndex].value;\">\n";
			echo "			<option value=''>".$text['label-domain']."...</option>\n";
			foreach ($_SESSION['domains'] as $domain) {
				echo "		<option value='".escape($domain["domain_uuid"])."'>".escape($domain["domain_name"])."</option>\n";
			}
			echo "		</select>";
			echo button::create(['type'=>'button','label'=>$text['button-paste'],'id'=>'button_paste','icon'=>'paste','style'=>'display: none;','onclick'=>"if (confirm('".$text['confirm-copy']."')) { list_action_set('copy'); list_form_submit('form_list'); } else { this.blur(); return false; }"]);
		}
	}
	if (permission_exists('default_setting_edit') && $default_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'onclick'=>"if (confirm('".$text['confirm-toggle']."')) { list_action_set('toggle'); list_form_submit('form_list'); } else { this.blur(); return false; }"]);
	}
	if (permission_exists('default_setting_delete') && $default_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'onclick'=>"if (confirm('".$text['confirm-delete']."')) { list_action_set('delete'); list_form_submit('form_list'); } else { this.blur(); return false; }"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (is_array($categories) && @sizeof($categories) != 0) {
		echo 		"<select class='formfld' style='width: auto; margin-left: 15px;' id='select_category' onchange='show_category(this.options[this.selectedIndex].value);'>\n";
		echo "			<option value='' selected='selected'>".$text['label-category']."...</option>\n";
		foreach ($categories as $category_original => $category) {
			echo "		<option value='".escape($category_original)."'>".escape($category['formatted'])." (".$category['count'].")</option>\n";
		}
		echo "			<option value=''>".$text['label-all']." (".$num_rows.")</option>\n";
		echo "		</select>";
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' style='margin-left: 0 !important;' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'default_settings.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-default_settings']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";
	echo "<input type='hidden' name='domain_uuid' id='domain_uuid'>";

	if (is_array($default_settings) && @sizeof($default_settings) != 0) {
		$x = 0;
		foreach ($default_settings as $row) {
			$default_setting_category = strtolower($row['default_setting_category']);

			$label_default_setting_category = $row['default_setting_category'];
			switch (strtolower($label_default_setting_category)) {
				case "api" : $label_default_setting_category = "API"; break;
				case "cdr" : $label_default_setting_category = "CDR"; break;
				case "ldap" : $label_default_setting_category = "LDAP"; break;
				case "ivr_menu" : $label_default_setting_category = "IVR Menu"; break;
				default:
					$label_default_setting_category = str_replace("_", " ", $label_default_setting_category);
					$label_default_setting_category = str_replace("-", " ", $label_default_setting_category);
					$label_default_setting_category = ucwords($label_default_setting_category);
			}

			if ($previous_default_setting_category != $row['default_setting_category']) {
				if ($previous_default_setting_category != '') {
					echo "</table>\n";
					echo "<br />\n";
					echo "</div>\n";
				}
				echo "<div class='category' id='category_".$default_setting_category."'>\n";
				echo "<b>".escape($label_default_setting_category)."</b><br>\n";

				echo "<table class='list'>\n";
				echo "<tr class='list-header'>\n";
				if (permission_exists('default_setting_add') || permission_exists('default_setting_edit') || permission_exists('default_setting_delete')) {
					echo "	<th class='checkbox'>\n";
					echo "		<input type='checkbox' id='checkbox_all_".$default_setting_category."' name='checkbox_all' onclick=\"list_all_toggle('".$default_setting_category."');\">\n";
					echo "	</th>\n";
				}
				if ($_GET['show'] == 'all' && permission_exists('default_setting_all')) {
					echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
				}
				echo th_order_by('default_setting_subcategory', $text['label-subcategory'], $order_by, $order, null, "class='pct-35'");
				echo th_order_by('default_setting_name', $text['label-name'], $order_by, $order, null, "class='pct-10 hide-sm-dn'");
				echo th_order_by('default_setting_value', $text['label-value'], $order_by, $order, null, "class='pct-30'");
				echo th_order_by('default_setting_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
				echo "	<th class='pct-25 hide-sm-dn'>".$text['label-description']."</th>\n";
				if (permission_exists('default_setting_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
					echo "	<td class='action-button'>&nbsp;</td>\n";
				}
				echo "</tr>\n";
			}
			if (permission_exists('default_setting_edit')) {
				$list_row_url = "default_setting_edit.php?id=".urlencode($row['default_setting_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('default_setting_add') || permission_exists('default_setting_edit') || permission_exists('default_setting_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='default_settings[$x][checked]' id='checkbox_".$x."' class='checkbox_".$default_setting_category."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all_".$default_setting_category."').checked = false; }\">\n";
				echo "		<input type='hidden' name='default_settings[$x][uuid]' value='".escape($row['default_setting_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == 'all' && permission_exists('default_setting_all')) {
				echo "	<td>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
			}
			echo "	<td class='overflow no-wrap'>".escape($row['default_setting_subcategory'])."</td>\n";
			echo "	<td class='hide-sm-dn'>".escape($row['default_setting_name'])."</td>\n";
			echo "	<td class='overflow no-wrap'>\n";
			$category = $row['default_setting_category'];
			$subcategory = $row['default_setting_subcategory'];
			$name = $row['default_setting_name'];
			if ($category == "domain" && $subcategory == "menu" && $name == "uuid" ) {
				$sql = "select * from v_menus ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$parameters['menu_uuid'] = $row['default_setting_value'];
				$database = new database;
				$sub_result = $database->select($sql, $parameters, 'all');
				foreach ($sub_result as &$sub_row) {
					echo $sub_row["menu_language"]." - ".$sub_row["menu_name"]."\n";
				}
				unset($sql, $sub_result, $sub_row);
			}
			else if ($category == "domain" && $subcategory == "template" && $name == "name" ) {
				echo "		".ucwords($row['default_setting_value']);
			}
			else if ($category == "domain" && $subcategory == "time_format" && $name == "text" ) {
				switch ($row['default_setting_value']) {
					case '12h': echo $text['label-12-hour']; break;
					case '24h': echo $text['label-24-hour']; break;
				}
			}
			else if (
				( $category == "theme" && $subcategory == "menu_main_icons" && $name == "boolean" ) ||
				( $category == "theme" && $subcategory == "menu_sub_icons" && $name == "boolean" ) ||
				( $category == "theme" && $subcategory == "menu_brand_type" && $name == "text" ) ||
				( $category == "theme" && $subcategory == "menu_style" && $name == "text" ) ||
				( $category == "theme" && $subcategory == "menu_position" && $name == "text" ) ||
				( $category == "theme" && $subcategory == "logo_align" && $name == "text" )
				) {
				echo "		".$text['label-'.$row['default_setting_value']];
			}
			else if ($category == 'theme' && $subcategory == 'custom_css_code' && $name == 'text') {
				echo "		[...]\n";
			}
			else if ($subcategory == 'password' || substr_count($subcategory, '_password') > 0 || $category == "login" && $subcategory == "password_reset_key" && $name == "text" || substr_count($subcategory, '_secret') > 0) {
				echo "		".str_repeat('*', strlen($row['default_setting_value']));
			}
			else if ($category == 'theme' && $subcategory == 'button_icons' && $name == 'text') {
				echo "		".$text['option-button_icons_'.$row['default_setting_value']]."\n";
			}
			else if ($category == "theme" && substr_count($subcategory, "_color") > 0 && ($name == "text" || $name == 'array')) {
				echo "		".(img_spacer('15px', '15px', 'background: '.escape($row['default_setting_value']).'; margin-right: 4px; vertical-align: middle; border: 1px solid '.(color_adjust($row['default_setting_value'], -0.18)).'; padding: -1px;'));
				echo "<span style=\"font-family: 'Courier New'; line-height: 6pt;\">".escape($row['default_setting_value'])."</span>\n";
			}
			else if ($category == 'recordings' && $subcategory == 'storage_type' && $name == 'text') {
				echo "		".$text['label-'.$row['default_setting_value']]."\n";
			}
			else {
				echo "		".escape($row['default_setting_value'])."\n";
			}
			echo "	</td>\n";
			if (permission_exists('default_setting_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['default_setting_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['default_setting_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn' title=\"".escape($row['default_setting_description'])."\">".escape($row['default_setting_description'])."</td>\n";
			if (permission_exists('default_setting_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";

			//set the previous category
			$previous_default_setting_category = $row['default_setting_category'];
			$x++;
		}
		unset($default_settings);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "</div>\n";

	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//focus on category selector
	echo "<script>\n";
	echo "	$(document).ready(function() {\n";
	echo "		document.getElementById('select_category').focus();\n";
	echo "	});\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>