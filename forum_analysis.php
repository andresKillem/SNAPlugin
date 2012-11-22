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
$page_url= new moodle_url('/local/cicei_snatools/forum_analysis.php', $params);
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
        $title = "Forum Analysis tool - $course->fullname";
        break;
    case 'forum':
        $cm = get_coursemodule_from_instance('forum', $id);
        $context = context_module::instance($cm->id);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $forum = $DB->get_record('forum', array('id' => $id), '*', MUST_EXIST);
        $title = "Forum Analysis tool - $forum->name";
        break;
    case 'discussion':
        $discussion = $DB->get_record('forum_discussions', array('id' => $id), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('forum', $discussion->forum);
        $context = context_module::instance($cm->id);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $forum = $DB->get_record('forum', array('id' => $discussion->forum), '*', MUST_EXIST);
        $title = "Forum Analysis tool - $discussion->name";
        break;
    default:
        notice("Input error");
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
    notice("CICEI SNA Tools is disabled", "/course/view.php?id=$course->id");
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
$mform_post = new local_cicei_snatools_forum_analysis_form($page_url, $custom_data);

// Set form defaults
//$mform_post->set_data(array());

// show form
$mform_post->display();

// Analysis results
// Try to get data form form
if ($fromform = $mform_post->get_data()) {
    //print_object($fromform);
    $fromform->searchcontext = $searchcontext;
    $fromform->course = $course;
    $fromform->forum = $forum;
    $fromform->discussion = $discussion;
    // Init analysis object
    $tool = SNA_Tool::create($fromform->function, $fromform);
    // Run analysis
    $result = $tool->analyze();
    if (empty($result)) {
        echo $OUTPUT->heading("Results");

        // Select view
        switch($fromform->view) {
            case 'table':
                echo html_writer::start_tag('center');
                echo html_writer::tag('p', "How to read these results: each row represents a person collaboration with other people as the number of replies obtained to his forum posts");
                $tool->renderTable();
                echo html_writer::end_tag('center');
                break;
            case 'pajek':
                echo html_writer::tag('p', "Hint: copy and paste these contents into a file and open it with Pajek. Users Array is not needed to represent the matrix.");
                $pajekusersvector = $tool->getPajekUsersVector();
                $pajekmatrix = $tool->getPajekMatrix();

                echo $OUTPUT->heading("Users array");
                echo $OUTPUT->box("<pre><code>$pajekusersvector</code></pre>");

                echo $OUTPUT->heading("Matrix");
                echo $OUTPUT->box("<pre><code>$pajekmatrix</code></pre>");
                break;
            case 'graph':
                $tool->renderGraph();
                break;
        }
    } else {
        notify($result);
    }
}

// End page
echo $OUTPUT->footer($course);

?>
