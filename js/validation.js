if (typeof(enmask) == 'undefined' || !enmask) {
	var enmask = {};
}

(function($) {
	enmask.validation = function(captcha, config)
	{
		this.captcha = captcha;
		this.checked = false;
		this.form = this.captcha.el.parents('form');

		this.config = $.extend({
			loadingText : 'Checking the captcha...',
			errorText : 'Captcha code is incorrect',
			validationUrl : null
		}, config);

		$('#enmask-captcha-checking').text(this.config.loadingText);
	}

	enmask.validation.prototype.setup = function()
	{
		var that = this;

		this.form.submit(function(e) {
			if (!that.checked) {
				e.preventDefault();
				that.validate();
			}
		});
	}

	enmask.validation.prototype.validate = function()
	{
		var that = this;

		this.showLoading();
		$.post(this.config.validationUrl, {code : this.captcha.resultEl.val()}, function(data) {
			that.hideLoading();

			if (data.result) {
				that.checked = true;

				if (typeof(that.form.get(0).submit) == 'function') {
					that.form.get(0).submit();
				} else {
					$(that.form.get(0).submit).trigger('click');
				}
			} else {
				alert(that.config.errorText);
				that.captcha.resultEl.get(0).focus();
				that.captcha.setCode(data.code, data.fontSubPath, data.fontName);
			}
		}, 'json');
	}

	enmask.validation.prototype.showLoading = function()
	{
		$('#enmask-captcha-checking').show();
	}

	enmask.validation.prototype.hideLoading = function()
	{
		$('#enmask-captcha-checking').hide();
	}
}) (jQuery);