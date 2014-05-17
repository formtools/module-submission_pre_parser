<?php

require("../../global/library.php");
ft_init_module_page();

$page_vars = array();
$page_vars["head_title"] = $L["phrase_add_rule"];

ft_display_module_page("templates/add.tpl", $page_vars);