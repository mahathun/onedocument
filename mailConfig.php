<?php

function sendEmail($to_email, $from_email, $msg){
  global $DB;
  //$toUser = 't.d.mahavithana@gmail.com';
  $toUser = $DB->get_record('user', array('email' => $to_email));
  $fromUser = $DB->get_record('user', array('email' => $from_email));
  //$fromUser = 'ICAN';
  $subject = 'Assignment Report';
  $messageText = $msg;
  $sent = email_to_user($toUser, $fromUser, $subject,'',$messageText);


  return $sent;
}
