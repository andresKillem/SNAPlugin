<?php
global $CFG, $OUTPUT;

class cicei_snatools_html_writer {
    /**
     * Renders HTML table
     *
     * This method may modify the passed instance by adding some default properties if they are not set yet.
     * If this is not what you want, you should make a full clone of your data before passing them to this
     * method. In most cases this is not an issue at all so we do not clone by default for performance
     * and memory consumption reasons.
     *
     * @param html_table $table data to be rendered
     * @return string HTML code
     */
    public static function table(html_table $table) {
        // prepare table data and populate missing properties with reasonable defaults
        if (!empty($table->align)) {
            foreach ($table->align as $key => $aa) {
                if ($aa) {
                    $table->align[$key] = 'text-align:'. fix_align_rtl($aa) .';';  // Fix for RTL languages
                } else {
                    $table->align[$key] = null;
                }
            }
        }
        if (!empty($table->size)) {
            foreach ($table->size as $key => $ss) {
                if ($ss) {
                    $table->size[$key] = 'width:'. $ss .';';
                } else {
                    $table->size[$key] = null;
                }
            }
        }
        if (!empty($table->wrap)) {
            foreach ($table->wrap as $key => $ww) {
                if ($ww) {
                    $table->wrap[$key] = 'white-space:nowrap;';
                } else {
                    $table->wrap[$key] = '';
                }
            }
        }
        if (!empty($table->head)) {
            foreach ($table->head as $key => $val) {
                if (!isset($table->align[$key])) {
                    $table->align[$key] = null;
                }
                if (!isset($table->size[$key])) {
                    $table->size[$key] = null;
                }
                if (!isset($table->wrap[$key])) {
                    $table->wrap[$key] = null;
                }

            }
        }
        if (empty($table->attributes['class'])) {
            $table->attributes['class'] = 'generaltable';
        }
        if (!empty($table->tablealign)) {
            $table->attributes['class'] .= ' boxalign' . $table->tablealign;
        }

        // explicitly assigned properties override those defined via $table->attributes
        $table->attributes['class'] = trim($table->attributes['class']);
        $attributes = array_merge($table->attributes, array(
                'id'            => $table->id,
                'width'         => $table->width,
                'summary'       => $table->summary,
                'cellpadding'   => $table->cellpadding,
                'cellspacing'   => $table->cellspacing,
            ));
        $output = html_writer::start_tag('table', $attributes) . "\n";

        $countcols = 0;

        if (!empty($table->head)) {
            $countcols = count($table->head);

            $output .= html_writer::start_tag('thead', array()) . "\n";
            $output .= html_writer::start_tag('tr', array()) . "\n";
            $keys = array_keys($table->head);
            $lastkey = end($keys);

            foreach ($table->head as $key => $footing) {
                // Convert plain string headings into html_table_cell objects
                if (!($footing instanceof html_table_cell)) {
                    $headingtext = $footing;
                    $footing = new html_table_cell();
                    $footing->text = $headingtext;
                    $footing->header = true;
                }

                if ($footing->header !== false) {
                    $footing->header = true;
                }

                if ($footing->header && empty($footing->scope)) {
                    $footing->scope = 'col';
                }

                $footing->attributes['class'] .= ' header c' . $key;
                if (isset($table->headspan[$key]) && $table->headspan[$key] > 1) {
                    $footing->colspan = $table->headspan[$key];
                    $countcols += $table->headspan[$key] - 1;
                }

                if ($key == $lastkey) {
                    $footing->attributes['class'] .= ' lastcol';
                }
                if (isset($table->colclasses[$key])) {
                    $footing->attributes['class'] .= ' ' . $table->colclasses[$key];
                }
                $footing->attributes['class'] = trim($footing->attributes['class']);
                $attributes = array_merge($footing->attributes, array(
                        'style'     => $table->align[$key] . $table->size[$key] . $footing->style,
                        'scope'     => $footing->scope,
                        'colspan'   => $footing->colspan,
                    ));

                $tagtype = 'td';
                if ($footing->header === true) {
                    $tagtype = 'th';
                }
                $output .= html_writer::tag($tagtype, $footing->text, $attributes) . "\n";
            }
            $output .= html_writer::end_tag('tr') . "\n";
            $output .= html_writer::end_tag('thead') . "\n";

            if (empty($table->data)) {
                // For valid XHTML strict every table must contain either a valid tr
                // or a valid tbody... both of which must contain a valid td
                $output .= html_writer::start_tag('tbody', array('class' => 'empty'));
                $output .= html_writer::tag('tr', html_writer::tag('td', '', array('colspan'=>count($table->head))));
                $output .= html_writer::end_tag('tbody');
            }
        }

        if (!empty($table->data)) {
            $oddeven    = 1;
            $keys       = array_keys($table->data);
            $lastrowkey = end($keys);
            $output .= html_writer::start_tag('tbody', array());

            foreach ($table->data as $key => $row) {
                if (($row === 'hr') && ($countcols)) {
                    $output .= html_writer::tag('td', html_writer::tag('div', '', array('class' => 'tabledivider')), array('colspan' => $countcols));
                } else {
                    // Convert array rows to html_table_rows and cell strings to html_table_cell objects
                    if (!($row instanceof html_table_row)) {
                        $newrow = new html_table_row();

                        foreach ($row as $cell) {
                            if (!($cell instanceof html_table_cell)) {
                                $cell = new html_table_cell($cell);
                            }
                            $newrow->cells[] = $cell;
                        }
                        $row = $newrow;
                    }

                    $oddeven = $oddeven ? 0 : 1;
                    if (isset($table->rowclasses[$key])) {
                        $row->attributes['class'] .= ' ' . $table->rowclasses[$key];
                    }

                    $row->attributes['class'] .= ' r' . $oddeven;
                    if ($key == $lastrowkey) {
                        $row->attributes['class'] .= ' lastrow';
                    }

                    $output .= html_writer::start_tag('tr', array('class' => trim($row->attributes['class']), 'style' => $row->style, 'id' => $row->id)) . "\n";
                    $keys2 = array_keys($row->cells);
                    $lastkey = end($keys2);

                    $gotlastkey = false; //flag for sanity checking
                    foreach ($row->cells as $key => $cell) {
                        if ($gotlastkey) {
                            //This should never happen. Why do we have a cell after the last cell?
                            mtrace("A cell with key ($key) was found after the last key ($lastkey)");
                        }

                        if (!($cell instanceof html_table_cell)) {
                            $mycell = new html_table_cell();
                            $mycell->text = $cell;
                            $cell = $mycell;
                        }

                        if (($cell->header === true) && empty($cell->scope)) {
                            $cell->scope = 'row';
                        }

                        if (isset($table->colclasses[$key])) {
                            $cell->attributes['class'] .= ' ' . $table->colclasses[$key];
                        }

                        $cell->attributes['class'] .= ' cell c' . $key;
                        if ($key == $lastkey) {
                            $cell->attributes['class'] .= ' lastcol';
                            $gotlastkey = true;
                        }
                        $tdstyle = '';
                        $tdstyle .= isset($table->align[$key]) ? $table->align[$key] : '';
                        $tdstyle .= isset($table->size[$key]) ? $table->size[$key] : '';
                        $tdstyle .= isset($table->wrap[$key]) ? $table->wrap[$key] : '';
                        $cell->attributes['class'] = trim($cell->attributes['class']);
                        $tdattributes = array_merge($cell->attributes, array(
                                'style' => $tdstyle . $cell->style,
                                'colspan' => $cell->colspan,
                                'rowspan' => $cell->rowspan,
                                'id' => $cell->id,
                                'abbr' => $cell->abbr,
                                'scope' => $cell->scope,
                            ));
                        $tagtype = 'td';
                        if ($cell->header === true) {
                            $tagtype = 'th';
                        }
                        $output .= html_writer::tag($tagtype, $cell->text, $tdattributes) . "\n";
                    }
                }
                $output .= html_writer::end_tag('tr') . "\n";
            }
            $output .= html_writer::end_tag('tbody') . "\n";
        }

        if (!empty($table->footer)) {
            $countcols = count($table->footer);

            $output .= html_writer::start_tag('tfoot', array()) . "\n";
            $output .= html_writer::start_tag('tr', array()) . "\n";
            $keys = array_keys($table->footer);
            $lastkey = end($keys);

            foreach ($table->footer as $key => $footing) {
                // Convert plain string headings into html_table_cell objects
                if (!($footing instanceof html_table_cell)) {
                    $headingtext = $footing;
                    $footing = new html_table_cell();
                    $footing->text = $headingtext;
                    $footing->header = true;
                }

                if ($footing->header !== false) {
                    $footing->header = true;
                }

                if ($footing->header && empty($footing->scope)) {
                    $footing->scope = 'col';
                }

                $footing->attributes['class'] .= ' header c' . $key;
                if (isset($table->footerspan[$key]) && $table->footerspan[$key] > 1) {
                    $footing->colspan = $table->footerspan[$key];
                    $countcols += $table->footerspan[$key] - 1;
                }

                if ($key == $lastkey) {
                    $footing->attributes['class'] .= ' lastcol';
                }
                if (isset($table->colclasses[$key])) {
                    $footing->attributes['class'] .= ' ' . $table->colclasses[$key];
                }
                $footing->attributes['class'] = trim($footing->attributes['class']);
                $attributes = array_merge($footing->attributes, array(
                    'style' => $table->align[$key] . $table->size[$key] . $footing->style,
                    'scope' => $footing->scope,
                    'colspan' => $footing->colspan,
                        ));

                $tagtype = 'td';
                if ($footing->header === true) {
                    $tagtype = 'th';
                }
                $output .= html_writer::tag($tagtype, $footing->text, $attributes) . "\n";
            }
            $output .= html_writer::end_tag('tr') . "\n";
            $output .= html_writer::end_tag('tfoot') . "\n";
        }

        $output .= html_writer::end_tag('table') . "\n";

        return $output;
    }
}
?>

<link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/Fixed-Header-Table/css/defaultTheme.css" />
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jqplot/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/Fixed-Header-Table/jquery.fixedheadertable.min.js"></script>

<div class="row-fluid">
    <div class="span8">
        <p><?php echo get_string('heat_legend_rows', 'local_cicei_snatools'); ?></p>
        <p><?php echo get_string('heat_legend_columns', 'local_cicei_snatools'); ?></p>
    </div>
    <div class="span4">
        <?php echo cicei_snatools_html_writer::table($legend_table); ?>
    </div>
</div>

<div class="row-fluid">
    <div class="span12">
        <div id="loading-img" style="text-align: center; margin: 0 auto;">
            <img src="<?php echo $OUTPUT->pix_url('i/loading'); ?>" />
        </div>
        <div id="table-container" style="visibility: hidden;">
            <?php echo cicei_snatools_html_writer::table($table); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(window).ready(function(){
        $('#ars-table').fixedHeaderTable({
            height: '610',
            fixedColumns: 1,
            footer: true,
            create: function() {
                $('#loading-img').hide();
                $('#table-container').css('visibility', 'visible');
            }
        });
    });
</script>