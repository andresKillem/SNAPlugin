<?php

require_once('../../config.php');
require_once('./snalib.php');

global $PAGE, $DB, $OUTPUT, $CFG;

// Input params
$courseid = optional_param('courseid', 0, PARAM_INT);
$forumid = optional_param('forumid', 0, PARAM_INT);
$discussionid = optional_param('discussionid', 0, PARAM_INT);

// Build url
$params = array();
if ($courseid) {
    $params['courseid'] = $courseid;
}
if ($forumid) {
    $params['forumid'] = $forumid;
}
if ($discussionid) {
    $params['discussionid'] = $discussionid;
}
$PAGE->set_url('/local/cicei_snatools/forum_analysis.php', $params);

// Get course and context
if ($forumid) {
    $cm = get_coursemodule_from_instance('forum', $forumid);
    $context = context_module::instance($cm->id);
    $course = $DB->get_record("course", array("id" => $cm->course));
} else {
    $context = context_course::instance($courseid);
    $course = $DB->get_record("course", array("id" => $courseid));
    $cm = NULL;
}

// Check login
require_course_login($course, true, $cm);

// Configure page
$title = "Forum Analysis tool";
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('admin');
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

$mform_post = new local_cicei_snatools_forum_analysis_form('forum_analysys.php', array(/*'course'=>$course, 'cm'=>$cm, 'coursecontext'=>$coursecontext, 'modcontext'=>$modcontext, 'forum'=>$forum, 'post'=>$post*/));

$mform_post->set_data(array());

if ($fromform = $mform_post->get_data()) {
    print_object($fromform);
}
$mform_post->display();

// Analysis results

// End page
echo $OUTPUT->footer($course);

?>
