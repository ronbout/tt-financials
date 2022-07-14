(function ($) {
  $(document).ready(function () {
    let $documentBody = $("body");
    $documentBody.hasClass("woocommerce_page_view-order-transactions") &&
      loadDateSelect();
  });

  const loadDateSelect = () => {
    let $yearSelect = jQuery("#trans-year-select");
    // let $dtRangeSelect = jQuery("#trans-date-range-container");
    $("#filter-by-date").change(function () {
      let dtSelectType = jQuery(this).val();
      if ("year" === dtSelectType) {
        // $dtRangeSelect.hide(300);
        $yearSelect.show(300);
      } else if ("custom" === dtSelectType) {
        $yearSelect.hide(300);
        // $dtRangeSelect.show(300);
      } else {
        $yearSelect.hide(300);
        // $dtRangeSelect.hide(300);
      }
    });
  };
})(jQuery);
