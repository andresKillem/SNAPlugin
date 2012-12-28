<?php

global $CFG;

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

<div style="clear: both;">
    <div id="chart4" class="jqplot-target" style="width: 60%; height: 600px; float:left;">
    </div>
    <div style="width: 40%; float: left;">
        <br>
        <div class="tooltip" style="border: 1px solid #AAA; margin-left: 10px;">
            <table>
                <tbody>
                    <tr>
                        <td>Usuario: </td><td><div class="tooltip-item" id="tooltipAge" style="display: none; "></div></td>
                    </tr>
                    <tr>
                        <td>Aportaciones: </td><td><div class="tooltip-item" id="tooltipMale" style="display: none; "></div></td>
                    </tr>
                    <tr>
                        <td>Respuestas: </td><td><div class="tooltip-item" id="tooltipFemale" style="display: none; "></div></td>
                    </tr>
                    <tr>
                        <td>Ratio A/R: </td><td><div class="tooltip-item" id="tooltipRatio" style="display: none; "></div></td>
                    </tr>
                    <tr>
                        <td>Ratio R/A: </td><td><div class="tooltip-item" id="tooltipInvRatio" style="display: none; "></div></td>
                    </tr>
                </tbody></table>
        </div>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
$(document).ready(function(){
    // users full names
    var names = <?php echo $names; ?>;

    // the "x" values from the data will go into the ticks array.
    var ticks = <?php echo $ticks; ?>;

    // The "y" values of the data are put into seperate series arrays.
    var serie1 = <?php echo $serie1; ?>;
    var serie2 = <?php echo $serie2; ?>;;

    // Custom color arrays are set up for each series to get the look that is desired.
    // Two color arrays are created for the default and optional color which the user can pick.
    var greenColors = ["#526D2C", "#77933C", "#C57225", "#C57225"];

    // To accomodate changing y axis, need to keep track of plot options, so they are defined separately
    // changing axes will require recreating the plot, so need to keep
    // track of state changes.
    var plotOptions = {
        // We set up a customized title which acts as labels for the left and right sides of the pyramid.
        title: '<div style="float:left;width:50%;text-align:center">Aportaciones</div><div style="float:right;width:50%;text-align:center">Respuestas</div>',

        // by default, the series will use the green color scheme.
        seriesColors: greenColors,

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
        // Here, assume first series is male poulation and second series is female population.
        // Adjust series indices as appropriate.
        var serie1 = Math.abs(plot1.series[0].data[pointIndex][1]);
        var serie2 = Math.abs(plot1.series[1].data[pointIndex][1]);
        var ratio = serie1 / serie2;
        var invratio = 1 / ratio;

        $('#tooltipMale').stop(true, true).fadeIn(250).html(serie1);
        $('#tooltipFemale').stop(true, true).fadeIn(250).html(serie2);
        $('#tooltipRatio').stop(true, true).fadeIn(250).html(ratio.toPrecision(4));
        $('#tooltipInvRatio').stop(true, true).fadeIn(250).html(invratio.toPrecision(4));

        // Since we don't know which axis is rendererd and acive with out a little extra work,
        // just use the supplied ticks array to get the age label.
        $('#tooltipAge').stop(true, true).fadeIn(250).html(ticks[pointIndex] + ' ' + names[pointIndex]);
    });

    // bind to the data highlighting event to make custom tooltip:
    $('.jqplot-target').bind('jqplotDataUnhighlight', function(evt, seriesIndex, pointIndex, data) {
        // clear out all the tooltips.
        $('.tooltip-item').stop(true, true).fadeOut(200).html('');
    });
});
//]]>
</script>