jQuery(document).ready(function () {
	let $documentBody = jQuery("body");
	userForms($documentBody);
	editPostVenues($documentBody);
	if (typeof products != "undefined") {
		// console.log("products ", products);
		let selectedProds = [];
		loadSelectProducts(selectedProds);
	} else {
		// console.log("no products");
	}
});

const userForms = ($documentBody) => {
	if (
		$documentBody.hasClass("user-new-php") ||
		$documentBody.hasClass("user-edit-php")
	) {
		let $roleSelect = jQuery("#role");
		$roleSelect.length &&
			$roleSelect.change(function () {
				let selectRole = this.value;
				if ("venue" === selectRole) {
					jQuery("#user-venue-fields").css("display", "block");
				} else {
					jQuery("#user-venue-fields").css("display", "none");
				}
			});
	}
};

const editPostVenues = ($documentBody) => {
	if (
		($documentBody.hasClass("post-php") ||
			$documentBody.hasClass("post-new-php") ||
			$documentBody.hasClass("edit-php")) &&
		$documentBody.hasClass("post-type-post")
	) {
		// postVenuesList is available
		console.log("postVenuesList: ", postVenuesList);
		buildVenueListsForPostAssign(postVenuesList);
		// setup change of the venue select to add to the venue lists
		$venueSelect = jQuery("#venue-select");
		$venueSelect.change(() => {
			let venueId = Number.parseInt($venueSelect.val());
			let fnd = postVenuesList.findIndex((venInfo) => {
				return Number.parseInt(venInfo.venueId) === venueId;
			});
			if (fnd !== -1) return;
			let venueSelected = {
				venueId,
				name: jQuery("#venue-select option:selected").text().trim(),
			};
			postVenuesList.push(venueSelected);

			buildVenueListsForPostAssign(postVenuesList);
		});
	}
};

const buildVenueListsForPostAssign = (postVenuesList) => {
	let venueInputList = postVenuesList.reduce((venList, venInfo) => {
		venList += venList ? "," : "";
		venList += venInfo.venueId;
		return venList;
	}, "");

	let venueChipList = postVenuesList.reduce((venList, venInfo) => {
		venList += `
			<div class="tt-chip">
				${venInfo.name}
				<span data-venid="${venInfo.venueId}" class="delete-venue-chip dashicons dashicons-no-alt tt-icon"></span>
			</div>
		`;
		return venList;
	}, "");
	jQuery("#post-venue-id-list").val(venueInputList);
	jQuery("#selected-venues-chips").html(venueChipList);
	setupVenueChipClicks(postVenuesList);
};

const setupVenueChipClicks = (postVenuesList) => {
	jQuery(".delete-venue-chip")
		.unbind("click")
		.click(function (e) {
			e.preventDefault();
			let venueId = Number.parseInt(jQuery(this).data("venid"));
			let fnd = postVenuesList.findIndex((venInfo) => {
				return Number.parseInt(venInfo.venueId) === venueId;
			});
			if (fnd !== -1) {
				postVenuesList.splice(fnd, 1);
			}
			buildVenueListsForPostAssign(postVenuesList);
		});
};

const loadSelectProducts = (selectedProds) => {
	jQuery("#product-input").autocomplete({
		source: products,
		select: function (event, ui) {
			event.preventDefault();
			// jQuery(this).val("");
			let ndx = products.findIndex((p) => {
				return p.value === ui.item.value;
			});
			let selected = products.splice(ndx, 1);
			selectedProds = selectedProds.concat(selected);
			let selectTbody = buildSelectedRows(selectedProds);
			jQuery("#select-products-body").html(selectTbody);
			jQuery("#product-input").autocomplete("option", "source", products);
			// add click events to freshly created buttons in selected table
			setupUnSelect(selectedProds, products);
		},
	});

	/*
	jQuery("#assign-products-btn").click(function (e) {
		// no use for this now...later can use with ajax call
	});
	*/
};

const setupUnSelect = (selectedProds, products) => {
	jQuery(".unselect-btn").click(function (e) {
		e.preventDefault();
		let ndx = jQuery(this).data("ndx");
		let unSelected = selectedProds.splice(ndx, 1);
		products = products.concat(unSelected);
		let selectTbody = buildSelectedRows(selectedProds);
		jQuery("#select-products-body").html(selectTbody);
		jQuery("#product-input").autocomplete("option", "source", products);
		setupUnSelect(selectedProds, products);
	});
};

const buildSelectedRows = (selectedProds) => {
	let selectTbody = selectedProds.reduce((bodyRows, prod, ndx) => {
		bodyRows =
			bodyRows +
			`
		<tr>
		<td>${prod.prodId}</td>
		<td>${prod.sku}</td>
		<td>${prod.prodTitle}</td>
		<td>${prod.prodDate}</td>
		<td>${prod.expired}</td>
		<td>
			<input type="hidden" name="prod_ids[]" value="${prod.prodId}" >
			<button data-ndx="${ndx}" class="unselect-btn button button-secondary">UnSelect</button>
		</td>
	</tr>
		`;
		return bodyRows;
	}, "");
	if (selectedProds.length) {
		jQuery("#assign-products-btn").prop("disabled", false);
	} else {
		jQuery("#assign-products-btn").prop("disabled", true);
	}
	return selectTbody;
};
