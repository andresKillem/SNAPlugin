<?php


/*function local_cicei_snatools_extends_navigation(global_navigation $nav) {
    global $PAGE;

}*/

/**
 * Adds specific settings to the settings block
 *
 * @param settings_navigation $nav Current settings navigation object
 * @param context $context Current context
 */
function local_cicei_snatools_extends_settings_navigation(settings_navigation $nav, context $context) {
    // If plugin is enabled and user can use it add links
    if (get_config('local_cicei_snatools', 'enabled') && has_capability('local/cicei_snatools:use', $context)) {

        if ($context->contextlevel == CONTEXT_COURSE) {
            $url = new moodle_url('/local/cicei_snatools/forum_analysis.php', array('courseid' => $context->instanceid));
            $icon = new pix_icon('icon', '', 'local_cicei_snatools');
            // select node to add link
            $parentnode = $context->instanceid == 1 ? 'frontpage' : 'courseadmin';
            $nav->get($parentnode)->add("SNA - Analyze course forums", $url, navigation_node::TYPE_SETTING, null, 'forumcoursesna', $icon);
        }

        if ($context->contextlevel == CONTEXT_MODULE) {
            // Check if this is a forum coursemodule
            $cm = get_coursemodule_from_id('forum', $context->instanceid);
            if ($cm) {
                $d = optional_param('d', 0, PARAM_INT);
                $params = array(
                    'forumid' => $cm->instance,
                    'discussionid' => $d,
                );

                $text = $d ? "SNA - Analyze this discussion" : "SNA - Analyze this forum";
                $url = new moodle_url('/local/cicei_snatools/forum_analysis.php', $params);
                $icon = new pix_icon('icon', '', 'local_cicei_snatools');
                // 0 is the forum admin node
                $nav->get(0)->add($text, $url, navigation_node::TYPE_SETTING, null, 'forummodulesna', $icon);
            }
        }
    }
}
?>
