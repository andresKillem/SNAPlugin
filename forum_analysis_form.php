<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir . '/formslib.php');

class local_cicei_snatools_forum_analysis_form extends moodleform {

    function definition() {
        global $DB;

        $mform = & $this->_form;

        $course = $this->_customdata['course'];
        $forum = $this->_customdata['forum'];
        $searchcontext = $this->_customdata['searchcontext'];
        $id = $this->_customdata['id'];

        // Forums selection
        $mform->addElement('header', 'general', 'Forums');
        if  ($searchcontext == 'course') {
            $forums = array(0 => 'All forums');
            foreach($DB->get_records('forum', array('course' => $id)) as $forum) {
                $forums[$forum->id] = $forum->name;
            }
        } else {
            $forums = array($forum->id => $forum->name);
        }
        $attributes = array(
            'multiple' => 'multiple',
            //'style' => 'height: 200px;',
        );
        $mform->addElement('select', 'forumsids', 'Select forums to analyze', $forums, $attributes);
        if  ($searchcontext == 'course') {
            $mform->setDefault('forumsids', array(0));
        } else {
            $mform->setDefault('forumsids', array($forum->id));
        }

        // Discussions selection
        $mform->addElement('header', 'general', 'Discussions');
        if ($searchcontext == 'discussion') {
            $discussion = $DB->get_record('forum_discussions', array('id' => $id), '*', MUST_EXIST);
            $discussions = array($discussion->id => $discussion->name);
        } else {
            $discussions = array(
                0 => 'All discussions'
            );
            foreach ($DB->get_records('forum_discussions', array('course' => $course->id, 'forum' => $id)) as $discussion) {
                $discussions[$discussion->id] = $discussion->name;
            }
        }
        $attributes = array(
            'multiple' => 'multiple',
            //'style' => 'height: 200px;',
        );
        $mform->addElement('select', 'discussionsids', 'Select discussions to analyze', $discussions, $attributes);
        if ($searchcontext == 'discussion') {
            $mform->setDefault('discussionsids', array($discussion->id));
        } else {
            $mform->setDefault('discussionsids', array(0));
        }

        // Analysis configuration
        $mform->addElement('header', 'general', 'Analysis parameters');
        $functions = array(
            'collaboration' => "Collaboration between users",
        );
        $mform->addElement('select', 'function', 'Select function to execute', $functions);

        $views = array(
            'table' => "Table",
            'graph' => "Visualize nodes graph",
            'pajek' => "Pajek users array file and matrix file",
        );
        $mform->addElement('select', 'view', 'Select form of view', $views);

        // Add submit button
        $this->add_action_buttons(false, 'Analyze');
    }
}

?>
