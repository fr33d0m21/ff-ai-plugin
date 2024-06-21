jQuery(document).ready(function($) {
    var cropPlannerForm = $('#ffai-crop-planner-form');
    var cropPlanResult = $('#ffai-crop-plan-result');
    var cropPlanContent = $('#ffai-crop-plan-content');
    var savePlanButton = $('#ffai-save-plan');
    var loadingSpinner = $('<div class="ffai-loading-spinner">Loading...</div>');

    cropPlannerForm.on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        showLoading();

        $.ajax({
            url: ffai_crop_planner_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'ffai_get_crop_plan',
                nonce: ffai_crop_planner_vars.nonce,
                ...formData
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    displayCropPlan(response.data);
                } else {
                    showError('Failed to generate crop plan. Please try again.');
                }
            },
            error: function() {
                hideLoading();
                showError('An error occurred. Please try again later.');
            }
        });
    });

    savePlanButton.on('click', function() {
        var planData = cropPlanContent.data('planData');
        showLoading();

        $.ajax({
            url: ffai_crop_planner_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'ffai_save_crop_plan',
                nonce: ffai_crop_planner_vars.nonce,
                plan_data: JSON.stringify(planData)
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showMessage(response.data);
                } else {
                    showError('Failed to save crop plan: ' + response.data);
                }
            },
            error: function() {
                hideLoading();
                showError('An error occurred while saving the plan. Please try again later.');
            }
        });
    });

    function displayCropPlan(planData) {
        var html = '<h5>' + ffai_crop_planner_vars.recommended_crops + '</h5>';
        html += '<ul class="ffai-crop-list">';
        planData.recommendations.forEach(function(crop, index) {
            html += '<li>';
            html += '<span class="ffai-crop-name">' + crop + '</span>';
            html += '<span class="ffai-planting-date">' + ffai_crop_planner_vars.plant_by + ': ' + planData.planting_dates[crop] + '</span>';
            html += '<button class="ffai-crop-info-btn" data-crop="' + crop + '">Info</button>';
            html += '</li>';
        });
        html += '</ul>';

        html += '<div id="ffai-crop-info-modal" class="ffai-modal">';
        html += '<div class="ffai-modal-content">';
        html += '<span class="ffai-modal-close">&times;</span>';
        html += '<h3 id="ffai-modal-crop-name"></h3>';
        html += '<div id="ffai-modal-crop-info"></div>';
        html += '</div>';
        html += '</div>';

        html += '<h5>' + ffai_crop_planner_vars.crop_rotation + '</h5>';
        html += '<div id="ffai-crop-rotation-chart"></div>';

        html += '<h5>' + ffai_crop_planner_vars.additional_info + '</h5>';
        html += '<p>' + planData.additional_info + '</p>';

        cropPlanContent.html(html);
        cropPlanContent.data('planData', planData);
        cropPlanResult.show();
        savePlanButton.show();

        initializeCropInfoModal();
        createCropRotationChart(planData.recommendations);
    }

    function initializeCropInfoModal() {
        var modal = $('#ffai-crop-info-modal');
        var closeBtn = modal.find('.ffai-modal-close');

        $('.ffai-crop-info-btn').on('click', function() {
            var crop = $(this).data('crop');
            showCropInfo(crop);
        });

        closeBtn.on('click', function() {
            modal.hide();
        });

        $(window).on('click', function(event) {
            if (event.target == modal[0]) {
                modal.hide();
            }
        });
    }

    function showCropInfo(crop) {
        var modal = $('#ffai-crop-info-modal');
        var modalCropName = $('#ffai-modal-crop-name');
        var modalCropInfo = $('#ffai-modal-crop-info');

        modalCropName.text(crop);
        modalCropInfo.html('Loading crop information...');
        modal.show();

        // Fetch crop information (you'll need to implement this API endpoint)
        $.ajax({
            url: ffai_crop_planner_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'ffai_get_crop_info',
                nonce: ffai_crop_planner_vars.nonce,
                crop: crop
            },
            success: function(response) {
                if (response.success) {
                    modalCropInfo.html(formatCropInfo(response.data));
                } else {
                    modalCropInfo.html('Failed to load crop information.');
                }
            },
            error: function() {
                modalCropInfo.html('An error occurred while loading crop information.');
            }
        });
    }

    function formatCropInfo(cropData) {
        var html = '<table class="ffai-crop-info-table">';
        html += '<tr><th>Growing Season:</th><td>' + cropData.growing_season + '</td></tr>';
        html += '<tr><th>Soil Requirements:</th><td>' + cropData.soil_requirements + '</td></tr>';
        html += '<tr><th>Water Needs:</th><td>' + cropData.water_needs + '</td></tr>';
        html += '<tr><th>Common Pests:</th><td>' + cropData.common_pests.join(', ') + '</td></tr>';
        html += '<tr><th>Nutrients:</th><td>' + cropData.nutrients + '</td></tr>';
        html += '</table>';
        return html;
    }

    function createCropRotationChart(crops) {
        var ctx = document.getElementById('ffai-crop-rotation-chart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: crops,
                datasets: [{
                    data: crops.map(() => 1), // Equal distribution for simplicity
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ]
                }]
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'Suggested Crop Rotation'
                }
            }
        });
    }

    function showLoading() {
        cropPlannerForm.append(loadingSpinner);
    }

    function hideLoading() {
        loadingSpinner.remove();
    }

    function showError(message) {
        $('<div class="ffai-error-message">' + message + '</div>')
            .insertBefore(cropPlannerForm)
            .delay(5000)
            .fadeOut(function() {
                $(this).remove();
            });
    }

    function showMessage(message) {
        $('<div class="ffai-success-message">' + message + '</div>')
            .insertBefore(cropPlannerForm)
            .delay(5000)
            .fadeOut(function() {
                $(this).remove();
            });
    }
});