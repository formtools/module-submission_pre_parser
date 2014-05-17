<?php

/**
 * The installation script for the Submission Preparser function.
 */
function submission_pre_parser__install($module_id)
{
  global $g_table_prefix, $L;

  $queries = array();
  $queries[] = "
		CREATE TABLE {$g_table_prefix}module_submission_pre_parser_rules (
		  rule_id mediumint(9) NOT NULL auto_increment,
		  status enum('enabled','disabled') NOT NULL default 'enabled',
		  rule_name varchar(255) NOT NULL,
		  php_code mediumtext NOT NULL,
		  PRIMARY KEY (rule_id)
		) ENGINE=InnoDB AUTO_INCREMENT=1
		  ";

  $queries[] = "
	  CREATE TABLE {$g_table_prefix}module_submission_pre_parser_rule_forms (
		  rule_id mediumint(8) unsigned NOT NULL,
		  form_id mediumint(8) unsigned NOT NULL,
		  PRIMARY KEY  (rule_id, form_id)
		) ENGINE=InnoDB
      ";

  $queries[] = "
    INSERT INTO {$g_table_prefix}settings (setting_name, setting_value, module)
    VALUES ('num_rules_per_page', '10', 'submission_pre_parser')
      ";

  foreach ($queries as $query)
  {
  	$result = mysql_query($query);

  	if (!$result)
  	  return array(false, "Failed Query: " . mysql_error());
  }

  return array(true, "");
}