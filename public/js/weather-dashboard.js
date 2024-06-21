jQuery(document).ready(function($) {
    var weatherDashboard = $('#ffai-weather-dashboard');
    var locationInput = $('#ffai-weather-location');
    var searchButton = $('#ffai-weather-search');
    var weatherContent = $('#ffai-weather-content');

    var defaultLocation = weatherDashboard.data('location');
    if (defaultLocation) {
        getWeatherData(defaultLocation);
    }

    searchButton.on('click', function() {
        var location = locationInput.val().trim();
        if (location) {
            getWeatherData(location);
        }
    });

    function getWeatherData(location) {
        $.ajax({
            url: ffai_weather_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'ffai_get_weather_data',
                nonce: ffai_weather_vars.nonce,
                location: location
            },
            success: function(response) {
                if (response.success) {
                    displayWeatherData(response.data);
                } else {
                    alert('Failed to fetch weather data. Please try again.');
                }
            },
            error: function() {
                alert('An error occurred. Please try again later.');
            }
        });
    }

    function displayWeatherData(data) {
        var currentWeather = data.current;
        var forecast = data.forecast;
        var agriInsights = data.agri_insights;

        // Display current weather
        var currentHtml = '<h4>' + ffai_weather_vars.current_weather + '</h4>' +
            '<p>' + ffai_weather_vars.temperature + ': ' + currentWeather.temperature + '°C</p>' +
            '<p>' + ffai_weather_vars.humidity + ': ' + currentWeather.humidity + '%</p>' +
            '<p>' + ffai_weather_vars.wind_speed + ': ' + currentWeather.wind_speed + ' m/s</p>' +
            '<p>' + ffai_weather_vars.description + ': ' + currentWeather.description + '</p>';
        
        $('#ffai-weather-current').html(currentHtml);

        // Display forecast
        var forecastHtml = '<h4>' + ffai_weather_vars.forecast + '</h4>';
        forecastHtml += '<canvas id="ffai-weather-chart"></canvas>';
        $('#ffai-weather-forecast').html(forecastHtml);

        // Create chart
        createForecastChart(forecast);

        // Display agricultural insights
        var insightsHtml = '<h4>' + ffai_weather_vars.agri_insights + '</h4><ul>';
        agriInsights.forEach(function(insight) {
            insightsHtml += '<li>' + insight + '</li>';
        });
        insightsHtml += '</ul>';
        $('#ffai-weather-agriculture').html(insightsHtml);
    }

    function createForecastChart(forecast) {
        var ctx = document.getElementById('ffai-weather-chart').getContext('2d');
        var dates = forecast.map(function(item) { return item.date; });
        var temperatures = forecast.map(function(item) { return item.temperature; });
        var precipitations = forecast.map(function(item) { return item.precipitation; });

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: ffai_weather_vars.temperature,
                    data: temperatures,
                    borderColor: 'rgb(255, 99, 132)',
                    yAxisID: 'y-temperature',
                }, {
                    label: ffai_weather_vars.precipitation,
                    data: precipitations,
                    borderColor: 'rgb(54, 162, 235)',
                    yAxisID: 'y-precipitation',
                }]
            },
            options: {
                scales: {
                    'y-temperature': {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: ffai_weather_vars.temperature + ' (°C)'
                        }
                    },
                    'y-precipitation': {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: ffai_weather_vars.precipitation + ' (mm)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
});