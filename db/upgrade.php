<?php

function xmldb_local_cicei_snatools_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    /*if ($oldversion < X) {

        // local savepoint reached
        upgrade_plugin_savepoint(true, X, 'local', 'cicei_snatools');
    }*/

    return true;

}

?>
