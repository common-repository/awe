jQuery( function ($) {

	var data = [];
	for( var key in sources_percentages ){
		data.push( [ sources_percentages[key]['title'], sources_percentages[key]['percentage'] ] );
	}

    $('#awe-widget-sources').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false
        },
        title: {
            text: ''
        },
        tooltip: {
			pointFormat: '<b>{point.percentage}%</b>',
			percentageDecimals: 1
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: false,
                    color: '#000000',
                    connectorColor: '#000000',
                    formatter: function() {
                        return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %';
                    }
                }
            }
        },
        series: [{
            type: 'pie',
            name: 'Total',
            data: data
        }]
    });
});

