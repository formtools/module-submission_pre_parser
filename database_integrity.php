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
    "Type"    => "set('ft_process_form','ft_api_process_form','ft_update_submission')",
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
