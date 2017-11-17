<?php

$STRUCTURE = array();
$STRUCTURE["tables"] = array();
$STRUCTURE["tables"]["module_submission_pre_parser_rules"] = array(
    array(
        "Field"   => "rule_id",
        "Type"    => "mediumint(9)",
        "Null"    => "NO",
        "Key"     => "PRI",
        "Default" => ""
    ),
    array(
        "Field"   => "status",
        "Type"    => "enum('enabled','disabled')",
        "Null"    => "NO",
        "Key"     => "",
        "Default" => "enabled"
    ),
    array(
        "Field"   => "rule_name",
        "Type"    => "varchar(255)",
        "Null"    => "NO",
        "Key"     => "",
        "Default" => ""
    ),
    array(
        "Field"   => "event",
        "Type"    => "set('on_form_submission','on_form_submission_api','on_submission_edit')",
        "Null"    => "YES",
        "Key"     => "",
        "Default" => ""
    ),
    array(
        "Field"   => "php_code",
        "Type"    => "mediumtext",
        "Null"    => "NO",
        "Key"     => "",
        "Default" => ""
    )
);

$STRUCTURE["tables"]["module_submission_pre_parser_rule_forms"] = array(
    array(
        "Field"   => "rule_id",
        "Type"    => "mediumint(8) unsigned",
        "Null"    => "NO",
        "Key"     => "PRI",
        "Default" => ""
    ),
    array(
        "Field"   => "form_id",
        "Type"    => "mediumint(8) unsigned",
        "Null"    => "NO",
        "Key"     => "PRI",
        "Default" => ""
    )
);


$HOOKS = array(
    array(
        "hook_type"       => "code",
        "action_location" => "start",
        "function_name"   => "FormTools\\Submissions::processFormSubmission",
        "hook_function"   => "parse",
        "priority"        => "50"
    ),
    array(
        "hook_type"       => "code",
        "action_location" => "start",
        "function_name"   => "FormTools\\API::processFormSubmission",
        "hook_function"   => "parse",
        "priority"        => "50"
    ),
    array(
        "hook_type"       => "code",
        "action_location" => "start",
        "function_name"   => "FormTools\\Submissions::updateSubmission",
        "hook_function"   => "parse",
        "priority"        => "50"
    )
);


$FILES = array(
    "code",
    "code/Module.class.php",
    "images/",
    "images/icon_preparser.gif",
    "lang/",
    "lang/en_us.php",
    "templates/",
    "templates/add.tpl",
    "templates/edit.tpl",
    "templates/help.tpl",
    "templates/index.tpl",
    "templates/settings.tpl",
    "add.php",
    "edit.php",
    "help.php",
    "index.php",
    "library.php",
    "LICENSE",
    "module_config.php",
    "README.md",
    "settings.php",
);
