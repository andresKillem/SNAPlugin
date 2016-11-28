<?php

global $CFG, $OUTPUT, $PAGE;

?>

<link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jqplot/jquery.jqplot.min.css" />

<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jqplot/excanvas.js"></script><![endif]-->
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jqplot/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jqplot/jquery.jqplot.min.js"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jqplot/plugins/jqplot.pyramidAxisRenderer.min.js"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jqplot/plugins/jqplot.pyramidGridRenderer.min.js"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jqplot/plugins/jqplot.pyramidRenderer.min.js"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>

<div class="row-fluid">
    <div class="span6 well well-small">
        <div style="height: 600px; overflow: auto;">
            <div id="chart4" class="jqplot-target" style=""></div>
        </div>
    </div>
    <div class="span6">
        <ul id="reference-height" class="nav nav-tabs" style="margin: 0;">
            <li class="active"><a id="main-tab-link" href="#user-messages" data-toggle="tab"><?php echo get_string('contributions', 'local_cicei_snatools'); ?> <span id="tooltip-serie-1" class="badge badge-info"></span></a></li>
            <li><a href="#user-responses" data-toggle="tab"><?php echo get_string('responses', 'local_cicei_snatools'); ?> <span id="tooltip-serie-2" class="badge badge-success"></span></a></li>
            <li class="disabled pull-right"><a><span id="tooltilpUser"><?php echo get_string('name', 'local_cicei_snatools'); ?> </span> <span class="label label-info"><?php echo get_string('role', 'local_cicei_snatools'); ?> <span id="tooltilpRole"></span></span></a></li>
        </ul>
        <div id="adjust-height" class="well well-small" style="overflow: auto;">
            <div class="tab-content" style="margin: 0;">
                <div id="user-messages" class="tab-pane active">
                    <?php echo get_string('user_messages_load_help', 'local_cicei_snatools'); ?>
                </div>
                <div id="user-responses" class="tab-pane">
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
$(document).ready(function(){
    //user ids
    var user_ids = <?php echo $user_ids; ?>;

    // users full names
    var names = <?php echo $names; ?>;

    // teacher flag
    var is_teacher = <?php echo $isteacher; ?>;

    // the "x" values from the data will go into the ticks array.
    var ticks = <?php echo $ticks; ?>;

    // The "y" values of the data are put into seperate series arrays.
    var serie1 = <?php echo $serie1; ?>;
    var serie2 = <?php echo $serie2; ?>;

    // adjust chart div height to series length
    $('#chart4').height(27 + (ticks.length * (23 + 3)) + 22);

    // To accomodate changing y axis, need to keep track of plot options, so they are defined separately
    // changing axes will require recreating the plot, so need to keep
    // track of state changes.
    var plotOptions = {
        // We set up a customized title which acts as labels for the left and right sides of the pyramid.
        title: '<div style="float:left;width:50%;text-align:center"><?php echo get_string('contributions', 'local_cicei_snatools'); ?></div>\n\
                <div style="float:right;width:50%;text-align:center"><?php echo get_string('responses', 'local_cicei_snatools'); ?></div>',

        // by default, the series will use the green color scheme.
        seriesColors: ["#416D9C", "#70A35E"],

        grid: {
            drawBorder: false,
            shadow: false,
            background: 'white'
        },

        // This makes the effective starting value of the axes 0 instead of 1.
        // For display, the y axis will use the ticks we supplied.
        defaultAxisStart: 0,
        seriesDefaults: {
            renderer: $.jqplot.PyramidRenderer,
            rendererOptions: {
                barPadding: 0
            },
            yaxis: 'yaxis',
            shadow: false
        },
        series: [
            {
                yaxis: 'yMidAxis',
                rendererOptions:{
                    side: 'left',
                    synchronizeHighlight: 1
                }
            },
            {
                yaxis: 'yMidAxis',
                rendererOptions:{
                    synchronizeHighlight: 0
                }
            }
        ],
        axes: {
            xaxis: {
                rendererOptions: {
                    baselineWidth: 1
                }
            },
            yMidAxis: {
                label: 'Usuarios',
                ticks: ticks,
                rendererOptions: {
                    category: true
                }
            }
        }
    };

    plot1 = $.jqplot('chart4', [serie1, serie2], plotOptions);

    $(window).resize(function() {
        plot1.replot();
    });
    $(window).load(function(){
        plot1.replot();
    });

    // bind to the data highlighting event to make custom tooltip:
    $('.jqplot-target').bind('jqplotDataHighlight', function(evt, seriesIndex, pointIndex, data) {
        // Adjust series indices as appropriate.
        var serie1 = Math.abs(plot1.series[0].data[pointIndex][1]);
        var serie2 = Math.abs(plot1.series[1].data[pointIndex][1]);
        $('#tooltip-serie-1').html(serie1);
        $('#tooltip-serie-2').html(serie2);

        var ratio = serie1 / serie2;
        var invratio = 1 / ratio;
        //$('#tooltipRatio').html(ratio.toPrecision(4));
        //$('#tooltipInvRatio').html(invratio.toPrecision(4));
        <?php
        //<span class="label label-info">ratio A/R <span id="tooltipRatio"></span></span>
        //<span class="label label-info">ratio R/A <span id="tooltipInvRatio"></span></span>
        ?>

        // use the supplied ticks array to get user label
        $('#tooltilpUser').html(ticks[pointIndex] + ' ' + names[pointIndex]);
        $('#tooltilpRole').html(is_teacher[pointIndex] ? '<?php echo get_string('role_teacher', 'local_cicei_snatools'); ?>' : '<?php echo get_string('role_student', 'local_cicei_snatools'); ?>');

        $.ajaxSetup ({
            cache: false
        });
        var ajax_load = "<div style=\"text-align: center;\"><img src=\"<?php echo $OUTPUT->pix_url('i/loading'); ?>\" alt=\"loading\" /></div>";
        //  load() functions
        <?php $ajax_url= new moodle_url('/local/cicei_snatools/forum_messages_ajax.php', $PAGE->url->params()); ?>
        var loadUrl = "<?php echo $ajax_url->out(false); ?>"
            + "&ajax=1"
            + "&userid=" + user_ids[pointIndex]
            + "&forumsids=<?php echo $forumsids; ?>"
            + "&discussionsids=<?php echo $discussionsids; ?>"
            + "&groupsids=<?php echo $groupsids; ?>";
        $("#user-messages").html(ajax_load);
        $("#user-responses").html(ajax_load);
        $.getJSON(loadUrl, function(json) {
            $("#user-messages").html(json.replies);
            $("#user-responses").html(json.responses);
            $('#main-tab-link').click();
        });
    });

    // bind to the data highlighting event to make custom tooltip:
    /*$('.jqplot-target').bind('jqplotDataUnhighlight', function(evt, seriesIndex, pointIndex, data) {
        // clear out all the tooltips.
        //$('.tooltip-item').stop(true, true).fadeOut(200).html('');
    });*/

    $('[data-toggle="tab"]').click(function(e){
        e.preventDefault();
        $(this).parent().parent().children('.active').removeClass('active');
        $(this).parent().addClass('active');
        var dest = $(this).attr('href');
        $(dest).parent().children('.active').removeClass('active');
        $(dest).addClass('active');
    });

    $('#adjust-height').height(600 - $('#reference-height').height())
});
//]]>
</script>
