<?php

require("../../global/library.php");
ft_init_module_page();

$folder = dirname(__FILE__);
require_once("$folder/library.php");

if (isset($_POST["update_rule"]))
	list($g_success, $g_message) = spp_update_rule($_POST["rule_id"], $_POST);

$rule_id = ft_load_module_field("submission_pre_parser", "rule_id", "rule_id");
$rule_info = spp_get_rule($rule_id);
$rule_info["event"] = explode(",", $rule_info["event"]);

// ------------------------------------------------------------------------------------------------

$page_vars = array();
$page_vars["head_title"] = $L["phrase_edit_rule"];
$page_vars["head_string"] = "<script type=\"text/javascript\" src=\"$g_root_url/global/codemirror/js/codemirror.js\"></script>";
$page_vars["rule_info"]  = $rule_info;

ft_display_module_page("templates/edit.tpl", $page_vars);