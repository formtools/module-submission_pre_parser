<?php

require("../../global/library.php");

use FormTools\Modules;

$module = Modules::initModulePage("admin");
$L = $module->getLangStrings();

$page_vars = array(
    "head_title" => $L["phrase_add_rule"]
);

$module->displayPage("templates/add.tpl", $page_vars);
