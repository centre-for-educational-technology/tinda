/*
 Highcharts JS v8.0.4 (2020-03-10)

 Solid angular gauge module

 (c) 2010-2019 Torstein Honsi

 License: www.highcharts.com/license
*/
(function(b){"object"===typeof module&&module.exports?(b["default"]=b,module.exports=b):"function"===typeof define&&define.amd?define("highcharts/modules/solid-gauge",["highcharts","highcharts/highcharts-more"],function(e){b(e);b.Highcharts=e;return b}):b("undefined"!==typeof Highcharts?Highcharts:void 0)})(function(b){function e(b,q,e,h){b.hasOwnProperty(q)||(b[q]=h.apply(null,e))}b=b?b._modules:{};e(b,"modules/solid-gauge.src.js",[b["parts/Globals.js"],b["parts/Color.js"],b["mixins/legend-symbol.js"],
  b["parts/Utilities.js"]],function(b,e,x,h){var l=e.parse,q=h.clamp,u=h.extend,v=h.isNumber,y=h.merge,t=h.pick,w=h.pInt;e=h.seriesType;h=h.wrap;h(b.Renderer.prototype.symbols,"arc",function(a,b,g,d,k,c){a=a(b,g,d,k,c);c.rounded&&(d=((c.r||d)-c.innerR)/2,c=["A",d,d,0,1,1,a[12],a[13]],a.splice.apply(a,[a.length-1,0].concat(["A",d,d,0,1,1,a[1],a[2]])),a.splice.apply(a,[11,3].concat(c)));return a});var z={initDataClasses:function(a){var b=this.chart,g,d=0,k=this.options;this.dataClasses=g=[];a.dataClasses.forEach(function(c,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             f){c=y(c);g.push(c);c.color||("category"===k.dataClassColor?(f=b.options.colors,c.color=f[d++],d===f.length&&(d=0)):c.color=l(k.minColor).tweenTo(l(k.maxColor),f/(a.dataClasses.length-1)))})},initStops:function(a){this.stops=a.stops||[[0,this.options.minColor],[1,this.options.maxColor]];this.stops.forEach(function(a){a.color=l(a[1])})},toColor:function(a,b){var g=this.stops,d=this.dataClasses,k;if(d)for(k=d.length;k--;){var c=d[k];var f=c.from;g=c.to;if(("undefined"===typeof f||a>=f)&&("undefined"===
    typeof g||a<=g)){var h=c.color;b&&(b.dataClass=k);break}}else{this.isLog&&(a=this.val2lin(a));a=1-(this.max-a)/(this.max-this.min);for(k=g.length;k--&&!(a>g[k][0]););f=g[k]||g[k+1];g=g[k+1]||f;a=1-(g[0]-a)/(g[0]-f[0]||1);h=f.color.tweenTo(g.color,a)}return h}};e("solidgauge","gauge",{colorByPoint:!0,dataLabels:{y:0}},{drawLegendSymbol:x.drawRectangle,translate:function(){var a=this.yAxis;u(a,z);!a.dataClasses&&a.options.dataClasses&&a.initDataClasses(a.options);a.initStops(a.options);b.seriesTypes.gauge.prototype.translate.call(this)},
  drawPoints:function(){var a=this,b=a.yAxis,g=b.center,d=a.options,k=a.chart.renderer,c=d.overshoot,h=v(c)?c/180*Math.PI:0,e;v(d.threshold)&&(e=b.startAngleRad+b.translate(d.threshold,null,null,null,!0));this.thresholdAngleRad=t(e,b.startAngleRad);a.points.forEach(function(c){if(!c.isNull){var e=c.graphic,f=b.startAngleRad+b.translate(c.y,null,null,null,!0),r=w(t(c.options.radius,d.radius,100))*g[2]/200,m=w(t(c.options.innerRadius,d.innerRadius,60))*g[2]/200,n=b.toColor(c.y,c),p=Math.min(b.startAngleRad,
    b.endAngleRad),l=Math.max(b.startAngleRad,b.endAngleRad);"none"===n&&(n=c.color||a.color||"none");"none"!==n&&(c.color=n);f=q(f,p-h,l+h);!1===d.wrap&&(f=q(f,p,l));p=Math.min(f,a.thresholdAngleRad);f=Math.max(f,a.thresholdAngleRad);f-p>2*Math.PI&&(f=p+2*Math.PI);c.shapeArgs=m={x:g[0],y:g[1],r:r,innerR:m,start:p,end:f,rounded:d.rounded};c.startR=r;e?(r=m.d,e.animate(u({fill:n},m)),r&&(m.d=r)):c.graphic=e=k.arc(m).attr({fill:n,"sweep-flag":0}).add(a.group);a.chart.styledMode||("square"!==d.linecap&&
  e.attr({"stroke-linecap":"round","stroke-linejoin":"round"}),e.attr({stroke:d.borderColor||"none","stroke-width":d.borderWidth||0}));e&&e.addClass(c.getClassName(),!0)}})},animate:function(a){a||(this.startAngleRad=this.thresholdAngleRad,b.seriesTypes.pie.prototype.animate.call(this,a))}});""});e(b,"masters/modules/solid-gauge.src.js",[],function(){})});
//# sourceMappingURL=solid-gauge.js.map