<?php

global $CFG, $OUTPUT;

?>
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/jqplot/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cicei_snatools/vendors/d3/d3.v3.min.js"></script>

<style type="text/css">
#forumgraphsvg  {
    background: #fff;
}

#forumgraphsvg circle {
    stroke: #fff;
    stroke-width: 1.5px;
}

#forumgraphsvg .link {
    stroke: #999;
    stroke-opacity: .6;
}

#forumgraphsvg path.link {
    fill: none;
    stroke: #666;
    stroke-width: 1.5px;
}

#forumgraphsvg text {
    fill: #000;
    font: 9px sans-serif;
    pointer-events: none;
}

#forumgraphoption {
    border: 0px;
}

div.forumgraphtooltip {
    position: absolute;
    text-align: center;
    width: 160px;
    height: auto;
    padding: 2px;
    font: 11px;
    background: lightsteelblue;
    border: 0px;
    border-radius: 5px;
    pointer-events: none;
}

marker#end {
    fill: #999;
}

ol#topposters {
    margin-top: 0px;
    margin-bottom: 5px;
}
</style>

<div id="forumgraphsvg" class="well well-small">
    <input name="toggleNodeLabelButton" type="button" value="<?php echo get_string('toggleauthorname', 'local_cicei_snatools'); ?>" onclick="toggleNodeLabel();" />
</div>

<script type="text/javascript">
var graph = <?php echo $graph_data; ?>;
console.log(graph);
var forumgraph = {};
var showingLabel = 1;
var edgeCurve = 1;

window.onload = function() { d3Graph(graph); }

function nodeclick(d) {
    //var param = 'chooselog=1&showusers=1&showcourses=1&date=0&modaction=add&logformat=showashtml&host_course=1%2F'+forumgraph.courseid+'&modid='+forumgraph.modid+'&user='+d.userid;
    //window.open('/report/log/index.php?'+param, '_blank', 'location=yes,height=600,width=800,scrollbars=yes,status=yes');
}

function toggleNodeLabel() {
    var svg = d3.select("#forumgraphsvg").transition();
    if (showingLabel) {
        svg.selectAll("text").style("display", "none");
        showingLabel = 0;
    } else {
        svg.selectAll("text").style("display", "inline");
        showingLabel = 1;
    }
}

function d3Graph(graph) {
    // D3 script
    var width = jQuery("#forumgraphsvg").width(),
        height = 600,
        markerWidth = 6,
        markerHeight = 6,
        refX = 10,
        refY = 0;

    var color = d3.scale.category10();

    var force = d3.layout.force()
        .charge(-300)
        .linkDistance(120)
        .size([width, height]);

    function zoomed() {
        d3.select("#forumgraphsvg svg").attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
    }

    var zoom = d3.behavior.zoom()
            .scaleExtent([-10, 10])
            .on("zoom", zoomed);

    var svg = d3.select("#forumgraphsvg").append("svg")
            //.attr("width", width)
            //.attr("height", height)
            .attr("viewBox", "0 0 " + width + " " + height )
            .attr("preserveAspectRatio", "xMidYMid meet")
            .call(zoom);

    // Tooltips
    var div = d3.select("#forumgraphsvg").append("div")
            .attr("class", "forumgraphtooltip")
            .style("opacity", 0);

    // build the arrow.
    svg.append("svg:defs").selectAll("marker")
        .data(["end"])
        .enter().append("svg:marker")
        .attr("id", "end")
        .attr("viewBox", "0 -5 10 10")
        .attr("refX", refX)
        .attr("refY", refY)
        .attr("markerWidth", markerWidth)
        .attr("markerHeight", markerHeight)
        .attr("orient", "auto")
        .append("svg:path")
        .attr("d", "M0,-5L10,0L0,5");

    //d3.json("getjson.php?forum="+forumgraph.forum, function(error, graph) {
        force.nodes(graph.nodes)
            .links(graph.links)
            .start();

        var linkedByIndex = {};
        graph.links.forEach(function(d) {
            linkedByIndex[d.source.index + "," + d.target.index] = 1;
        });

        function isConnected(a, b) {
            return linkedByIndex[a.index + "," + b.index] || linkedByIndex[b.index + "," + a.index] || a.index === b.index;
        }

        var path = svg.append("svg:g").selectAll("path")
            .data(graph.links)
            .enter().append("svg:path")
            .attr("class", "link")
            .attr("marker-start", "url(#end)")
            .attr("marker-end", "url(#end)")
            .style("stroke-width", function(d) { return Math.sqrt(d.value); });

        var node = svg.selectAll(".node")
            .data(graph.nodes)
            .enter().append("g")
            .attr("class", "node")
            .style("fill", function(d) { return color(d.group); })
            .on("mouseover", fade(.1, true))
            .on("mouseout", fade(1, false))
            .on("click", nodeclick)
            .call(force.drag);

        // add the nodes
        node.append("circle")
            .attr("r", function(d) {
                if (d.size) {
                    d.radius = Math.sqrt(d.size)*5;
                } else {
                    d.radius = 5;
                }
                return d.radius;
            });

        // add the text (label)
        node.append("text")
            .attr("x", 12)
            .attr("dy", ".35em")
            .text(function(d) { return d.name; });

        force.on("tick", function() {
            path.attr("d", function(d) {
                var dx = d.target.x - d.source.x,
                    dy = d.target.y - d.source.y,
                    dr = Math.sqrt(dx * dx + dy * dy);

                // x and y distances from center to outside edge of target node
                var offsetX = (dx * d.target.radius) / dr;
                var offsetY = (dy * d.target.radius) / dr;

                return "M" +
                    d.source.x + "," +
                    d.source.y + "A" +
                    dr + "," + dr + " 0 0,1 " +
                    (d.target.x - offsetX) + "," +
                    (d.target.y - offsetY);
            });

            node.attr("transform", function(d) {
                return "translate(" + d.x + "," + d.y + ")";
            });
        });

        function fade(opacity, mouseover) {
            return function(d) {
                node.style("stroke-opacity", function(o) {
                    thisOpacity = isConnected(d, o) ? 1 : opacity;
                    this.setAttribute('fill-opacity', thisOpacity);
                    return thisOpacity;
                });

                path.style("opacity", function(o) {
                    return o.source === d || o.target === d ? 1 : opacity;
                });

                if (mouseover) {
                    div.transition()
                        .duration(100)
                        .style("opacity", .9);
                    div.html(d.name+"<br />"+d.photo+"<br /><strong>"+d.contributions+"</strong> contributions <br /> <strong>"+d.responses+"</strong> responses")
                        .style("left", (d3.event.pageX) + "px")
                        .style("top", (d3.event.pageY - 50) + "px");
                } else {
                    div.transition()
                        .duration(200)
                        .style("opacity", 0);
                }
            };
        }
    //});
}
</script>