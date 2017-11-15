<?php

namespace FormTools\Modules\SubmissionPreParser;

use FormTools\Core;
use FormTools\Hooks;
use FormTools\Module as FormToolsModule;
use PDO, PDOException;

class Module extends FormToolsModule
{
    protected $moduleName = "Submission Pre-parser";
    protected $moduleDesc = "This module is for PHP programmers who'd like to examine the incoming data prior to adding to the database. It lets you add custom PHP to do things such as filtering out or tweaking invalid form values, redirect to alternate pages, combine form fields or to prevent the submission from being added.";
    protected $author = "Ben Keen";
    protected $authorEmail = "ben.keen@gmail.com";
    protected $authorLink = "https://formtools.org";
    protected $version = "2.0.0";
    protected $date = "2017-11-13";
    protected $originLanguage = "en_us";

    protected $jsFiles = array(
        "{FTROOT}/global/codemirror/lib/codemirror.js",
    );
    protected $cssFiles = array(
        "{FTROOT}/global/codemirror/lib/codemirror.css"
    );

//        "$root_url/global/codemirror/lib/codemirror.js",
//        "$root_url/global/codemirror/mode/xml/xml.js",
//        "$root_url/global/codemirror/mode/smarty/smarty.js",
//        "$root_url/global/codemirror/mode/php/php.js",
//        "$root_url/global/codemirror/mode/htmlmixed/htmlmixed.js",
//        "$root_url/global/codemirror/mode/css/css.js",
//        "$root_url/global/codemirror/mode/javascript/javascript.js",
//        "$root_url/global/codemirror/mode/clike/clike.js"

    protected $nav = array(
        "module_name"     => array("index.php", false),
        "phrase_add_rule" => array("add.php", true),
        "word_settings"   => array("settings.php", true),
        "word_help"       => array("help.php", true)
    );

    /**
     * The installation script for the Submission Preparser function.
     */
    public function install($module_id)
    {
        $db = Core::$db;

        try {
            $db->beginTransaction();
            $db->query("
                CREATE TABLE {PREFIX}module_submission_pre_parser_rules (
                  rule_id mediumint(9) NOT NULL auto_increment,
                  status enum('enabled','disabled') NOT NULL default 'enabled',
                  rule_name varchar(255) NOT NULL,
                  event set('ft_process_form','ft_api_process_form','ft_update_submission') default NULL,
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

            $db->processTransaction();

        } catch (PDOException $e) {
            $db->rollbackTransaction();
            return array(false, $e->getMessage());
        }

        // register the hooks. This simply adds the POTENTIAL for the module to be called in those
        // functions. The spp_parse function does the job of processing the user-defined list of
        // parsing rules, as entered via the UI. If there are no rules, nothing happens
        Hooks::registerHook("code", "submission_pre_parser", "start", "FormTools\\Submissions::processFormSubmission", "parse");
        Hooks::registerHook("code", "submission_pre_parser", "start", "FormTools\\API::apiProcessFormSubmission", "parse");
        Hooks::registerHook("code", "submission_pre_parser", "start", "FormTools\\Submissions::updateSubmission", "parse");

        return array(true, "");
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

        $form_ids  = isset($info["form_ids"]) ? $info["form_ids"] : array();

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

        $rule_info = $db->fetch(PDO::FETCH_COLUMN);
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
        $db->query("rule_id", $rule_id);
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
     * @return array the updated POST data.
     */
    public function parse($vars)
    {
        $vars["form_data"]["form_tools_calling_function"] = $vars["form_tools_calling_function"];

        $form_data_key = "form_data";
        if ($vars["form_tools_calling_function"] == "ft_update_submission") {
            $form_data_key = "infohash";
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
            if (!in_array($vars["form_tools_calling_function"], $events)) {
                continue;
            }

            eval($rule_info["php_code"]);
        }

        $return_vals = array($form_data_key => $_POST);

        return $return_vals;
    }

}

