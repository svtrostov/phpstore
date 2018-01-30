;(function(){
var PAGE_NAME = 'admin_stickies';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': [],
		'stickies': null,
		'color_classes': ['sticky-default','sticky-pink','sticky-blue','sticky-lightblue','sticky-grey','sticky-green'],
		'active_sticky': null,
		'maxZIndex': 0,
		'zindexes':{}
	},


	/*******************************************************************
	 * Инициализация
	 ******************************************************************/

	//Вход на страницу
	enter: function(success, status, data){
		App.Location.addEvent('beforeLoadPage', App.pages[PAGE_NAME].exit);
		this.objects = $unlink(this.defaults);
		this.start(data);
	},//end function



	//Выход со страницы
	exit: function(){
		App.Location.removeEvent('beforeLoadPage', App.pages[PAGE_NAME].exit);
		var self = App.pages[PAGE_NAME];
		self.objects['tables'].each(function(table){
			if(self.objects[table]) self.objects[table].terminate();
		});
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){

		$('sticky_add_button').addEvent('click',this.stickyAdd.bind(this));

		this.setData(data);
	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type,el,area=$('tmpl_stats');
		if(typeOf(data)!='object') return;

		//
		if(typeOf(data['stickies'])=='array'){
			this.objects['stickies'] = data['stickies'];
			this.buildStickies();
		}


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/

	/*
	 * Построение стикеров
	 */
	buildStickies: function(){
		var board = $('stickies_board');
		this.objects['active_sticky'] = null;
		board.empty();
		var board_size = board.getSize();
		for(var i=0; i< this.objects['stickies'].length; i++){
			var item = this.objects['stickies'][i];
			if(this.objects['zindexes'][item['sticky_id']]==undefined){
				this.objects['zindexes'][item['sticky_id']] = i+1;
			}
			if(this.objects['maxZIndex'] < this.objects['zindexes'][item['sticky_id']]) this.objects['maxZIndex'] = this.objects['zindexes'][item['sticky_id']];
			var sticky = new Element('div',{
				'class': 'sticky '+(item['color']==''?'sticky-default':item['color']),
				'styles':{
					'left'	:item['left']+'px',
					'top'	:item['top']+'px',
					'width'	:item['width']+'px',
					'height':item['height']+'px',
					'z-index': this.objects['zindexes'][item['sticky_id']]
				},
				'events':{
					'mousedown': this.stickySelect.bind(this, item)
				}
			}).inject(board);


			var sticky_inner = new Element('div',{
				'class': 'sticky-inner'
			}).inject(sticky);

			var sticky_header = new Element('div',{
				'class': 'sticky-header'
			}).inject(sticky_inner);

			var sticky_title = new Element('div',{
				'class': 'sticky-title',
				'text' : item['author']
			}).inject(sticky_header);
			var sticky_tool_close = new Element('img',{
				'class': 'tool',
				'src': '/client/images/icons/icon_cancel_10.png',
				'events':{
					'click': this.stickyDelete.bind(this, item)
				}
			}).inject(sticky_header);
			var sticky_tool_color = new Element('img',{
				'class': 'tool',
				'src': '/client/images/icons/icon_options_10.png',
				'events':{
					'click': this.stickyChangeColor.bind(this, item)
				}
			}).inject(sticky_header);


			var sticky_content = new Element('div',{
				'class': 'sticky-content'
			}).inject(sticky_inner);

			var sticky_textarea = new Element('textarea',{
				'class': 'sticky-content',
				'wrap':'soft',
				'text': item['content']
			}).inject(sticky_content);
			sticky_textarea.addEvents({
				'keyup': function(){App.pages[PAGE_NAME].stickyAutoFontSize(this);},
				'change': function(){App.pages[PAGE_NAME].stickyUpdate(this,'content');}.bind(item)
			});

			var sticky_resizable = new Element('div',{
				'class': 'sticky-resizable'
			}).inject(sticky);


			item['board'] = board;
			item['sticky'] = sticky;
			item['sticky_inner'] = sticky_inner;
			item['sticky_header'] = sticky_header;
			item['sticky_content'] = sticky_content;
			item['sticky_textarea'] = sticky_textarea;
			item['sticky_resizable'] = sticky_resizable;

			new Drag.Move(sticky, {
				//container: board,
				limit: {x: [5,5000], y: [5, 5000]},
				handle: sticky_header,
				onSnap: function(el){
					//App.pages[PAGE_NAME].stickySelect.bind(this, item)
				}.bind(item),
				onComplete: function(el){
					App.pages[PAGE_NAME].stickyUpdate(this, 'pos');
				}.bind(item),
				onDrag: function(el, e){
				}.bind(item)
			});

			sticky.makeResizable({
				handle: sticky_resizable,
				limit: {x: [100,500], y: [100, 500]},
				onComplete: function(el, event){
					App.pages[PAGE_NAME].stickyAutoFontSize(sticky_textarea);
					App.pages[PAGE_NAME].stickyUpdate(this, 'size');
				}.bind(item)
			});
			this.stickyAutoFontSize(sticky_textarea);
		}

		if(typeOf(this.objects['board_scroll'])=='object'){
			board.scrollTo(this.objects['board_scroll'].x,this.objects['board_scroll'].y);
		}

	},//end function


	/*
	 * Подбор размера текста в зависимости от размера стикера
	 */
	stickyAutoFontSize: function(textarea){
		for (var font = 4; font <= 20; font += 1 ) {
			textarea.setStyle('font-size',  font);
			if (textarea.scrollHeight > textarea.clientHeight) {
				font =  font - 1;
				textarea.setStyle('font-size',  font);
				break;
			}
		}
	},//end function


	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	/*
	 * Удаление стикера
	 */
	stickyDelete: function(item){
		if(typeOf(item)!='object') return;
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранный стикер пользователя: <b>'+item['author']+'</b>?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/admin',
					data:{
						'action':'sticky.delete',
						'sticky_id': item['sticky_id']
					},
					silent: false,
					waiter: true,
					callback: function(success, status, data){
						if(success){
							App.pages[PAGE_NAME].setData(data);
						}
					}
				}).request();
			}
		);
	},//end function



	/*
	 * Изменение цвета стикера
	 */
	stickyChangeColor: function(item){
		if(typeOf(item)!='object') return;
		var sticky = item['sticky'];
		var classes = this.objects['color_classes'];
		var l = classes.length;
		var changed = false;
		for(var i=0; i<l; i++){
			if(sticky.hasClass(classes[i])){
				item['color'] = classes[(i+1 >= l ? 0 : i+1)];
				sticky.removeClass(classes[i]).addClass(item['color']);
				changed = true;
				break;
			}
		}
		if(!changed){
			item['color'] = classes[0];
			sticky.addClass(item['color']);
		}
		this.stickyUpdate(item, 'color');
	},//end function


	/*
	 * Новый стикер
	 */
	stickyAdd: function(){
		new axRequest({
			url : '/admin/ajax/admin',
			data:{
				'action':'sticky.add'
			},
			silent: false,
			waiter: false,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function


	/*
	 * Обновление сведения о стикере на сервере
	 */
	stickyUpdate: function(item, type){
		if(typeOf(item)!='object') return;
		var pos = item['sticky'].getPosition(item['board']);
		var scroll = item['board'].getScroll();
		this.objects['board_scroll'] = scroll;
		var size = item['sticky'].getSize();
		new axRequest({
			url : '/admin/ajax/admin',
			data:{
				'action'	:'sticky.update',
				'sticky_id'	: item['sticky_id'],
				'type'		: type,
				'content'	: item['sticky_textarea'].getValue(),
				'left'		: pos.x + scroll.x,
				'top'		: pos.y + scroll.y,
				'width'		: size.x,
				'height'	: size.y,
				'color'		: item['color']
			},
			silent: false,
			waiter: false,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function


	/*
	 * Выбран стикер
	 */
	stickySelect: function(item){
		if(typeOf(item)!='object') return;
		if(this.objects['active_sticky']){
			this.objects['active_sticky']['sticky'].removeClass('active');
		}
		this.objects['active_sticky'] = item;
		this.objects['maxZIndex']++;
		var sticky = item['sticky'];
		sticky.setStyle('z-index',this.objects['maxZIndex']).addClass('active');
		this.objects['zindexes'][item['sticky_id']] = this.objects['maxZIndex'];
	},//end function


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();