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
        $parent_forum = $this->_customdata['forum'];
        $searchcontext = $this->_customdata['searchcontext'];
        $id = $this->_customdata['id'];

        // Forums selection
        $mform->addElement('header', 'general', get_string('forums', 'local_cicei_snatools'));
        $forums = array(0 => get_string('all_forums', 'local_cicei_snatools'));
        foreach ($DB->get_records('forum', array('course' => $course->id)) as $forum) {
            $forums[$forum->id] = $forum->name;
        }
        $mform->addElement('select', 'forumsids', get_string('select_forums', 'local_cicei_snatools'), $forums, array('multiple' => 'multiple'));
        if  ($searchcontext == 'course') {
            $mform->setDefault('forumsids', array(0));
        } else {
            $mform->setDefault('forumsids', array($parent_forum->id));
        }

        // Groups
        $mform->addElement('header', 'general', get_string('groups', 'local_cicei_snatools'));
        $groups = array(0 => get_string('all_groups', 'local_cicei_snatools'));
        foreach (groups_get_all_groups($course->id) as $group) {
            $groups[$group->id] = $group->name;
        }
        $mform->addElement('select', 'groupsids', get_string('select_groups', 'local_cicei_snatools'), $groups, array('multiple' => 'multiple'));
        $mform->setDefault('groupsids', array(0));

        // Discussions selection
        $mform->addElement('header', 'general', get_string('discussions', 'local_cicei_snatools'));
        $discussions = array(0 => get_string('all_discussions', 'local_cicei_snatools'));
        // If forums were been selected, discussions will be loaded from that forums
        $selected_forums = optional_param_array('forumsids', array(0 => 0), PARAM_INT);
        if (!in_array(0, $selected_forums)) {
            foreach ($DB->get_records_list('forum_discussions', 'forum', $selected_forums) as $discussion) {
                $discussions[$discussion->id] = $discussion->name;
            }
        } else {
            // By default, discussions from current contextual forum are loaded
            $conditions = array('course' => $course->id);
            // If not in course searchcontext, filter by forum
            if ($searchcontext != 'course') {
                $conditions['forum'] = $parent_forum->id;
            }
            foreach ($DB->get_records('forum_discussions', $conditions) as $discussion) {
                $discussions[$discussion->id] = $discussion->name;
            }
        }
        $mform->addElement('select', 'discussionsids', get_string('select_discussions', 'local_cicei_snatools'), $discussions, array('multiple' => 'multiple'));
        if ($searchcontext == 'discussion') {
            $mform->setDefault('discussionsids', array($discussion->id));
        } else {
            $mform->setDefault('discussionsids', array(0));
        }

        // Analysis configuration
        $mform->addElement('header', 'general', get_string('analysys_section', 'local_cicei_snatools'));
        $functions = array(
            'collaboration' => get_string('collaboration_analysys', 'local_cicei_snatools'),
        );
        $mform->addElement('select', 'function', get_string('select_function', 'local_cicei_snatools'), $functions);

        $views = array(
            'table' => get_string('view_table', 'local_cicei_snatools'),
            'bars' => get_string('view_bars', 'local_cicei_snatools'),
            'nodes'  => get_string('view_nodes', 'local_cicei_snatools'),
            'nodes_alt' => get_string('view_nodes_alt', 'local_cicei_snatools'),
            'pajek' => get_string('view_pajek', 'local_cicei_snatools'),
        );
        $mform->addElement('select', 'view', get_string('select_view', 'local_cicei_snatools'), $views);

        // Add submit button
        $this->add_action_buttons(false, get_string('submit_button', 'local_cicei_snatools'));
    }
}

?>
