/**
 * @file
 * dropzone_default_values.js
 *
 * Checks if the Dropzone field has default values and attaches them.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.dropzoneDefaultValues = {
    attach: function (context) {

      $('.dropzone', context).once('dropzoneField').each(function () {
        // This is the value that is set for the dropzonejs instance.
        var dropzoneId = $(this).attr('id');
        var selector = $('#' + dropzoneId);
        var input = selector.siblings('input');
        var removeFilePath = input.attr('data-remove-path');

        if (
            typeof drupalSettings.qualificationTestRounds == "undefined" ||
            drupalSettings.qualificationTestRounds[dropzoneId].length < 1 ||
            drupalSettings.dropzonejs.instances[dropzoneId].instance.length < 1) {
          return;
        }

        // Get the correct dropzonejs instance.
        var thisDropzone = drupalSettings.dropzonejs.instances[dropzoneId].instance;

        // Get the file information.
        var data = drupalSettings.qualificationTestRounds[dropzoneId];
        var files = data.files;

        if (files.length === 0) {
          // Make sure the remove links are shown. We only want this to happen
          // when they are uploading files on the frontend.
          thisDropzone.options.addRemoveLinks = true;
        }

        if (files !== null && files.length > 0) {
          thisDropzone.options.addRemoveLinks = true;
          // Loop through all files attached to this field and attach to the
          // correct dropzoneJS instance.
          files.forEach(function (file) {
            thisDropzone.options.addedfile.call(thisDropzone, file);

            // Display a thumb of the file if it is an image.
            if (file.is_image) {
              thisDropzone.options.thumbnail.call(thisDropzone, file, file.path);
            }

            thisDropzone.emit('success', file, { result: file.name });
            thisDropzone.emit('complete', file);
          });
        }

        thisDropzone.on("removedfile", function (file) {
          $.ajax({
            url: removeFilePath,
            type: "POST",
            data: {
              name: file.name,
            },
          });
        });

      });

    },
  };

}(jQuery, Drupal, drupalSettings));
