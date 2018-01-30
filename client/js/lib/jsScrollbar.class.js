/*
 *Скроллбар
 */
var jsScrollbar = new Class({

	Implements: [Options, Events],

	options: {
		autoHide: 1,
		fade: 1,
		className: 'scrollbar',
		proportional: true,
		proportionalMinHeight: 15
	},

	initialize: function(element, options) {
		this.setOptions(options);

		if (typeOf(element) == 'elements') {
			var collection = [];
			element.each(function(element) {
				collection.push(new jsScrollbar(element, options));
			});
			return collection;
		}
		else {
			var scrollable = this;
			this.element = document.id(element);
			if (!this.element) return 0;

			this.active = false;

			//Построение элемента скроллинга
			this.container = new Element('div', {
				'class': this.options.className,
				html: '<div class="knob"></div>'
			}).inject(document.body, 'bottom');
			this.slider = new Slider(this.container, this.container.getElement('div'), {
				mode: 'vertical',
				onChange: function(step) {
					this.element.scrollTop = ((this.element.scrollHeight - this.element.offsetHeight) * (step / 100));
				}.bind(this)
			});
			this.knob = this.container.getElement('div');
			this.reposition();
			if (!this.options.autoHide) this.container.fade('show');

			this.element.addEvents({
				'mouseenter': function() {
					if (this.scrollHeight > this.offsetHeight) {
						scrollable.showContainer();
					}
					scrollable.reposition();
				},
				'mouseleave': function(e) {
					if (!scrollable.isInside(e) && !scrollable.active) {
						scrollable.hideContainer();
					}
				},
				'mousewheel': function(event) {
					event.preventDefault();
					if ((event.wheel < 0 && this.scrollTop < (this.scrollHeight - this.offsetHeight)) || (event.wheel > 0 && this.scrollTop > 0)) {
						this.scrollTop = this.scrollTop - (event.wheel * 30);
						scrollable.reposition();
					}
				}
			});
			this.container.addEvent('mouseleave', function() {
				if (!scrollable.active) {
					scrollable.hideContainer();
				}
			});
			this.knob.addEvent('mousedown', function(e) {
				scrollable.active = true;
				window.addEvent('mouseup', function(e) {
					scrollable.active = false;
					if (!scrollable.isInside(e)) {
						scrollable.hideContainer();
					}
					this.removeEvents('mouseup');
				});
			});
			window.addEvents({
				'resize': function() {
					scrollable.reposition.delay(50,scrollable);
				},
				'mousewheel': function() {
					if (scrollable.element.isVisible()) scrollable.reposition();
				}
			});

			if (this.options.autoHide) scrollable.container.fade('hide');
			return this;
		}
	},
	reposition: function() {
		(function() {
			this.size = this.element.getComputedSize();
			this.position = this.element.getPosition();
			var containerSize = this.container.getSize();

			this.container.setStyle('height', this.size['height']).setPosition({
				x: (this.position.x+this.size['totalWidth']-containerSize.x),
				y: (this.position.y+this.size['computedTop'])
			});
			this.slider.autosize();
		}).bind(this).delay(50);

		if (this.options.proportional === true) {
			if (isNaN(this.options.proportionalMinHeight) || this.options.proportionalMinHeight <= 0) {
				throw new Error('Scrollable: option "proportionalMinHeight" is not a positive number.');
			} else {
				var minHeight = Math.abs(this.options.proportionalMinHeight);
				var knobHeight = this.element.offsetHeight * (this.element.offsetHeight / this.element.scrollHeight);
				this.knob.setStyle('height', Math.max(knobHeight, minHeight));
			}
		}

		this.slider.set(Math.round((this.element.scrollTop / (this.element.scrollHeight - this.element.offsetHeight)) * 100));
	},

	//низ
	scrollBottom: function() {
		this.element.scrollTop = this.element.scrollHeight;
		this.reposition();
	},

	//верх
	scrollTop: function() {
		this.element.scrollTop = 0;
		this.reposition();
	},

	isInside: function(e) {
		if(!e || !e.client) return false;
		if(!this.position){
			this.reposition();
			if(!this.position) return false;
		}
		if (e.client.x > this.position.x && e.client.x < (this.position.x + this.size.totalWidth) && e.client.y > this.position.y && e.client.y < (this.position.y + this.size.totalHeight))
			return true;
		else return false;
	},

	showContainer: function(force) {
		if ((this.options.autoHide && this.options.fade && !this.active) || (force && this.options.fade)) this.container.fade('in');
		else if ((this.options.autoHide && !this.options.fade && !this.active) || (force && !this.options.fade)) this.container.fade('show');
	},

	hideContainer: function(force) {
		if ((this.options.autoHide && this.options.fade && !this.active) || (force && this.options.fade)) this.container.fade('out');
		else if ((this.options.autoHide && !this.options.fade && !this.active) || (force && !this.options.fade)) this.container.fade('hide');
	},

	terminate: function() {
		this.container.destroy();
	}
});