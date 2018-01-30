var Loop = new Class({

	loopCount: 0,
	isStopped: true,
	isLooping: false,
	loopMethod: $empty,

	setLoop: function(fn,delay){
		if(this.isLooping) {
			this.stopLoop();
			var wasLooping = true;
		} else {
			var wasLooping = false;
		}
		this.loopMethod = fn;
		this.loopDelay = delay || 3000;
		if(wasLooping) this.startLoop();
		return this;
	},

	stopLoop: function() {
		this.isStopped = true;
		this.isLooping = false;
		$clear(this.periodical);
		return this;
	},

	startLoop: function(delay) {
		if(this.isStopped){
			var delay = (delay) ? delay : this.loopDelay;
			this.isStopped = false;
			this.isLooping = true;
			this.periodical = this.looper.periodical(delay,this);
		};
		return this;
	},

	resetLoop: function(){
		this.loopCount = 0;
		return this;
	},

	looper: function(){
		this.loopCount++;
		this.loopMethod(this.loopCount);
		return this;
	}

});


var jsSlideShow = new Class({

	Implements: [Options, Events, Loop],

		options: {
			/*
			onShow: $empty,
			onShowComplete: $empty,
			onReverse: $empty,
			onPlay: $empty,
			onPause: $empty,
			*/
			delay: 7000,
			transition: 'crossFade',
			duration: '500',
			autoplay: false
		},

	initialize: function(element, options){
		this.setOptions(options);
		this.setLoop(this.showNext, this.options.delay);
		this.element = $(element);
		this.slides = this.element.getChildren();
		this.current = this.slides[0];
		this.transitioning = false;
		this.setup();
		if (this.options.autoplay) this.play();
	},

	setup: function(){
	  this.setupElement().setupSlides(true);
		return this;
	},

	setupElement: function(){
		var el = this.element;
		if (el.getStyle('position') != 'absolute' && el != document.body) el.setStyle('position','relative');
		return this;
	},

	setupSlides: function(hideFirst){
		this.slides.each(function(slide, index){
			this.storeTransition(slide).reset(slide);
			if (hideFirst && index != 0) slide.setStyle('display','none');
		}, this);
		return this;
	},

	storeTransition: function(slide){
		var classes = slide.get('class') || '';
		var transitionRegex = /transition:[a-zA-Z]+/;
		var durationRegex = /duration:[0-9]+/;
		var transition = (classes.match(transitionRegex)) ? classes.match(transitionRegex)[0].split(':')[1] : this.options.transition;
		var duration = (classes.match(durationRegex)) ? classes.match(durationRegex)[0].split(':')[1] : this.options.duration;
		slide.store('ssTransition', transition).store('ssDuration', duration);
		return this;
	},

	resetOptions: function(options){
		this.options = $merge(this.options, options);
		this.setupSlides(false);
		return this;
	},

	getTransition: function(slide){
		return slide.retrieve('ssTransition');
	},

	getDuration: function(slide){
		return slide.retrieve('ssDuration');
	},

	show: function(slide, options){
		slide = (typeof slide == 'number') ? this.slides[slide] : slide;
		if (slide != this.current && !this.transitioning){
			this.transitioning = true;
			var transition = (options && options.transition) ? options.transition: this.getTransition(slide),
				duration = (options && options.duration) ? options.duration: this.getDuration(slide),
				previous = this.current.setStyle('z-index', 1),
				next = this.reset(slide);
			var slideData = {
				previous: {
					element: previous,
					index: this.slides.indexOf(previous)
				}, 
				next: {
					element: next,
					index: this.slides.indexOf(next)
				}
			};
			this.fireEvent('show', slideData);
			this.transitions[transition](previous, next, duration, this);
			(function() { 
				previous.setStyle('display','none');
				this.fireEvent('showComplete', slideData);
				this.transitioning = false;
			}).bind(this).delay(duration);
			this.current = next;
		}
		return this;
	},
	
	reset: function(slide){
		return slide.setStyles({
			'position': 'absolute',
			'z-index': 0,
			'display': 'block',
			'left': 0,
			'top': 0
		}).fade('show');
	},

	nextSlide: function(){
		var next = this.current.getNext();
		return (next) ? next : this.slides[0];
	},

	previousSlide: function(){
		var previous = this.current.getPrevious();
		return (previous) ? previous : this.slides.getLast();
	},

	showNext: function(options){
		this.show(this.nextSlide(), options);
		return this;
	},

	showPrevious: function(options){
		this.show(this.previousSlide(), options);
		return this;
	},

	play: function(){
		this.startLoop();
		this.fireEvent('play');
		return this;
	},

	pause: function(){
		this.stopLoop();
		this.fireEvent('pause');
		return this;
	},

	reverse: function(){
		var fn = (this.loopMethod == this.showNext) ? this.showPrevious : this.showNext;
		this.setLoop(fn, this.options.delay);
		this.fireEvent('reverse');
		return this;
	},

	toElement: function(){
		return this.element;
	}

});

Element.Properties.slideshow = {
	set: function(options){
		var slideshow = this.retrieve('slideshow');
		if (slideshow) slideshow.pause();
		return this.eliminate('slideshow').store('slideshow:options', options);
	},
	get: function(options){
		if (options || !this.retrieve('slideshow')){
			if (options || !this.retrieve('slideshow:options')) this.set('slideshow', options);
			this.store('slideshow', new SlideShow(this, this.retrieve('slideshow:options')));
		}
		return this.retrieve('slideshow');
	}
};


Element.implement({
	playSlideShow: function(options){
		this.get('slideshow', options).play();
		return this;
	},
	pauseSlideShow: function(options){
		this.get('slideshow', options).pause();
		return this;
	}
});

jsSlideShow.adders = {
	transitions:{},
	add: function(className, fn){
		this.transitions[className] = fn;
		this.implement({
			transitions: this.transitions
		});
	},
	addAllThese : function(transitions){
		$A(transitions).each(function(transition){
			this.add(transition[0], transition[1]);
		}, this);
	}
}

$extend(jsSlideShow, jsSlideShow.adders);
jsSlideShow.implement(jsSlideShow.adders);

jsSlideShow.add('fade', function(previous, next, duration, instance){
	previous.set('tween',{duration: duration}).fade('out');
	return this;
});

jsSlideShow.addAllThese([

	['none', function(previous, next, duration, instance){
		previous.setStyle('display','none');
		return this;
	}],

	['crossFade', function(previous, next, duration, instance){
		previous.set('tween',{duration: duration}).fade('out');
		next.set('tween',{duration: duration}).fade('in');
		return this;
	}],

	['fadeThroughBackground', function(previous, next, duration, instance){
		var half = duration/2;
		previous.setStyle('display','none');
		next.set('tween',{ duration: duration}).fade('hide').fade('in');
	}],
/*
	['fadeThroughBackground', function(previous, next, duration, instance){
		var half = duration/2;
		next.set('tween',{ duration: half}).fade('hide');
		previous.set('tween',{
			duration: half,
			onComplete: function(){
				next.fade('in');
			}
		}).fade('out');
	}],
	*/
	['pushLeft', function(previous, next, duration, instance){
		var distance = instance.element.getSize().x;
		next.setStyle('left', distance);
		new Fx.Elements([previous,next],{duration: duration}).start({
			0: { left: [-distance] },
			1: { left: [0] }
		});
		return this;
	}],

	['pushRight', function(p,n,d,i){
		var distance = i.element.getSize().x;
		n.setStyle('left', -distance);
		new Fx.Elements([p,n],{duration: d}).start({
			0: { left: [distance] },
			1: { left: [0] }
		});
		return this;
	}],

	['pushUp', function(p,n,d,i){
		var distance = i.element.getSize().y;
		n.setStyle('top', distance);
		new Fx.Elements([p,n],{duration: d}).start({
			0: { top: [-distance] },
			1: { top: [0] }
		});
		return this;
	}],

	['pushDown', function(p,n,d,i){
		var distance = i.element.getSize().y;
		n.setStyle('top', -distance);
		new Fx.Elements([p,n],{duration: d}).start({
			0: { top: [distance] },
			1: { top: [0] }
		});
		return this;
	}],

	['blindRight', function(p,n,d,i){
		var distance = i.element.getSize().x;
		n.setStyles({
			left: -distance,
			'z-index': 2
		}).set('tween',{duration: d}).tween('left',0);
		return this;
	}],

	['blindLeft', function(p,n,d,i){
		var distance = i.element.getSize().x;
		n.setStyles({
			left: distance,
			'z-index': 2
		}).set('tween',{duration: d}).tween('left',0);
		return this;
	}],

	['blindUp', function(p,n,d,i){
		var distance = i.element.getSize().y;
		n.setStyles({
			top: distance,
			'z-index': 2
		}).set('tween',{duration: d}).tween('top',0);
		return this;
	}],

	['blindDown', function(p,n,d,i){
		var distance = i.element.getSize().y;
		n.setStyles({
			top: -distance,
			'z-index': 2
		}).set('tween',{duration: d}).tween('top',0);
		return this;
	}],

	['blindDownFade', function(p,n,d,i){
		this.blindDown(p,n,d,i).fade(p,n,d,i);
	}],

	['blindUpFade', function(p,n,d,i){
		this.blindUp(p,n,d,i).fade(p,n,d,i);
	}],

	['blindLeftFade', function(p,n,d,i){
		this.blindLeft(p,n,d,i).fade(p,n,d,i);
	}],

	['blindRightFade', function(p,n,d,i){
		this.blindRight(p,n,d,i).fade(p,n,d,i);
	}]

]);

