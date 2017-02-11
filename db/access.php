<?php

defined('MOODLE_INTERNAL') || die;

$capabilities = array(
  'report/onedocument:view'=> array(
    'riskbitmask' => RISK_PERSONAL,
    'captype' => 'read',
    'contextlevel' => CONTEXT_COURSE,
    'archetypes' => array(
      'manager' => CAP_ALLOW,
      'teacher' => CAP_ALLOW,
    ),
  )
);
