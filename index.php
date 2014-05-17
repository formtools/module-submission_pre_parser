<?php

require_once("../../global/library.php");
ft_init_module_page();

$folder = dirname(__FILE__);
require_once("$folder/library.php");

if (isset($_POST["add_rule"]))
  list($g_success, $g_message) = spp_add_rule($_POST);
else if (isset($_GET["delete"]))
  list($g_success, $g_message) = spp_delete_rule($_GET["delete"]);

$page = ft_load_module_field("submission_pre_parser", "page", "page", 1);
$rule_info = ssp_get_rules(10, $page);
$results     = $rule_info["results"];
$num_results = $rule_info["num_results"];

$settings = ft_get_module_settings();

// ------------------------------------------------------------------------------------------------

$page_vars = array();
$page_vars["head_title"]  = $L["module_name"];
$page_vars["results"]     = $results;
$page_vars["num_results"] = $num_results;
$page_vars["pagination"] = ft_get_page_nav($num_results, $settings["num_rules_per_page"], $page);
$page_vars["js_messages"] = array("word_edit");
$page_vars["head_js"] =<<< EOF
var page_ns = {};
page_ns.delete_rule = function(rule_id) {
  ft.create_dialog({
    title:      "{$LANG["phrase_please_confirm"]}",
    content:    "{$L["confirm_delete_rule"]}",
    popup_type: "warning",
    buttons: {
      "{$LANG["word_yes"]}": function() { window.location = 'index.php?delete=' + rule_id; },
      "{$LANG["word_no"]}": function() { $(this).dialog("close"); }
    }
  });
  return false;
}
EOF;

ft_display_module_page("templates/index.tpl", $page_vars);