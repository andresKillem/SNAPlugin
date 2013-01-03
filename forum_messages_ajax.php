<?php
require_once('../../config.php');

global $PAGE, $DB, $OUTPUT, $CFG;

// Input params
$ajax = required_param('ajax', PARAM_BOOL);
$searchcontext = required_param('searchcontext', PARAM_ALPHANUMEXT);
$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$forumsids = optional_param('forumsids', '', PARAM_RAW_TRIMMED);
$forumsids = explode(',', $forumsids);
$discussionsids = optional_param('discussionsids', '', PARAM_RAW_TRIMMED);
$discussionsids = explode(',', $discussionsids);

// Get course and context
switch ($searchcontext) {
    case 'course':
        $context = context_course::instance($id);
        $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
        $forum = NULL;
        $cm = NULL;
        break;
    case 'forum':
        $cm = get_coursemodule_from_instance('forum', $id);
        $context = context_module::instance($cm->id);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $forum = $DB->get_record('forum', array('id' => $id), '*', MUST_EXIST);
        break;
    case 'discussion':
        $discussion = $DB->get_record('forum_discussions', array('id' => $id), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('forum', $discussion->forum);
        $context = context_module::instance($cm->id);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $forum = $DB->get_record('forum', array('id' => $discussion->forum), '*', MUST_EXIST);
        break;
    default:
        die("Input error");
}

// Check login
require_course_login($course, true, $cm);

if ($ajax) {
    switch ($searchcontext) {
        case 'course':
            if (in_array(0, $forumsids) || empty($forumsids)) {
                $forums = $DB->get_records('forum', array('course' => $course->id));
            } else {
                $forums = $DB->get_records_list('forum', 'id', $forumsids);
            }
            $discussions = $DB->get_records_list('forum_discussions', 'forum', array_keys($forums));
            break;
        case 'forum':
            $forums = array($forum->id => $forum);
            if (in_array(0, $discussionsids) || empty($discussionsids)) {
                $discussions = $DB->get_records('forum_discussions', array('forum' => $forum->id));
            } else {
                $discussions = $DB->get_records_list('forum_discussions', 'id', $discussionsids);
            }
            break;
        case 'discussion':
            $forums = array($forum->id => $forum);
            $discussions = array($discussion->id => $discussion);
            break;
        default:
            die("Input error");
    }

    $posts = array();
    foreach ($discussions as $id => $value) {
        $posts = array_merge($posts, $DB->get_records('forum_posts', array('discussion' => $id, 'userid' => $userid), 'created ASC'));
    }

    $content = '';
    foreach ($posts as $post) {
        ?>
        <div class = "forumpost clearfix">
            <div class = "row header clearfix">
                <div class = "topic" style="margin-left: 0px;">
                    <div class = "subject">
                        <a href="<?php echo $CFG->wwwroot . '/mod/forum/discuss.php?d=' . $discussions[$post->discussion]->id . '#p' . $post->id; ?>" target="_blank">
                            <?php echo $post->subject; ?>
                        </a>
                    </div>
                    <div class = "author">
                        <a href="<?php echo $CFG->wwwroot . '/mod/forum/discuss.php?d=' . $discussions[$post->discussion]->id; ?>" target="_blank">
                            <?php echo $discussions[$post->discussion]->name; ?>
                        </a>
                        -
                        <a href="<?php echo $CFG->wwwroot . '/mod/forum/view.php?f=' . $forums[$discussions[$post->discussion]->forum]->id; ?>" target="_blank">
                            <?php echo $forums[$discussions[$post->discussion]->forum]->name; ?>
                        </a>
                    </div>
                </div>
            </div>
            <div class = "row maincontent clearfix">
                <div class = "no-overflow">
                    <div class = "content">
                        <div class = "posting fullpost">
                            <?php echo $post->message; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    echo $content;
    return;
} else {
    die('Not allowed');
//redirect("course/view.php?id= $course->id", 'Not allowed');
}
?>