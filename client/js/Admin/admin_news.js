;(function(){
var PAGE_NAME = 'admin_news';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_news'],
		'table_news': null
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
		['news_content'].each(function(id){
			var ed = tinyMCE.get(id);
			if(ed) ed.destroy(false);
		});
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){
		$('news_area').hide();

		//Создание календарей
		new Picker.Date($$('.calendar_input'), {
			timePicker: false,
			positionOffset: {x: 0, y: 2},
			pickerClass: 'calendar',
			useFadeInOut: false
		});

		tinymce.init({
			height : "200",
			add_unload_trigger : false,
			selector: "textarea.tinymce",
			language : 'ru',
			plugins: [
				"advlist autolink link image lists charmap print preview hr anchor table",
				"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
				"table contextmenu directionality textcolor paste colorpicker textpattern"
			],

			toolbar1: "newdocument fullpage | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
			toolbar2: "cut copy paste pastetext | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime | forecolor backcolor",
			toolbar3: "table | hr removeformat | subscript superscript | charmap | print fullscreen | ltr rtl | visualchars visualblocks preview",

			menubar: false,
			toolbar_items_size: 'small',

			style_formats: [
				{title: 'Полужирный', inline: 'b'},
				{title: 'Красный текст', inline: 'span', styles: {color: '#ff0000'}},
				{title: 'Красный заголовок', block: 'h1', styles: {color: '#ff0000'}},
				{title: 'Таблица', selector: 'table', classes: 'cmptTable'}
			],
			content_css : "/client/css/ui-shop-ed.css"
		 });

		$('button_new_news').addEvent('click',this.newsNew.bind(this));
		$('neditor_cancel_button').addEvent('click',this.editorClose.bind(this));
		$('neditor_complete_button').addEvent('click',this.editorComplete.bind(this));

		this.objects['__data'] = data;
		this.wto();

	},//end function


	wto: function(){
		var ready = tinyMCE.get('news_content');
		if(!ready){
			window.setTimeout(this.wto.bind(this),100);
		}else{
			$('news_area').show();
			$('news_start').hide();
			this.setData(this.objects['__data']);
			this.objects['__data'] = null;
		}
	},



	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		if(typeOf(data['news'])=='array'){
			this.newsDataSet(data['news']);
		}

		if(typeOf(data['news_info'])=='object'){
			this.editorOpen(data['news_info']);
		}


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	newsDataSet: function(data){
		if(!data.length){
			$('news_table').hide();
			$('news_none').show();
			return;
		}else{
			$('news_none').hide();
			$('news_table').show();
		}

		if(!this.objects['table_news']){
			this.objects['table_news'] = new jsTable('news_table',{
				'dataBackground1':'#efefef',
				columns:[
					{
						width:'40px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'news_id',
						dataType:'html',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': INTERFACE_IMAGES+'/document_go.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.pages[PAGE_NAME].newsEdit(text);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						caption: 'ID',
						dataSource:'news_id',
						sortable:true,
						dataType:'int',
						width:'60px',
						dataStyle:{'text-align':'center'}
					},
					{
						caption: 'Видимость',
						dataSource:'enabled',
						sortable:true,
						width:'120px',
						dataStyle:{'text-align':'center'},
						dataType:'html',
						dataFunction:function(table, cell, text, data){
							if(parseInt(text)==0) return '<font color="red">Скрыта</font>';
							return '<font color="green">Отображается</font>';
						}
					},
					{
						caption: 'Время создания',
						sortable:true,
						dataSource:'timestamp',
						width:'120px',
						dataStyle:{'text-align':'center'}
					},
					{
						caption: 'Дата публикации',
						sortable:true,
						dataSource:'date',
						width:'120px',
						dataStyle:{'text-align':'center'}
					},
					{
						caption: 'Заголовок',
						sortable:true,
						dataSource:'theme',
						width:'auto',
						dataStyle:{'text-align':'left'}
					},
					{
						name:'delete',
						width:'30px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'30px'},
						dataStyle:{'text-align':'center'},
						dataSource:'news_id',
						dataFunction:function(table, cell, text, data){
							var product_id = data['product_id'];
							new Element('img',{
								'src': INTERFACE_IMAGES+'/delete.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.message(
											'Подтвердите действие',
											'Вы действительно хотите удалить выбранную новость:<br><br><b>'+data['theme']+'</b>',
											'CONFIRM',
											function(){
												App.pages[PAGE_NAME].newsDelete(data['news_id']);
											}
										);
										e.stop();
									}
								}
							}).inject(cell);
							return '';
						}
					}
				]
			});
		}

		this.objects['table_news'].setData(data);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	newsDelete: function(news_id){
		if(parseInt(news_id)==0) return;
		new axRequest({
			url : '/admin/ajax/admin',
			data:  {
				'action': 'news.delete',
				'news_id': news_id
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function


	newsEdit: function(news_id){
		if(parseInt(news_id)==0) return;
		new axRequest({
			url : '/admin/ajax/admin',
			data:  {
				'action': 'news.get',
				'news_id': news_id
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function



	newsNew: function(){
		this.editorOpen(null);
	},//end function


	/*
	 * Открытие окна редактирования или создания новости
	 */
	editorOpen: function(data){
		var ed = tinyMCE.get('news_content');
		//Редактирование новости
		if(typeOf(data)=='object'){
			$('neditor_title').set('text','Редактирование новости ID:'+data['news_id']);
			$('news_id').setValue(data['news_id']);
			$('news_enabled').setValue(data['enabled']);
			var news_date = new Date.parse(data['date']).format('%d.%m.%Y');
			$('news_date').setValue(news_date);
			$('news_theme').setValue(data['theme']);
			if(ed)ed.setContent(data['content']);
		}
		//Новая новость
		else{
			$('neditor_title').set('text','Добавление новости');
			$('news_id').setValue(0);
			$('news_enabled').setValue(1);
			$('news_date').setValue(_TODAY);
			$('news_theme').setValue('');
			if(ed)ed.setContent('');
		}
		$('bigblock_wrapper').hide();
		$('neditor').show();
	},//end function


	/*
	 * Закрытие окна редактирования или создания новости
	 */
	editorClose: function(){
		$('neditor').hide();
		$('bigblock_wrapper').show();
	},//end function


	/*
	 * Сохранение новости и закрытие окна редактирования
	 */
	editorComplete: function(){
		var ed = tinyMCE.get('news_content');
		new axRequest({
			url : '/admin/ajax/admin',
			data:  {
				'action'	: 'news.save',
				'news_id'	: $('news_id').getValue(),
				'enabled'	: $('news_enabled').getValue(),
				'date'		: $('news_date').getValue(),
				'theme'		: $('news_theme').getValue(),
				'content'	: ed.getContent('')
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].editorClose();
				}
			}
		}).request();
	},//end function



	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();