if (typeof(enmask) == 'undefined' || !enmask) {
	var enmask = {};
}

(function($) {
	enmask.admin = function(el, version)
	{
		this.el = el;
		this.form = $('.keywords-form', this.el);
		this.keywordsTbl = $('.keywords-tbl', this.el);
		this.licenseKeyEl = $('.license-key', this.el);
		this.addEl = $('.add-keyword', this.el);
		this.version = version;
	}

	enmask.admin.prototype.setup = function()
	{
		var that = this;

		this.addEl.click(function(e) {
			e.preventDefault();

//			if (that.version == 'free') {
//				alert('This feature avaible only in PRO version');
//				return;
//			}

			that.addRow();
			that.checkAllowAdd();
		});

		$('.upgrade-version', this.el).click(function(e) {
			e.preventDefault();
			that.licenseKeyEl.get(0).focus();
		});

		$('.remove', this.keywordsTbl).click($.proxy(this.onRemoveClick, this));
	}

	enmask.admin.prototype.addRow = function()
	{
		var row = $('.sample-row', this.keywordsTbl).clone();

		row.removeClass('sample-row');
		$(':input', row).attr('disabled', false);

		row.find('.remove').click($.proxy(this.onRemoveClick, this));

		this.keywordsTbl.append(row);
//		this.checkAllowRm();
	}

	enmask.admin.prototype.onRemoveClick = function(e)
	{
		e.preventDefault();

		$(e.currentTarget).parents('tr').remove();
//		this.checkAllowRm();
		this.checkAllowAdd();
	}

	enmask.admin.prototype.checkAllowAdd = function()
	{
		if ($('tr', this.keywordsTbl).size() >= 10) {
			this.addEl.hide();
		} else {
			this.addEl.show();
		}
	}

	enmask.admin.prototype.checkAllowRm = function()
	{
		if ($('tr', this.keywordsTbl).size() > 1) {
			$('.remove', this.keywordsTbl).show();
		} else {
			$('.remove', this.keywordsTbl).hide();
		}
	}
}) (jQuery);