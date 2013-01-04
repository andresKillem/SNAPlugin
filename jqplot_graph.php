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

<div style="clear: both; text-align: center;">
    <br>
    <br>
</div>

<div style="clear: both;">
    <div id="chart4" class="jqplot-target" style="width: 60%; height: 600px;  float:left;">
    </div>
    <div style="width: 40%; float: left;">
        <div class="tooltip" style="border: 1px solid #AAA; margin-left: 10px; height: 200px;">
            <table>
                <tbody>
                    <tr>
                        <td>Usuario: </td><td><div class="tooltip-item" id="tooltilpUser" style="display: none; "></div></td>
                    </tr>
                    <tr>
                        <td>Rol: </td><td><div class="tooltip-item" id="tooltilpRole" style="display: none; "></div></td>
                    </tr>
                    <tr>
                        <td>Aportaciones: </td><td><div class="tooltip-item" id="tooltipSerie1" style="display: none; "></div></td>
                    </tr>
                    <tr>
                        <td>Respuestas: </td><td><div class="tooltip-item" id="tooltipSerie2" style="display: none; "></div></td>
                    </tr>
                    <tr>
                        <td>Ratio A/R: </td><td><div class="tooltip-item" id="tooltipRatio" style="display: none; "></div></td>
                    </tr>
                    <tr>
                        <td>Ratio R/A: </td><td><div class="tooltip-item" id="tooltipInvRatio" style="display: none; "></div></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <br>
        <div id="userMessages" style="border: 1px solid #AAA; margin-left: 10px; height: 400px; overflow: auto;">
            Pasa el ratón por encima del gráfico para ver aquí los mensajes del usuario
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
        title: '<div style="float:left;width:50%;text-align:center">Aportaciones</div>\n\
                <div style="float:right;width:50%;text-align:center">Respuestas</div>',

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

    // bind to the data highlighting event to make custom tooltip:
    $('.jqplot-target').bind('jqplotDataHighlight', function(evt, seriesIndex, pointIndex, data) {
        // Adjust series indices as appropriate.
        var serie1 = Math.abs(plot1.series[0].data[pointIndex][1]);
        var serie2 = Math.abs(plot1.series[1].data[pointIndex][1]);
        var ratio = serie1 / serie2;
        var invratio = 1 / ratio;

        $('#tooltipSerie1').stop(true, true).fadeIn(250).html(serie1);
        $('#tooltipSerie2').stop(true, true).fadeIn(250).html(serie2);
        $('#tooltipRatio').stop(true, true).fadeIn(250).html(ratio.toPrecision(4));
        $('#tooltipInvRatio').stop(true, true).fadeIn(250).html(invratio.toPrecision(4));

        // use the supplied ticks array to get user label
        $('#tooltilpUser').stop(true, true).fadeIn(250).html(ticks[pointIndex] + ' ' + names[pointIndex]);
        $('#tooltilpRole').stop(true, true).fadeIn(250).html(is_teacher[pointIndex] ? 'profesor' : 'alumno');

        $.ajaxSetup ({
            cache: false
        });
        var ajax_load = "<img src=\"<?php echo $OUTPUT->pix_url('i/loading'); ?>\" alt=\"loading\" />";
        //  load() functions
        <?php $ajax_url= new moodle_url('/local/cicei_snatools/forum_messages_ajax.php', $PAGE->url->params()); ?>
        var loadUrl = "<?php echo $ajax_url->out(false); ?>&ajax=1&userid="
            + user_ids[pointIndex]
            + "&forumsids=<?php echo $forumsids; ?>"
            + "&discussionsids=<?php echo $discussionsids; ?>";
        $("#userMessages").html(ajax_load).load(loadUrl);
    });

    // bind to the data highlighting event to make custom tooltip:
    /*$('.jqplot-target').bind('jqplotDataUnhighlight', function(evt, seriesIndex, pointIndex, data) {
        // clear out all the tooltips.
        //$('.tooltip-item').stop(true, true).fadeOut(200).html('');
    });*/
});
//]]>
</script>