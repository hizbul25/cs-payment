jQuery(function ($) {
	if($('.payment_success').length) {
		$("a[href*='format=pdf']").show();
		$(".export-pdf").show();
	}
	else {
		$("a[href*='format=pdf']").hide();
		$("input.export-pdf").hide();
	}
});