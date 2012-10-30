<?php

interface SNA_Analyzer {
    function analyze();
}

interface SNA_Matrix {
    function getUsersVector();
    function getFixedDataMatrix();
    function renderUsersVector();
    function renderDataMatrix();
}

abstract class SNA_Tool implements SNA_Analyzer, SNA_Matrix {
    protected $course;
    protected $users_array = array();
    protected $data_array = array();
    protected $fixed_data_array = array();

    /**
     * Gets an array of all users present in this analysis
     * @return type
     */
    public function getUsersVector() {
        if (empty($this->users_array)) {
            foreach ($this->data_array as $userid1 => $data_row) {
                if (!in_array($userid1, $this->users_array)) {
                    $this->users_array[] = $userid1;
                }
                foreach ($data_row as $userid2 => $data_cell) {
                    if (!in_array($userid2, $this->users_array)) {
                        $this->users_array[] = $userid2;
                    }
                }
            }
            sort($this->users_array);
        }
        return $this->users_array;
    }

    /**
     * Get fixed matrix data with all users present in this analysis.
     * Not found interactions will be filled with 0 values.
     * @return type
     */
    public function getFixedDataMatrix() {
        if (empty($this->fixed_data_array)) {
            $this->fixed_data_array = $this->data_array;
            foreach ($this->users_array as $userid1) {
                if (!isset($this->fixed_data_array[$userid1])) {
                    $this->fixed_data_array[$userid1] = array();
                }
                foreach ($this->users_array as $userid2) {
                    if (!isset($this->fixed_data_array[$userid1][$userid2])) {
                        $this->fixed_data_array[$userid1][$userid2] = 0;
                    }
                }
            }
        }
        return $this->fixed_data_array;
    }

    public function renderDataMatrix($return = false) {
        global $DB;
        global $OUTPUT;

        //print_object($this->users_array);
        //print_object($this->data_array);
        $this->getUsersVector();
        //$this->getFixedDataMatrix();
        //print_object($this->users_array);
        //print_object($this->data_array);

        $table = new html_table();
        $table->head[] = "";
        //$table->align = array("CENTER", "LEFT");

        /*foreach ($this->users_array as $userid1) {
            $table->head[] = $userid1;
            $row = array();
            $row[] = "<strong>$userid1</strong>";
            foreach ($this->users_array as $userid2) {
                 $row[] = $this->data_array[$userid1][$userid2];
            }
            $table->data[] = $row;
        }*/

        $users = $DB->get_records_list('user', 'id', array_values($this->users_array));
        foreach ($this->users_array as $userid1) {
            $user_pic = $OUTPUT->user_picture($users[$userid1], array('size' => 25, 'popup' => true));
            $table->head[] = $user_pic;
            $row = array();
            $row[] = $user_pic;
            foreach ($this->users_array as $userid2) {
                $style = 'text-align: center; vertical-align: middle;';
                if (isset($this->data_array[$userid1][$userid2])) {
                    $style .= 'font-weight: bold; ';
                    $cellcontent = $this->data_array[$userid1][$userid2];
                } else {
                    $cellcontent = ' ';
                }
                $row[] = html_writer::tag('div', $cellcontent, array('style' => $style));
            }
            $table->data[] = $row;
        }

        if ($return) {
            return html_writer::table($table);
        } else {
            echo html_writer::table($table);
        }
    }

    public function renderUsersVector() {

    }

    // Factory to create objects
    static function create($function, $params = array()) {
        switch($function) {
            case 'collaboration':
                switch ($params->searchcontext) {
                    case 'course':
                        return new SNA_CourseCollaboration($params->course, $params->forumsids);
                    case 'forum':
                        return new SNA_ForumCollaboration($params->forum, $params->discussionsids);
                    case 'discussion':
                        return new SNA_DiscussionCollaboration($params->discussion);
                }
        }
        return NULL;
    }
}

/**
 * Collaboration for a course forums (ome, more or all)
 */
class SNA_CourseCollaboration extends SNA_Tool {
    protected $forumsids;

    function __construct($course, $forumsids = array()) {
        $this->course = $course;
        $this->forumsids = $forumsids;
    }

    public function analyze() {
        global $DB;

        try {
            // data will be an alias for data_array property;
            $data = & $this->data_array;

            // Select forums to analyze
            if (in_array(0, $this->forumsids) || is_empty($forumsids)) {
                $forums = $DB->get_records('forum', array('course' => $this->course->id), '', 'id');
            } else {
                $forums = $DB->get_records_list('forum', 'id', $this->forumsids);
            }

            foreach ($forums as $forum) {
                $forum_collaboration = new SNA_ForumCollaboration($forum);
                $result = $forum_collaboration->analyze();
                if (empty($result)) {
                    // Merge this results with main results
                    $data = SNAToolUtils::merge_and_sum($data, $forum_collaboration->getFixedDataMatrix());
                } else {
                    throw new Exception($result);
                }
            }

            return "";
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
}

/**
 * Collaboration for a forum (one, more or all discussions)
 */
class SNA_ForumCollaboration extends SNA_Tool {
    protected $forum;
    protected $discussionsids;

    function __construct($forum, $discussionsids = array()) {
        $this->forum = $forum;
        $this->discussionsids = $discussionsids;
    }

    public function analyze() {
        global $DB;

        try {
            // data will be an alias for data_array property;
            $data = & $this->data_array;

            // Select discussions to analyze
            if (in_array(0, $this->discussionsids) || empty($this->discussionsids)) {
                // All discussion
                $discussions = $DB->get_records('forum_discussions', array('forum' => $this->forum->id));
            } else {
                // Selected discussions id list
                $discussions = $DB->get_records_list('forum_discussions', 'id', $this->discussionsids);
            }

            // For each discussion, count collaboration
            foreach ($discussions as $discussion) {
                $discussion_collaboration = new SNA_DiscussionCollaboration($discussion);
                $result = $discussion_collaboration->analyze();
                if (empty($result)) {
                    // Merge this results with main results
                    $data = SNAToolUtils::merge_and_sum($data, $discussion_collaboration->getFixedDataMatrix());
                } else {
                    throw new Exception($result);
                }
            }

            return "";
        } catch(Exception $ex) {
            return $ex->getMessage();
        }
    }
}

/**
 * Collaboration for a discussion
 */
class SNA_DiscussionCollaboration extends SNA_Tool {
    protected $discussion;

    function __construct($discussion) {
        $this->discussion = $discussion;
    }

    public function analyze() {
        global $DB;

        try {
            // data will be an alias for data_array property;
            $data = & $this->data_array;

            // For each post in discussion, count collaboration
            $posts = $DB->get_records('forum_posts', array('discussion' => $this->discussion->id), 'created ASC');
            foreach ($posts as $post) {
                if ($post->parent != 0) {
                    $parentpost = $posts[$post->parent];

                    // Count collaboration only if user is not replying to himself
                    if ($parentpost->userid != $post->userid) {
                        // Initialize array with user ids
                        if (!isset($data[$parentpost->userid]) || !isset($data[$parentpost->userid][$post->userid])) {
                            $data[$parentpost->userid][$post->userid] = 0;
                        }
                        $data[$parentpost->userid][$post->userid] += 1;
                    }
                }
            }

            return "";
        } catch(Exception $ex) {
            return $ex->getMessage();
        }
    }
}

/**
 * Utils static functions namespace
 */
abstract class SNAToolUtils {

    // Function to merge data matrices
    static function merge_and_sum(array $matrix1, array $matrix2) {
        /*
         * matrix structure:
         * userid = array ( userid => count, userid => count )
         */
        foreach ($matrix2 as $userid1 => $collaboration) {
            if (!isset($matrix1[$userid1])) {
                $matrix1[$userid1] = array();
            }
            foreach ($collaboration as $userid2 => $count) {
                if (!isset($matrix1[$userid1][$userid2])) {
                    $matrix1[$userid1][$userid2] = 0;
                }
                $matrix1[$userid1][$userid2] += $count;
            }
        }
        return $matrix1;
    }
}
?>
