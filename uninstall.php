<?php


/**
 * The uninstallation script for the Pages module. This basically does a little clean up
 * on the database to ensure it doesn't leave any footprints. Namely:
 *   - the module_pages table is removed
 *   - any references in client or admin menus to any Pages are removed
 *   - if the default login page for any user account was a Page, it attempts to reset it to
 *     a likely login page (the Forms page for both).
 *
 * The message returned by the script informs the user the module has been uninstalled, and warns them
 * that any references to any of the Pages in the user accounts has been removed.
 *
 * @return array [0] T/F, [1] success message
 */
function submission_pre_parser__uninstall($module_id)
{
	global $g_table_prefix;

	mysql_query("DROP TABLE {$g_table_prefix}module_submission_pre_parser_rules");
	mysql_query("DROP TABLE {$g_table_prefix}module_submission_pre_parser_rule_forms");

	return array(true, "");
}