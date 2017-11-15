<?php

require_once("../../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Modules;

$module = Modules::initModulePage("admin");
$settings = $module->getSettings();
$L = $module->getLangStrings();
$LANG = Core::$L;

$num_rules_per_page = $settings["num_rules_per_page"];

$success = true;
$message = "";
if (isset($_POST["add_rule"])) {
    list($success, $message) = $module->addRule($_POST);
} else if (isset($_GET["delete"])) {
    list($success, $message) = $module->deleteRule($_GET["delete"]);
}

$page = Modules::loadModuleField("submission_pre_parser", "page", "page", 1);
$rule_info = $module->getRules($num_rules_per_page, $page);
$results     = $rule_info["results"];
$num_results = $rule_info["num_results"];

$page_vars = array(
    "g_success" => $success,
    "g_message" => $message,
    "head_title" => $L["module_name"],
    "results" => $results,
    "num_results" => $num_results,
    "pagination" => General::getPageNav($num_results, $num_rules_per_page, $page),
    "js_messages" => array("word_edit")
);

$page_vars["head_js"] =<<< END
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
END;

$module->displayPage("templates/index.tpl", $page_vars);
