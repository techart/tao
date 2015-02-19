document.onkeydown = function(e) {
	e = e || window.event
	if (83 === e.keyCode && e.ctrlKey) {
		var wrapper = $(".mfp-content");
		if (wrapper.size() == 0){
			wrapper = $("#content")
		}
		if (wrapper.size() == 0){
			return;
		}
		var form = wrapper.find("#mainform_form");
		if (e.shiftKey){
			$(form).find(".save-and-stay-button").eq(0).click();
		} else {
			$(form).find(".submit-button").not(".save-and-stay-button").eq(0).click();
		}
		if (e.preventDefault){
			e.preventDefault();
		} else {
			e.returnValue = false;
		}
	}
};