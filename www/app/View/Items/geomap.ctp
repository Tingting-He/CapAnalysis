<!--
   CapAnalysis

   Copyright 2012 Gianluca Costa (http://www.capanalysis.net) 
   All rights reserved.
-->
<div style="position: absolute; right: 0;">
	<div id="reload_gm" class="treload"><a href="#"><?php echo $this->Html->image('reload.png', array('alt' => '')); ?></a></div>
</div>
<div id="geomap">
	<p id="gmap" style="width: 1000px; height: 500px; padding: 5px">
	<div class="clear">&nbsp;</div>
</div>
<div class="clear">&nbsp;</div>

<script>
	var attribution = '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
		gmap = new L.Map("gmap", {zoomControl: false})
			.setView(new L.LatLng(20, 12), 2)
			.addLayer(new L.TileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png',
				{minZoom: 2,
				 maxZoom: 11,
				 attribution: attribution
				 }));
				 
	var gsvg = d3.select(gmap.getPanes().overlayPane).append("svg"),
		gcmap = gsvg.append("g");
				
	var countries = JSON.parse('<?php echo $countries; ?>'),
		countries_id = JSON.parse('<?php echo $countries_id; ?>'),
		geoshow = 'flows',
		/*
		colors =  d3.interpolateRgb("#FFFF00", "#CC0066"),
		clr_map = d3.scale.linear()
			.range([0,1])
			.domain([d3.min(countries, function(d) { return +d.data[geoshow]; }), d3.max(countries, function(d) { return +d.data[geoshow]; })]),
		*/
		colors = d3.scale.ordinal().range(["#5E4FA2", "#3288BD", "#66C2A5", "#ABDDA4", "#E6F598", "#F6FAAA", "#FEE08B", "#FDAE61", "#F46D43", "#D53E4F", "#9E0142"]).domain(d3.range(11)),
		clr_map = d3.scale.linear()
			.rangeRound([0, 10])
			.domain([d3.min(countries, function(d) { return +d.data[geoshow]; }), d3.max(countries, function(d) { return +d.data[geoshow]; })]),
		flow_tot = d3.sum(countries, function(d) { return +d.data.flows; }),
		sent_tot = d3.sum(countries, function(d) { return +d.data.sent; }),
		received_tot = d3.sum(countries, function(d) { return +d.data.received; }),
		data_tot = sent_tot + received_tot;
	var geolabels, geocolors;

	function d3GeoMapLegend() {
		geocolors = d3.select("#geomap div.legend").append("div");
		var mlabels = Array({name:'<?php echo __('Flows'); ?>', view: 'flows'}, {name:'<?php echo __('Data'); ?>', view: 'tot'}, {name:'<?php echo __('Received'); ?>', view: 'received'}, {name:'<?php echo __('Sent'); ?>', view: 'sent'}, {name:'<?php echo __('None'); ?>', view: 'none'}),
			w = 100, h = 13*(mlabels.length+1.5),
			d3legend = d3.select("#geomap div.legend").append("svg")
				.attr("width", w)
				.attr("height", h);
		geolabels = d3legend.append("g");
			
		geolabels.append("text")
			.attr("text-anchor", "start")
			.attr("class", "bold")
			.attr("x", 0)
			.attr("y", 10)
			.attr("dy", ".32em")
			.attr("fill", "white")
			.text('<?php echo __('Visualize'); ?>:');
		var circles = geolabels.selectAll("circle")
			.data(mlabels)
			.enter().append("g")
			.attr("transform", function(d, i) { i++; return "translate(7,"+i*13+")"; })
			.on("click", d3GeoMapUpdate);
		circles.append("circle")
			.attr("r", 5)
			.attr("cx", 0)
			.attr("cy", 10)
			.attr("stroke", "black")
			.attr("stroke-width", 1.5)
			.style("fill-opacity", function(d, i){ if(i!=0) return 0; return 1;})
			.attr("fill", "red");
		circles.append("text")
			.attr("text-anchor", "start")
			.attr("x", 8)
			.attr("y", 10)
			.attr("dy", ".32em")
			.attr("class", "bold")
			.attr("fill", "white")
			.text(function(d, i) { return d.name; });		
	} 

	function d3GeoColorScale() {
		if (geoshow != "none") {
			var grades = clr_map.ticks(4),
				labels = [],
				from, to;
			labels.push('<span><b><?php echo __('Colors Scale');?>:</b></span>');
			if (grades[0])
				labels.push('<i style="background:' + colors(clr_map(0)) + '"></i> 0' + '&ndash;' + SizeBMG(grades[0]));
			for (var i = 0; i < grades.length; i++) {
				from = grades[i];
				to = grades[i + 1];

				labels.push(
					'<i style="background:' + colors(clr_map(from)) + '"></i> ' +
					SizeBMG(from) + (SizeBMG(to) ? '&ndash;' + SizeBMG(to) : '+'));
			}
			geocolors.html(labels.join('<br>'));
		}
		else
			geocolors.html('');
	}
	
	function fillColor(p) {
		if (typeof countries_id[p.id] === 'undefined')
			return "#000";
		return colors(clr_map(+countries[countries_id[p.id]].data[geoshow]));
	}
		
	function fillOpct(p) {
		if (typeof countries_id[p.id] === 'undefined')
			return 0;
		else
			return .5;
	}
		
	function d3GeoMapUpdate(p) {
		var fillcb, opct, vclass;
		geoshow = p.view;
		if (geoshow == "none") {
			fillcb = "#fff";
			opct = 0;
			vclass = false;
		}
		else {
			fillcb = fillColor;
			opct = fillOpct;
			vclass = true;
		}
		geolabels.selectAll("circle").style("fill-opacity", "0");
		d3.select(this).select("circle").style("fill-opacity", "1");
		clr_map.domain([d3.min(countries, function(d) { return +d.data[geoshow]; }), d3.max(countries, function(d) { return +d.data[geoshow]; })]);
		gcmap.selectAll("path")
			.classed("border", vclass)
			.transition()
			.duration(900)
			.style("fill-opacity", opct)
			.style("fill", fillcb)
		d3GeoColorScale();
	}
	
	function d3GeoMap(collection) {
		var bounds = d3.geo.bounds(collection),
			path = d3.geo.path().projection(project);

		var feature = gcmap.selectAll("path")
				.data(collection.features)
			.enter().append("path")
				.style("fill", fillColor)
				.style("fill-opacity", fillOpct)
				.classed("border", true)
				.on("click", cntrclick)
				.on("mouseover", mouseover)
				.on("mouseout", mouseout)

		function mouseover(p) {
			info.update(p);
		}
		
		function mouseout(p) {
			//info.update();
		}
		
		function cntrclick(p) {
		}
  
		gmap.on("viewreset", reset);
		reset();

		// Reposition the SVG to cover the features.
		function reset() {
			var bottomLeft = project(bounds[0]),
				topRight = project(bounds[1]);
			
			gsvg.attr("width", topRight[0] - bottomLeft[0])
				.attr("height", bottomLeft[1] - topRight[1])
				.style("margin-left", bottomLeft[0] + "px")
				.style("margin-top", topRight[1] + "px");

			gcmap.attr("transform", "translate(" + -bottomLeft[0] + "," + -topRight[1] + ")");

			feature.attr("d", path);
		}

		// Use Leaflet to implement a D3 geographic projection.
		function project(x) {
			var point = gmap.latLngToLayerPoint(new L.LatLng(x[1], x[0]));
			return [point.x, point.y];
		}
		
		// control  info on hover
		var info = L.control({position: 'bottomright'});

		info.onAdd = function (map) {
			this._div = L.DomUtil.create('div', 'info opie');
			this.update();
			return this._div;
		};

		info.update = function (f) {
			if (typeof f === 'undefined' || typeof countries_id[f.id] === 'undefined') {
				this._div.innerHTML = '';
			}
			else {
				var flow_v = +countries[countries_id[f.id]].data['flows'],
					data_v = +countries[countries_id[f.id]].data['tot'],
					sent_v = +countries[countries_id[f.id]].data['sent'],
					rec_v = +countries[countries_id[f.id]].data['received'];
					
				this._div.innerHTML = '<strong>' + f.properties.name + '</strong><br/><hr /><br/>'+
				'<span><?php echo __('Flows'); ?>: <strong>'+SizeBMG(flow_v, 1000)+' ('+perCent(flow_v,flow_tot)+')</strong></span><br/>'+
				'<div id="gmflow"></div><hr />'+
				'<?php echo __('Data'); ?>: <strong>'+SizeBMG(data_v)+' ('+perCent(data_v,data_tot)+')</strong><br/>'+
				'<div id="gmdata"></div><hr />'+
				'<?php echo __('Sent'); ?>: <strong>'+SizeBMG(sent_v)+'</strong><br/>'+
				'<?php echo __('Received'); ?>: <strong>'+SizeBMG(rec_v)+'</strong><br/>'+
				'<div id="gmsntrcv"></div>';
				opie("#gmflow", [flow_v,flow_tot-flow_v], 100, 50);
				opie("#gmdata", [data_v,data_tot-data_v], 100, 55);
				opie("#gmsntrcv", [sent_v,rec_v], 100, 60);
			}
		};

		info.addTo(gmap);

		// legend
		var legend = L.control({position: 'bottomleft'});

		legend.onAdd = function (map) {
			var div = L.DomUtil.create('div', 'info legend');
			return div;
		};

		legend.addTo(gmap);		
	};
	d3GeoMap(world_countries);
	d3GeoMapLegend();
	d3GeoColorScale();
	
	// reload
	$('#reload_gm').unbind('click');
	$("#reload_gm").click(function() {
		$.ajax({
			url: "<?php echo $this->Html->url(array('controller' => 'items', 'action' => 'geomap')); ?>",
			context: document.body
		}).done(function(data) { 
			$("#ui-tabs-5").html(data);
		});
	});
</script>
