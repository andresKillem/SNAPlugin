<?php

require_once('../../config.php');
require_once('./snalib.php');

global $PAGE, $DB, $OUTPUT, $CFG;

// Input params
$searchcontext = required_param('searchcontext', PARAM_ALPHANUMEXT);
$id = required_param('id', PARAM_INT);

// Build url
$params = array(
    'searchcontext' => $searchcontext,
    'id' => $id,
);
$page_url= new moodle_url('/local/cicei_snatools/forum_analysis.php#sna-results', $params);
$PAGE->set_url($page_url);

// Init vars
$context = NULL;
$cm = NULL;
$course = NULL;
$forum = NULL;
$discussion = NULL;
// Get course and context
switch ($searchcontext) {
    case 'course':
        $context = context_course::instance($id);
        $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
        $forum = NULL;
        $cm = NULL;
        $title = get_string('page_title', 'local_cicei_snatools', $course->fullname);
        break;
    case 'forum':
        $cm = get_coursemodule_from_instance('forum', $id);
        $context = context_module::instance($cm->id);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $forum = $DB->get_record('forum', array('id' => $id), '*', MUST_EXIST);
        $title = get_string('page_title', 'local_cicei_snatools', $course->fullname);
        break;
    case 'discussion':
        $discussion = $DB->get_record('forum_discussions', array('id' => $id), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('forum', $discussion->forum);
        $context = context_module::instance($cm->id);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $forum = $DB->get_record('forum', array('id' => $discussion->forum), '*', MUST_EXIST);
        $title = get_string('page_title', 'local_cicei_snatools', $course->fullname);
        break;
    default:
        notice(get_string('error', 'moodle'));
}

// Check login
require_course_login($course, true, $cm);

// Configure page
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('base');
$PAGE->navbar->add($title);

// Begin page
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// Check if plugin is enabled
if (!get_config('local_cicei_snatools', 'enabled')) {
    notice(get_string('plugin_disabled', 'local_cicei_snatools'), "/course/view.php?id=$course->id");
}

// Check if user has capability to use the plugin
require_capability('local/cicei_snatools:use', $context);

// Form to config analysis
require_once('forum_analysis_form.php');

// Create form
$custom_data = array(
    'course' => $course,
    'forum' => $forum,
    'searchcontext' => $searchcontext,
    'id' => $id,
);
$mform_post = new local_cicei_snatools_forum_analysis_form($page_url->out(false), $custom_data);

// Set form defaults
//$mform_post->set_data(array());

// show form
$mform_post->display();

// Analysis results
// Try to get data form form
if ($fromform = $mform_post->get_data()) {
    $fromform->searchcontext = $searchcontext;
    $fromform->course = $course;
    $fromform->forum = $forum;
    $fromform->discussion = $discussion;
    // Init analysis object
    $tool = SNA_Tool::create($fromform->function, $fromform);
    // Run analysis
    $result = $tool->analyze();
    if (empty($result)) {
        echo html_writer::div('<br><br>', '', array('id' => 'sna-results'));

        echo $OUTPUT->heading(get_string('results_title', 'local_cicei_snatools'), 2);

        // Select view
        switch($fromform->view) {
            case 'table':
                $tool->renderTable();
                break;
            case 'pajek':
                $pajekusersvector = $tool->getPajekUsersVector();
                $pajekmatrix = $tool->getPajekMatrix();

                echo $OUTPUT->heading(get_string('pajek_users_array', 'local_cicei_snatools'));
                echo $OUTPUT->box("<pre><code>$pajekusersvector</code></pre>");

                echo $OUTPUT->heading(get_string('pajek_matrix', 'local_cicei_snatools'));
                echo $OUTPUT->box("<pre><code>$pajekmatrix</code></pre>");
                break;
            case 'nodes':
                $tool->renderNodesGraph();
                break;
            case 'nodes_alt':
                $tool->renderNodesGraphAlt();
                break;
            case 'bars':
                $tool->renderBarsGraph();
                break;
        }
    } else {
        notify($result);
    }
}

// End page
echo $OUTPUT->footer($course);

?>
