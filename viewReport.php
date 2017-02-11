<?php

require_once('../../config.php');
require($CFG->dirroot.'/report/onedocument/index_form.php');
require($CFG->dirroot.'/report/onedocument/mailConfig.php');
//get the system context.
$systemcontext = context_system::instance();
$url = new moodle_url('/report/onedocument/index.php');
$viewurl = new moodle_url('/report/onedocument/viewReport.php');

//check basic permission
require_capability('report/onedocument:view', $systemcontext);

//get the language string from the language FilesystemIterator


$strstudent = "Name";
$strviewstudent = "View Student Report";
$strtitle =  "View Report" ;//get_string('title2','report_onedocument');

//set up page page oci_fetch_object
$PAGE->set_url($url);
$PAGE->set_context($systemcontext);
$PAGE->set_title($strtitle);
$PAGE->set_pagelayout('report');
$PAGE->set_heading($strtitle);



//get the courses.
// $sql = "SELECT id, fullname
//         FROM {course}
//         WHERE visible=:visible
//         AND id!=:siteid
//         ORDER BY fullname";
// $courses = $DB->get_records_sql_menu($sql, array('visible'=>1, 'siteid'=> SITEID));
//$mform = new onedocument_form('',array('courses'=>$courses));
echo $OUTPUT->header();
echo $OUTPUT->heading($strtitle);


$courseid = $_REQUEST['courseId'];
$userid = $_REQUEST['userId'];
$usersql = "SELECT firstname,lastname, email
            FROM {user}
            WHERE id=$userid";

$assignmentsql = "SELECT {assign_submission}.id,name, intro, onlinetext, userid
           FROM {assign}
           LEFT JOIN {assignsubmission_onlinetext}
           ON {assign}.id = {assignsubmission_onlinetext}.assignment
           LEFT JOIN {assign_submission}
           ON {assign_submission}.assignment = {assignsubmission_onlinetext}.assignment
           WHERE course = $courseid ";

$assignmentArray = $DB->get_records_sql($assignmentsql);
$userArray = $DB->get_records_sql($usersql);
$user = array();
$userEmail = "";

foreach ($userArray as $key => $value) {
  $user =  $userArray[$key];
  $userEmail = $userArray[$key]->email;
}

// echo "<pre>";
// print_r($USER->email);
// print_r($userEmail);
// echo "</pre>";
$out = "</br>";
$name = sprintf("<h5>Name : %s %s </h5>",$user->firstname, $user->lastname);
$totalAssignmentSql = "SELECT id FROM {assign} WHERE course=$courseid";
$totalAssignmentArray = $DB->get_records_sql($totalAssignmentSql);
$totalassignments = count($totalAssignmentArray);
$completedAssignments = 0;
$hasEnrolledAssignments = false;
foreach ($assignmentArray as $assignment) {
  if($assignment->userid == $userid){
    $hasEnrolledAssignments = true;
  }
}
if($hasEnrolledAssignments){
  foreach ($assignmentArray as $assignment) {
    if($assignment->userid == $userid){
      $completedAssignments += 1;
      $out .= "<hr>";
      $out .= sprintf("<strong>Section Heading : </strong> %s <br>", $assignment->name);
      $out .= sprintf("<strong>Question : </strong> %s <br>", $assignment->intro);
      $out .= sprintf("<strong>Answer : </strong> %s <br>", $assignment->onlinetext);
      $out .= "<hr>";
    }
  }

  $name .= sprintf("completed %d out of %d assignments", $completedAssignments,$totalassignments);
  $string = "<div>
      $name
      $out

  </div>";

  echo $string;
  if(isset($_REQUEST['sendEmail']) && !empty($_REQUEST['sendEmail'])){

      $sent = sendEmail($userEmail, $USER->email, $string);
      if($sent){
        echo "<div class='alert alert-success alert-block'>Email sent successfully</div>";
      }else{
        echo "<h1>Sending Failed</h1>";
        echo $OUTPUT->single_button("viewReport.php?courseId=$courseid&userId=$userid&sendEmail=1","Email this to student");
      }
  }else{

    echo $OUTPUT->single_button("viewReport.php?courseId=$courseid&userId=$userid&sendEmail=1","Email this to student");
  }
}else{
  echo "<div class='alert alert-warning alert-block'>No Enrolled assignments found for this user.</div>";
}

echo $OUTPUT->footer();
?>
