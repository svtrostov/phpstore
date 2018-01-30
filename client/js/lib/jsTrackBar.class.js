/*----------------------------------------------------------------------
Трек бар
----------------------------------------------------------------------*/
var jsTrackBar = new Class({

	Implements: [Options, Events],

	/*Настройки*/
	options: {
		icons: INTERFACE_IMAGES+'/trackbar/',//Путь к иконкам
		id:	String.uniqueID(),
		parent: null,
		width: 250, // px
		leftLimit: 0, // unit of value
		leftValue: 500, // unit of value
		rightLimit: 5000, // unit of value
		rightValue: 1500, // unit of value
		leftWidth: 0, // px
		rightWidth: 0, // px
		intervalWidth: 0, // px
		valueInterval: 0,
		widthRem: 6,
		valueWidth: 0,
		roundUp: 0,
		x0: 0, 
		y0: 0,
		blockX0: 0, 
		rightX0: 0, 
		leftX0: 0,
		// Flags
		dual: true,
		moveState: false,
		moveIntervalState: false,
		debugMode: false,
		clearLimits: false,
		clearValues: false,
		// Handlers
		eventFunction: null
	},

	LEFT_BLOCK_PREFIX : "leftBlock_",
	RIGHT_BLOCK_PREFIX : "rightBlock_",
	LEFT_BEGUN_PREFIX : "leftBegun_",
	RIGHT_BEGUN_PREFIX : "rightBegun_",
	CENTER_BLOCK_PREFIX : "centerBlock_",

	// Nodes
	trackbar: null,
	leftBlock: null,
	centerBlock: null,
	rightBlock: null,
	leftBegun: null,
	rightBegun: null,
	itWasMove: false,


	setRightLimit: function(v){
		this.rightLimit = v;
		this.rightValue = this.rightValue || this.rightLimit;
		this.valueInterval = this.rightLimit - this.leftLimit;
		this.leftWidth = parseInt((this.leftValue - this.leftLimit) / this.valueInterval * this.valueWidth) + this.widthRem;
		this.rightWidth = this.valueWidth - parseInt((this.rightValue - this.leftLimit) / this.valueInterval * this.valueWidth) + this.widthRem;
		// Set limits
		if (!this.clearLimits) {
			this.leftBlock.firstChild.nextSibling.innerHTML = this.leftLimit;
			this.rightBlock.firstChild.nextSibling.innerHTML = this.rightLimit;
		}
		this.setCurrentState();
	},


	/*Инициализация*/
	initialize: function(options){

		this.setOptions(options);
		this.build();

		for(var option in this.options){
			this[option] = this.options[option];
		}

		// Set default
		this.valueWidth = this.width - 2 * this.widthRem;
		this.rightValue = this.rightValue || this.rightLimit;
		this.leftValue = this.leftValue || this.leftLimit;
		if (!this.dual) this.rightValue = this.leftValue;
		this.valueInterval = this.rightLimit - this.leftLimit;
		this.leftWidth = parseInt((this.leftValue - this.leftLimit) / this.valueInterval * this.valueWidth) + this.widthRem;
		this.rightWidth = this.valueWidth - parseInt((this.rightValue - this.leftLimit) / this.valueInterval * this.valueWidth) + this.widthRem;
		// Set limits
		if (!this.clearLimits) {
			this.leftBlock.firstChild.nextSibling.innerHTML = this.leftLimit;
			this.rightBlock.firstChild.nextSibling.innerHTML = this.rightLimit;
		}
		// Do it!
		this.setCurrentState();
		if(this.options.eventFunction){
			this.options.eventFunction();
		}
		// Add handers
		var _this = this;
		this.addHandler (
			document,
			"mousemove",
			function(evt) {
				if (_this.moveState) _this.moveHandler(evt);
				if (_this.moveIntervalState) _this.moveIntervalHandler(evt);
			}
		);
		this.addHandler (
			document,
			"mouseup",
			function() {
				_this.moveState = false;
				_this.moveIntervalState = false;
			}
		);
		this.addHandler (
			this.leftBegun,
			"mousedown",
			function(evt) {
				evt = evt || window.event;
				if (evt.preventDefault) evt.preventDefault();
				evt.returnValue = false;
				_this.moveState = "left";
				_this.x0 = _this.defPosition(evt).x;
				_this.blockX0 = _this.leftWidth;
			}
		);
		this.addHandler (
			this.rightBegun,
			"mousedown",
			function(evt) {
				evt = evt || window.event;
				if (evt.preventDefault) evt.preventDefault();
				evt.returnValue = false;
				_this.moveState = "right";
				_this.x0 = _this.defPosition(evt).x;
				_this.blockX0 = _this.rightWidth;
			}
		);
		this.addHandler (
			this.centerBlock,
			"mousedown",
			function(evt) {
				evt = evt || window.event;
				if (evt.preventDefault) evt.preventDefault();
				evt.returnValue = false;
				_this.moveIntervalState = true;
				_this.intervalWidth = _this.width - _this.rightWidth - _this.leftWidth;
				_this.x0 = _this.defPosition(evt).x;
				_this.rightX0 = _this.rightWidth; 
				_this.leftX0 = _this.leftWidth;
			}
		),
		this.addHandler (
			this.centerBlock,
			"click",
			function(evt) {
				if (!_this.itWasMove) _this.clickMove(evt);
				_this.itWasMove = false;
			}
		);
		this.addHandler (
			this.leftBlock,
			"click",
			function(evt) {
				if (!_this.itWasMove)_this.clickMoveLeft(evt);
				_this.itWasMove = false;
			}
		);
		this.addHandler (
			this.rightBlock,
			"click",
			function(evt) {
				if (!_this.itWasMove)_this.clickMoveRight(evt);
				_this.itWasMove = false;
			}
		);

	},//end function


	/*Создание*/
	build: function(){

		this.trackbar = new Element('table',{
			'id': this.options.id,
			'class': 'trackbar',
			'styles':{
				'width':(this.options.width?this.options.width+'px':'100%')
			},
			'events':{
				'selectstart': function(){return false;}
			}
		}).inject($(this.options.parent));
		var tr = new Element('tr').inject(this.trackbar);

		var td_leftBlock	= new Element('td',{'class': 'l'}).inject(tr);
		var td_centerBlock	= new Element('td',{'class': 'c'}).inject(tr);
		var td_rightBlock	= new Element('td',{'class': 'r'}).inject(tr);

		//leftBlock
		this.leftBlock = new Element('div',{'id': 'leftBlock_'+this.options.id}).inject(td_leftBlock);
		new Element('span').inject(this.leftBlock);
		new Element('span',{'class': 'limit'}).inject(this.leftBlock);
		this.leftBegun =  new Element('img',{
			'id': 'leftBegun_'+this.options.id,
			'src':this.options.icons+'b_l.gif',
			'styles':{
				'width':'5px',
				'height':'17px'
			},
			'events':{
				'dragstart':function(){return false;}
			}
		}).inject(this.leftBlock);
		

		//centerBlock
		this.centerBlock = new Element('div',{'id': 'centerBlock_'+this.options.id}).inject(td_centerBlock);
		if(!this.dual) this.centerBlock.hide();

		//rightBlock
		this.rightBlock = new Element('div',{'id': 'rightBlock_'+this.options.id}).inject(td_rightBlock);
		new Element('span').inject(this.rightBlock);
		new Element('span',{'class': 'limit'}).inject(this.rightBlock);
		this.rightBegun =  new Element('img',{
			'id': 'leftBegun_'+this.options.id,
			'src':this.options.icons+'b_r.gif',
			'styles':{
				'width':'5px',
				'height':'17px'
			},
			'events':{
				'dragstart':function(){return false;}
			}
		}).inject(this.rightBlock);

	},//end function



	addHandler : function(object, event, handler, useCapture) {
		if (object.addEventListener) {
			object.addEventListener(event, handler, useCapture ? useCapture : false);
		} else if (object.attachEvent) {
			object.attachEvent('on' + event, handler);
		} else alert(this.errorArray[9]);
	},
	defPosition : function(event) { 
		var x = y = 0; 
		if (document.attachEvent != null) {
			x = window.event.clientX + document.documentElement.scrollLeft + document.body.scrollLeft; 
			y = window.event.clientY + document.documentElement.scrollTop + document.body.scrollTop; 
		} 
		if (!document.attachEvent && document.addEventListener) { // Gecko 
			x = event.clientX + window.scrollX; 
			y = event.clientY + window.scrollY; 
		} 
		return {x:x, y:y}; 
	},
	absPosition : function(obj) { 
		var x = y = 0; 
		while(obj) { 
			x += obj.offsetLeft; 
			y += obj.offsetTop; 
			obj = obj.offsetParent; 
		} 
		return {x:x, y:y}; 
	},

	setCurrentState : function() {
		this.leftBlock.style.width = this.leftWidth + "px";
		if (!this.clearValues) this.leftBlock.firstChild.innerHTML = (!this.dual && this.leftWidth > this.width / 2) ? "" : this.leftValue;
		if(!this.dual) {
			var x = this.leftBlock.firstChild.offsetWidth;
			this.leftBlock.firstChild.style.right = (this.widthRem * (1 - 2 * (this.leftWidth - this.widthRem) / this.width) - ((this.leftWidth - this.widthRem) * x / this.width)) + 'px';
		}
		this.rightBlock.style.width = this.rightWidth + "px";
		if (!this.clearValues) this.rightBlock.firstChild.innerHTML = (!this.dual && this.rightWidth >= this.width / 2) ? "" : this.rightValue;
		if(!this.dual) {
			var x = this.rightBlock.firstChild.offsetWidth;
			this.rightBlock.firstChild.style.left = (this.widthRem * (1 - 2 * (this.rightWidth - this.widthRem) / this.width) - ((this.rightWidth - this.widthRem) * x / this.width)) + 'px';
		}
	},
	clickMoveRight : function(evt) {
		evt = evt || window.event;
		if (evt.preventDefault) evt.preventDefault();
		evt.returnValue = false;
		var x = this.defPosition(evt).x - this.absPosition(this.rightBlock).x;
		var w = this.rightBlock.offsetWidth;
		if (x <= 0 || w <= 0 || w < x || (w - x) < this.widthRem) return;
		this.rightWidth = (w - x);
		this.rightCounter();

		this.setCurrentState();
		if(this.options.eventFunction) this.options.eventFunction();
	},
	clickMoveLeft : function(evt) {
		evt = evt || window.event;
		if (evt.preventDefault) evt.preventDefault();
		evt.returnValue = false;
		var x = this.defPosition(evt).x - this.absPosition(this.leftBlock).x;
		var w = this.leftBlock.offsetWidth;
		if (x <= 0 || w <= 0 || w < x || x < this.widthRem) return;
		this.leftWidth = x;
		this.leftCounter();

		this.setCurrentState();
		if(this.options.eventFunction) this.options.eventFunction();
	},
	clickMove : function(evt) {
		evt = evt || window.event;
		if (evt.preventDefault) evt.preventDefault();
		evt.returnValue = false;
		var x = this.defPosition(evt).x - this.absPosition(this.centerBlock).x;
		var w = this.centerBlock.offsetWidth;
		if (x <= 0 || w <= 0 || w < x) return;
		if (x >= w / 2) {
			this.rightWidth += (w - x);
			this.rightCounter();
		} else {
			this.leftWidth += x;
			this.leftCounter();
		}
		this.setCurrentState();
		if(this.options.eventFunction) this.options.eventFunction();
	},
	moveHandler : function(evt) {
		this.itWasMove = true;
		evt = evt || window.event;
		if (evt.preventDefault) evt.preventDefault();
		evt.returnValue = false;
		if (this.moveState == "left") {
			this.leftWidth = this.blockX0 + this.defPosition(evt).x - this.x0;
			this.leftCounter();
		}
		if (this.moveState == "right") {
			this.rightWidth = this.blockX0 + this.x0 - this.defPosition(evt).x;
			this.rightCounter();
		}
		this.setCurrentState();
		if(this.options.eventFunction) this.options.eventFunction();
	},
	moveIntervalHandler : function(evt) {
		this.itWasMove = true;
		evt = evt || window.event;
		if (evt.preventDefault) evt.preventDefault();
		evt.returnValue = false;
		var dX = this.defPosition(evt).x - this.x0;
		if (dX > 0) {
			this.rightWidth = this.rightX0 - dX > this.widthRem ? this.rightX0 - dX : this.widthRem;
			this.leftWidth = this.width - this.rightWidth - this.intervalWidth;
		} else {
			this.leftWidth = this.leftX0 + dX > this.widthRem ? this.leftX0 + dX : this.widthRem;
			this.rightWidth = this.width - this.leftWidth - this.intervalWidth;
		}
		this.rightCounter();
		this.leftCounter();
		this.setCurrentState();
		if(this.options.eventFunction) this.options.eventFunction();
	},
	rightCounter : function() {
		if (this.dual) {
			this.rightWidth = this.rightWidth > this.width - this.leftWidth ? this.width - this.leftWidth : this.rightWidth;
			this.rightWidth = this.rightWidth < this.widthRem ? this.widthRem : this.rightWidth;
			this.rightValue = this.leftLimit + this.valueInterval - parseInt((this.rightWidth - this.widthRem) / this.valueWidth * this.valueInterval);
			if (this.roundUp) this.rightValue = parseInt(this.rightValue / this.roundUp) * this.roundUp;
			if (this.leftWidth + this.rightWidth >= this.width) this.rightValue = this.leftValue;
		} else {
			this.rightWidth = this.rightWidth > (this.width - this.widthRem) ? this.width - this.widthRem : this.rightWidth;
			this.rightWidth = this.rightWidth < this.widthRem ? this.widthRem : this.rightWidth;
			this.leftWidth = this.width - this.rightWidth;
			this.rightValue = this.leftLimit + this.valueInterval - parseInt((this.rightWidth - this.widthRem) / this.valueWidth * this.valueInterval);
			if (this.roundUp) this.rightValue = parseInt(this.rightValue / this.roundUp) * this.roundUp;
			this.leftValue = this.rightValue;
		}
	},
	leftCounter : function() {
		if (this.dual) {
			this.leftWidth = this.leftWidth > this.width - this.rightWidth ? this.width - this.rightWidth : this.leftWidth;
			this.leftWidth = this.leftWidth < this.widthRem ? this.widthRem : this.leftWidth;
			this.leftValue = this.leftLimit + parseInt((this.leftWidth - this.widthRem) / this.valueWidth * this.valueInterval);
			if (this.roundUp) this.leftValue = parseInt(this.leftValue / this.roundUp) * this.roundUp;
			if (this.leftWidth + this.rightWidth >= this.width) this.leftValue = this.rightValue;
		} else {
			this.leftWidth = this.leftWidth > (this.width - this.widthRem) ? this.width - this.widthRem : this.leftWidth;
			this.leftWidth = this.leftWidth < this.widthRem ? this.widthRem : this.leftWidth;
			this.rightWidth = this.width - this.leftWidth;
			this.leftValue = this.leftLimit + parseInt((this.leftWidth - this.widthRem) / this.valueWidth * this.valueInterval);
			if (this.roundUp) this.leftValue = parseInt(this.leftValue / this.roundUp) * this.roundUp;
			this.rightValue = this.leftValue;
		}
	},

	updateRightValue : function(rightValue) {
		try {
			this.rightValue = parseInt(rightValue);
			this.rightValue = this.rightValue < this.leftLimit ? this.leftLimit : this.rightValue;
			this.rightValue = this.rightValue > this.rightLimit ? this.rightLimit : this.rightValue;
			if (this.dual) {
				this.rightValue = this.rightValue < this.leftValue ? this.leftValue : this.rightValue;
			} else this.leftValue = this.rightValue;
			this.rightWidth = this.valueWidth - parseInt((this.rightValue - this.leftLimit) / this.valueInterval * this.valueWidth) + this.widthRem;
			this.rightWidth = isNaN(this.rightWidth) ? this.widthRem : this.rightWidth;
			if (!this.dual) this.leftWidth = this.width - this.rightWidth;
			this.setCurrentState();
		} catch(e) {}
	},

	updateLeftValue : function(leftValue) {
		try {
			this.leftValue = parseInt(leftValue);
			this.leftValue = this.leftValue < this.leftLimit ? this.leftLimit : this.leftValue;
			this.leftValue = this.leftValue > this.rightLimit ? this.rightLimit : this.leftValue;
			if (this.dual) {
				this.leftValue = this.rightValue < this.leftValue ? this.rightValue : this.leftValue;
			} else this.rightValue = this.leftValue;
			this.leftWidth = parseInt((this.leftValue - this.leftLimit) / this.valueInterval * this.valueWidth) + this.widthRem;
			this.leftWidth = isNaN(this.leftWidth) ? this.widthRem : this.leftWidth;
			if (!this.dual) this.rightWidth = this.width - this.leftWidth;
			this.setCurrentState();
		} catch(e) {}
	},

	empty:{}

});//end class
