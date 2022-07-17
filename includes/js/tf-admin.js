(function ($) {
  $(document).ready(function () {
    let $documentBody = $("body");
    if (
      $documentBody.hasClass("woocommerce_page_view-order-transactions") ||
      $documentBody.hasClass("woocommerce_page_view-payments")
    ) {
      // let $datepickers = jQuery("#list-date-start, #list-date-end");
      // let listStartDateDefault = jQuery("#list-date-start").val();
      // let listEndDateDefault = jQuery("#list-date-end").val();
      // $datepickers.datepicker();
      // $datepickers.datepicker("option", {
      //   showAnim: "slideDown",
      //   dateFormat: "yy-mm-dd",
      //   changeMonth: true,
      //   changeYear: true,
      // });
      // jQuery("#list-date-start").datepicker("setDate", listStartDateDefault);
      // jQuery("#list-date-end").datepicker("setDate", listEndDateDefault);
      loadDateSelect();
      loadDetailToggle();
    }
  });

  const loadDateSelect = () => {
    let $yearSelect = jQuery("#list-year-select");
    let $dtRangeSelect = jQuery("#list-date-range-container");
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

  const loadDetailToggle = () => {
    $(".display-details-btn")
      .off("click")
      .click(function (e) {
        e.preventDefault();
        let paymentId = $(this).data("id");
        $(`#payment-details-${paymentId}`).toggle();
      });
  };
})(jQuery);
