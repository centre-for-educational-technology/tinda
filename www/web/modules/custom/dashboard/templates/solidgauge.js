let chart1 = Highcharts.chart('v-pills-home', {

  chart: {
    type: 'solidgauge',
    height: '80%',

  },

  title: {
    text: '<h3>Õpetaja Digipädevuste</h3><br/>Overall Score <br><span style="font-size:2em; color: dodgerblue; font-weight: bold">80%</span>',
    style: {
      fontSize: '24px'
    }
  },

  tooltip: {
    borderWidth: 0,
    backgroundColor: 'none',
    shadow: false,
    style: {
      fontSize: '16px'
    },
    valueSuffix: '%',
    pointFormat: '{series.name}<br><span style="font-size:2em; color: {point.color}; font-weight: bold">{point.y}</span>',
    positioner: function (labelWidth) {
      return {
        x: (this.chart.chartWidth - labelWidth) / 2,
        y: (this.chart.plotHeight / 2) + 15
      };
    }
  },
  legend:{
    labelFormatter: function() {
      return '<span style="text-weight:bold;color:' + this.userOptions.color + '">' + this.name + '</span>';
    },

  },

  pane: {
    startAngle: 0,
    endAngle: 360,
    background: [{ // Track for Move
      outerRadius: '100%',
      innerRadius: '88%',
      backgroundColor: Highcharts.color(Highcharts.getOptions().colors[0])
        .setOpacity(0.3)
        .get(),
      borderWidth: 0
    },
      { // Track for Move
        outerRadius: '87%',
        innerRadius: '75%',
        backgroundColor: Highcharts.color(Highcharts.getOptions().colors[2])
          .setOpacity(0.3)
          .get(),
        borderWidth: 0
      },
      { // Track for Move
        outerRadius: '74%',
        innerRadius: '62%',
        backgroundColor: Highcharts.color(Highcharts.getOptions().colors[3])
          .setOpacity(0.3)
          .get(),
        borderWidth: 0
      },
      { // Track for Move
        outerRadius: '61%',
        innerRadius: '49%',
        backgroundColor: Highcharts.color(Highcharts.getOptions().colors[4])
          .setOpacity(0.3)
          .get(),
        borderWidth: 0
      },
      { // Track for Move
        outerRadius: '48%',
        innerRadius: '36%',
        backgroundColor: Highcharts.color(Highcharts.getOptions().colors[5])
          .setOpacity(0.3)
          .get(),
        borderWidth: 0
      },
      { // Track for Move
        outerRadius: '35%',
        innerRadius: '23%',
        backgroundColor: Highcharts.color(Highcharts.getOptions().colors[7])
          .setOpacity(0.3)
          .get(),
        borderWidth: 0
      }]
  },

  yAxis: {
    min: 0,
    max: 100,
    lineWidth: 0,
    tickPositions: []
  },

  plotOptions: {
    solidgauge: {
      dataLabels: {
        enabled: false
      },
      linecap: 'round',
      stickyTracking: false,
      rounded: true
    }
  },

  series: [
    {
      name: 'Kutsealane areng ja kaasatus',
      color: Highcharts.getOptions().colors[0],
      data: [{
        color: Highcharts.getOptions().colors[0],
        radius: '100%',
        innerRadius: '88%',
        y: 76
      }],
      showInLegend:true,

    },
    {
      name: 'Digiõppevara',
      color: Highcharts.getOptions().colors[2],
      data: [{
        color: Highcharts.getOptions().colors[2],
        radius: '87%',
        innerRadius: '75%',
        y: 68
      }],
      showInLegend:true,

    },
    {
      name: 'Õpetamine ja õppimine',
      color: Highcharts.getOptions().colors[3],
      data: [{
        color: Highcharts.getOptions().colors[3],
        radius: '74%',
        innerRadius: '62%',
        y: 90
      }],
      showInLegend:true,

    },
    {
      name: 'Hindamine',
      color: Highcharts.getOptions().colors[4],
      data: [{
        color: Highcharts.getOptions().colors[4],
        radius: '61%',
        innerRadius: '49%',
        y: 92
      }],
      showInLegend:true,

    },
    {
      name: 'Õppijate võimestamine',
      color: Highcharts.getOptions().colors[5],
      data: [{
        color: Highcharts.getOptions().colors[5],
        radius: '48%',
        innerRadius: '36%',
        y: 85
      }],
      showInLegend:true,

    },
    {
      name: ' Õppijate digipädevuse ',
      color: Highcharts.getOptions().colors[7],
      data: [{
        color: Highcharts.getOptions().colors[7],
        radius: '35%',
        innerRadius: '23%',
        y: 75
      }],
      showInLegend:true,

    }]
});
