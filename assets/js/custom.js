$(document).ready(function () {
    // Hide the overlay initially
    $('.overlay').hide();

    $('#uploadForm').on('submit', function (e) {
        e.preventDefault();

        // Check if the file is selected
        if (!$('#csvFile')[0].files[0]) {
            showAlert("Please select a CSV file to upload.", 'danger');
            return;
        }

        // Show the overlay with the loader animation and text
        $('.overlay').show();

        // Create a new FormData instance and append the file to it
        var formData = new FormData();
        formData.append('csvFile', $('#csvFile')[0].files[0]);

        // Rest of your code for file upload...

        $.ajax({
            url: 'upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function () {
                var myXhr = $.ajaxSettings.xhr();
                if (myXhr.upload) {
                    myXhr.upload.addEventListener('progress', progressHandlingFunction, false);
                }
                return myXhr;
            },
            success: function (response) {
                if (response.status) {
                    showAlert(response.message, 'success');
                    // Display the execution time
                    $('#executionTime').html('Execution time: ' + response.execution_time).fadeIn();
                    setTimeout(function () {
                        $('#executionTime').fadeOut();
                    }, 10000);
                } else {
                    showAlert("Failed to upload file: " + response.message, 'danger');
                }
                $('.progress').hide();

                // Hide the overlay after the upload is complete
                $('.overlay').hide();
            },
            error: function (jqXHR) {
                var errorMessage = "An error occurred while uploading the file.";
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMessage = jqXHR.responseJSON.message;
                }
                showAlert(errorMessage, 'danger');
                $('.progress').hide();

                // Hide the overlay on error as well
                $('.overlay').hide();
            }
        });
    });

    // Rest of your code for progress handling, showAlert, etc...

    function progressHandlingFunction(e) {
        if (e.lengthComputable) {
            var max = e.total;
            var current = e.loaded;

            var percentage = Math.round((current * 100) / max);
            $('.bar').width(percentage + '%');
            $('.percent').html(percentage + '%');

            if (percentage >= 100) {
                // process completed
            }
        }
    }

    function showAlert(message, type) {
        $('#alertMessage').html('<div class="alert alert-' + type + '">' + message + '</div>').fadeIn();
        setTimeout(function () {
            $('#alertMessage').fadeOut();
        }, 10000);
    }
});