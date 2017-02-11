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

$strstudent = "Name";
$strviewstudent = "View Student Report";
$strtitle = get_string('title','report_onedocument');

//set up page page oci_fetch_object
$PAGE->set_url($url);
$PAGE->set_context($systemcontext);
$PAGE->set_title($strtitle);
$PAGE->set_pagelayout('report');
$PAGE->set_heading($strtitle);

//get the courses.
$sql = "SELECT id, fullname
        FROM {course}
        WHERE visible=:visible
        AND id!=:siteid
        ORDER BY fullname";
$courses = $DB->get_records_sql_menu($sql, array('visible'=>1, 'siteid'=> SITEID));
$mform = new onedocument_form('',array('courses'=>$courses));
echo $OUTPUT->header();
echo $OUTPUT->heading($strtitle);
//  Had any data been submitted?
$table = new html_table();
$table->head = array($strstudent, $strviewstudent);
$students_exists = false;
if(isset($_REQUEST['course'])){
  $courseid = $_REQUEST['course'];
}else{
  $courseid='';
}
if($data = $mform->get_data()){
  // Process the data:
  // Select the course details correponding to the requested course.
  $params = array($_POST['course']);


  //$context = get_context_instance(CONTEXT_COURSE,$courseid);
  $context = context_course::instance($courseid);
//$systemcontext = context_course::instance($courseid);

  // print_r($courseid);
  $sql = "SELECT userid, firstname, lastname, courseid
          FROM {user_enrolments}
          LEFT JOIN {enrol}
          ON {user_enrolments}.enrolid = {enrol}.id
          LEFT JOIN {user}
          ON {user_enrolments}.userid = {user}.id
          WHERE courseid = $courseid";


  if($users = $DB->get_records_sql($sql)){
     foreach($users as $u) {
      $isStudent = !has_capability('moodle/course:viewhiddensections', $context,$u->userid);

      //if enrolled user is a student
      if($isStudent){
        $students_exists = true;
        $fullname = sprintf("%s %s", $u->firstname, $u->lastname) ;
        $viewurl = new moodle_url("/report/onedocument/viewReport.php?courseId=$u->courseid&userId=$u->userid");
        $viewstudentlink = "<a href='$viewurl'>View</a>";

         // Displays the table data.
         $table->data[] = array($fullname, $viewstudentlink);
      }

     }

  }
}

//send mail if form data is set
if(isset($_REQUEST['student_only']) || isset($_REQUEST['student_and_teachers'])){

    $context = context_course::instance($courseid);
    $sql = "SELECT userid, firstname, lastname, courseid,email
            FROM {user_enrolments}
            LEFT JOIN {enrol}
            ON {user_enrolments}.enrolid = {enrol}.id
            LEFT JOIN {user}
            ON {user_enrolments}.userid = {user}.id
            WHERE courseid = $courseid";

    if($users = $DB->get_records_sql($sql)){
      $assignmentsql = "SELECT {assign_submission}.id,name, intro, onlinetext, userid
                 FROM {assign}
                 LEFT JOIN {assignsubmission_onlinetext}
                 ON {assign}.id = {assignsubmission_onlinetext}.assignment
                 LEFT JOIN {assign_submission}
                 ON {assign_submission}.assignment = {assignsubmission_onlinetext}.assignment
                 WHERE course = $courseid ";

      $assignmentArray = $DB->get_records_sql($assignmentsql);

       foreach($users as $u) {
        $isStudent = !has_capability('moodle/course:viewhiddensections', $context,$u->userid);
        //if enrolled user is a student

        $out = "</br>";
        $name = sprintf("<h5>Name : %s %s </h5>",$u->firstname, $u->lastname);
        $totalAssignmentSql = "SELECT id FROM {assign} WHERE course=$courseid";
        $totalAssignmentArray = $DB->get_records_sql($totalAssignmentSql);
        $totalassignments = count($totalAssignmentArray);
        $completedAssignments = 0;
        $hasEnrolledAssignments = false;
        foreach ($assignmentArray as $assignment) {
          if($assignment->userid == $u->userid){
            $hasEnrolledAssignments = true;
          }
        }
        if($hasEnrolledAssignments){
          if($isStudent){
            foreach ($assignmentArray as $assignment) {
              if($assignment->userid == $u->userid){
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

              $emails[] = $string;
              $sent = sendEmail($u->email, $USER->email, $string);




            if(isset($sent) && $sent){
              $sent_emails[1][] = $u->email;
              // echo "<div class='alert alert-success alert-block'>Email sent successfully</div>";
            }else{
              $sent_emails[0][] = $u->email;
              // echo "<h1>Sending Failed</h1>";
              //echo $OUTPUT->single_button("viewReport.php?courseId=$courseid&userId=$userid&sendEmail=1","Email This");
            }
            //echo $string;
          }else{
              $teachers[] = $u->email;
          }
        }
      }

      if(isset($_REQUEST['student_and_teachers']) && isset($teachers) && isset($emails)){
        foreach ($teachers as $teacher) {

          # sends student reports as emails to teachers
          foreach ($emails as$email) {
            # code...
            // echo "<h1>".$teacher."</h1>";
            // echo  $email;
            $sent = sendEmail($teacher, $USER->email, $email);
          }


        }
        // print_r($emails);
        // print_r($teachers);
      }

      //displaying the succss/failed mb_ereg_search_getpos
      if(isset($sent_emails) & !empty($sent_emails)){
        if(isset($sent_emails[1]) && count($sent_emails[1])>0){
          echo "<div class='alert alert-success alert-block'> ".count($sent_emails[1])." emails sent successfully</div>";
        }
        if(isset($sent_emails[0]) && count($sent_emails[0])>0){
          echo "<div class='alert alert-danger alert-block'> ".count($sent_emails[0])." emails failed sending.</div>";

        }

      }


    }
  //}
}


$mform->display();
echo "<div class='form-group fitem'>
<form name='email_all' method='POST'>
  <div class='col-md-3'>Email <input style='display:none;' type='text' hidden name='course' value='$courseid'></div>
  <div class='col-md-9'>
    <button name='student_only' ".(($students_exists)?'':'disabled')." value='1' type='submit' class='btn ".(($students_exists)?'btn-primary':'btn-default')."' onclick='javascript:return confirm(\"You are about to send reports as emails to all the listed users?\\n(page will reload and this may take some time depending on number of emails to be sent. Please be patient)\");'>Students Only</button>
    <span style='margin:0 2em'>&nbsp;</span>
    <button name='student_and_teachers' ".(($students_exists)?'':'disabled')." value='1' type='submit' class='btn ".(($students_exists)?'btn-primary':'btn-default')."' onclick='javascript:return confirm(\"You are about to send reports as emails to all the listed users and course teacher/s?\\n(page will reload and this may take some time depending on number of emails to be sent. Please be patient)\");'>Students &amp; Teachers</button>
  </div>
</form>
</div>";
echo html_writer::table($table);

echo $OUTPUT->footer();
?>
