/*
 * "Мигание" элемента
 * Stanislav V. Tretyakov (svtrostov@yandex.ru)
 */
var jsPulseFade = new Class({

	//implements
	Implements: [Options,Events],

	//options
	options: {
		min: 0.5,
		max: 1,
		duration: 1000,
		times: 10
	},

	//initialization
	initialize: function(el,options) {
		this.setOptions(options);
		this.element = $(el);
		this.times = 0;
	},

	start: function(times) {
		if(!times) times = this.options.times * 2;
		this.running = 1;
		this.fireEvent('start').run(times -1);
	},

	stop: function() {
		this.running = 0;
		this.fireEvent('stop');
	},

	run: function(times){
		var to = this.element.get('opacity') == this.options.min ? this.options.max : this.options.min;
		this.fx = new Fx.Tween(this.element,{
			duration: this.options.duration / 2,
			onComplete: function() {
				this.fireEvent('tick');
				if(this.running && times){
					this.run(times-1);
				}
				else{
					this.fireEvent('complete');
				}
			}.bind(this)
		}).start('opacity',to);
	}
});