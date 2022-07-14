(function ($) {
  $(document).ready(function () {
    let $documentBody = $("body");
    if ($documentBody.hasClass("woocommerce_page_view-order-transactions")) {
      let $datepickers = jQuery("#trans-date-start, #trans-date-end");
      let transStartDateDefault = jQuery("#trans-date-start").val();
      let transEndDateDefault = jQuery("#trans-date-end").val();
      $datepickers.datepicker();
      $datepickers.datepicker("option", {
        showAnim: "slideDown",
        dateFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true,
      });
      jQuery("#trans-date-start").datepicker("setDate", transStartDateDefault);
      jQuery("#trans-date-end").datepicker("setDate", transEndDateDefault);
      loadDateSelect();
    }
  });

  const loadDateSelect = () => {
    let $yearSelect = jQuery("#trans-year-select");
    let $dtRangeSelect = jQuery("#trans-date-range-container");
    $("#filter-by-date").change(function () {
      let dtSelectType = jQuery(this).val();
      if ("year" === dtSelectType) {
        $dtRangeSelect.hide(300);
        $yearSelect.show(300);
      } else if ("custom" === dtSelectType) {
        $yearSelect.hide(300);
        $dtRangeSelect.show(300);
      } else {
        $yearSelect.hide(300);
        $dtRangeSelect.hide(300);
      }
    });
  };
})(jQuery);
