var nowDate = new Date();

function iAnalyticsUpdateSelect(field) {
	var fromDate = $(field).parent().parent().children('td:eq(1)').children('input').datepicker('getDate').getTime()/(24*3600*1000);
	var toDate = $(field).parent().parent().children('td:eq(2)').children('input').datepicker('getDate').getTime()/(24*3600*1000);
	var interval = null;

	if ($.datepicker.formatDate('yy-mm-dd', $(field).parent().parent().children('td:eq(2)').children('input').datepicker('getDate')) == $.datepicker.formatDate('yy-mm-dd', nowDate)) {
		interval = toDate - fromDate + 1;
	}

	if (interval == 7) {
		$('.iAnalyticsSelectBox').val('last-week');
	} else if (interval == 30) {
		$('.iAnalyticsSelectBox').val('last-month');
	} else if (interval == 365) {
		$('.iAnalyticsSelectBox').val('last-year');
	} else {
		$('.iAnalyticsSelectBox').val('custom');
	}
}

function iAnalyticsFilterDates(fromDate, toDate) {
	try {
		$.datepicker.parseDate('yy-mm-dd', toDate);
	} catch(err) {
		$( ".iAnalyticsDateFilter input.fromDate" ).datepicker( "setDate", $('.iAnalyticsDateFilter input.toDate').datepicker("option", "maxDate"));
	}

	try {
		$.datepicker.parseDate('yy-mm-dd', fromDate);
	} catch(err) {
		$( ".iAnalyticsDateFilter input.fromDate" ).datepicker( "setDate", iAnalyticsMinDate );
	}
}

$(document).ready(function(e) {
	$('.iAnalyticsDateFilter button.dateFilterButton').click(function(event) {
		var fromDate = $(this).parent().parent().children('td:eq(1)').children('input').val();
		var toDate = $(this).parent().parent().children('td:eq(2)').children('input').val();

		var filter = 0;
		if ($(this).parent().parent().children('td:eq(3)').children('select').val()) {
			var filter = $(this).parent().parent().children('td:eq(3)').children('select').val();
		}

		iAnalyticsFilterDates(fromDate, toDate);

		fromDate = $.datepicker.formatDate('yy-mm-dd', $(this).parent().parent().children('td:eq(1)').children('input').datepicker( "getDate" ));
		toDate =  $.datepicker.formatDate('yy-mm-dd', $(this).parent().parent().children('td:eq(2)').children('input').datepicker( "getDate" ));

		var newURL = document.location.search;

		if (newURL.match(/fromDate=/) != null) {
			newURL = newURL.replace(/fromDate=(.*)(&|$)/g, "fromDate=" + fromDate + "&");
		} else {
			newURL +='&fromDate=' + fromDate;
		}

		if (newURL.match(/toDate=/) != null) {
			newURL = newURL.replace(/toDate=(.*)(&|$)/g, "toDate=" + toDate + "&");
		} else {
			newURL +='&toDate=' + toDate;
		}
		//
		if (newURL.match(/filterOrders=/) != null) {
			newURL = newURL.replace(/filterOrders=(.*)(&|$)/g, "filterOrders=" + filter + "&");
		} else {
			newURL +='&filterOrders=' + filter;
		}

		var groups = ['day', 'week', 'month', 'year'],
			elVal  = $(this).parent().parent().children('td:eq(4)').children('select').val();
		if (groups.indexOf(elVal) >= 0) {
	    	if (newURL.match(/filterGroup=/) != null) {
				newURL = newURL.replace(/filterGroup=(.*)(&|$)/g, "filterGroup=" + elVal + "&");
			} else {
				newURL +='&filterGroup=' + elVal;
			}
	    }

		//===
		newURL = newURL.replace(/&+/g, "&");
		newURL = newURL.replace(/(&$)/g, "");

		document.location.search = newURL + '&user_token=' + user_token;
	});

	$('.iAnalyticsDateFilter input').datepicker({
		dateFormat:'yy-mm-dd',
		changeMonth:true,
		changeYear:true,
		maxDate:'+0 d',
		minDate:iAnalyticsMinDate
	});

	$('.fromDate').datepicker("option", "onSelect", function( selectedDate ) {
		iAnalyticsUpdateSelect($(this));
		$( ".iAnalyticsDateFilter input.toDate" ).datepicker( "option", "minDate", selectedDate );
	});

	$('.toDate').datepicker("option", "minDate", $( ".iAnalyticsDateFilter input.fromDate" ).datepicker( "getDate" ));
	$('.toDate').datepicker("option", "onSelect", function( selectedDate ) {
		iAnalyticsUpdateSelect($(this));
		$( ".iAnalyticsDateFilter input.fromDate" ).datepicker( "option", "maxDate", selectedDate );
	});

	$('.iAnalyticsSelectBox').change(function(e) {
		var fromDate = new Date(); fromDate.setTime(nowDate.getTime());
		var toDate = new Date(); toDate.setTime(nowDate.getTime());
		var substract = 0;
		if ($(this).val() == 'last-week') {
			substract = 6*24*3600*1000;
		} else if ($(this).val() == 'last-month') {
			substract = 29*24*3600*1000;
		} else if ($(this).val() == 'last-year') {
			substract = 364*24*3600*1000;
		}

		fromDate.setTime(fromDate.getTime() - substract);

		if (substract > 0) {
			$('.iAnalyticsDateFilter .toDate').datepicker('setDate', toDate);
			$('.iAnalyticsDateFilter .fromDate').datepicker('setDate', fromDate);
		}
	});

});

function render_visible_charts() {
	$.each( iAnalytics.charts , function( i, val ) {
		var canvas_id = iAnalytics.charts[i].canvas;
		if ($('#' + canvas_id).is(':visible')) {
			setTimeout(function(){
				render_chart(iAnalytics.charts[i]);
			}, 270);
		}
	});
}

function render_chart(ChartInfo) {
	var name = (typeof ChartInfo.name != "undefined") ? ChartInfo.name : Math.random().toString(36).substring(5);
	if (typeof ChartInfo.canvas != "undefined" && document.getElementById(ChartInfo.canvas)) {
		var ctx = document.getElementById(ChartInfo.canvas).getContext("2d");
	} else {
		return false;
	}

	if (typeof ChartInfo.data == "undefined" || typeof Chart != "function") {
		return false;
	} else {
		var data = ChartInfo.data;
	}

	if (ChartInfo.instance != null) {
		iAnalytics.charts[name].instance.destroy();
	}

	// Create Instance
	if (ChartInfo.type == "Line") {
		iAnalytics.charts[name].instance = new Chart(ctx).Line(data,{
			scaleBeginAtZero: true,
			maintainAspectRatio: false,
			datasetFill : true,
			pointHitDetectionRadius : 4,
			bezierCurve : false,
			legendTemplate : "<div class=\"chart-legend\"><% for (var i=0; i<datasets.length; i++){%><span><span style=\"background-color:<%=datasets[i].strokeColor%>\">&nbsp;</span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></span><%}%></div>"
		});
	} else if (ChartInfo.type == "Pie") {
		iAnalytics.charts[name].instance = new Chart(ctx).Doughnut(data,{
			maintainAspectRatio: false,
			scaleBeginAtZero: true,
			legendTemplate : "<div class=\"chart-legend\"><% for (var i=0; i<segments.length; i++){%><span><span style=\"background-color:<%=segments[i].fillColor%>\">&nbsp;</span><%if(segments[i].label){%><%=segments[i].label%><%}%></span><%}%></div>"
		});
	} else if (ChartInfo.type == "Bar") {
		iAnalytics.charts[name].instance = new Chart(ctx).Bar(data,{
			maintainAspectRatio: false,
			scaleBeginAtZero: true
		});
	}

	if (ChartInfo.instance != null) {
		var legend = iAnalytics.charts[name].instance.generateLegend();
		$('#' + name + '_legend').html(legend);
	}
}

$(window).load(function(e) {
    if (window.localStorage && window.localStorage['currentTab']) {
		$('.mainMenuTabs a[href="'+window.localStorage['currentTab']+'"]').tab('show');
	} else {
		$('.mainMenuTabs li:first a').tab('show');
	}
	if (window.localStorage && window.localStorage['currentSubTab1']) {
		$('a[href="'+window.localStorage['currentSubTab1']+'"]').tab('show');
	} else {
		$('#preSaleTabs li:first a').tab('show');
	}
	if (window.localStorage && window.localStorage['currentSubTab2']) {
		$('a[href="'+window.localStorage['currentSubTab2']+'"]').tab('show');
	} else {
		$('#afterSaleTabs li:first a').tab('show');
	}
	if (window.localStorage && window.localStorage['currentSubTab3']) {
		$('a[href="'+window.localStorage['currentSubTab3']+'"]').tab('show');
	} else {
		$('#visitsTabs li:first a').tab('show');
	}

	$('.mainMenuTabs a[data-toggle="tab"]').click(function() {
		if (window.localStorage) {
			window.localStorage['currentTab'] = $(this).attr('href');
		}
	});
	$('a[data-toggle="tab"]:not(.mainMenuTabs a[data-toggle="tab"], #afterSaleTabs a[data-toggle="tab"], #visitsTabs a[data-toggle="tab"])').click(function() {
		if (window.localStorage) {
			window.localStorage['currentSubTab1'] = $(this).attr('href');
		}
	});
	$('a[data-toggle="tab"]:not(.mainMenuTabs a[data-toggle="tab"], #preSaleTabs a[data-toggle="tab"], #visitsTabs a[data-toggle="tab"])').click(function() {
		if (window.localStorage) {
			window.localStorage['currentSubTab2'] = $(this).attr('href');
		}
	});
	$('a[data-toggle="tab"]:not(.mainMenuTabs a[data-toggle="tab"], #preSaleTabs a[data-toggle="tab"], #afterSaleTabs a[data-toggle="tab"])').click(function() {
		if (window.localStorage) {
			window.localStorage['currentSubTab3'] = $(this).attr('href');
		}
	});

	render_visible_charts();
});

$(document).ready(function(e) {
	$('a[href=#daily-total-stats-more]').on('click', function() {
		$('a[href=#visitors]').click();
		$('a[href=#daily-total-stats]').click();
	});
	$('a[href=#traffic-sources-more]').on('click', function() {
		$('a[href=#visitors]').click();
		$('a[href=#referer-stats]').click();
	});
	$('a[href=#conversion-rate-more]').on('click', function() {
		$('a[href=#aftersale]').click();
		$('a[href=#customer-funnel]').click();
	});
	$('a[href=#sales-report-more]').on('click', function() {
		$('a[href=#aftersale]').click();
		$('a[href=#sales-report]').click();
	});
	$('a[href=#ordered-products-more]').on('click', function() {
		$('a[href=#aftersale]').click();
		$('a[href=#ordered-products]').click();
	});
	$('a[href=#searched-keywords-more]').on('click', function() {
		$('a[href=#presale]').click();
		$('a[href=#most-searched-products]').click();
	});

	$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		render_visible_charts();
	});

	$('#button-menu').on('click', function() {
		render_visible_charts();
	});
});
