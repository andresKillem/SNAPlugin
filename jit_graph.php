<?php

global $CFG;

// Colors
$colors = array(
    'nodes' => '#C74243',
    'nodes2' => '#416D9C',
    'edges' => '#909291',
    'edges2' => '#70A35E',
    'background' => '#FFFFFF',
);

?>

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
        padding: 5px;
    }
    #inner-details * h4 {
        border-top: #afafaf solid 1px;
    }
    #inner-details * span {
        border-top: #afafaf solid 1px;
    }
</style>

<div id="jitcontainer">
    <div id="center-container">
        <div id="jitcontainer">
            <div style="float: left;">
                <button type="button" onclick="javascript:init_jit_graph('RGraph', json)">View as RGraph</button>
                <button type="button" onclick="javascript:init_jit_graph('Hypertree', json)">View as Hypertree</button>
                <button type="button" onclick="javascript:init_jit_graph('ForceDirected', json)">View as Force Directed</button>
            </div>
            <!--<div style="float: right;">
                <strong>Zoom: </strong>
                <button type="button" onclick="javascript:init_jit_graph('RGraph', json)">-</button>
                <button type="button" onclick="javascript:init_jit_graph('RGraph', json)">+</button>
            </div>-->
        </div>
        <div id="infovis">
        </div>
    </div>
    <div id="right-container">
        <h3>Selected node:</h3>
        <div id="inner-details"></div>
    </div>
</div>

<script type="text/javascript">
    //<![CDATA[
    var labelType, useGradients, nativeTextSupport, animate;
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

    function init_jit_graph(graphtype, json){
        //Get constructor function based in graphtype
        var cfn = $jit[graphtype];

        //Clear injecting area
        $jit.id('infovis').innerHTML = "";

        //init Graph
        var nodegraph = new cfn({
            'injectInto': 'infovis',
            //Optional: Add a background canvas
            //that draws some concentric circles.
            /*'background': {
          'CanvasStyles': {
            'strokeStyle': '#555',
            'shadowBlur': 50,
            'shadowColor': '#ccc'
          }
        },*/
            //Add navigation capabilities:
            //zooming by scrolling and panning.
            Navigation: {
                enable: true,
                panning: 'avoid nodes',
                zooming: 50
            },
            //Nodes and Edges parameters
            //can be overridden if defined in
            //the JSON input data.
            //This way we can define different node
            //types individually.
            Node: {
                'overridable': true,
                'color': '<?php echo $colors['nodes'] ; ?>'
            },
            Edge: {
                'overridable': true,
                'color': '<?php echo $colors['edges'] ; ?>'
            },
            //Set polar interpolation.
            //Default's linear.
            interpolation: 'polar',
            //Change the transition effect from linear
            //to elastic.
            //transition: $jit.Trans.Elastic.easeOut,
            //Change other animation parameters.
            duration: 3500,
            fps: 30,
            //Number of iterations for the FD algorithm
            iterations: 100,
            //Change father-child distance.
            levelDistance: 200,
            // Add node events
            /*Events: {
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
                if(!node) return;
                // reset all nodes and adjacencies
                nodegraph.graph.eachNode(function(n) {
                    delete n.selected;
                });
                node.selected = true;
                // Center graph in selected node
                if (typeof nodegraph.onClick == 'function') {
                    nodegraph.onClick(node.id, {
                        hideLabels: false,
                        onComplete: function() {
                            console.log("done");
                        }
                    });
                } else {
                    nodegraph.plot();
                }
              }
            },*/
            //This method is called right before plotting a node
            onBeforePlotNode: function(node) {
                if(node.selected) {
                    node.setData('color', '<?php echo $colors['nodes2'] ; ?>');
                    node.setData('dim', '5');
                } else {
                    node.removeData('color');
                    node.removeData('dim');
                }
            },
            //This method is called right before plotting an edge.
            onBeforePlotLine: function(adj){
                if(adj.nodeFrom.selected && adj.data.$direction[0] == adj.nodeFrom.id) {
                    adj.setData('color', '<?php echo $colors['edges2'] ; ?>');
                } else if(adj.nodeTo.selected && adj.data.$direction[0] == adj.nodeTo.id) {
                    adj.setData('color', '<?php echo $colors['edges2'] ; ?>');
                    //adj.removeData('color');
                } else {
                    adj.removeData('color');
                }
                //Add some random lineWidth to each edge.
                if (!adj.data.$lineWidth && adj.data.weight) {
                    //adj.data.$lineWidth = Math.random() * 5 + 1;
                    adj.data.$lineWidth = adj.data.weight;
                }
            },
            //This method is called right before performing all computations and animations.
            onBeforeCompute: function(node){
                console.log("centering " + node.name + "...");

                //Make right column relations list.
                var html = "<h4>" + node.data.photohtml + " " + node.name + "</h4>";
                html += '<ul>';
                node.eachAdjacency(function(adj){
                    if (adj.data.$direction[0] == node.id) {
                        var child = adj.nodeTo;
                        html += "<li>" + child.data.photohtml + " " + child.name + " : "  + adj.data.weight + "</li>";
                    }
                });
                html += "</ul>";
                $jit.id('inner-details').innerHTML = html;
            },
            //Add node click handler and some styles.
            //This method is called only once for each node/label crated.
            onCreateLabel: function(domElement, node){
                domElement.innerHTML = node.name;
                domElement.onclick = function () {
                    // reset all nodes and adjacencies
                    nodegraph.graph.eachNode(function(n) {
                        delete n.selected;
                    });
                    node.selected = true;
                    // Center graph in selected node
                    if (typeof nodegraph.onClick == 'function') {
                        nodegraph.onClick(node.id, {
                            hideLabels: false,
                            onComplete: function() {
                                console.log("done");
                            }
                        });
                    } else {
                        nodegraph.controller.onBeforeCompute(node);
                        nodegraph.plot();
                    }
                };
                var style = domElement.style;
                style.cursor = 'pointer';
                //style.fontSize = "0.8em";
                //style.color = "<?php echo $colors['nodes'] ; ?>";
            },
            //This method is called when rendering/moving a label.
            //This is method is useful to make some last minute changes
            //to node labels like adding some position offset.
            onPlaceLabel: function(domElement, node){
                var style = domElement.style;
                var left = parseInt(style.left);
                var w = domElement.offsetWidth;
                style.left = (left - w / 2) + 'px';
                if(node.selected) {
                    domElement.style.color = "<?php echo $colors['nodes2'] ; ?>";
                    style.fontSize = "1em";
                } else {
                    domElement.style.color = "<?php echo $colors['nodes'] ; ?>";
                    style.fontSize = "0.8em";
                }
            }
        });

        //load graph.
        nodegraph.loadJSON(json, 1);

        //compute positions and plot
        nodegraph.graph.getNode(nodegraph.root).selected = true;
        nodegraph.refresh();
        //end
        nodegraph.controller.onBeforeCompute(nodegraph.graph.getNode(nodegraph.root));
        console.log("done");
    }

    // Create graph
    var graphtype = "<?php echo $graph; ?>";
    var json = <?php echo $json; ?>;
    window.onload = function(){ init_jit_graph(graphtype, json); };
    //window.onload = function(){ init(json); };
    //]]>
</script>
