(function ($) {
  $(document).ready(function () {
    let $documentBody = $("body");
    if (
      $documentBody.hasClass("woocommerce_page_view-order-transactions") ||
      $documentBody.hasClass("woocommerce_page_view-payments") ||
      $documentBody.hasClass("woocommerce_page_view-venues")
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
      $("#filter-by-date").length && loadDateSelect();
      $(".display-details-btn").length && loadDetailToggle();
      $(".check-venue-product-payment").length && loadPaymentCheckboxes();
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
        let id = $(this).data("id");
        $(`#list-details-${id}`).toggle(600);
      });
  };

  const loadPaymentCheckboxes = () => {
    $("#cb-select-all-1").change(function (e) {
      let $cb = $(this);
      let checkboxChecked = $cb.prop("checked");
      $(".check-venue-product-payment").prop("checked", checkboxChecked);
      let $selectOrdQty = $(".select-order-qty");
      let $selectOrdAmt = $(".select-order-amt");
      if (checkboxChecked) {
        $selectOrdQty.each(function () {
          $(this).text($(this).data("qty"));
        });
        $selectOrdAmt.each(function () {
          $(this).text($(this).data("amt"));
        });
      } else {
        $selectOrdQty.each(function () {
          $(this).text("0");
        });
        $selectOrdAmt.each(function () {
          $(this).text("0.00");
        });
      }
    });

    $(".venues-list-bulk-cb").change(function (e) {
      let $cb = $(this);
      let venueId = $cb.val();
      let checkboxChecked = $cb.prop("checked");
      $(`.venue-payment-${venueId}`).prop("checked", checkboxChecked);
      let $selectOrdQty = $(`.details-row-${venueId} .select-order-qty`);
      let $selectOrdAmt = $(`.details-row-${venueId} .select-order-amt`);
      if (checkboxChecked) {
        $selectOrdQty.each(function () {
          $(this).text($(this).data("qty"));
        });
        $selectOrdAmt.each(function () {
          $(this).text($(this).data("amt"));
        });
      } else {
        $selectOrdQty.each(function () {
          $(this).text("0");
        });
        $selectOrdAmt.each(function () {
          $(this).text("0.00");
        });
      }
    });

    $(".check-venue-product-payment").change(function (e) {
      let $cb = $(this);
      let checkboxChecked = $cb.prop("checked");
      let venueProd = $cb.val();
      let $selectOrdQty = $(`#oq-${venueProd}`);
      let $selectOrdAmt = $(`#oa-${venueProd}`);
      if (checkboxChecked) {
        $selectOrdQty.each(function () {
          $(this).text($(this).data("qty"));
        });
        $selectOrdAmt.each(function () {
          $(this).text($(this).data("amt"));
        });
      } else {
        $selectOrdQty.each(function () {
          $(this).text("0");
        });
        $selectOrdAmt.each(function () {
          $(this).text("0.00");
        });
      }
    });
  };

  $("#doaction").click(function (e) {
    let bulkAction = $("#bulk-action-selector-top").val();
    if ("make_payment" === bulkAction) {
      e.preventDefault();
      makePayments();
    }
  });

  const makePayments = () => {
    // select all checked products and put into array of objects by venue id
  };
})(jQuery);
