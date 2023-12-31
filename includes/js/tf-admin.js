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
      $(".check-venue-product-payment").length && loadVenuePaymentCheckboxes();
      $(".payments-list-bulk-cb").length && loadPaymentListPageCheckboxes();
      $(".transactions-list-bulk-cb").length &&
        loadTransactionListPageCheckboxes();
    }

    if (
      $documentBody.hasClass("woocommerce_page_wc-settings") ||
      $documentBody.hasClass("woocommerce_page_view-order-transactions")
    ) {
      $("#run-build-trans").length && tfLoadRunTransButton();
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

  const loadVenuePaymentCheckboxes = () => {
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

    $("#doaction").click(function (e) {
      let bulkAction = $("#bulk-action-selector-top").val();
      if ("make_payment" === bulkAction) {
        e.preventDefault();
        tfMakePayments();
      } else if ("bulk-export" === bulkAction) {
        e.preventDefault();
        const dt = new Date();
        const dateStr =
          dt.getFullYear() + "-" + dt.getMonth() + "-" + dt.getDate();
        let outputFile = `export-venues-${dateStr}.csv`;
        // CSV
        tfExportTableToCSV.apply(this, [
          jQuery("#tf-venues-form table.venues"),
          outputFile,
          "venues",
        ]);
      } else {
        e.preventDefault();
      }
    });
  };

  const loadPaymentListPageCheckboxes = () => {
    $("[id^=cb-select-all-]").change(function () {
      checkMarkPaidDisabled();
    });

    $("#doaction").click(function (e) {
      let bulkAction = $("#bulk-action-selector-top").val();
      if ("mark-paid" === bulkAction) {
        e.preventDefault();
        tfMakePaidPaymentStatus();
      } else if ("bulk-export" === bulkAction) {
        e.preventDefault();
        const dt = new Date();
        const dateStr =
          dt.getFullYear() + "-" + dt.getMonth() + "-" + dt.getDate();
        let outputFile = `export-payments-${dateStr}.csv`;
        // CSV
        tfExportTableToCSV.apply(this, [
          jQuery("#tf-payments-form table.payments"),
          outputFile,
          "payments",
        ]);
      } else {
        e.preventDefault();
      }
    });

    $(".payments-list-bulk-cb").change(function (e) {
      checkMarkPaidDisabled();
    });
    checkMarkPaidDisabled();
  };

  const loadTransactionListPageCheckboxes = () => {
    $("[id^=cb-select-all-]").change(function () {
      checkExportDisabled();
    });

    $("#doaction").click(function (e) {
      let bulkAction = $("#bulk-action-selector-top").val();
      if ("bulk-export" === bulkAction) {
        e.preventDefault();
        const dt = new Date();
        const dateStr =
          dt.getFullYear() + "-" + dt.getMonth() + "-" + dt.getDate();
        let outputFile = `export-transactions-${dateStr}.csv`;
        // CSV
        tfExportTableToCSV.apply(this, [
          jQuery("#tf-transactions-form table.transactions"),
          outputFile,
          "transactions",
        ]);
      } else {
        e.preventDefault();
      }
    });

    $(".transactions-list-bulk-cb").change(function (e) {
      checkExportDisabled();
    });
    checkExportDisabled();
  };

  const tfMakePayments = () => {
    $("#venues-list-page-spinner").addClass("is-active");
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
        $("#venues-list-page-spinner").removeClass("is-active");
        console.log(responseText);
        const parseResponse = JSON.parse(responseText);
        if (parseResponse.hasOwnProperty("success")) {
          window.location.reload(true);
        } else {
          if (parseResponse.hasOwnProperty("error")) {
            console.log(parseResponse.error);
            alert(parseResponse.error);
          } else {
            alert("Unknown error from server");
          }
        }
      },
      error: function (xhr, status, errorThrown) {
        $("#venues-list-page-spinner").removeClass("is-active");
        console.log(errorThrown);
        alert(
          "Error making payment. Your login may have timed out. Please refresh the page and try again."
        );
      },
    });
  };

  const tfMakePaidPaymentStatus = () => {
    $("#payments-list-page-spinner").addClass("is-active");
    // select all checked products and put into array of objects by venue id
    let paymentArray = [];
    $(".payments-list-bulk-cb:checked").each(function () {
      const paymentId = $(this).data("id");
      paymentArray.push(paymentId);
    });

    $.ajax({
      url: tasteFinancial.ajaxurl,
      type: "POST",
      datatype: "JSON",
      data: {
        action: "payments_page_mark_paid",
        security: tasteFinancial.security,
        payment_list: paymentArray,
      },
      success: function (responseText) {
        $("#payments-list-page-spinner").removeClass("is-active");
        console.log(responseText);
        const parseResponse = JSON.parse(responseText);
        if (parseResponse.hasOwnProperty("success")) {
          window.location.reload(true);
        } else {
          if (parseResponse.hasOwnProperty("error")) {
            console.log(parseResponse.error);
            alert(parseResponse.error);
          } else {
            alert("Unknown error from server");
          }
        }
      },
      error: function (xhr, status, errorThrown) {
        $("#payments-list-page-spinner").removeClass("is-active");
        console.log(errorThrown);
        alert(
          "Error updating Payment Status. Your login may have timed out. Please refresh the page and try again."
        );
      },
    });
  };

  const checkMakePaymentDisabled = () => {
    let $bulkSelector = $("[id^=bulk-action-selector-]");
    let $makePaymentOption = $bulkSelector.children(
      "option[value='make_payment']"
    );
    let $bulkExportOption = $bulkSelector.children(
      "option[value='bulk-export']"
    );
    if ($(".check-venue-product-payment:checked").length) {
      $makePaymentOption.prop("disabled", false);
      $bulkExportOption.prop("disabled", false);
    } else {
      $makePaymentOption.attr("disabled", true);
      $bulkExportOption.attr("disabled", true);
      $bulkSelector.each(function () {
        if (
          "make_payment" === $(this).val() ||
          "bulk-export" === $(this).val() ||
          null === $(this).val()
        ) {
          $(this).val(-1).change();
        }
      });
    }
  };

  const checkExportDisabled = () => {
    let $bulkSelector = $("[id^=bulk-action-selector-]");
    let $bulkExportOption = $bulkSelector.children(
      "option[value='bulk-export']"
    );
    if ($(".transactions-list-bulk-cb:checked").length) {
      $bulkExportOption.prop("disabled", false);
    } else {
      $bulkExportOption.attr("disabled", true);
      $bulkSelector.each(function () {
        if ("bulk-export" === $(this).val() || null === $(this).val()) {
          $(this).val(-1).change();
        }
      });
    }
  };

  const checkMarkPaidDisabled = () => {
    let $bulkSelector = $("[id^=bulk-action-selector-]");
    let $markPaidOption = $bulkSelector.children("option[value='mark-paid']");
    let $bulkExportOption = $bulkSelector.children(
      "option[value='bulk-export']"
    );
    if ($(".payments-list-bulk-cb:checked").length) {
      $markPaidOption.prop("disabled", false);
      $bulkExportOption.prop("disabled", false);
    } else {
      $markPaidOption.attr("disabled", true);
      $bulkExportOption.attr("disabled", true);
      $bulkSelector.each(function () {
        if (
          "mark-paid" === $(this).val() ||
          "bulk-export" === $(this).val() ||
          null === $(this).val()
        ) {
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

  const tfRunTransBuild = (startDate, page, deleteFlg = 0) => {
    const spinnerId = deleteFlg
      ? "trans-update-spinner-rebuild"
      : "trans-update-spinner";
    $(`#${spinnerId}`).addClass("is-active");
    $.ajax({
      url: tasteFinancial.ajaxurl,
      type: "POST",
      datatype: "html",
      data: {
        action: "build_trans_bulk",
        security: tasteFinancial.security,
        start_date: startDate,
        delete_flag: deleteFlg,
      },
      success: function (responseText) {
        $(`#${spinnerId}`).removeClass("is-active");
        console.log(responseText);
        if (-1 !== ["view-order-transactions", "wc-settings"].indexOf(page)) {
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
        $(`#${spinnerId}`).removeClass("is-active");
        console.log(errorThrown);
        alert(
          "Error building trans table. Your login may have timed out. Please refresh the page and try again."
        );
      },
    });
  };

  const tfSetTransCron = (cronOnOff, $cronToggle) => {
    $("#trans-cron-spinner").addClass("is-active");
    const frequency = $("#tf_financials_trans_cron_schedule").val();
    $.ajax({
      url: tasteFinancial.ajaxurl,
      type: "POST",
      datatype: "json",
      data: {
        action: "set_trans_cron",
        security: tasteFinancial.security,
        cron_on_off: cronOnOff,
        frequency: frequency,
      },
      success: function (responseText) {
        $(`#trans-cron-spinner`).removeClass("is-active");
        console.log(responseText);
        const parseResponse = JSON.parse(responseText);
        if (parseResponse.hasOwnProperty("success")) {
          const nextTime = parseResponse.success;
          $cronToggle.toggleClass(
            "woocommerce-input-toggle--disabled woocommerce-input-toggle--enabled"
          );
          $("#tf-financial-cron-next-time").html(nextTime);
          $("#tf-financial-cron-off, #tf-financial-cron-on").toggle();
        } else {
          alert(
            "unknown error setting/cancelling Transactions Build Cron Event"
          );
        }
      },
      error: function (xhr, status, errorThrown) {
        $(`#trans-cron-spinner`).removeClass("is-active");
        console.log(errorThrown);
        alert(
          "Error building trans table. Your login may have timed out. Please refresh the page and try again."
        );
      },
    });
  };

  const tfLoadRunTransButton = () => {
    $("#run-build-trans, #run-rebuild-trans").each(function () {
      $(this)
        .off("click")
        .click(function (e) {
          e.preventDefault();
          const startDate = $("#trans_update_start_date").val();
          const page = $(this).data("page");
          const deleteFlg = "run-rebuild-trans" === $(this).attr("id") ? 1 : 0;
          tfRunTransBuild(startDate, page, deleteFlg);
        });
    });

    const $dateEntry = $("#tf_financials_trans_start_date");
    if ($dateEntry.length) {
      $dateEntry.change(function () {
        $("#trans_update_start_date").val($(this).val());
      });
    }

    $cronToggle = $("#tf-trans-cron-toggle");
    $cronToggle.length &&
      $cronToggle.off("click").click(function (e) {
        const cronOnOff = $cronToggle.hasClass(
          "woocommerce-input-toggle--enabled"
        )
          ? 0
          : 1;
        tfSetTransCron(cronOnOff, $cronToggle);
      });
  };

  /**
   * code for exporting table to csv
   */
  function tfExportTableToCSV($table, filename, tableType) {
    const $headers = $table.children("thead").children("tr");
    const $rows = $table
      .find(`.${tableType}-list-bulk-cb:checked`)
      .closest("tr");

    // Temporary delimiter characters unlikely to be typed by keyboard
    // This is to avoid accidentally splitting the actual contents
    const tmpColDelim = String.fromCharCode(11); // vertical tab character
    const tmpRowDelim = String.fromCharCode(0); // null character
    // actual delimiter characters for CSV format
    const colDelim = '","';
    const rowDelim = '"\r\n"';

    // Grab text from table into CSV formatted string
    let csv = '"';
    const headersData = $headers.map((i, row) =>
      tfExportGrabRow(i, row, tableType)
    );
    csv += tfFormatExportRows(headersData);
    csv += rowDelim;

    const rowsData = $rows.map((i, row) => tfExportGrabRow(i, row, tableType));
    csv += tfFormatExportRows(rowsData) + '"';
    console.log("csv:");
    console.log(csv);
    // Data URI
    let csvData =
      "data:application/csv;charset=utf-8," + encodeURIComponent(csv);

    // For IE (tested 10+)
    if (window.navigator.msSaveOrOpenBlob) {
      let blob = new Blob([decodeURIComponent(encodeURI(csv))], {
        type: "text/csv;charset=utf-8;",
      });
      navigator.msSaveBlob(blob, filename);
    } else {
      let tmpEl = document.createElement("a");
      $(tmpEl).attr({
        download: filename,
        href: csvData,
        //,'target' : '_blank' //if you want it to open in a new window
      });
      tmpEl.click();
    }

    //------------------------------------------------------------
    // CSV Helper Functions
    //------------------------------------------------------------
    // Format the output so it has the appropriate delimiters
    function tfFormatExportRows(rows) {
      return rows
        .get()
        .join(tmpRowDelim)
        .split(tmpRowDelim)
        .join(rowDelim)
        .split(tmpColDelim)
        .join(colDelim);
    }
    // Grab and format a row from the table
    function tfExportGrabRow(i, row, tableType) {
      let $row = jQuery(row);
      //for some reason $cols = $row.find('td') || $row.find('th') won't work...
      let $cols = $row
        .find("td, th")
        .not(".hidden")
        .not(".check-column")
        .not(".column-actions");
      // ignore some columns based on list page type
      switch (tableType) {
        case "payments":
          $cols = $cols.not(".column-invoice");
          break;
        case "venues":
        case "transactions":
        default:
          $cols = $cols;
      }
      return $cols.map(tfExportGrabCol).get().join(tmpColDelim);
    }
    // Grab and format a column from the table
    function tfExportGrabCol(j, col) {
      let $col = $(col);
      let $text = $col
        .filter(":visible")
        .text()
        .trim()
        .replace("Filter By", "");

      return $text.replace('"', '""'); // escape double quotes
    }
  }
})(jQuery);
