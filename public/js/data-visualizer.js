class FFAI_DataVisualizer {
    constructor() {
        this.charts = {};
        this.colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#FF6384', '#C9CBCF', '#7BC225', '#B56DB4'
        ];
    }

    initCharts() {
        this.createYieldTrendChart();
        this.createSoilHealthChart();
        this.createWeatherImpactChart();
        this.createCropComparisonChart();
        this.createProfitabilityChart();
    }

    createYieldTrendChart() {
        this.fetchData('yield_trend', (data) => {
            const ctx = document.getElementById('ffai-yield-trend-chart').getContext('2d');
            this.charts.yieldTrend = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.years,
                    datasets: data.crops.map((crop, index) => ({
                        label: crop.name,
                        data: crop.yields,
                        borderColor: this.colors[index % this.colors.length],
                        fill: false
                    }))
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Crop Yield Trends'
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Year'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Yield (tons/acre)'
                            }
                        }
                    }
                }
            });
        });
    }

    createSoilHealthChart() {
        this.fetchData('soil_health', (data) => {
            const ctx = document.getElementById('ffai-soil-health-chart').getContext('2d');
            this.charts.soilHealth = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['pH', 'Organic Matter', 'Nitrogen', 'Phosphorus', 'Potassium'],
                    datasets: [{
                        label: 'Current Levels',
                        data: data.current,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgb(255, 99, 132)',
                        pointBackgroundColor: 'rgb(255, 99, 132)'
                    }, {
                        label: 'Optimal Levels',
                        data: data.optimal,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgb(54, 162, 235)',
                        pointBackgroundColor: 'rgb(54, 162, 235)'
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Soil Health Indicators'
                    },
                    scale: {
                        ticks: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    }

    createWeatherImpactChart() {
        this.fetchData('weather_impact', (data) => {
            const ctx = document.getElementById('ffai-weather-impact-chart').getContext('2d');
            this.charts.weatherImpact = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.factors,
                    datasets: [{
                        label: 'Impact on Yield',
                        data: data.impact,
                        backgroundColor: this.colors
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Weather Factors Impact on Yield'
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Impact (%)'
                            }
                        }
                    }
                }
            });
        });
    }

    createCropComparisonChart() {
        this.fetchData('crop_comparison', (data) => {
            const ctx = document.getElementById('ffai-crop-comparison-chart').getContext('2d');
            this.charts.cropComparison = new Chart(ctx, {
                type: 'bubble',
                data: {
                    datasets: data.crops.map((crop, index) => ({
                        label: crop.name,
                        data: [{
                            x: crop.water_usage,
                            y: crop.profitability,
                            r: crop.land_usage * 5
                        }],
                        backgroundColor: this.colors[index % this.colors.length]
                    }))
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Crop Comparison: Water Usage vs Profitability vs Land Usage'
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Water Usage (gallons/acre)'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Profitability ($/acre)'
                            }
                        }
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var label = data.datasets[tooltipItem.datasetIndex].label;
                                return label + ': (' +
                                    'Water: ' + tooltipItem.xLabel + ' gal/acre, ' +
                                    'Profit: $' + tooltipItem.yLabel + '/acre, ' +
                                    'Land: ' + (tooltipItem.value / 5) + ' acres)';
                            }
                        }
                    }
                }
            });
        });
    }

    createProfitabilityChart() {
        this.fetchData('profitability', (data) => {
            const ctx = document.getElementById('ffai-profitability-chart').getContext('2d');
            this.charts.profitability = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.categories,
                    datasets: [{
                        data: data.values,
                        backgroundColor: this.colors
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Farm Profitability Breakdown'
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var label = data.labels[tooltipItem.index];
                                var value = data.datasets[0].data[tooltipItem.index];
                                return label + ': $' + value.toFixed(2);
                            }
                        }
                    }
                }
            });
        });
    }

    fetchData(endpoint, callback) {
        jQuery.ajax({
            url: ffai_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'ffai_get_chart_data',
                nonce: ffai_vars.nonce,
                chart: endpoint
            },
            success: function(response) {
                if (response.success) {
                    callback(response.data);
                } else {
                    console.error('Failed to fetch data for ' + endpoint);
                }
            },
            error: function() {
                console.error('Ajax request failed for ' + endpoint);
            }
        });
    }

    updateChart(chartName, newData) {
        if (this.charts[chartName]) {
            this.charts[chartName].data = newData;
            this.charts[chartName].update();
        }
    }
}

// Initialize the visualizer when the document is ready
jQuery(document).ready(function($) {
    window.ffaiVisualizer = new FFAI_DataVisualizer();
    ffaiVisualizer.initCharts();

    // Example of how to update a chart with new data
    $('#update-yield-trend').on('click', function() {
        ffaiVisualizer.fetchData('yield_trend', function(data) {
            ffaiVisualizer.updateChart('yieldTrend', data);
        });
    });
});