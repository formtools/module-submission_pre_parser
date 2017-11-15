<?php

require("../../global/library.php");

use FormTools\Modules;

$module = Modules::initModulePage("admin");
$L = $module->getLangStrings();

$success = true;
$message = "";
if (isset($_POST["update_rule"])) {
    list($success, $message) = $module->updateRule($_POST["rule_id"], $_POST);
}

$rule_id = Modules::loadModuleField("submission_pre_parser", "rule_id", "rule_id");
$rule_info = $module->getRule($rule_id);
$rule_info["event"] = explode(",", $rule_info["event"]);

$page_vars = array(
    "g_success" => $success,
    "g_message" => $message,
    "head_title" => $L["phrase_edit_rule"],
    "rule_info"  => $rule_info
);

$module->displayPage("templates/edit.tpl", $page_vars);
