<?php

namespace FormTools\Modules\SubmissionPreParser;

use FormTools\Core;
use FormTools\General;
use FormTools\Hooks;
use FormTools\Module as FormToolsModule;
use FormTools\Views;
use PDO, Exception;

class Module extends FormToolsModule
{
	protected $moduleName = "Submission Pre-parser";
	protected $moduleDesc = "This module is for PHP programmers who'd like to examine the incoming data prior to adding to the database. It lets you add custom PHP to do things such as filtering out or tweaking invalid form values, redirect to alternate pages, combine form fields or to prevent the submission from being added.";
	protected $author = "Ben Keen";
	protected $authorEmail = "ben.keen@gmail.com";
	protected $authorLink = "https://formtools.org";
	protected $version = "2.0.5";
	protected $date = "2019-03-16";
	protected $originLanguage = "en_us";

	protected $function_event_map = array(
		"on_form_submission" => "FormTools\\Submissions::processFormSubmission",
		"on_form_submission_api" => "FormTools\\API->processFormSubmission",
		"on_submission_edit" => "FormTools\\Submissions::updateSubmission",
		"add_submission_from_ui" => "FormTools\\Views::getNewViewSubmissionDefaults"
	);

	protected $jsFiles = array(
		"{FTROOT}/global/codemirror/lib/codemirror.js",
		"{FTROOT}/global/codemirror/mode/xml/xml.js",
		"{FTROOT}/global/codemirror/mode/smarty/smarty.js",
		"{FTROOT}/global/codemirror/mode/php/php.js",
		"{FTROOT}/global/codemirror/mode/htmlmixed/htmlmixed.js",
		"{FTROOT}/global/codemirror/mode/css/css.js",
		"{FTROOT}/global/codemirror/mode/javascript/javascript.js",
		"{FTROOT}/global/codemirror/mode/clike/clike.js"
	);
	protected $cssFiles = array(
		"{FTROOT}/global/codemirror/lib/codemirror.css"
	);

	protected $nav = array(
		"module_name" => array("index.php", false),
		"phrase_add_rule" => array("add.php", true),
		"word_settings" => array("settings.php", true),
		"word_help" => array("help.php", true)
	);

	/**
	 * The installation script for the Submission Preparser function.
	 */
	public function install($module_id)
	{
		$db = Core::$db;

		try {
			$db->query("
                CREATE TABLE {PREFIX}module_submission_pre_parser_rules (
                  rule_id mediumint(9) NOT NULL auto_increment,
                  status enum('enabled','disabled') NOT NULL default 'enabled',
                  rule_name varchar(255) NOT NULL,
                  event set('on_form_submission','on_form_submission_api','on_submission_edit','add_submission_from_ui') default NULL,
                  php_code mediumtext NOT NULL,
                  PRIMARY KEY (rule_id)
                ) AUTO_INCREMENT=1            
            ");
			$db->execute();

			$db->query("
                CREATE TABLE {PREFIX}module_submission_pre_parser_rule_forms (
                  rule_id mediumint(8) unsigned NOT NULL,
                  form_id mediumint(8) unsigned NOT NULL,
                  PRIMARY KEY (rule_id, form_id)
                )
            ");
			$db->execute();

			$db->query("
                INSERT INTO {PREFIX}settings (setting_name, setting_value, module)
                VALUES ('num_rules_per_page', '10', 'submission_pre_parser')
            ");
			$db->execute();
		} catch (Exception $e) {
			return array(false, $e->getMessage());
		}

		// register the hooks. This simply adds the POTENTIAL for the module to be called in those
		// functions. The spp_parse function does the job of processing the user-defined list of
		// parsing rules, as entered via the UI. If there are no rules, nothing happens
		$this->resetHooks();

		return array(true, "");
	}

	public function upgrade($module_id, $old_module_version)
	{
		$this->resetHooks();

		if (General::isVersionEarlierThan($old_module_version, "2.0.4")) {
			$this->addNewAddSubmissionEventDbField();
		}
	}

	/**
	 * The uninstallation script for the Submission Pre-Parser module. This does a custom little
	 * clean up on the database to ensure it doesn't leave any footprints. Namely:
	 *   - the module_pages table is removed
	 *   - any references in client or admin menus to any Pages are removed
	 *   - if the default login page for any user account was a Page, it attempts to reset it to
	 *     a likely login page (the Forms page for both).
	 *
	 * The core script removes hooks, menu references and attempts to remove the main folder.
	 *
	 * @return array [0] T/F, [1] success message
	 */
	public function uninstall($module_id)
	{
		$db = Core::$db;

		$db->query("DROP TABLE {PREFIX}module_submission_pre_parser_rules");
		$db->execute();

		$db->query("DROP TABLE {PREFIX}module_submission_pre_parser_rule_forms");
		$db->execute();

		return array(true, "");
	}


	public function resetHooks()
	{
		$this->clearHooks();

		Hooks::registerHook("code", "submission_pre_parser", "start", "FormTools\\Submissions::processFormSubmission", "newSubmissionFromExternalFormHook");
		Hooks::registerHook("code", "submission_pre_parser", "start", "FormTools\\Submissions::updateSubmission", "updateSubmissionHook");
		Hooks::registerHook("code", "submission_pre_parser", "end", "FormTools\\Views::getNewViewSubmissionDefaults", "newSubmissionFromUIHook");
		Hooks::registerHook("code", "submission_pre_parser", "start", "FormTools\\API->processFormSubmission", "updateSubmissionFromApi");
	}

	/**
	 * Returns a page worth of Submission Pre-Parser rules for display purposes.
	 *
	 * @param mixed $num_per_page a number or "all"
	 * @param integer $page_num
	 * @return array
	 */
	public function getRules($num_per_page, $page_num = 1)
	{
		$db = Core::$db;

		if ($num_per_page == "all") {
			$db->query("
                SELECT *
                FROM   {PREFIX}module_submission_pre_parser_rules
                ORDER BY rule_id
            ");
		} else {
			if (empty($page_num)) {
				$page_num = 1;
			}
			$first_item = ($page_num - 1) * $num_per_page;

			$db->query("
                SELECT *
                FROM   {PREFIX}module_submission_pre_parser_rules
                ORDER BY rule_id
                LIMIT $first_item, $num_per_page
            ");
		}
		$db->execute();
		$results = $db->fetchAll();

		$db->query("SELECT count(*) FROM {PREFIX}module_submission_pre_parser_rules");
		$db->execute();

		$num_results = $db->fetch(PDO::FETCH_COLUMN);

		$infohash = array();
		foreach ($results as $field) {
			$form_ids = $this->getRuleForms($field["rule_id"]);
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
	public function addRule($info)
	{
		$db = Core::$db;
		$L = $this->getLangStrings();

		$form_ids = isset($info["form_ids"]) ? $info["form_ids"] : array();

		$db->query("
            INSERT INTO {PREFIX}module_submission_pre_parser_rules (status, rule_name, event, php_code)
            VALUES (:status, :rule_name, :event, :php_code)
        ");
		$db->bindAll(array(
			"status" => $info["status"],
			"rule_name" => $info["rule_name"],
			"event" => isset($info["event"]) ? join(",", $info["event"]) : "",
			"php_code" => $info["php_code"]
		));
		$db->execute();

		$rule_id = $db->getInsertId();

		if ($rule_id != 0) {
			foreach ($form_ids as $form_id) {
				$db->query("
                    INSERT INTO {PREFIX}module_submission_pre_parser_rule_forms (rule_id, form_id)
                    VALUES (:rule_id, :form_id)
                ");
				$db->bindAll(array(
					"rule_id" => $rule_id,
					"form_id" => $form_id
				));
				$db->execute();
			}
			$success = true;
			$message = $L["notify_rule_added"];
		} else {
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
	public function deleteRule($rule_id)
	{
		$db = Core::$db;
		$L = $this->getLangStrings();

		$db->query("DELETE FROM {PREFIX}module_submission_pre_parser_rules WHERE rule_id = :rule_id");
		$db->bind("rule_id", $rule_id);
		$db->execute();

		$db->query("DELETE FROM {PREFIX}module_submission_pre_parser_rule_forms WHERE rule_id = :rule_id");
		$db->bind("rule_id", $rule_id);
		$db->execute();

		return array(true, $L["notify_rule_deleted"]);
	}


	/**
	 * Returns all information about a particular Page.
	 *
	 * @param integer $page_id
	 * @return array
	 */
	public function getRule($rule_id)
	{
		$db = Core::$db;

		$db->query("
            SELECT *
            FROM   {PREFIX}module_submission_pre_parser_rules
            WHERE  rule_id = :rule_id
        ");
		$db->bind("rule_id", $rule_id);
		$db->execute();

		$rule_info = $db->fetch();
		$rule_info["form_ids"] = $this->getRuleForms($rule_id);

		return $rule_info;
	}


	/**
	 * Updates the (one and only) setting on the Settings page.
	 *
	 * @param array $info
	 * @return array [0] true/false
	 *               [1] message
	 */
	public function updateSettings($info)
	{
		$L = $this->getLangStrings();

		$settings = array(
			"num_rules_per_page" => $info["num_rules_per_page"]
		);
		$this->setSettings($settings);

		return array(true, $L["notify_settings_updated"]);
	}


	/**
	 * Returns all form IDs that a rule is associated with.
	 *
	 * @param integer $form_id
	 * @return array
	 */
	public function getRuleForms($rule_id)
	{
		$db = Core::$db;

		$db->query("
            SELECT form_id
            FROM {PREFIX}module_submission_pre_parser_rule_forms
            WHERE rule_id = :rule_id
        ");
		$db->bind("rule_id", $rule_id);
		$db->execute();

		return $db->fetchAll(PDO::FETCH_COLUMN);
	}


	/**
	 * Updates a pre-parser rule.
	 *
	 * @param integer $rule_id
	 * @param array
	 */
	public function updateRule($rule_id, $info)
	{
		$db = Core::$db;
		$L = $this->getLangStrings();

		$db->query("
            UPDATE {PREFIX}module_submission_pre_parser_rules
            SET    status = :status,
                   rule_name = :rule_name,
                   event = :event,
                   php_code = :php_code
            WHERE  rule_id = :rule_id
        ");
		$db->bindAll(array(
			"status" => $info["status"],
			"rule_name" => $info["rule_name"],
			"event" => isset($info["event"]) ? join(",", $info["event"]) : "",
			"php_code" => $info["php_code"],
			"rule_id" => $rule_id
		));
		$db->execute();

		$db->query("DELETE FROM {PREFIX}module_submission_pre_parser_rule_forms WHERE rule_id = :rule_id");
		$db->bind("rule_id", $rule_id);
		$db->execute();

		$form_ids = isset($info["form_ids"]) ? $info["form_ids"] : array();
		foreach ($form_ids as $form_id) {
			$db->query("
                INSERT INTO {PREFIX}module_submission_pre_parser_rule_forms (rule_id, form_id)
                VALUES (:rule_id, :form_id)
            ");
			$db->bindAll(array(
				"rule_id" => $rule_id,
				"form_id" => $form_id
			));
			$db->execute();
		}

		return array(true, $L["notify_rule_updated"]);
	}


	/**
	 * Returns all those rules that are applicable to a particular form.
	 *
	 * @param integer $form_id
	 */
	public function getFormRules($form_id)
	{
		$db = Core::$db;

		$db->query("
            SELECT r.*
            FROM   {PREFIX}module_submission_pre_parser_rules r,
                   {PREFIX}module_submission_pre_parser_rule_forms rf
            WHERE  r.rule_id = rf.rule_id AND
                   rf.form_id = :form_id
        ");
		$db->bind("form_id", $form_id);
		$db->execute();

		return $db->fetchAll();
	}


	/**
	 * The parser function. This is called for all form submissions by both direct submission and the API; it
	 * figures out what rules have been defined by the administrator for this form and processes the incoming
	 * data through it.
	 *
	 * @param string $vars
	 * @param string $form_data_key the location that contains the key->value pairs of the form data
	 * @return array the updated POST data.
	 */
	public function parse($vars, $form_data_key)
	{
		// the namespace, class + function of the hook location (as a single string value)
		$calling_function = $vars["form_tools_hook_info"]["function_name"];

		if (!isset($vars[$form_data_key])) {
			return array();
		}

		$_POST = $vars[$form_data_key];

		if (isset($vars["form_id"])) {
			$form_id = $vars["form_id"];
		} else {
			$form_id = $_POST["form_tools_form_id"];
		}

		if (!isset($form_id) || empty($form_id) || !is_numeric($form_id)) {
			return array();
		}

		$rules = $this->getFormRules($form_id);

		foreach ($rules as $rule_info) {
			if ($rule_info["status"] == "disabled") {
				continue;
			}

			// if this rule hasn't been associated with this calling function, ignore it
			$events = explode(",", $rule_info["event"]);

			$matched_functions = array();
			foreach ($events as $event) {
				$matched_functions[] = $this->function_event_map[$event];
			}

			if (!in_array($calling_function, $matched_functions)) {
				continue;
			}

			eval($rule_info["php_code"]);
		}

		$return_vals = array($form_data_key => $_POST);

		return $return_vals;
	}


	public function updateSubmissionHook($vars)
	{
		return $this->parse($vars, "infohash");
	}

	/**
	 * Hook for submissions created within the Form Tools interface. This hook is different from the others: it
	 * ties in with the Views::getNewViewSubmissionDefaults() method to append whatever additional POST vars are
	 * added by the user into $_POST within the hook.
	 *
	 * Basically, to keep the UI consistent across all hooks, the user can STILL define [fieldname] => value
	 * pairs in the $_POST var, even though it's completely different to what's really going on behind the
	 * scenes.
	 */
	public function newSubmissionFromUIHook($vars)
	{
		// our hook doesn't contain the form ID so we need to find it. Needed by parse() to get the SPP rules
		$view_info = Views::getView($vars["view_id"]);
		$vars["form_id"] = $view_info["form_id"];

		// for new submissions we don't have a convenient key-value pair hash somewhere containing the list
		// of data about to be inserted. Instead, $vars["results"] is an array of the following structure
		// containing info about the default values to be inserted:
		// [0] => Array
		// (
		//     [view_id] => X
		// 	   [field_id] => Y
		// 	   [default_value] => value here
		// 	   [list_order] => 1
		// )
		$result = $this->parse($vars, "spp_scoped_vars");

		// find out what the user added to $_POST within their rule. These may or may not be valid field names.
		$overridden_field_names = !empty($result["spp_scoped_vars"]) && is_array($result["spp_scoped_vars"]) ?
			array_keys($result["spp_scoped_vars"]) : array();

		// now we're going to update the data to be used for constructing default values. Use any predefined
		// values defined in the UI as the starting point (if defined), but this module will overwrite them
		// if the user entered it in their SPP rule

		$default_values_field_id_map = array(); // field_id => index
		$final_data = array();
		if (isset($vars["results"])) {
			for ($i = 0; $i < count($vars["results"]); $i++) {
				$default_values_field_id_map["field_id-" . $vars["results"][$i]["field_id"]] = $i;
			}
			$final_data = $vars["results"];
		}

		foreach ($view_info["fields"] as $field_info) {
			$curr_field_name = $field_info["field_name"];
			$key = "field_id-" . $field_info["field_id"];

			// if the user hasn't defined anything for this field, don't worry about it
			if (!in_array($curr_field_name, $overridden_field_names)) {
				continue;
			}

			$new_data = array(
				"view_id" => $vars["view_id"],
				"field_id" => $field_info["field_id"],
				"default_value" => $result["spp_scoped_vars"][$curr_field_name]
			);

			// if the user has a default value preset for this field, we'll OVERWRITE that value
			// from whatever's in the SPP rule
			if (array_key_exists($key, $default_values_field_id_map)) {
				$index = $default_values_field_id_map[$key];
				$final_data[$index] = $new_data;
			} else {
				$final_data[] = $new_data;
			}
		}

		// we return "results" because that's what the Views::getNewViewSubmissionDefaults() it permitted to
		// overwrite
		return array(
			"results" => $final_data
		);
	}

	public function newSubmissionFromExternalFormHook($vars)
	{
		return $this->parse($vars, "form_data");
	}

	public function updateSubmissionFromApi($vars)
	{
		return $this->parse($vars, "form_data");
	}

	private function addNewAddSubmissionEventDbField()
	{
		$db = Core::$db;

		$db->query("
			ALTER TABLE {PREFIX}module_submission_pre_parser_rules
			CHANGE event event SET('on_form_submission','on_form_submission_api','on_submission_edit','add_submission_from_ui')
			DEFAULT NULL
		");
		$db->execute();
	}
}
