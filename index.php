<?php

require_once("../../global/library.php");
ft_init_module_page();

$folder = dirname(__FILE__);
require_once("$folder/library.php");

if (isset($_POST["add_rule"]))
	list($g_success, $g_message) = spp_add_rule($_POST);
else if (isset($_GET["delete"]))
  list($g_success, $g_message) = spp_delete_rule($_GET["delete"]);

$page = ft_load_module_field("submission_acounts", "page", "page", 1);
$rule_info = ssp_get_rules(10, $page);
$results     = $rule_info["results"];
$num_results = $rule_info["num_results"];

// ------------------------------------------------------------------------------------------------

$page_vars = array();
$page_vars["head_title"]  = $L["module_name"];
$page_vars["results"]     = $results;
$page_vars["num_results"] = $num_results;
$page_vars["head_js"] = "var page_ns = {};
page_ns.delete_rule = function(rule_id)
{
  if (confirm(\"{$L["confirm_delete_rule"]}\"))
    window.location = 'index.php?delete=' + rule_id;

  return false;
}
";

ft_display_module_page("templates/index.tpl", $page_vars);