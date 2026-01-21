// File Upload Handler with Drag & Drop and Image Preview
$(document).ready(function() {
    var uploadedFile = null;
    var uploadedFileName = '';

    // Create hidden file input
    if ($('#paymentProofInput').length === 0) {
        $('<input>', {
            type: 'file',
            id: 'paymentProofInput',
            accept: 'image/*',
            style: 'display: none;'
        }).appendTo('body');
    }

    var $uploadArea = $('#uploadArea');
    var $fileInput = $('#paymentProofInput');

    // Click to browse
    $('.browser-btn, #uploadArea').on('click', function(e) {
        e.preventDefault();
        $fileInput.click();
    });

    // Handle file selection via browse
    $fileInput.on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            handleFile(file);
        }
    });

    // Prevent default drag behaviors
    $(document).on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });

    // Highlight drop area when file is dragged over it
    $uploadArea.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-over');
    });

    $uploadArea.on('dragleave dragend drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
    });

    // Handle dropped files
    $uploadArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            var file = files[0];
            handleFile(file);
        }
    });

    // Handle file processing
    function handleFile(file) {
        // Validate file type
        if (!file.type.match('image.*')) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: 'Please upload an image file (PNG, JPG, JPEG, GIF)',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Validate file size (max 5MB)
        var maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (file.size > maxSize) {
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'Please upload an image smaller than 5MB',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Store the file
        uploadedFile = file;
        uploadedFileName = file.name;

        // Read and preview the image
        var reader = new FileReader();
        reader.onload = function(e) {
            displayPreview(e.target.result, file.name, file.size);
        };
        reader.readAsDataURL(file);
    }

    // Display image preview
    function displayPreview(imageSrc, fileName, fileSize) {
        var fileSizeKB = (fileSize / 1024).toFixed(2);
        
        // Get the preview template from the page
        var $previewTemplate = $('#uploadPreviewTemplate').clone();
        $previewTemplate.removeAttr('id');
        $previewTemplate.find('.preview-image').attr('src', imageSrc);
        $previewTemplate.find('.filename-text').text(fileName);
        $previewTemplate.find('.filesize-text').text(fileSizeKB + ' KB');
        $previewTemplate.show();
        
        $uploadArea.html($previewTemplate.html());
        $uploadArea.addClass('has-file');
    }

    // Remove file handler
    $(document).on('click', '.remove-file-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        Swal.fire({
            icon: 'question',
            title: 'Remove File?',
            text: 'Are you sure you want to remove this file?',
            showCancelButton: true,
            confirmButtonText: 'Yes, Remove',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                resetUploadArea();
            }
        });
    });

    // Reset upload area
    function resetUploadArea() {
        uploadedFile = null;
        uploadedFileName = '';
        $fileInput.val('');
        
        // Get the default template from the page
        var $defaultTemplate = $('#uploadDefaultTemplate').clone();
        $defaultTemplate.removeAttr('id');
        $defaultTemplate.show();
        
        $uploadArea.html($defaultTemplate.html());
        $uploadArea.removeClass('has-file drag-over');
    }

    // Get uploaded file (for submission)
    window.getUploadedPaymentProof = function() {
        return {
            file: uploadedFile,
            fileName: uploadedFileName
        };
    };

    // Validation function
    window.validatePaymentProof = function() {
        if (!uploadedFile) {
            Swal.fire({
                icon: 'warning',
                title: 'Payment Proof Required',
                text: 'Please upload your payment proof screenshot before proceeding.',
                confirmButtonText: 'OK'
            });
            return false;
        }
        return true;
    };
});