<?php

require("../../global/library.php");

use FormTools\Modules;

$module = Modules::initModulePage("admin");

$success = true;
$message = "";
if (isset($_POST["update"])) {
    list ($success, $message) = $module->updateSettings($_POST);
}

$page_vars = array(
    "num_rules_per_page" => $module->getSettings("num_rules_per_page")
);

$module->displayPage("templates/settings.tpl", $page_vars);
