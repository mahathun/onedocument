<?php

defined('MOODLE_INTERNAL') || die;

// $ADMIN->add('reports', new admin_externalpage('reportlastaccess', get_string('pluginname','report_lastaccess'), "$CFG->wwwroot/report/lastaccess/index.php"))
$ADMIN->add('reports', new admin_externalpage('reportonedocument', get_string('menuname','report_onedocument'), "$CFG->wwwroot/report/onedocument/index.php"));
$settings = null;
