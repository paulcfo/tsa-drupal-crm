(function ($, Drupal, once) {
  // Add a datepicker to the date field on the query wizard form
  // This is a jQuery UI datepicker, so we need to load the jQuery UI library
  Drupal.behaviors.datePickerAttach = {
    attach: function (context, settings) {
      $("#edit-field-attendance-date-value-value", context).datepicker({
        dateFormat: "yy-mm-dd",
      });
      $("#edit-field-attendance-date-value-min", context).datepicker({
        dateFormat: "yy-mm-dd",
      });
      $("#edit-field-attendance-date-value-max", context).datepicker({
        dateFormat: "yy-mm-dd",
      });
    },
  };

  Drupal.behaviors.uploadResults = {
    attach: function (context, settings) {
      $("#student-select-list", context)
        .change(function () {
          var studentInfo = $("#student-select-list option:selected").text();
          var studentId = $("#student-select-list option:selected").val(); // this is also the node id
          console.log(studentId);
          $("#student-name").text(studentInfo);
        }).change();
    },
  };

  Drupal.behaviors.switchColumnOptions = {
    attach: function (context, settings) {
      var columnElement = $("#edit-flexible-tables-fieldset", context);
      var detailsElement = $(".flexible-views-manual-selection-details", context);

      if (columnElement.length && detailsElement.length) {
        var clonedColumn = columnElement.clone();
        columnElement.remove();
        detailsElement.before(clonedColumn);
        detailsElement.prop("open", true);
      }
    },
  };

})(jQuery, Drupal, drupalSettings, once);


