<?php

/**
 * Adds specific settings to the settings block
 *
 * @param navigation_node $nav Current navigation object
 */
function cicei_snatools_extends_navigation($nav) {
    global $PAGE;

    // If we are in context module add conditional edition page link
    /*if ($PAGE->context->contextlevel == CONTEXT_MODULE &&
            $PAGE->settingsnav &&
            $PAGE->course->useconditionals &&
            get_config('local_ciceiconditional', 'enableconditionals') &&
            has_capability('local/ciceiconditional:createconditionals', $PAGE->context)) {
        // Node with key 0 its the first settings node
        // Note: If menu doesn't appear try to uncomment the next line to see node keys
        //print_object($PAGE->settingsnav->get_children_key_list());
        $settingsnode = $PAGE->settingsnav->get(0);
        $icon = new pix_icon('lock_edit', '', 'local_ciceiconditional');
        $url = new moodle_url('/local/ciceiconditional/conditions.php', array('id' => $PAGE->cm->id, 'return' => true, 'sesskey' => sesskey()));
        $settingsnode->add(get_string('activitylocks', 'local_ciceiconditional'), $url, navigation_node::TYPE_SETTING, null, 'activitylocks', $icon);
    }*/
}

?>
