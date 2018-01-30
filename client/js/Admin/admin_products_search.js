;(function(){
var PAGE_NAME = 'admin_products_search';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		//таблицы jsTable
		'tables': ['table_products'],
		'validators': ['form_order'],
		'form_order':null,
		'table_products': null,
		'categories':null,
		'currencies_assoc':{},
		'deliveries':[]
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
		self.objects['validators'].each(function(validator){
			if(self.objects[validator]) self.objects[validator].destroy();
		});
		if(self.objects['orgchart'])self.objects['orgchart'].empty();
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){
		$('bigblock_expander').addEvent('click',this.fullscreen);

		$('search_button').addEvent('click',this.search.bind(this));
		$('search_term').addEvent('keydown',function(e){if(e.code==13) this.search();}.bind(this));

		//Создание календарей
		new Picker.Date($$('.calendar_input'), {
			timePicker: false,
			positionOffset: {x: 0, y: 2},
			pickerClass: 'calendar',
			useFadeInOut: false
		});

		this.objects['table_products'] = new jsTable('search_table', {
			'name': PAGE_NAME+'_pselector_table',
			'dataBackground1':'#efefef',
			'class': 'jsTable',
			'contextmenu':true,
			columns: [
				{
					name:'edit',
					width:'30px',
					sortable:false,
					caption: '-',
					styles:{'min-width':'30px'},
					dataStyle:{'text-align':'center'},
					dataSource:'product_id',
					dataFunction:function(table, cell, text, data){
						var a = new Element('a',{
							'href': '/admin/products/info?product_id='+text,
							'target':'_blank',
							'class':'no-push',
							'events':{
								'click':function(e){e.stopPropagation();return true;}
							}
						}).inject(cell);
						var img = new Element('img',{
							'src': INTERFACE_IMAGES+'/edit.png',
							'styles':{
								'cursor':'pointer',
								'margin-left':'4px'
							}
						}).inject(a);
						return '';
					}
				},
				{
					name:'product_id',
					width:'50px',
					sortable:true,
					caption: 'ID',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'product_id',
					dataType: 'int'
				},
				{
					name:'source_id',
					width:'100px',
					caption: 'Поставщик',
					sortable:true,
					visible: false,
					styles:{'min-width':'80px'},
					dataStyle:{'text-align':'center'},
					dataSource:'source_id',
					dataFunction:function(table, cell, text, data){
						switch(parseInt(text)){
							case 0: return '-[DTBOX]-';
							case 1: return 'TEKO';
							case 2: return 'VTT';
							case 3: return 'CITILINK';
						}
						return '???';
					}
				},
				{
					name:'article',
					width:'130px',
					sortable:true,
					caption: 'Артикул',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'left'},
					dataSource:'article'
				},
				{
					name:'preview',
					width:'18px',
					caption: '-',
					styles:{'min-width':'18px'},
					dataStyle:{'text-align':'center'},
					dataSource:'pic_big',
					dataFunction:function(table, cell, text, data){
						if(!text||text==''){
							new Element('img',{
								'src':INTERFACE_IMAGES+'/preview_none.png',
							}).inject(cell).setStyles({
								'cursor':'default',
								'float':'left'
							});
						}else{
							new Element('img',{
								'src':INTERFACE_IMAGES+'/preview_active.png',
							}).inject(cell).setStyles({
								'cursor':'pointer',
								'float':'left'
							}).addEvents({
								'click': function(e){
									jsSlimbox.open(text, 0, {});
									e.stop();
								}
							});
						}
						return '';
					}
				},
				{
					name:'yml_enabled',
					width:'120px',
					sortable:true,
					visible: false,
					caption: 'Yandex market',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'yml_enabled',
					dataType:'html',
					dataFunction:function(table, cell, text, data){
						if(parseInt(text)==0) return '<font color="red">Нет</font>';
						return '<font color="green">Да</font>';
					}
				},
				{
					name:'name',
					width:'auto',
					caption: 'Наименование',
					sortable:true,
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'name'
				},
				{
					name:'description',
					width:'auto',
					caption: 'Описание товара',
					sortable:true,
					visible: false,
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'description'
				},
				{
					name:'vendor',
					width:'auto',
					caption: 'Производитель',
					sortable:true,
					visible: false,
					styles:{'min-width':'100px'},
					dataStyle:{'text-align':'left'},
					dataSource:'vendor'
				},
				{
					name:'part_nums',
					width:'auto',
					caption: 'Парт-номера',
					sortable:true,
					visible: false,
					styles:{'min-width':'120px'},
					dataStyle:{'text-align':'left'},
					dataSource:'part_nums'
				},
				{
					name:'category_name',
					width:'auto',
					caption: 'Каталог',
					sortable:true,
					visible: false,
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'category_name'
				},
				{
					name:'currency',
					width:'50px',
					sortable:true,
					caption: 'Валюта',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'currency'
				},
				{
					name:'base_price',
					width:'100px',
					sortable:true,
					caption: 'Базовая цена',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'base_price',
					dataType: 'num'
				},
				{
					name:'base_price_rub',
					width:'100px',
					sortable:true,
					caption: 'Базовая цена, руб',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'base_price_rub',
					dataType: 'num',
				},
				{
					name:'price',
					width:'100px',
					sortable:true,
					caption: 'Цена для клиента, руб (без скидок)',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'price',
					dataType: 'num',
				},
				{
					name:'count',
					width:'100px',
					sortable:true,
					caption: 'Остаток на складах',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'count',
					dataFunction:function(table, cell, text, data){
						new Element('a',{
							'href':'#',
							'text':text,
							'events':{
								'click':function(){
									App.pages[PAGE_NAME].pwhInfo(parseInt(data['product_id']));
								}
							}
						}).inject(cell);
						return '';
					}
				},
				{
					name:'bridge_id',
					width:'50px',
					sortable:true,
					visible: false,
					caption: 'Объединение товаров',
					styles:{'min-width':'25px'},
					dataStyle:{'text-align':'center'},
					dataSource:'bridge_id',
					dataType:'html',
					dataFunction:function(table, cell, text, data){
						if(parseInt(text)==0) return '<font color="black">Нет</font>';
						return '<a href="/admin/bridge/info?bridge_id='+text+'" target="_blank">'+text+'</a>';
					}
				},
				{
					name:'bridge_price',
					width:'100px',
					sortable:true,
					visible: false,
					caption: 'Объединенная цена для клиента, руб (без скидки)',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'bridge_price',
					dataType: 'num'
				},
				{
					name:'bridge_count',
					width:'80px',
					sortable:true,
					visible: false,
					caption: 'Объединенный остаток на складах',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'bridge_count',
					dataFunction:function(table, cell, text, data){
						new Element('a',{
							'href':'#',
							'text':text,
							'events':{
								'click':function(){
									App.pages[PAGE_NAME].pwhInfo(parseInt(data['product_id']));
								}
							}
						}).inject(cell);
						return '';
					}
				},
				{
					name:'create_time',
					width:'100px',
					sortable:true,
					visible: false,
					caption: 'Добавлен',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'create_time'
				},
				{
					name:'update_time',
					width:'100px',
					sortable:true,
					visible: false,
					caption: 'Изменен',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'update_time'
				},
				{
					name:'enabled',
					width:'50px',
					sortable:true,
					caption: 'Отображение',
					styles:{'min-width':'25px'},
					dataStyle:{'text-align':'center'},
					dataSource:'enabled',
					dataType:'html',
					dataFunction:function(table, cell, text, data){
						if(parseInt(text)==0) return '<font color="red">Скрыт</font>';
						return '<font color="green">Виден</font>';
					}
				},
				{
					name:'client_enabled',
					width:'50px',
					sortable:true,
					caption: 'Виден клиенту',
					styles:{'min-width':'25px'},
					dataStyle:{'text-align':'center'},
					dataSource:'client_enabled',
					dataType:'html',
					dataFunction:function(table, cell, text, data){
						if(parseInt(text)==0) return '<font color="red">Скрыт</font>';
						return '<font color="green">Виден</font>';
					}
				}
			],
			selectType:2
		});


		$('products_select_all').addEvent('click', function(){
			App.pages[PAGE_NAME].objects['table_products'].selectAll();
		});
		$('products_select_none').addEvent('click', function(){
			App.pages[PAGE_NAME].objects['table_products'].clearSelected();
		});
		$('products_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].objects['table_products'].filter($('products_filter').getValue());});
		$('products_action_go').addEvent('click', this.productsDoAction.bind(this));

		this.objects['catalog_selector'] = new jsCatalog({
			'parent': 'category_selector_tree',
			'onselectnode': this.selectParentCategoryChange.bind(this),
			'showRoot': true
		});
		$('category_selector_complete_button').addEvent('click', this.selectParentCategoryComplete.bind(this));
		$('category_selector_cancel_button').addEvent('click', this.selectParentCategoryClose.bind(this));


		this.setData(data);

	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var text;
		if(typeOf(data)!='object') return;

		if(typeOf(data['categories'])=='array'){
			this.objects['categories'] = data['categories'];
		}

		if(typeOf(data['category_list'])=='array'){
			this.objects['category_list'] = data['category_list'];
			select_add({
				'list': 'search_catalog',
				'key': 0,
				'value': 1,
				'options': data['category_list'],
				'default': 'all',
				'clear': false
			});
		}

		if(typeOf(data['currencies'])=='array'){
			this.objects['currencies'] = data['currencies'];
			select_add({
				'list': 'search_currency',
				'key': 'code',
				'value': 'name',
				'options': data['currencies'],
				'default': 'all',
				'clear': false
			});
		}

		if(typeOf(data['products_search'])=='array'){
			$('search_select').hide();
			$('search_none').hide();
			$('search_table').hide();
			$('search_table_tool').hide();
			if(data['products_search'].length==0){
				$('search_none').show();
			}else{
				this.objects['table_products'].setData(data['products_search']);
				$('search_table').show();
				$('search_table_tool').show();
			}
		}

		if(typeOf(data['search_term'])=='string'){
			$('search_term').setValue(data['search_term']);
			this.search();
		}

		if(data['search_refresh']!=undefined){
			this.search();
		}

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_products_search');
		var title = $('bigblock_title').getParent('.titlebar');
		if(title.hasClass('expanded')||normal_screen==true){
			title.removeClass('expanded').addClass('normal');
			panel.inject($('adminarea'));
		}else{
			title.removeClass('normal').addClass('expanded');
			panel.inject(document.body);
		}
	},//end function


	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Поиск товаров
	search: function(){
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'products.search',
				'enabled': $('search_enabled').getValue(),
				'product_id': $('search_product_id').getValue(),
				'term': $('search_term').getValue(),
				'limit': $('search_limit').getValue(),
				'category_id': $('search_catalog').getValue(),
				'subcategories': $('search_subcategories').getValue(),
				'article': $('search_article').getValue(),
				'vendor': $('search_vendor').getValue(),
				'description': $('search_description').getValue(),
				'compatible': $('search_compatible').getValue(),
				'part_nums': $('search_part_nums').getValue(),
				'currency': $('search_currency').getValue(),
				'price_min': $('search_price_min').getValue(),
				'price_max': $('search_price_max').getValue(),
				'source': $('search_source').getValue(),
				'yml': $('search_yml').getValue(),
				'date': $('search_date').getValue(),
				'datetype': $('search_datetype').getValue()
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


	/*
	 * Получение массива ID выделенных товаров
	 */
	getSelectedProducts: function(){
		if(!this.objects['table_products'].selectedRows.length) return [];
		var result = [];
		for(var i=0; i<this.objects['table_products'].selectedRows.length;i++){
			tr = this.objects['table_products'].selectedRows[i];
			if(typeOf(tr)!='element') continue;
			data = tr.retrieve('data');
			if(typeOf(data)!='object') continue;
			result.push(data['product_id']);
		}
		return result;
	},//end function


	/*
	 * Выполнение действия с выбранными товарами
	 */
	productsDoAction: function(){
		var action = $('products_selected_action').getValue();
		if(action=='none') return;
		var products = this.getSelectedProducts();
		if(!products.length) return;
		switch(action){
			//Включить/выключить
			case 'enable':
			case 'disable':
				new axRequest({
					url : '/admin/ajax/catalog',
					data:{
						'action':'products.search.status',
						'products': products,
						'status': (action=='enable'?1:0)
					},
					silent: false,
					waiter: true,
					callback: function(success, status, data){
						if(success){
							App.pages[PAGE_NAME].setData(data);
						}
					}
				}).request();
			break;

			//Добавить / удалить для Yandex.market
			case 'ymlon':
			case 'ymloff':
				new axRequest({
					url : '/admin/ajax/catalog',
					data:{
						'action':'products.search.yml',
						'products': products,
						'yml': (action=='ymlon'?1:0)
					},
					silent: false,
					waiter: true,
					callback: function(success, status, data){
						if(success){
							App.pages[PAGE_NAME].setData(data);
						}
					}
				}).request();
			break;

			//Перенести
			case 'move':
				if(typeOf(this.objects['categories'])!='array') return;
				this.objects['catalog_selector'].options['selected_catalog']	= -1;
				this.objects['catalog_selector'].options['hidden_catalog']		= -1;
				this.objects['catalog_selector'].options['showRoot']			= false;
				this.objects['catalog_selector_selected'] = null;
				this.objects['catalog_selector'].build(this.objects['categories']);
				this.objects['catalog_selector'].options.onselectcomplete = this.selectParentCategoryCompleteForProducts.bind(this);
				$('category_selector_show_element').setValue('bigblock_wrapper');
				this.objects['products_moving'] = products;
				$('bigblock_wrapper').hide();
				$('category_selector').show();
				$('category_selector_complete_button').hide();
			break;

			case 'bridge':
				if(products.length<2) return App.message('Внимание!','Объединить можно не менее двух товаров','WARNING');
				for(var i=0; i<this.objects['table_products'].selectedRows.length;i++){
					var tr = this.objects['table_products'].selectedRows[i];
					if(typeOf(tr)!='element') continue;
					var data = tr.retrieve('data');
					if(typeOf(data)!='object') continue;
					if(data['bridge_id']>0){
						return App.message('Ошибка','Нельзя объединить товары, которые уже объединены с другими товарами:<br><br>'+data['article']+'<br>'+data['name'],'ERROR');
					}
				}
				new axRequest({
					url : '/admin/ajax/catalog',
					data:{
						'action':'products.search.bridge',
						'products': products
					},
					silent: false,
					waiter: true,
					callback: function(success, status, data){
						if(success){
							App.pages[PAGE_NAME].setData(data);
						}
					}
				}).request();
			break;
		}
		
	},//end function


	/*
	 * Выбран новый родительский элемент
	 */
	selectParentCategoryChange: function(data){
		$('category_selector_complete_button').show();
		$('category_selector_selected_name').setValue(data['name']);
		this.objects['catalog_selector_selected'] = data;
	},//end function

	/*
	 * Закрытие выбора родительского каталога
	 */
	selectParentCategoryClose: function(){
		$($('category_selector_show_element').getValue()).show();
		$('category_selector').hide();
	},//end function


	/*
	 * Родительский каталог был выбран
	 */
	selectParentCategoryComplete: function(){
		if(typeOf(this.objects['catalog_selector_selected'])!='object')return;
		if(this.objects['catalog_selector'].options.onselectcomplete) this.objects['catalog_selector'].options.onselectcomplete();
		this.objects['catalog_selector_selected'] = null;
		this.selectParentCategoryClose();
	},//end function


	/*
	 * Родительский каталог был выбран для товаров
	 */
	selectParentCategoryCompleteForProducts: function(){
		var move_to = this.objects['catalog_selector_selected']['category_id'];
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'products.search.move',
				'products': this.objects['products_moving'],
				'move_to': move_to
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


	//Информация об остатках на складах
	pwhInfo: function(product_id){
		if(!product_id) return;
		new axRequest({
			url : '/admin/ajax/catalog',
			data:  {
				'action': 'product.warehouse.info',
				'product_id': product_id
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					if(data['pwh_html']!=undefined){
						new jsMessage({
							'width'		: '900px',
							'isUrgent'	: false,
							'autoDismiss': false,
							'centered'	: true,
							'title'		: 'Остатки на складах',
							'message'	: data['pwh_html'],
							'type'		: 'info',
							'isModal'	: true,
							'yesLink'	: 'Да',
							'noLink'	: 'Закрыть'
						}).say();
					}
				}
			}
		}).request();
	},//end function

	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();