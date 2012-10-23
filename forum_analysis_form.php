<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir . '/formslib.php');

class local_cicei_snatools_forum_analysis_form extends moodleform {

    function definition() {

        $mform = & $this->_form;

//        $course = $this->_customdata['course'];
//        $cm = $this->_customdata['cm'];
//        $coursecontext = $this->_customdata['coursecontext'];
//        $modcontext = $this->_customdata['modcontext'];
//        $forum = $this->_customdata['forum'];
//        $post = $this->_customdata['post'];

        $mform->addElement('header', 'general', '');
    }
}

?>
