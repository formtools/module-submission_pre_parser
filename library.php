<?php

/**
 * This file defines all functions relating to the Submission Pre-Parser module.
 *
 * @copyright Encore Web Studios 2008
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-0
 * @subpackage SubmissionPreParser
 */


// ------------------------------------------------------------------------------------------------


/**
 * Returns a page worth of Submission Pre-Parser rules for display purposes.
 *
 * @param mixed $num_per_page a number or "all"
 * @param integer $page_num
 * @return array
 */
function ssp_get_rules($num_per_page, $page_num = 1)
{
	global $g_table_prefix;

	if ($num_per_page == "all")
	{
		$query = mysql_query("
		  SELECT *
	    FROM   {$g_table_prefix}module_submission_pre_parser_rules
	    ORDER BY rule_id
	      ");
	}
	else
	{
	  // determine the offset
	  if (empty($page_num)) { $page_num = 1; }
		$first_item = ($page_num - 1) * $num_per_page;

	  $query = mysql_query("
	    SELECT *
	    FROM   {$g_table_prefix}module_submission_pre_parser_rules
	    ORDER BY rule_id
	    LIMIT $first_item, $num_per_page
		    ") or handle_error(mysql_error());
	}

	$count_query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}module_submission_pre_parser_rules");
	$count_hash = mysql_fetch_assoc($count_query);
  $num_results = $count_hash["c"];

  $infohash = array();
	while ($field = mysql_fetch_assoc($query))
	{
	  $form_ids = spp_get_rule_forms($field["rule_id"]);
	  $field["form_ids"] = $form_ids;
    $infohash[] = $field;
	}

  $return_hash["results"] = $infohash;
  $return_hash["num_results"] = $num_results;

  return $return_hash;
}



/**
 * Adds a new rule to the module_submission_pre_parser_rules table.
 *
 * @param array $info
 * @return array standard return array
 */
function spp_add_rule($info)
{
	global $g_table_prefix, $L;

	$info = ft_sanitize($info);

  $status    = $info["status"];
	$rule_name = $info["rule_name"];
  $form_ids  = isset($info["form_ids"]) ? $info["form_ids"] : array();
  $php_code  = $info["php_code"];

  mysql_query("
    INSERT INTO {$g_table_prefix}module_submission_pre_parser_rules (status, rule_name, php_code)
    VALUES ('$status', '$rule_name', '$php_code')
      ");
  $rule_id = mysql_insert_id();

  if ($rule_id != 0)
  {
    // add the form IDs
    foreach ($form_ids as $form_id)
    {
      mysql_query("
        INSERT INTO {$g_table_prefix}module_submission_pre_parser_rule_forms (rule_id, form_id)
        VALUES ($rule_id, $form_id)
          ") or die(mysql_error());
    }

  	$success = true;
  	$message = $L["notify_rule_added"];
  }
  else
  {
  	$success = false;
  	$message = $L["notify_rule_not_added"];
  }

  return array($success, $message);
}


/**
 * Deletes a page.
 *
 * @param integer $page_id
 */
function spp_delete_rule($rule_id)
{
	global $g_table_prefix, $L;

	mysql_query("DELETE FROM {$g_table_prefix}module_submission_pre_parser_rules WHERE rule_id = $rule_id");
	mysql_query("DELETE FROM {$g_table_prefix}module_submission_pre_parser_rule_forms WHERE rule_id = $rule_id");

	return array(true, $L["notify_rule_deleted"]);
}


/**
 * Returns all information about a particular Page.
 *
 * @param integer $page_id
 * @return array
 */
function spp_get_rule($rule_id)
{
	global $g_table_prefix;

	$query = mysql_query("SELECT * FROM {$g_table_prefix}module_submission_pre_parser_rules WHERE rule_id = $rule_id");
	$rule_info = mysql_fetch_assoc($query);

	$rule_info["form_ids"] = spp_get_rule_forms($rule_id);

	return $rule_info;
}


/**
 * Updates the (one and only) setting on the Settings page.
 *
 * @param array $info
 * @return array [0] true/false
 *               [1] message
 */
function spp_update_settings($info)
{
  global $L;

  $settings = array("num_rules_per_page" => $info["num_rules_per_page"]);
  ft_set_module_settings($settings);

  return array(true, $L["notify_settings_updated"]);
}


/**
 * Returns all form IDs that a rule is associated with.
 *
 * @param integer $form_id
 * @return array
 */
function spp_get_rule_forms($rule_id)
{
	global $g_table_prefix;

  $query = mysql_query("SELECT form_id FROM {$g_table_prefix}module_submission_pre_parser_rule_forms WHERE rule_id = $rule_id");

  $form_ids = array();
  while ($row = mysql_fetch_assoc($query))
    $form_ids[] = $row["form_id"];

  return $form_ids;
}


/**
 * Updates a pre-parser rule.
 *
 * @param integer $rule_id
 * @param array
 */
function spp_update_rule($rule_id, $info)
{
	global $g_table_prefix, $L;

	$info = ft_sanitize($info);
  $status    = $info["status"];
  $rule_name = $info["rule_name"];
  $form_ids  = isset($info["form_ids"]) ? $info["form_ids"] : array();
  $php_code  = $info["php_code"];

	mysql_query("
	  UPDATE {$g_table_prefix}module_submission_pre_parser_rules
	  SET    status = '$status',
	         rule_name = '$rule_name',
	         php_code = '$php_code'
	  WHERE  rule_id = $rule_id
	    ");

	mysql_query("DELETE FROM {$g_table_prefix}module_submission_pre_parser_rule_forms WHERE rule_id = $rule_id");
	foreach ($form_ids as $form_id)
	{
    mysql_query("
      INSERT INTO {$g_table_prefix}module_submission_pre_parser_rule_forms (rule_id, form_id)
      VALUES ($rule_id, $form_id)
        ") or die(mysql_error());
  }

	return array(true, $L["notify_rule_updated"]);
}


/**
 * Returns all those rules that are applicable to a particular form.
 *
 * @param integer $form_id
 */
function spp_get_form_rules($form_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT r.*
    FROM   {$g_table_prefix}module_submission_pre_parser_rules r,
           {$g_table_prefix}module_submission_pre_parser_rule_forms rf
    WHERE  r.rule_id = rf.rule_id AND
           rf.form_id = $form_id
      ");

  $info = array();
  while ($row = mysql_fetch_assoc($query))
    $info[] = $row;

  return $info;
}


/**
 * The actual parser function. This is called for all form submissions; it figures out what
 * rules have been defined by the administrator for this form, proce
 *
 * @param array $post_data The contents of the $_POST submission (N.B. it's already passed through ft_sanitize()).
 * @return array $post_data
 */
function spp_parse()
{
  $form_id = $_POST["form_tools_form_id"];
  $rules = spp_get_form_rules($form_id);

  foreach ($rules as $rule_info)
  {
    if ($rule_info["status"] == "disabled")
      continue;

    eval($rule_info["php_code"]);
  }
}