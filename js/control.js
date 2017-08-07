if (typeof(enmask) == 'undefined' || !enmask) {
	var enmask = {};
}

(function($) {
	enmask.control = function(el, config)
	{
		this.el = el;
		this.config = $.extend({
			checkUrl : null,
			refreshUrl : null,
			fontsDir : '',
			loadingClass : 'enmask-loading',
			minFont : 300,
			maxFont : 500
		}, config);

		this.codeEl = $('.enmask-code', this.el);
		this.knobEl = $('.enmask-knob', this.el);
		this.sliderEl = $('.enmask-slider', this.el);
		this.refreshLinkEl = $('.enmask-refresh', this.el);
		this.resultEl = $('.enmask-result', this.el);
		this.verifyEl = $('.enmask-verify', this.el);
	}

	enmask.control.prototype.setup = function()
	{
		var that = this;

		this.sliderPosX = this.sliderEl.offset().left;
		this.knobWidth = this.knobEl.width(),
		this.sliderWidth = this.sliderEl.width(),
		this.maxKnobPos = this.sliderWidth - this.knobWidth;

		this.setupSlider();

		this.el.bind('knobMoved.enmask', function(e, pos) {
			var font = that.config.minFont + (that.config.maxFont - that.config.minFont) * (pos.percent / 100);
			font = Math.round(font);
			that.codeEl.css('font-size', font + '%');
		});

		this.refreshLinkEl.click(function(e) {
			e.preventDefault();
			that.refresh();
		});

		this.verifyEl.click(function(e) {
			e.preventDefault();
			that.verify();
		});

		if (this.verifyEl.size() > 0) {
			this.resultEl.keydown(function(e) {
				if (e.keyCode == 13) {
					that.verify();
				}
			});
		}
	}

	enmask.control.prototype.showLoading = function()
	{
		this.el.addClass(this.config.loadingClass);
	}

	enmask.control.prototype.hideLoading = function()
	{
		this.el.removeClass(this.config.loadingClass);
	}

	enmask.control.prototype.setupSlider = function()
	{
		var that = this,
			allowMove = false;


		this.knobEl.mousedown(function(e) {
			allowMove = true;
		});

		this.el.mouseup(function(e) {
			allowMove = false;
		})

		this.sliderEl.mousemove(function(e) {
			if (allowMove) {
				that.moveKnob(e.pageX);
			}
		});

		this.sliderEl.click(function(e) {
			that.moveKnob(e.pageX);
		});
	}

	enmask.control.prototype.moveKnob = function(pos)
	{
		pos = pos - this.sliderPosX - this.knobWidth;

		if (pos < 0) {
			pos = 0;
		} else if (pos > this.maxKnobPos) {
			pos = this.maxKnobPos;
		}

		this.knobEl.css('left', pos);

		var percent = Math.round(((pos + this.knobWidth) / this.sliderWidth) * 100);

		this.el.trigger('knobMoved.enmask', [{
			percent : percent,
			pos : pos
		}]);
	}

	enmask.control.prototype.setCode = function(code, fontSubPath, fontName, fontPath)
	{
		var webPath = (typeof(fontPath) != 'undefined') ? fontPath : this.config.fontsDir + fontSubPath,
			rnd = Math.round(Math.random() * 1000),
			fontName = 'a' + fontName + rnd;

		var cssStr = '\
			<style type="text/css"> \
				@font-face { \
					font-family: "' + fontName + 'enc"; \
					src: url("' + webPath + '/encrypted.eot?rnd=' + rnd +'"); \
					src: url("' + webPath + '/encrypted.eot?rnd=' + rnd + '#iefix") format("embedded-opentype"), \
					url("' + webPath + '/encrypted.ttf?rnd=' + rnd + '") format("truetype"); \
					font-weight: normal; \
					font-style: normal; \
				} \
				@font-face { \
					font-family: "' + fontName + 'real"; \
					src: url("' + webPath + '/real.eot?rnd=' + rnd + '"); \
					src: url("' + webPath + '/real.eot?rnd=' + rnd + '#iefix") format("embedded-opentype"), \
					url("' + webPath + '/real.ttf?rnd=' + rnd + '") format("truetype"); \
					font-weight: normal; \
					font-style: normal; \
				} \
			</style> \
		';

		$('head').append(cssStr);
		this.codeEl.text(code);
		this.codeEl.css('font-family', fontName + 'enc');

		this.resultEl.val('');
		this.resultEl.css('font-family', fontName + 'real');
	}

	enmask.control.prototype.refresh = function()
	{
		if (this.refreshLinkEl.size() == 0) {
			throw 'Cannot refresh without refreshLink!';
		}

		var that = this;

		this.showLoading();
		$.post(this.refreshLinkEl.attr('href'), {}, function(data) {
			that.hideLoading();
			that.setCode(data.code, data.fontSubPath, data.fontName, data.fontPath);
		}, 'json');
	}

	enmask.control.prototype.verify = function()
	{
		var that = this;

		if (this.verifyEl.size() == 0) {
			throw 'verifyEl is empty';
		}

		this.showLoading();
		$.post(this.verifyEl.attr('href'), {code : that.resultEl.val()}, function(data) {
			that.hideLoading();

			if (typeof(data.code) != 'undefined') {
				that.setCode(data.code, data.fontSubPath, data.fontName, data.fontPath);
			}

			that.resultEl.val('');

			if (data.result) {
				that.el.trigger('success.enmask');
			} else {
				that.el.trigger('error.enmask');
			}
		}, 'json');
	}
}) (jQuery);