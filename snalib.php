<?php

interface SNA_Analyzer {
    function analyze();
}

interface SNA_Matrix {
    function getUsersVector();
    function getFixedDataMatrix();
    // table view
    function renderTable();
    // pajek view
    function getPajekUsersVector();
    function getPajekMatrix();
    // graphs views
    function renderNodesGraph();
    function renderBarsGraph();
}

abstract class SNA_Tool implements SNA_Analyzer, SNA_Matrix {
    protected $params;
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

    public function renderTable($return = false) {
        global $DB;
        global $OUTPUT;

        // Get max value in data array
        $max_value = SNAToolUtils::matrix_max($this->data_array);

        // Calculate user vector
        $this->getUsersVector();

        // Basic cell styles
        $cellstyle = 'text-align: center; color: #ffffcc; font-weight: bold; padding: 1px; line-height: 25px;';
        $firstcellstyle = 'background-color: #EEEEEE; background-image: none; text-align: center; padding: 1px;';
        $lastcellstyle = 'background-color: #EEEEEE; font-weight: bold; line-height: 25px; text-align: center; padding: 1px;';

        // 6 levels of colors
        $colors = array(
            'background-color: #FFF4E6;',
            'background-color: #F4CD97;',
            'background-color: #D69B79;',
            'background-color: #B8695B;',
            'background-color: #9A373D;',
            'background-color: #7C051F;'
        );

        // Build table
        $table = new html_table();
        //$table->attributes = array('class' => 'ars-table');

        // Header
        $cell = new html_table_cell();
        $cell->style = $firstcellstyle;
        $cell->text = "Receptor \ Emisor";
        $table->head[] = $cell;

        $users = $DB->get_records_list('user', 'id', array_values($this->users_array));
        foreach ($this->users_array as $userid1) {
            // Start a new row
            $row = new html_table_row();

            $user_pic = $OUTPUT->user_picture($users[$userid1], array('size' => 25, 'popup' => true));

            // First cell has user pic
            $cell = new html_table_cell();
            $cell->style = $firstcellstyle;
            $cell->text = $user_pic;
            $row->cells[] = $cell;
            $table->head[] = $cell;

            $totalcount = 0;
            // Add a cell for each user relation
            foreach ($this->users_array as $userid2) {
                if (isset($this->data_array[$userid1][$userid2])) {
                    $cellcontent = $this->data_array[$userid1][$userid2];
                    $logscaled = round((log($this->data_array[$userid1][$userid2]) / log($max_value + .0001)) * count($colors));
                    if ($logscaled >= count($colors)) $logscaled--;
                    else if ($logscaled <= 0) $logscaled = 1;
                    $cellcolor = $colors[$logscaled];
                    $totalcount += $cellcontent;
                } else {
                    $cellcontent = '';
                    $cellcolor = $colors[0];
                }
                $cell = new html_table_cell();
                $cell->style = $cellstyle.$cellcolor;
                $cell->text = $cellcontent;
                $row->cells[] = $cell;
            }

            // Last cell has total
            $cell = new html_table_cell();
            $cell->style = $lastcellstyle;
            $cell->text = $totalcount;
            $row->cells[] = $cell;

            // Add row to table
            $table->data[] = $row;
        }

        // last header cell
        $cell = new html_table_cell();
        $cell->style = $firstcellstyle;
        $cell->text = "Total";
        $table->head[] = $cell;

        // legend table
        $legend_table = new html_table();
        $legend_row = new html_table_row();
        foreach ($colors as $color) {
            $cell = new html_table_cell();
            $cell->style = $cellstyle.$color."width: 25px; height: 25px;";
            $cell->text = "";
            $legend_row->cells[] = $cell;
        }
        $legend_table->data[] = $legend_row;
        $cell = new html_table_cell();
        $cell->colspan = 6;
        $cell->text = "Legend";
        $legend_table->head[] = $cell;

        if ($return) {
            return html_writer::table($legend_table) . html_writer::table($table);
        } else {
            echo html_writer::table($legend_table) . html_writer::table($table);
        }
    }

    public function getPajekMatrix() {
        $this->getUsersVector();
        $this->getFixedDataMatrix();
        $usercounts = count($this->users_array);
        $contents = '';
        $contents .= "*Vertices $usercounts\n";
        $contents .= "*Matrix\n";

        foreach ($this->users_array as $userid1) {
            foreach ($this->users_array as $userid2) {
                $contents .= $this->fixed_data_array[$userid1][$userid2] . " ";
            }
            $contents = substr($contents, 0, -1);
            $contents .= "\n";
        }

        return $contents;
    }

    public function getPajekUsersVector() {
        global $DB;

        $this->getUsersVector();
        $users = $DB->get_records_list('user', 'id', array_values($this->users_array));
        $count = 1;
        $contents = '';
        foreach ($this->users_array as $userid) {
            $fullname = fullname($users[$userid]);
            $contents .= "$count \"$fullname\"\n";
            $count++;
        }

        return $contents;
    }

    public function renderNodesGraph() {
        global $DB, $OUTPUT;

        $max_value = SNAToolUtils::matrix_max($this->data_array);

        $this->getUsersVector();
        $users = $DB->get_records_list('user', 'id', array_values($this->users_array));

        $node_list = array();
        foreach ($this->users_array as $userid1) {
            $node = new stdClass();
            $node->id = (string)$userid1;
            $node->name = fullname($users[$userid1]);
            $node->data = new stdClass();
            $node->data->photohtml = $OUTPUT->user_picture($users[$userid1], array('size' => 25, 'popup' => true));
            $node->adjacencies = array();
            if (isset($this->data_array[$userid1])) {
                foreach ($this->data_array[$userid1] as $userid2 => $data) {
                    $inner_node = new stdClass();
                    $inner_node->nodeTo = (string)$userid2;
                    $inner_node->data = new stdClass();
                    if (isset($this->data_array[$userid2]) && isset($this->data_array[$userid2][$userid1])) {
                        $inner_node->data->{'$type'} = "double_arrow";
                        $inner_node->data->{'$direction'} = array((string)$userid2, (string)$userid1);
                        $inner_node->data->weight = (int)(($data + $this->data_array[$userid2][$userid1])/2);
                        $inner_node->data->weight_in = (int)$data;
                        $inner_node->data->weight_out = (int)$this->data_array[$userid2][$userid1];
                    } else {
                        $inner_node->data->{'$type'} = "arrow";
                        $inner_node->data->{'$direction'} = array((string)$userid2, (string)$userid1);
                        $inner_node->data->weight = (int)$data;
                    }
                    // log scaled value from 0 to 3
                    //$inner_node->data->logweight = round((log((int)$data) / log($max_value + .0001)) * 3);
                    $inner_node->data->logweight = (log((int)$data) / log($max_value + .0001)) * 3;
                    $node->adjacencies[] = $inner_node;
                }
            }
            $node_list[] = $node;
        }
        $graph = "RGraph";
        //$graph = "Hypertree";
        //$graph = "ForceDirected";
        $json = json_encode($node_list);
        include 'jit_graph.php';
    }

    public function renderBarsGraph() {
        global $DB, $OUTPUT, $COURSE;

        $this->getUsersVector();
        $this->getFixedDataMatrix();
        $users = $DB->get_records_list('user', 'id', array_values($this->users_array));

        $roles_ids = $DB->get_records_list('role','shortname', array('manager', 'editingteacher', 'teacher'));
        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        $teachers = array_keys(get_role_users(array_keys($roles_ids), $context, true, 'u.id'));

        $names = array();
        $isteacher = array();
        $labels= array();
        $comments_made = array();
        $comments_received = array();

        foreach ($this->users_array as $userid1) {
            $names[$userid1] = fullname($users[$userid1]);
            $isteacher[$userid1] = in_array($userid1, $teachers) ? 1 : 0;
            $labels[$userid1] = $OUTPUT->user_picture($users[$userid1], array('size' => 23));

            $made = 0;
            foreach ($this->fixed_data_array as $row) {
                $made += $row[$userid1];
            }
            $comments_made[$userid1] = $made;

            $received = 0;
            foreach ($this->fixed_data_array[$userid1] as $userid2 => $data) {
                $received += $data;
            }
            $comments_received[$userid1] = $received;
        }

        $user_ids = json_encode(array_values($this->users_array));
        $names = json_encode(array_values($names));
        $isteacher = json_encode(array_values($isteacher));
        $ticks = json_encode(array_values($labels));
        $serie1 = json_encode(array_values($comments_made));
        $serie2 = json_encode(array_values($comments_received));

        $forumsids = implode(',', $this->params->forumsids);
        $discussionsids = implode(',', $this->params->discussionsids);

        include 'jqplot_graph.php';
    }

    // Factory to create objects
    static function create($function, $params = array()) {
        switch($function) {
            case 'collaboration':
                switch ($params->searchcontext) {
                    case 'course':
                        $tool = new SNA_CourseCollaboration($params->course, $params->forumsids);
                        break;
                    case 'forum':
                        $tool = new SNA_ForumCollaboration($params->forum, $params->discussionsids);
                        break;
                    case 'discussion':
                        $tool = new SNA_DiscussionCollaboration($params->discussion);
                        break;
                    default:
                        $tool = NULL;
                        break;
                }
        }
        if ($tool !== NULL) {
            $tool->params = $params;
        }
        return $tool;
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
            if (in_array(0, $this->forumsids) || empty($this->forumsids)) {
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

    // Function to search max value in a matrix
    static function matrix_max(array $matrix) {
        $max = ~PHP_INT_MAX;
        foreach ($matrix as $userid1 => $values) {
            foreach ($values as $userid2 => $value) {
                if ($value > $max) {
                    $max = $value;
                }
            }
        }
        return $max;
    }
}
?>
