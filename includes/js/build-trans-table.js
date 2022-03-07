jQuery(document).ready(function () {
  // console.log("good");
  tfLoadRunTransButton();
});

const tfRunTransBuild = (startDate) => {
  jQuery.ajax({
    url: tasteFinancial.ajaxurl,
    type: "POST",
    datatype: "html",
    data: {
      action: "build_trans",
      security: tasteFinancial.security,
      start_date: startDate,
    },
    success: function (responseText) {
      console.log(responseText);
      jQuery("#response").text(responseText);
    },
    error: function (xhr, status, errorThrown) {
      console.log(errorThrown);
      alert(
        "Error building trans table. Your login may have timed out. Please refresh the page and try again."
      );
    },
  });
};

const tfLoadRunTransButton = () => {
  jQuery("#run-build-trans")
    .off("click")
    .click(function (e) {
      e.preventDefault();
      let startDate = jQuery("#start-date").val();
      tfRunTransBuild(startDate);
    });
};
