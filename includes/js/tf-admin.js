(function ($) {
  $(document).ready(function () {
    let $documentBody = $("body");
    if (
      $documentBody.hasClass("woocommerce_page_view-order-transactions") ||
      $documentBody.hasClass("woocommerce_page_view-payments") ||
      $documentBody.hasClass("woocommerce_page_view-venues")
    ) {
      $("#filter-by-date").length && loadDateSelect();
      $(".display-details-btn").length && loadDetailToggle();
      $(".check-venue-product-payment").length && loadPaymentCheckboxes();
      $("#run-build-trans").length && tfLoadRunTransButton();
      $("#tf-order-trans-form").length && tfLoadOrderTransFormSubmit();
    }
  });

  const loadDateSelect = () => {
    const $yearSelect = $("#list-year-select");
    const $dtRangeSelect = $("#list-date-range-container");
    const $dtInputs = $("#list-date-start, #list-date-end");
    $("#filter-by-date").change(function () {
      let dtSelectType = $(this).val();
      if ("year" === dtSelectType) {
        $dtRangeSelect.hide(300);
        $dtInputs.attr("disabled", true);
        $yearSelect.attr("disabled", false);
        $yearSelect.show(300);
      } else if ("custom" === dtSelectType) {
        $yearSelect.hide(300);
        $yearSelect.attr("disabled", true);
        $dtInputs.attr("disabled", false);
        $dtRangeSelect.show(300);
      } else {
        $yearSelect.hide(300);
        $dtRangeSelect.hide(300);
        $yearSelect.attr("disabled", true);
        $dtInputs.attr("disabled", true);
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
    $("[id^=cb-select-all-]").each(function () {
      $(this).change(function () {
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
        checkMakePaymentDisabled();
      });
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
      checkMakePaymentDisabled();
    });

    $(".check-venue-product-payment").change(function (e) {
      const $cb = $(this);
      const checkboxChecked = $cb.prop("checked");
      const venueProd = $cb.val();
      const venueId = venueProd.split("-")[0];
      const $selectOrdQty = $(`#oq-${venueProd}`);
      const $selectOrdAmt = $(`#oa-${venueProd}`);
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
      checkMakePaymentDisabled();
      checkVenueCheckbox(venueId);
      checkVenueCheckAll();
    });
    checkMakePaymentDisabled();
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
    let paymentObj = {};
    $(".check-venue-product-payment:checked").each((ndx, chckbox) => {
      let $rowData = $(chckbox).closest("tr");
      let venueId = $rowData.data("venue-id");
      let productId = $rowData.data("product-id");
      let paymentAmt = $rowData.data("amt");
      let orderInfo = $rowData.data("order-info");
      if (!orderInfo.length) return;
      let paymentInfo = {
        productId,
        paymentAmt,
        orderInfo,
      };
      if (paymentObj.hasOwnProperty(venueId)) {
        paymentObj[venueId].push(paymentInfo);
      } else {
        paymentObj[venueId] = [paymentInfo];
      }
    });

    if (!Object.keys(paymentObj).length) {
      alert(
        "No valid products were selected for payment.  Balance due amounts may have been too small for a single order of that product."
      );
      return;
    }

    $.ajax({
      url: tasteFinancial.ajaxurl,
      type: "POST",
      datatype: "JSON",
      data: {
        action: "venues_page_make_payment",
        security: tasteFinancial.security,
        payment_info: paymentObj,
      },
      success: function (responseText) {
        console.log(responseText);
        const parseResponse = JSON.parse(responseText);
        if (parseResponse.hasOwnProperty("success")) {
          window.location.reload(true);
        } else {
          if (parseResponse.hasOwnProperty("error")) {
            console.log(parseResponse.error);
            alert(parseResponse.error);
          } else {
            alert("Unknown response from server");
          }
        }
      },
      error: function (xhr, status, errorThrown) {
        console.log(errorThrown);
        alert(
          "Error making payment. Your login may have timed out. Please refresh the page and try again."
        );
      },
    });
  };

  const checkMakePaymentDisabled = () => {
    let $bulkSelector = $("[id^=bulk-action-selector-]");
    let $makePaymentOption = $bulkSelector.children(
      "option[value='make_payment']"
    );
    if ($(".check-venue-product-payment:checked").length) {
      $makePaymentOption.prop("disabled", false);
    } else {
      $makePaymentOption.attr("disabled", true);
      $bulkSelector.each(function () {
        if ("make_payment" === $(this).val() || null === $(this).val()) {
          $(this).val(-1).change();
        }
      });
    }
  };

  const checkVenueCheckbox = (venueId) => {
    const $venueCbs = $(`.venue-payment-${venueId}`);
    const venueCbCount = $venueCbs.length;
    const venueCbCheckedCount = $(`.venue-payment-${venueId}:checked`).length;
    const venueChckBox = $(`.venues-list-bulk-cb[value='${venueId}']`);
    if (venueCbCheckedCount === venueCbCount) {
      $(`.venues-list-bulk-cb[value='${venueId}']`).prop("checked", true);
    } else {
      $(`.venues-list-bulk-cb[value='${venueId}']`).prop("checked", false);
    }
  };

  const checkVenueCheckAll = () => {
    const $venueRowCbs = $(".venues-list-bulk-cb");
    const venueRowCount = $venueRowCbs.length;
    const venueRowCheckedCount = $(".venues-list-bulk-cb:checked").length;
    const $venueAllChckBox = $("[id^=cb-select-all-]");
    if (venueRowCheckedCount === venueRowCount) {
      $venueAllChckBox.prop("checked", true);
    } else {
      $venueAllChckBox.prop("checked", false);
    }
  };

  const tfRunTransBuild = (startDate, page) => {
    $("#trans-update-spinner").addClass("is-active");
    $.ajax({
      url: tasteFinancial.ajaxurl,
      type: "POST",
      datatype: "html",
      data: {
        action: "build_trans_bulk",
        security: tasteFinancial.security,
        start_date: startDate,
      },
      success: function (responseText) {
        $("#trans-update-spinner").removeClass("is-active");
        console.log(responseText);
        if ("view-order-transactions" === page) {
          const resultsId = "trans-refresh-results";
          $(`#${resultsId}`).html(responseText);
          const tbTitle = "Transactions Table Update Results";
          const tbHRef = `#TB_inline?height=255&width=400&inlineId=${resultsId}`;
          tb_show(tbTitle, tbHRef, false);
        } else {
          $("#results").html(responseText);
        }
      },
      error: function (xhr, status, errorThrown) {
        $("#trans-update-spinner").removeClass("is-active");
        console.log(errorThrown);
        alert(
          "Error building trans table. Your login may have timed out. Please refresh the page and try again."
        );
      },
    });
  };

  const tfLoadRunTransButton = () => {
    $("#run-build-trans")
      .off("click")
      .click(function (e) {
        e.preventDefault();
        const startDate = $("#trans_update_start_date").val();
        const page = $(this).data("page");
        tfRunTransBuild(startDate, page);
      });
  };

  const tfLoadOrderTransFormSubmit = () => {
    // turn off filters that are not used to clean up the url
    // const formId = "#tf-order-trans-form";
    // $(formId)
    //   .off("submit")
    //   .on("submit", function (e) {
    //     const dropdowns = [
    //       "bulk-action-selector-top",
    //       "bulk-action-selector-bottom",
    //       "trans-list-venue-selection",
    //     ];
    //     dropdowns.forEach((selectId) => {
    //       let $select = $(`#${selectId}`);
    //       if ("-1" == $select.val()) {
    //         $select.val("");
    //       }
    //     });
    //   });
  };
})(jQuery);
