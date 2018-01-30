/*----------------------------------------------------------------------
Класс отображения сообщений
Stanislav V. Tretyakov (svtrostov@yandex.ru)
----------------------------------------------------------------------*/

var jsMessage = new Class({
	Implements: [Options, Events],
	msgChain: null,
	textArea: null,
	end: false,
	isDisplayed: false,
	isCreated: false,
	windowSize: null,
	pageSize: null,
	page: $(document),
	box: null,
	boxSize: null,
	scrollPos: null,
	windowSize: null,
	hasVerticalBar: false,
	hasHorizontalBar: false,
	boxPos: function(){},
	tipCheck: true,
	cancel: false,
	overlay: null,
	fx: null,
	fxOut: null,
	options: {
		callingElement: null,
		top: false,	//Показывать сверху
		left: false,	//Показывать слева
		centered: false,	//По центру экрана
		offset: 30, 	//Отступы от края окна
		width: 'auto',	//Ширина окна сообщения
		height: '200px',//Высота окна сообщения (для блока комментариев)
		title: null,	//Заголовок сообщения
		message: null,	//Текст сообщения
		delay: 0,	//Задержка отображения
		autoDismiss: true,	//Автоматически скрывать
		dismissOnEvent: false,	//Скрывать по событию
		isUrgent: false,	//Это важное сообщение, которое требует подтверждения нажатием на кнопку Ок
		isModal: false,	//Сообщение в модальном режиме, создается слой, закрывающий остальные элементы окна
		isComment: false, //Признак, что это окно комментария
		callback: null,	//Функция, вызываемая при нажатии на Ок
		passEvent: null,			// Событие, при нахождении курсора мыши над окном сообщения
		stack: false,				//Признак, разрешающий отображения нескольких сообщений друг над другом
		fxTransition: null,			// Эффект при отображении обычного сообщения
		fxDuration: 'normal',		// Скорость эффекта
		fxUrgentTransition: Fx.Transitions.Pow.easeOut, //Эффект при отображении важного сообщения (Pow, Expo, Circ, Sine, Back, Bounce, Elastic, Quad)
		fxOutTransition: null,		// Эффект сокрытия сообщения
		fxOutDuration: 'normal',		//Скорость эффекта при скрытии сообщения
		overlayOpacity:	.2,
		overlayColor:	"#000000",
		overlayClick:true,
		yesLink: "Да",
		noLink: "Отмена",
		type: 'info'
	},
	
	//Инициализация
	initialize: function(options){
		this.setOptions(options);
		this.box = this;
		if(this.options.width == 'auto') this.options.width = '450px';
		if(this.options.passEvent != null && this.options.callingElement != undefined) {
			this.options.dismissOnEvent = true;
			this.options.callingElement.addEvent('mouseout', function(){
				if(this.isDisplayed) this.dismiss(); else this.cancel = true;
			}.bind(this));
		}
	},


	//Стандартное сообщение
	say: function(){
		this.box = this.createBox();
		this.msgChain = new Chain();
		this.setMsgChain();
	},


	//Комментарий
	comment: function(){
		this.options.isModal		= true;
		this.options.isUrgent		= true;
		this.options.autoDismiss 	= false;
		this.options.isComment		= true;
		this.options.centered		= true;
		this.options.top			= false;
		this.options.left			= false;
		this.say();
	},


	//Всплывающаа подсказка
	tip: function(){
		this.options.autoDismiss 	= true;
		this.options.dismissOnEvent = false;
		this.options.centered		= false;
		this.options.top			= false;
		this.options.left			= false;
		this.options.isModal		= false;
		this.options.isUrgent		= false;
		this.options.callback		= null;
		this.say();
	},


	//Установка признаков отображения и сокрытия окна сообщения
	setMsgChain: function(){
		
		if(this.fx == null){
			this.fx = new Fx.Tween(this.box, {
				link: 'chain',
				onComplete: function(){
					if((this.options.autoDismiss && !this.options.dismissOnEvent) || (!this.isDisplayed && this.options.callback == null) ) this.msgChain.callChain();
				}.bind(this),
				transition: this.options.fxTransition,
				duration: this.options.fxDuration
			});
		}

		// Установка задержки перед сокрытием окна
		var waitTime = (this.options.isUrgent || this.options.callback != null || this.options.autoDismiss == false || this.options.dismissOnEvent) ? 0 : 2000;

		this.msgChain.wait(
			this.options.delay
		).chain(
			function(){
				if(!this.cancel) this.showMsg(); else this.complete();
				this.fireEvent('show');
			}.bind(this)
		).wait(waitTime).chain(
			function(){
				this.hideMsg();
			}.bind(this)
		).callChain();
	},


	showMsg: function(){
		//Вычисление размеров и позиционарования окна сообщения на экране
		this.setSizes();
		this.setBoxPosition();
		
		if(this.hasVerticalBar) $(document.body).setStyle('overflow', 'hidden');
		
		this.box.setStyles({
			'opacity': 0,
			'top': this.boxPos.startTop,
			'left': this.boxPos.startLeft,
			'z-index': '9999'
		}).fade('in');
		
		if(!this.options.isUrgent){
			this.fx.start('top', this.boxPos.endTop);
		} else {
			var urgentFx = new Fx.Tween(this.box, {
				duration: 'long', 
				transition: this.options.fxUrgentTransition
			});
			urgentFx.start('top', this.boxPos.endTop);
		}
		this.isDisplayed = true;
	},


	dismiss: function(){
		this.msgChain.callChain();
		this.fx = null;
	},


	// Определение, где будет отображаться сообщение
	setBoxPosition: function(){
		this.boxPos = {};
		var usePosition = (this.options.top && this.options.left), 
			startTopPos, 
			startLeftPos, 
			endLeftPos, 
			endTopPos, 
			stackUp = 0,
			stackDown = 0,
			stackPad = 3.5, 
			messages,
			messagesLength = 1,
			heights,
			mcClass = null,
			tops;
			
		if(this.options.isUrgent){ mcClass = '[class*=mcUrgent]';}
		else if(this.options.top){ mcClass = '[class*=mcTop]';}
		else if(this.options.callingElement != undefined){ mcClass = '[class*=mcElement]'}
		else { mcClass = '[class*=mcDefault]'; }
			
		if(this.options.stack){ 
			messages = $$('[class*=messageClass]' + mcClass + '');
			messagesInfo = messages.getCoordinates();

			var heights = new Array();
			var tops 	= new Array();

			messagesInfo.each(function(m){
				heights.push(m.height);
				if(m.top > 0) tops.push(m.top);
			});

			stackUp = this.scrollPos.y + this.windowSize.y - (heights.sum() + stackPad * messages.length);			
			if(stackUp >= tops.min()) stackUp = tops.min() - this.boxSize.y - stackPad;
			
			stackDown = heights.sum() - this.boxSize.y + (stackPad * messages.length);
			if(tops.length > 0){
				if(stackDown <= tops[tops.length-1] + heights[heights.length-2] + stackPad) stackDown = tops[tops.length-1] + heights[heights.length-2] + stackPad;
			}
		} else {
			stackUp = this.scrollPos.y + this.windowSize.y - this.boxSize.y - this.options.offset;
			stackDown = this.options.offset;
		}
		
		this.options.top  ? startTopPos  = (this.boxSize.y * -1) : startTopPos = this.scrollPos.y + this.windowSize.y;
		this.options.left ? startLeftPos = this.options.offset : startLeftPos = this.windowSize.x - this.boxSize.x - this.options.offset;
		this.options.top  ? endTopPos 	 = stackDown : endTopPos = (stackUp) ;		

		if((this.options.passEvent != null && !this.options.isUrgent) && !usePosition){
			var offsetCursor;
			(this.options.passEvent.page.x + this.boxSize.x > this.windowSize.x)? offsetCursor = (this.boxSize.x * -1) - 5 : offsetCursor = 5;
			
			Object.append(this.boxPos,{
				startTop  : this.options.passEvent.page.y - this.options.offset,
				startLeft : this.options.passEvent.page.x + offsetCursor,
				endTop	  : this.options.passEvent.page.y + stackDown - (stackPad * 3)
			});
		} else if((this.options.isUrgent && !usePosition) || this.options.centered) {
			this.box.position();
			this.boxPosition = this.box.getCoordinates();
			
			if(this.options.stack && messages.length > 1){
				stackDown = tops[tops.length-1] + heights[heights.length-2] + stackPad;
			} else {
				stackDown = this.boxPosition.top;
			}
			
			Object.append(this.boxPos,{
				startTop  : this.boxPosition.top - 100,
				startLeft : this.boxPosition.left,
				endTop 	  : stackDown
			});
		
		} else {
			Object.append(this.boxPos,{
				startTop  : startTopPos,
				startLeft : startLeftPos,
				endTop 	  : endTopPos 
			});
		}
	},
	

	setSizes: function(){
		this.boxSize     = this.box.getSize();
		this.boxPosition = this.box.getCoordinates();
		this.windowSize	 = this.page.getSize();
		this.scrollPos 	 = this.page.getScroll();
		this.pageSize 	 = this.page.getScrollSize();
		if(this.windowSize.y >= this.pageSize.y) this.hasVerticalBar = true || false
		if(this.windowSize.x >= this.pageSize.x) this.hasHorizontalBar = true || false
	},


	//Создание элементов окна сообщения
	createBox: function(){
		var 
		top = "", 
		left = "",
		normal = "",
		urgent = "",
		mcElement = "",
		newBox,
		newTitle,
		newIcon,
		newContent,
		newTextarea,
		newButtonpanel,
		newClear,
		newWrapper;
		
		//Оверлей
		if(this.options.isModal){
			this.overlay = new Element("div");
			this.overlay.inject( $$("body")[0] ).addClass('msgOverlay').setStyle("background-color", this.options.overlayColor).setStyle('opacity',this.options.overlayOpacity);
			if( this.options.overlayClick){
				this.overlay.addEvent("click", function(e){
					if(e) e.stop();
					if(!this.options.isUrgent)
					this.msgChain.callChain();
				}.bind(this))
			}
		}

		if(this.options.top){ top = " mcTop"; }
		else if(this.options.isUrgent){ urgent = " mcUrgent"; }
		else if(this.options.callingElement != undefined){ mcElement = " mcElement"; }
		else{ normal = ' mcDefault'; }

		newBox = new Element('div', {'class': 'msgBox messageClass' + top + normal + urgent + mcElement, 'styles': {'max-width':this.options.width, 'width':this.options.width}});

		//Если это всплывающее сообщение Tip
		if(this.options.autoDismiss == true && !this.options.dismissOnEvent){
			newTitle = new Element('div', {'class': 'msgBoxTipTitle','html': this.options.title});
			newIcon = new Element('div', {'class': 'msgBoxIcon '+(this.options.type=='confirmwarn'?'warning':this.options.type)});
			newContent = new Element('div', {'class': 'msgBoxTipContent','html': this.options.message});
			newWrapper = new Element('div', {'class':'msgBoxTipWrapper'});
			newIcon.inject(newBox);
			newWrapper.inject(newBox);
			newTitle.inject(newWrapper);
			newContent.inject(newWrapper);
		}else{
			newTitle = new Element('div', {'class': 'msgBoxTitle','html': this.options.title});
			newIcon = new Element('div', {'class': 'msgBoxIcon '+(this.options.type=='confirmwarn'?'warning':this.options.type)});
			
			if(this.options.type == 'comment'){
				newContent = new Element('div', {'class': 'msgBoxContent'});
				newTextarea = new Element('textarea', {
					'value': this.options.message,
					'styles':{
						'height': this.options.height
					}
				}).inject(newContent);
				this.textArea = newTextarea;
			}else{
				newContent = new Element('div', {'class': 'msgBoxContent','html': this.options.message});
			}
			newClear = new Element('div', {'class': 'clear'}); 
			newButtonpanel = new Element('div', {'class': 'msgBoxButtonPanel'});
			if(this.options.type == 'confirm' || this.options.type == 'confirmwarn' || this.options.type == 'comment'){
				var yes = this.createLink(this.options.yesLink, (this.options.callback != null ? true: false));
				var no 	= this.createLink(this.options.noLink, false);
				yes.inject(newButtonpanel);
				no.inject(newButtonpanel);
			}else{
				var ok = this.createLink('Ок', (this.options.callback != null ? true: false));
				ok.inject(newButtonpanel);
			}
			newTitle.inject(newBox);
			newIcon.inject(newBox);
			newContent.inject(newBox);
			newClear.inject(newBox);
			newButtonpanel.inject(newBox);
		}



		newBox.inject(this.page.body);
		this.isCreated = true;
		
		this.box = newBox;
		return newBox;
	},


	//Создание элемента кнопки
	createLink: function(html, callMe){
		var button = new Element('div',{
			'class':'ui-button',
			'id': html.replace(" ", "_") + 'Link',
			'events':{
				'click': function(){
					this.msgChain.callChain();
					if(callMe) this.executeCallback();
				}.bind(this)
			}
		});
		new Element('span',{
			'html': html,
		}).inject(button);
		return button;
	},


	//Вызов Callback функции
	executeCallback: function(){
		if(typeOf(this.options.callback) == 'element') this.options.callback.fireEvent('click');
		else 
		if (typeOf(this.options.callback)=='function'){
			var sendMessage = (this.textArea != null && this.options.type == 'comment') ? this.textArea.get('value') : '';
			this.options.callback.run([sendMessage]);
		}
	},


	complete: function(){
		this.textArea = null;
		this.box.dispose();
		this.end = true;
		this.isDisplayed = false;
		this.fireEvent('complete');
		//$(document.body).setStyle('overflow', 'auto');
		if(this.options.isModal && this.overlay) this.overlay.dispose();
		this.fx = null;
	},


	//Сокрытие окна сообщения
	hideMsg: function(){
		if(this.hasVerticalBar) $(document.body).setStyle('overflow', 'hidden');
		var position = this.box.getCoordinates();
		this.box.fade('out');
		
		this.fxOut = new Fx.Tween(this.box, {
			transition: this.options.fxOutTransition,
			duration: this.options.fxOutDuration
		});
		
		this.fxOut.addEvent('complete',this.complete.bind(this));
		
		var topPos;
		this.options.top ? topPos = this.boxSize.y * -1 : topPos = position.top + this.boxSize.y;
		
		this.fxOut.start('top', topPos);
	}
});