<?php

global $CFG;

// Colors
//#444444 dark grey
//#909291 light grey
//#C74243 red
//#416D9C blue
//#70A35E green
//#83548B purple
$colors = array(
    'nodes' => '#444444',  // unselected nodes
    'edges' => '#909291',  // unselected edges
    'selected' => '#C74243', // selected node
    'in' => '#70A35E', // nodes and edges that are OUT of selected node
    'out' => '#416D9C', // nodes and edges that are IN of selected node
    'in&out' => '#83548B', // nodes and edges that are IN&OUT of selected node
    'background' => '#FFFFFF', // background for canvas
);

?>
<script language="javascript" type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jquery/jquery-1.8.2.min.js"></script>
<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/Jit/Extras/excanvas.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/Jit/jit-yc.js"></script>
<style media="screen" type="text/css">
    #jitcontainer {
        clear: both;
    }
    #infovis {
        position: relative;
        width: 100%;
        height: 600px;
        margin: auto;
        overflow: hidden;
    }
    #infovis > h1 {
        margin-top: 275px;
        text-align: center;
        text-decoration: blink;
    }
    #center-container {
        width: 79%;
        background-color: <?php echo $colors['background'] ; ?>;
        float: left;
        border: #afafaf solid 1px;
    }
    #right-container {
        width: 19%;
        float: left;
        margin-left: 5px;
        border: #afafaf solid 1px;
        padding: 2px;
    }
    #inner-details h4 {
        color: <?php echo $colors['selected']; ?>;
        border-bottom: #afafaf solid 1px;
        padding-bottom: 2px;
        margin-bottom: 2px;
    }
    #inner-details ul {
        list-style: none;
        margin: 0px;
    }
    .selected-graph {
        font-weight: bold;
        text-decoration: underline;
    }
</style>

<div id="jitcontainer">
    <div id="center-container" style="position: relative;">
        <div id="jitcontainer">
            <div style="float: left;">
                <button id="RGraph-button" type="button" onclick="javascript:init_jit_graph('RGraph', json); $('#rg-button').addClass('selected-graph'); $('#fd-button').removeClass('selected-graph');"><?php echo get_string('nodes_rgraph', 'local_cicei_snatools'); ?></button>
                <!--<button type="button" onclick="javascript:init_jit_graph('Hypertree', json)">View as Hypertree</button>-->
                <button id="ForceDirected-button" type="button" onclick="javascript:init_jit_graph('ForceDirected', json); $('#fd-button').addClass('selected-graph'); $('#rg-button').removeClass('selected-graph');"><?php echo get_string('nodes_force_directed', 'local_cicei_snatools'); ?></button>
            </div>
            <div style="float: right;">
                <strong id="jit-zoom">Zoom: </strong>
                <button type="button" onclick="javascript:zoom_jit_graph(-1);">-</button>
                <button type="button" onclick="javascript:zoom_jit_graph(1);">+</button>
                <button type="button" onclick="javascript:zoom_jit_graph(0);"><?php echo get_string('reset_button', 'local_cicei_snatools'); ?></button>
            </div>
        </div>
        <div id="infovis">
            <h1><?php echo get_string('loading', 'local_cicei_snatools'); ?></h1>
        </div>
        <div id="jitcontainer" style="position:absolute; bottom: 0; right: 0;">
            <div>
                <ul>
                    <li style="color: <?php echo $colors['in'];?>"><?php echo get_string('input', 'local_cicei_snatools'); ?></li>
                    <li style="color: <?php echo $colors['out'];?>"><?php echo get_string('output', 'local_cicei_snatools'); ?></li>
                    <li style="color: <?php echo $colors['in&out'];?>"><?php echo get_string('input_output', 'local_cicei_snatools'); ?></li>
                </ul>
            </div>
        </div>
    </div>
    <div id="right-container">
        <!--<h3>Selected node:</h3>-->
        <div id="inner-details"></div>
    </div>
</div>

<script type="text/javascript">
    //<![CDATA[
    var labelType, useGradients, nativeTextSupport, animate;
    var nodegraph;
    (function() {
        var ua = navigator.userAgent,
        iStuff = ua.match(/iPhone/i) || ua.match(/iPad/i),
        typeOfCanvas = typeof HTMLCanvasElement,
        nativeCanvasSupport = (typeOfCanvas == 'object' || typeOfCanvas == 'function'),
        textSupport = nativeCanvasSupport
            && (typeof document.createElement('canvas').getContext('2d').fillText == 'function');
        //I'm setting this based on the fact that ExCanvas provides text support for IE
        //and that as of today iPhone/iPad current text support is lame
        labelType = (!nativeCanvasSupport || (textSupport && !iStuff))? 'Native' : 'HTML';
        nativeTextSupport = labelType == 'Native';
        useGradients = nativeCanvasSupport;
        animate = !(iStuff || !nativeCanvasSupport);
    })();

    function render_double_arrow(adj, canvas) {
        var from = adj.nodeFrom.pos.getc(true),
        to = adj.nodeTo.pos.getc(true),
        dim = adj.getData('dim'),
        ctx = canvas.getCtx(),
        vect = new $jit.Complex(to.x - from.x, to.y - from.y);
        vect.$scale(dim / vect.norm());
        //Needed for drawing the first arrow
        var intermediatePoint = new $jit.Complex(to.x - vect.x,
        to.y - vect.y),
        normal = new $jit.Complex(-vect.y / 2, vect.x / 2),
        v1 = intermediatePoint.add(normal),
        v2 = intermediatePoint.$add(normal.$scale(-1));

        var vect2 = new $jit.Complex(to.x - from.x, to.y -
            from.y);
        vect2.$scale(dim / vect2.norm());
        //Needed for drawing the second arrow
        var intermediatePoint2 = new $jit.Complex(from.x +
            vect2.x, from.y + vect2.y),
        normal = new $jit.Complex(-vect2.y / 2, vect2.x / 2),
        v12 = intermediatePoint2.add(normal),
        v22 = intermediatePoint2.$add(normal.$scale(-1));

        //Drawing the double arrow on the canvas, first the line, then the ends
        ctx.beginPath();
        ctx.moveTo(from.x, from.y);
        ctx.lineTo(to.x, to.y);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(v1.x, v1.y);
        ctx.lineTo(v2.x, v2.y);
        ctx.lineTo(to.x, to.y);
        ctx.closePath();
        ctx.fill();
        ctx.beginPath();
        ctx.moveTo(v12.x, v12.y);
        ctx.lineTo(v22.x, v22.y);
        ctx.lineTo(from.x, from.y);
        ctx.closePath();
        ctx.fill();
    }

    function init_jit_graph(graphtype, json){
        $jit.RGraph.Plot.EdgeTypes.implement({
            'double_arrow': {
                'render': function(adj, canvas) {
                    render_double_arrow(adj, canvas);
                }
            }
        });
        $jit.ForceDirected.Plot.EdgeTypes.implement({
            'double_arrow': {
                'render': function(adj, canvas) {
                    render_double_arrow(adj, canvas);
                }
            }
        });


        //Get constructor function based in graphtype
        var cfn = $jit[graphtype];

        //Clear injecting area
        $jit.id('infovis').innerHTML = "";

        //init Graph
        nodegraph = new cfn({
            injectInto: 'infovis',
            //Add navigation capabilities:
            Navigation: {
                enable: true,
                panning: 'avoid nodes',
                zooming: false
            },
            //Nodes and Edges parameters
            Node: {
                overridable: true,
                color: '<?php echo $colors['nodes'] ; ?>',
                dim: 2,
                angularWidth:0.5,
                span:0.5
            },
            Edge: {
                overridable: true,
                type: 'line',
                dim: 4,
                color: '<?php echo $colors['edges'] ; ?>',
                alpha: 0.6
            },
            // Use native canvas text
            Label: {
                overridable: true,
                type: labelType,
                size: 8,
                //family: 'Verdana',
                color: '<?php echo $colors['nodes'] ; ?>'
            },
            //Set polar interpolation.
            //Default's linear.
            interpolation: 'polar',
            //Change other animation parameters.
            duration: 3500,
            fps: 30,
            //Number of iterations for the FD algorithm
            iterations: 100,
            //Change father-child distance.
            levelDistance: 200,
            // Add node events
            Events: {
                enable: true,
                type: 'Native',
                //Change cursor style when hovering a node
                onMouseEnter: function() {
                    nodegraph.canvas.getElement().style.cursor = 'move';
                },
                onMouseLeave: function() {
                    nodegraph.canvas.getElement().style.cursor = '';
                },
                //Update node positions when dragged
                onDragMove: function(node, eventInfo, e) {
                    var pos = eventInfo.getPos();
                    node.pos.setc(pos.x, pos.y);
                    nodegraph.plot();
                },
                //Implement the same handler for touchscreens
                onTouchMove: function(node, eventInfo, e) {
                    $jit.util.event.stop(e); //stop default touchmove event
                    this.onDragMove(node, eventInfo, e);
                },
                //Add also a click handler to nodes
                onClick: function(node) {
                    // if no node selected, return
                    if(!node) { return; }
                    // reset all nodes and adjacencies
                    delete nodegraph.selectednode;
                    nodegraph.graph.eachNode(function(n) {
                        delete n.selected;
                    });
                    // select clicked node
                    node.selected = true;
                    nodegraph.selectednode = node;
                    // Center graph in selected node (rgraph)
                    if (typeof nodegraph.onClick == 'function') {
                        nodegraph.onClick(node.id, {
                            hideLabels: false,
                            onComplete: function() {}
                        });
                        // Replot grapg (Force Directed)
                    } else {
                        nodegraph.controller.myPlot();
                        nodegraph.controller.onBeforeCompute(node);
                    }
                },
                // right click handler to unselect selected node
                onRightClick: function(node) {
                    // reset all nodes and adjacencies
                    delete nodegraph.selectednode;
                    nodegraph.graph.eachNode(function(n) {
                        delete n.selected;
                    });
                    nodegraph.controller.myPlot();
                    $jit.id('inner-details').innerHTML = "";
                },
                // Zooming
                onMouseWheel: function(delta, e) {
                    $jit.util.event.stop(e);
                    zoom_jit_graph(delta);
                }
            },
            // Recompute before ploting nodes again
            myPlot: function() {
                nodegraph.graph.eachNode(function(n) {
                    nodegraph.controller.onBeforePlotNode(n);
                    n.eachAdjacency(function(adj) {
                        nodegraph.controller.onBeforePlotLine(adj);
                    });
                });
                nodegraph.plot();
            },
            //This method is called right before plotting a node
            onBeforePlotNode: function(node) {
                if (node.selected) {
                    node.setData('alpha', 1);
                    node.setData('color', '<?php echo $colors['selected'] ; ?>');
                    node.setLabelData('color', '<?php echo $colors['selected'] ; ?>');
                    node.setLabelData('style', 'bold');
                } else if (nodegraph.hasOwnProperty('selectednode') && node.adjacentTo(nodegraph.selectednode)) {
                    node.setData('alpha', 1);
                    var adj = node.adjacencies[nodegraph.selectednode.id];
                    var nodeFrom = nodegraph.graph.getNode(adj.data.$direction[0]);
                    var nodeTo = nodegraph.graph.getNode(adj.data.$direction[1]);
                    var color;
                    if (adj.data.$type == 'double_arrow') {
                        color = '<?php echo $colors['in&out']; ?>';
                    } else {
                        color = nodeFrom.selected ? '<?php echo $colors['in']; ?>' : '<?php echo $colors['out']; ?>';
                    }
                    node.setData('color', color);
                    node.setLabelData('color', color);
                    node.setLabelData('style', 'bold');
                } else {
                    node.setData('alpha', nodegraph.hasOwnProperty('selectednode') ? 0.5 : 1.);
                    node.removeData('color');
                    node.removeLabelData('color');
                    node.removeLabelData('style');
                }
            },
            //This method is called right before plotting an edge.
            onBeforePlotLine: function(adj) {
                var nodeFrom = nodegraph.graph.getNode(adj.data.$direction[0]);
                var nodeTo = nodegraph.graph.getNode(adj.data.$direction[1]);
                if (nodeFrom.selected || nodeTo.selected) {
                    var color;
                    if (adj.data.$type == 'double_arrow') {
                        color = '<?php echo $colors['in&out']; ?>';
                    } else {
                        color = nodeFrom.selected ? '<?php echo $colors['in']; ?>' : '<?php echo $colors['out']; ?>';
                    }
                    adj.setData('color', color);
                    adj.setData('alpha', '1');
                } else {
                    adj.removeData('color');
                    adj.removeData('alpha');
                }
                //Changes lineWidth of each edge (if logweight is 0, default linewidth will be used
                if (!adj.data.$lineWidth && adj.data.logweight) {
                    adj.data.$lineWidth = adj.data.logweight;
                }
            },
            //This method is called right before performing all computations and animations.
            onBeforeCompute: function(node) {
                //Make right column relations list.
                var html = "<h4>" + node.data.photohtml + " " + node.name + "</h4>";
                html += '<ul>';
                node.eachAdjacency(function(adj){
                    var child = adj.nodeTo;
                    var nodeFrom = nodegraph.graph.getNode(adj.data.$direction[0]);
                    var nodeTo = nodegraph.graph.getNode(adj.data.$direction[1]);
                    var color;
                    var data;
                    if (adj.data.$type == 'double_arrow') {
                        color = '<?php echo $colors['in&out']; ?>';
                        data = adj.data.weight_in + " <?php echo get_string('input', 'local_cicei_snatools'); ?>, " + adj.data.weight_out + " <?php echo get_string('output', 'local_cicei_snatools'); ?>";
                    } else {
                        color = nodeFrom.selected ? '<?php echo $colors['in']; ?>' : '<?php echo $colors['out']; ?>';
                        data = adj.data.weight + (nodeFrom.selected ? " <?php echo get_string('input', 'local_cicei_snatools'); ?>" : " <?php echo get_string('output', 'local_cicei_snatools'); ?>");
                    }
                    html += '<li style="color: ' + color + '">';
                    html += child.data.photohtml + " " + child.name + " : "  + data;
                    html += "</li>";
                });
                html += "</ul>";
                $jit.id('inner-details').innerHTML = html;
            }
        });

        //load graph.
        nodegraph.loadJSON(json, 1);

        //compute positions and plot
        //nodegraph.graph.getNode(nodegraph.root).selected = true;
        //nodegraph.selectednode = nodegraph.graph.getNode(nodegraph.root);
        nodegraph.refresh();
        nodegraph.plot();
        //end
        //nodegraph.controller.onBeforeCompute(nodegraph.graph.getNode(nodegraph.root));
        update_zoom_text();
    }

    function zoom_jit_graph(scroll) {
        var current = nodegraph.canvas.getZoom().x;
        if (scroll == 0) {
            current = 1.;
        } else {
            current += scroll * 0.05;
        }
        nodegraph.canvas.setZoom(current,current,false);
        update_zoom_text();
    }

    function update_zoom_text() {
        $('#jit-zoom').html("Zoom: " + nodegraph.canvas.getZoom().x.toFixed(2));
    }

    // Create graph
    var graphtype = "<?php echo $graph; ?>";
    var json = <?php echo $json; ?>;
    window.onload = function(){ init_jit_graph(graphtype, json); $('#<?php echo $graph; ?>-button').addClass('selected-graph');};
    //]]>
</script>
