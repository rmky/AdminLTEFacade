/*
 * Author: Abdullah A Almsaeed
 * Date: 4 Jan 2014
 * Description:
 *      This is a demo file used only for the main dashboard (index.html)
 **/
"use strict";

$(function () {

  //Activate the iCheck Plugin
 /* $('input[type="checkbox"]').iCheck({
    checkboxClass: 'icheckbox_flat-blue',
    radioClass: 'iradio_flat-blue'
  });*/
  
  //bootstrap WYSIHTML5 - text editor
  //$(".textarea").wysihtml5();
  
  /*$('.daterange').daterangepicker(
          {
            ranges: {
              'Today': [moment(), moment()],
              'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
              'Last 7 Days': [moment().subtract(6, 'days'), moment()],
              'Last 30 Days': [moment().subtract(29, 'days'), moment()],
              'This Month': [moment().startOf('month'), moment().endOf('month')],
              'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            startDate: moment().subtract(29, 'days'),
            endDate: moment()
          },
  function (start, end) {
    alert("You chose: " + start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
  });*/

  /* jQueryKnob */
  //$(".knob").knob();

  //jvectormap data
  var visitorsData = {
    "US": 398, //USA
    "SA": 400, //Saudi Arabia
    "CA": 1000, //Canada
    "DE": 500, //Germany
    "FR": 760, //France
    "CN": 300, //China
    "AU": 700, //Australia
    "BR": 600, //Brazil
    "IN": 800, //India
    "GB": 320, //Great Britain
    "RU": 3000 //Russia
  };
  

  //Sparkline charts
  /*var myvalues = [1000, 1200, 920, 927, 931, 1027, 819, 930, 1021];
  $('#sparkline-1').sparkline(myvalues, {
    type: 'line',
    lineColor: '#92c1dc',
    fillColor: "#ebf4f9",
    height: '50',
    width: '80'
  });
  myvalues = [515, 519, 520, 522, 652, 810, 370, 627, 319, 630, 921];
  $('#sparkline-2').sparkline(myvalues, {
    type: 'line',
    lineColor: '#92c1dc',
    fillColor: "#ebf4f9",
    height: '50',
    width: '80'
  });
  myvalues = [15, 19, 20, 22, 33, 27, 31, 27, 19, 30, 21];
  $('#sparkline-3').sparkline(myvalues, {
    type: 'line',
    lineColor: '#92c1dc',
    fillColor: "#ebf4f9",
    height: '50',
    width: '80'
  });*/

  //The Calender
  //$("#calendar").datepicker();

  //SLIMSCROLL FOR CHAT WIDGET
  /*$('#chat-box').slimScroll({
    height: '250px'
  });*/

  /* Morris.js Charts */
  // Sales chart
  var area = new Morris.Line({
    element: 'revenue-chart',
    resize: true,
    data: [
      /*{y: '2015-04', item1: 42666, item2: 24666},
      {y: '2015-05', item1: 32778, item2: 22294},
      {y: '2015-06', item1: 36912, item2: 21969},
      {y: '2015-07', item1: 32767, item2: 33597},
      {y: '2015-08', item1: 46810, item2: 31914},
      {y: '2015-09', item1: 42670, item2: 34293},
      {y: '2015-10', item1: 43820, item2: 33795},
      {y: '2015-11', item1: 44073, item2: 35967},
      {y: '2015-12', item1: 54687, item2: 50460},
      {y: '2016-01', item1: 38432, item2: 25713}*/
	  {y: '2015-12-19', item1: 42666, item2: 24666},
      {y: '2015-12-28', item1: 32778, item2: 22294},
      {y: '2016-01-02', item1: 36912, item2: 21969},
      {y: '2016-01-09', item1: 32767, item2: 33597},
      {y: '2016-01-16', item1: 46810, item2: 31914},
      {y: '2016-01-23', item1: 42670, item2: 34293},
      {y: '2016-01-30', item1: 43820, item2: 33795},
      {y: '2016-02-06', item1: 44073, item2: 35967},
      {y: '2016-02-13', item1: 54687, item2: 50460},
      {y: '2016-02-20', item1: 38432, item2: 25713}
    ],
    xkey: 'y',
    ykeys: ['item1', 'item2'],
    labels: ['Diese Filiale', 'Durchschnitt'],
    lineColors: ['#3c8dbc', '#a0d0e0'],
    hideHover: 'auto'
  });

  //Donut Chart
  var donut = new Morris.Donut({
    element: 'sales-chart',
    resize: true,
    colors: ["#3c8dbc", "#f56954", "#00a65a"],
    data: [
      {label: "DOB", value: 1358},
      {label: "Wäsche", value: 660},
      {label: "Lederwaren", value: 450},
      {label: "HAKA", value: 588},
      {label: "Spielwaren", value: 491},
	  {label: "Sonstige", value: 823}
    ],
	colors: [
		"#dd4b39",
		"#00a65a",
		"#f39c12",
		"#00c0ef",
		"#3c8dbc",
		"#d2d6de"
	],
    hideHover: 'auto'
  });

  //Fix for charts under tabs
  $('.box ul.nav a').on('shown.bs.tab', function (e) {
    area.redraw();
    donut.redraw();
  });


  /* BOX REFRESH PLUGIN EXAMPLE (usage with morris charts) */
  $("#loading-example").boxRefresh({
    source: "ajax/dashboard-boxrefresh-demo.php",
    onLoadDone: function (box) {
      bar = new Morris.Bar({
        element: 'bar-chart',
        resize: true,
        data: [
          {y: '2006', a: 100, b: 90},
          {y: '2007', a: 75, b: 65},
          {y: '2008', a: 50, b: 40},
          {y: '2009', a: 75, b: 65},
          {y: '2010', a: 50, b: 40},
          {y: '2011', a: 75, b: 65},
          {y: '2012', a: 100, b: 90}
        ],
        barColors: ['#00a65a', '#f56954'],
        xkey: 'y',
        ykeys: ['a', 'b'],
        labels: ['CPU', 'DISK'],
        hideHover: 'auto'
      });
    }
  });

  /* The todo list plugin */
  $(".todo-list").todolist({
    onCheck: function (ele) {
      console.log("The element has been checked")
    },
    onUncheck: function (ele) {
      console.log("The element has been unchecked")
    }
  });

});