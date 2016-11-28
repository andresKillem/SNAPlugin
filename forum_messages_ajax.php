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
$groupsids = optional_param('groupsids', '', PARAM_RAW_TRIMMED);
$groupsids = explode(',', $groupsids);

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

// Basic JSON encoded response object
$response = new stdClass();
$response->replies = array();
$response->responses = array();

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

    $replies = array();
    foreach ($discussions as $id => $value) {
        $replies = array_merge($replies, $DB->get_records('forum_posts', array('discussion' => $id, 'userid' => $userid), 'created ASC'));
    }

    $responses = array();
    foreach ($discussions as $id => $value) {
        foreach ($replies as $post) {
            if (in_array(0, $groupsids) || empty($groupsids)) {
                $where = 'discussion = ? AND parent = ? AND userid != ?';
                $params = array($id, $post->id, $userid);
            } else {
                $where = 'discussion = ? AND parent = ? AND userid != ? AND userid IN (SELECT userid FROM {groups_members} WHERE groupid IN (?))';
                $params = array($id, $post->id, $userid, implode(',', $groupsids));
            }
            $responses = array_merge($responses, $DB->get_records_select('forum_posts', $where , $params, 'created ASC'));
        }
    }

    foreach (array('replies' => $replies, 'responses' => $responses) as $name => $posts) :
        ob_start();
        foreach ($posts as $post) : ?>
            <div class = "forumpost clearfix">
                <div class = "row header clearfix">
                    <div class = "topic" style="margin-left: 0px;">
                        <div class="left picture">
                            <?php
                            $postuser = $DB->get_record('user', array('id'=>$post->userid));
                            echo $OUTPUT->user_picture($postuser, array('courseid'=>$course->id));
                            ?>
                        </div>
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
                                <?php
                                // format the post body
                                $post->message = file_rewrite_pluginfile_urls($post->message, 'pluginfile.php', $context->id, 'mod_forum', 'post', $post->id);
                                $options = new stdClass;
                                $options->para = false;
                                $options->trusted = $post->messagetrust;
                                $options->context = $context;
                                $post->message = format_text($post->message, $post->messageformat, $options, $course->id);
                                echo $post->message;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    <?php
        endforeach;
        $response->$name = ob_get_clean();
    endforeach;
}
echo json_encode($response);