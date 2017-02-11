<?php
require_once('../../config.php');
require_once("$CFG->libdir/formslib.php");

class onedocument_form extends moodleform{

  public function definition(){
    global $DB;
    $mform = $this->_form;
    //get the courses passed to the formmlib
    $options = array();
    $options[0] = "choose";//get_string('choose');
    //$options[1] = "course1";//get_string('choose');
    $options = $this->_customdata['courses'];
    $mform->addElement('select','course', "Courses",$options);
    $mform->setType('course', PARAM_ALPHANUMEXT);
    // $mform->addElement('date_selector', 'lastaccesseddate',"Last access Date");
    // $mform->setType('lastaccesseddate', PARAM_INT);
    // $mform->addElement('date_selector', 'currentdate', "Current Date");
    // $mform->setType('currentdate', PARAM_INT);
    $mform->addElement('submit', 'showstudent', "Show");
  }

  public function validation($data, $files){
    $errors = parent::validation($data,$files);
    //added to check whether the course option selected is validation
    if($data['course'] == '0'){
      $errors['course'] = "Invalid Course";
    }

    return $errors;
  }

}
