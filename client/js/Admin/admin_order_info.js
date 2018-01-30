;(function(){
var PAGE_NAME = 'admin_order_info';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		//таблицы jsTable
		'tables': ['table_products','table_pselector'],
		'validators': ['form_order'],
		'form_order':null,
		'table_products': null,
		'table_pselector':null,
		'tabs': null,
		'currencies':[],
		'currencies_assoc':{},
		'order_statuses':[],
		'deliveries':[],
		'order_paymethods':[],
		'order_products':[],
		'pselector_selected':null,
		'client_info': null,
		'client_discount_percent': 0.00
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

		//Вкладки
		this.objects['tabs'] = new jsTabPanel('tabs_area',{
			'onchange': null
		});

		//Создание календарей
		new Picker.Date($$('.calendar_input'), {
			timePicker: false,
			positionOffset: {x: 0, y: 2},
			pickerClass: 'calendar',
			useFadeInOut: false
		});

		$('order_save_button').addEvent('click', this.orderSaveInfo.bind(this));

		this.objects['form_order'] = new jsValidator('order_form');
		this.objects['form_order'].required('info_delivery_id').numeric('info_delivery_id').required('info_name').required('info_delivery_cost').ufloat('info_delivery_cost');

		this.objects['table_products'] = new jsTable('product_table', {
			'name': PAGE_NAME+'_product_table',
			'dataBackground1':'#efefef',
			'dataBackground2':'#ffffff',
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
					name: 'product_id',
					width:'50px',
					sortable:true,
					caption: 'ID',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'product_id',
					dataType: 'int'
				},
				{
					name: 'article',
					width:'100px',
					sortable:true,
					caption: 'Артикул',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'left'},
					dataSource:'article'
				},
				{
					name: 'name',
					width:'auto',
					caption: 'Наименование',
					sortable:true,
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'name'
				},
				{
					name: 'p_count',
					width:'60px',
					sortable:true,
					caption: 'Остаток на складах',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'p_count',
					dataFunction:function(table, cell, text, data){
						new Element('a',{
							'href':'#',
							'text':text,
							'events':{
								'click':function(e){
									App.pages[PAGE_NAME].pwhInfo(parseInt(data['product_id']));
									e.stopPropagation();
								}
							}
						}).inject(cell);
						return '';
					}
				},
				{
					name: 'p_currency',
					width:'50px',
					sortable:true,
					caption: 'Текущая валюта',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'p_currency'
				},
				{
					name:'p_base_price',
					width:'80px',
					sortable:true,
					caption: 'Текущая базовая цена',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'p_base_price',
					dataType: 'num'
				},
				{
					name:'p_currency_exchange',
					width:'60px',
					sortable:true,
					caption: 'Текущий курс обмена',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'p_currency',
					dataType: 'num',
					dataFunction:function(table, cell, text, data){
						return App.pages[PAGE_NAME].objects['currencies_assoc'][text];
					}
				},
				{
					name:'p_base_price_rub',
					width:'80px',
					sortable:true,
					caption: 'Текущая базовая цена, руб',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'p_base_price_rub',
					dataType: 'num'
				},
				{
					name:'p_price',
					width:'100px',
					sortable:true,
					caption: 'Текущая цена для клиента, руб (без скидок)',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'p_price',
					dataType: 'num',
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
					name:'bridge_base_price',
					width:'100px',
					sortable:true,
					visible: false,
					caption: 'Объединенная базовая цена, руб',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'bridge_base_price',
					dataType: 'num'
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
								'click':function(e){
									App.pages[PAGE_NAME].pwhInfo(parseInt(data['product_id']));
									e.stopPropagation();
								}
							}
						}).inject(cell);
						return '';
					}
				},
				{
					name:'client_discount_percent',
					width:'100px',
					sortable:true,
					visible: false,
					caption: 'Текущий % скидки для клиента',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'bridge_base_price',
					dataFunction:function(table, cell, text, data){
						return App.pages[PAGE_NAME].objects['client_discount_percent']+'%';
					}
				},
				{
					name:'client_discount_value',
					width:'100px',
					sortable:true,
					visible: false,
					caption: 'Размер скидки, руб',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'bridge_base_price',
					dataType: 'num',
					dataFunction:function(table, cell, text, data){
						var percent = App.pages[PAGE_NAME].objects['client_discount_percent'];
						var price = parseFloat(text);
						return Math.ceil(percent * price / 100);
					}
				},
				{
					name:'client_discount_price',
					width:'100px',
					sortable:true,
					visible: false,
					caption: 'Цена со скидкой, руб',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'bridge_base_price',
					dataType: 'num',
					dataFunction:function(table, cell, text, data){
						var percent = App.pages[PAGE_NAME].objects['client_discount_percent'];
						var base_price = parseFloat(text);
						var price = parseFloat(data['bridge_price']);
						return Math.floor(price - percent * base_price / 100);
					}
				},
				{
					name:'currency',
					width:'50px',
					sortable:true,
					caption: 'Валюта (заказ)',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'currency'
				},
				{
					name:'base_price',
					width:'80px',
					sortable:true,
					caption: 'Базовая цена (заказ)',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'base_price',
					dataType: 'num'
				},
				{
					name:'exchange',
					width:'60px',
					sortable:true,
					caption: 'Курс обмена (заказ)',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'exchange',
					dataType: 'num'
				},
				{
					name:'base_price_rub',
					width:'80px',
					sortable:true,
					caption: 'Базовая цена, руб (заказ)',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'base_price_rub',
					dataType: 'num'
				},
				{
					name:'price',
					width:'80px',
					sortable:false,
					caption: 'Цена для клиента, руб (заказ)',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'price',
					dataType: 'num',
					dataFunction:function(table, cell, text, data){
						var inpt = new Element('input',{
							'id': 'p'+data['product_id']+'price',
							'type':'text',
							'value': text,
							'maxchars': 20
						}).inject(cell).setStyles({
							'width':'100%'
						}).inject(cell);
						inpt.addEvent('change', function(e){
							var price = parseFloat(inpt.getValue());
							if(isNaN(price))price = 0;
							if(price == 0){
								App.message('Внимание!','Вы указали некорректную цену для товара:<br>'+data['name'],'WARNING');
								inpt.setValue(text);
							}else{
								inpt.setValue(price);
							}
							App.pages[PAGE_NAME].updateProductCost(data['product_id']);
							e.stop();
						});
					}
				},
				{
					name:'count',
					width:'60px',
					sortable:false,
					caption: 'Количество (заказ)',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'count',
					dataType: 'num',
					dataFunction:function(table, cell, text, data){
						var inpt = new Element('input',{
							'id': 'p'+data['product_id']+'count',
							'type':'text',
							'value': text,
							'maxchars': 20
						}).inject(cell).setStyles({
							'width':'100%'
						}).inject(cell);
						inpt.addEvent('change', function(e){
							var count = parseFloat(inpt.getValue());
							if(isNaN(count))count = 0;
							inpt.setValue(count);
							if(count == 0){
								App.message('Внимание!','Вы указали некорректное количество товара:<br>'+data['name'],'WARNING');
								inpt.setValue(text);
							}else{
								inpt.setValue(count);
							}
							App.pages[PAGE_NAME].updateProductCost(data['product_id']);
							e.stop();
						});
					}
				},
				{
					name:'sum',
					width:'100px',
					sortable:true,
					caption: 'Сумма по позиции, руб (заказ)',
					styles:{'min-width':'70px'},
					dataStyle:{'text-align':'right'},
					dataSource:'price',
					dataType: 'num',
					dataFunction:function(table, cell, text, data){
						cell.set('id','p'+data['product_id']+'sum');
						return Math.ceil(data['price'] * data['count']);
					}
				},
				{
					name:'delete',
					width:'30px',
					sortable:false,
					caption: '-',
					styles:{'min-width':'30px'},
					dataStyle:{'text-align':'center'},
					dataSource:'product_id',
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
										'Вы действительно хотите удалить выбранный товар из заказа:<br><br><b>'+data['article']+'</b><br><b>'+data['name']+'</b>',
										'CONFIRM',
										function(){
											App.pages[PAGE_NAME].deleteProductFromOrder(data['product_id']);
										}
									);
									e.stop();
								}
							}
						}).inject(cell);
						return '';
					}
				}
			],
			selectType:1
		});
		//this.objects['table_products'].addEvent('click', this.selectProduct.bind(this));

		$('button_new_product').addEvent('click', this.pselectorOpen.bind(this));
		$('button_refresh_product').addEvent('click', this.productsReload.bind(this));
		$('product_save_button').addEvent('click', this.productsSave.bind(this));

		//Селектор товаров
		$('pselector_cancel_button').addEvent('click', this.pselectorCancel.bind(this));
		$('pselector_complete_button').addEvent('click', this.pselectorComplete.bind(this));
		$('pselector_search').addEvent('click',this.pselectorSearch.bind(this));
		$('pselector_term').addEvent('keydown',function(e){if(e.code==13) this.pselectorSearch();}.bind(this));


		this.objects['table_pselector'] = new jsTable('pselector_table', {
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
					caption: 'pic',
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
					name:'name',
					width:'auto',
					caption: 'Наименование',
					sortable:true,
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'name'
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
					name:'vendor',
					width:'100px',
					caption: 'Производитель',
					sortable:true,
					visible: false,
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'left'},
					dataSource:'vendor'
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
					dataType: 'num'
				},
				{
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
								'click':function(e){
									App.pages[PAGE_NAME].pwhInfo(parseInt(data['product_id']));
									e.stopPropagation();
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
					name:'bridge_base_price',
					width:'100px',
					sortable:true,
					visible: false,
					caption: 'Объединенная базовая цена, руб',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'bridge_base_price',
					dataType: 'num'
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
								'click':function(e){
									App.pages[PAGE_NAME].pwhInfo(parseInt(data['product_id']));
									e.stopPropagation();
								}
							}
						}).inject(cell);
						return '';
					}
				},
				{
					name:'client_discount_percent',
					width:'100px',
					sortable:true,
					visible: false,
					caption: 'Текущий % скидки для клиента',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'bridge_base_price',
					dataFunction:function(table, cell, text, data){
						return App.pages[PAGE_NAME].objects['client_discount_percent']+'%';
					}
				},
				{
					name:'client_discount_value',
					width:'100px',
					sortable:true,
					visible: false,
					caption: 'Размер скидки, руб',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'bridge_base_price',
					dataType: 'num',
					dataFunction:function(table, cell, text, data){
						var percent = App.pages[PAGE_NAME].objects['client_discount_percent'];
						var price = parseFloat(text);
						return Math.ceil(percent * price / 100);
					}
				},
				{
					name:'client_discount_price',
					width:'100px',
					sortable:true,
					visible: false,
					caption: 'Цена со скидкой, руб',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'bridge_base_price',
					dataType: 'num',
					dataFunction:function(table, cell, text, data){
						var percent = App.pages[PAGE_NAME].objects['client_discount_percent'];
						var base_price = parseFloat(text);
						var price = parseFloat(data['bridge_price']);
						return Math.floor(price - percent * base_price / 100);
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
			selectType:1
		});
		this.objects['table_pselector'].addEvent('click', this.pselectorSelectProduct.bind(this));


		$('check_link').addEvent('click', this.checkOpenLink.bind(this));
		$('bill_link').addEvent('click', this.billOpenLink.bind(this));
		$('invoice_link').addEvent('click', this.invoiceOpenLink.bind(this));
		$('torg12_link').addEvent('click', this.torg12OpenLink.bind(this));


		if(!data){
			$('tabs_area').hide();
			$('tabs_none').show();
			return;
		}else{
			this.setData(data);
		}

		if(typeOf(this.objects['client_info'])=='object'){
			var client_id = parseInt(this.objects['client_info']['client_id']);
			$('info_order_client_id').set('html', '<a href="/admin/clients/info?client_id='+client_id+'">Зарегистрированный клиент ID: '+client_id+'</a>');
		}else{
			$('info_order_client_id').set('html','Незарегистрированный клиент');
		}

	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var text;
		if(typeOf(data)!='object') return;

		if(typeOf(data['client_info'])=='object'){
			this.objects['client_info'] = data['client_info'];
			if(typeOf(data['client_info']['discount'])=='object'){
				this.objects['client_discount_percent'] = parseFloat(data['client_info']['discount']['percent']);
			}
		}

		if(typeOf(data['currencies'])=='array'){
			this.objects['currencies'] = data['currencies'];
			/*
			select_add({
				'list': 'info_product_currency',
				'key': 'code',
				'value': 'name',
				'options': data['currencies'],
				'default': 'rub',
				'clear': true
			});
			*/
			this.objects['currencies_assoc'] = {};
			for(var i=0; i<data['currencies'].length; i++){
				this.objects['currencies_assoc'][data['currencies'][i]['code']]= parseFloat(data['currencies'][i]['exchange']);
			}
		}


		if(typeOf(data['deliveries'])=='array'){
			this.objects['deliveries'] = data['deliveries'];
			select_add({
				'list': 'info_delivery_id',
				'key': 'delivery_id',
				'value': 'name',
				'options': data['deliveries'],
				'default': '0',
				'clear': true
			});
		}

		//Типы доступа
		if(typeOf(data['accounts'])=='array'){
			select_add({
				'list': ['bill_account_code','invoice_account_code','check_account_code','torg12_account_code'],
				'key': 'code',
				'value': 'name',
				'options': data['accounts'],
				'default': '0',
				'clear': true
			});
		}//Типы доступа


		if(typeOf(data['order_statuses'])=='array'){
			this.objects['order_statuses'] = data['order_statuses'];
			select_add({
				'list': 'info_status',
				'key': 'status',
				'value': 'name',
				'options': data['order_statuses'],
				'default': '0',
				'clear': true
			});
		}

		if(typeOf(data['order_paymethods'])=='array'){
			this.objects['order_paymethods'] = data['order_paymethods'];
			select_add({
				'list': 'info_paymethod',
				'key': 'type',
				'value': 'name',
				'options': data['order_paymethods'],
				'default': 'cash',
				'clear': true
			});
		}

		//order_info
		if(typeOf(data['order_info'])=='object'){
			if(typeOf(this.objects['client_info'])=='object'){
				['okpo','bank_name','bank_bik','bank_account','bank_account_corr','legal_address'].each(function(n){
					if(!data['order_info'][n]) data['order_info'][n] = this.objects['client_info'][n];
				}.bind(this));
			}
			
			this.objects['order_info'] = data['order_info'];
			for(var key in data['order_info']){
				switch(key){
					case 'status':
						var status = parseInt(data['order_info'][key]);
						if($('info_'+key))$('info_'+key).setValue(status);
						if(status==0 || status==100){
							$('product_save_button_fail').show();
							$('product_save_button').hide();
						}else{
							$('product_save_button_fail').hide();
							$('product_save_button').show();
						}
					break;
					default:
						if($('info_'+key)){
							$('info_'+key).setValue(data['order_info'][key]);
						}
				}
			}
			var client_link = 'http://dtbox.ru/order/info?email='+encodeURIComponent(data['order_info']['email'])+'&order='+encodeURIComponent(data['order_info']['order_num']);
			$('info_client_link').setValue(client_link);
			$('info_client_link_a').set('href',client_link);
			$('bigblock_title').set('text','Информация о заказе '+data['order_info']['order_num']+' (ID:'+data['order_info']['order_id']+')');

			$('bill_buyer').setValue((data['order_info']['company'] == '' ? data['order_info']['name'] : data['order_info']['company']));
			$('invoice_buyer').setValue((data['order_info']['company'] == '' ? data['order_info']['name'] : data['order_info']['company']));
			$('invoice_buyer_address').setValue(data['order_info']['address']);
			$('invoice_buyer_inn').setValue(data['order_info']['inn']);
			$('invoice_buyer_kpp').setValue(data['order_info']['kpp']);
			$('invoice_num').setValue(data['order_info']['order_num']);
			var invoice_date = new Date.parse(data['order_info']['timestamp']).format('%d.%m.%Y');
			$('invoice_date').setValue(invoice_date);
			$('invoice_doc_num').setValue(data['order_info']['order_num']);
			$('invoice_doc_date').setValue(invoice_date);

			$('check_num').setValue(data['order_info']['order_num']);
			$('check_date').setValue(invoice_date);

			$('torg12_num').setValue(data['order_info']['order_num']);
			$('torg12_date').setValue(invoice_date);
			$('torg12_why').setValue('Счет №'+data['order_info']['order_num']+' от '+invoice_date+'г.');
			var client_full = data['order_info']['company']+', ИНН '+data['order_info']['inn']+', '+data['order_info']['legal_address']+', тел.: '+data['order_info']['phone']+', р/с '+data['order_info']['bank_account']+', в банке '+data['order_info']['bank_name']+', БИК '+data['order_info']['bank_bik']+', к/с '+data['order_info']['bank_account_corr']+'';
			$('torg12_buyer').setValue(client_full);
			$('torg12_payer').setValue(client_full);
			$('torg12_buyer_okpo').setValue(data['order_info']['okpo']);
			$('torg12_payer_okpo').setValue(data['order_info']['okpo']);
		}//order_info

		if(typeOf(data['order_products'])=='array'){
			this.objects['order_products'] = data['order_products'];
			this.setOrderProducts(data['order_products']);
		}

		if(typeOf(data['products_search'])=='array'){
			$('pselector_select').hide();
			$('pselector_none').hide();
			$('pselector_table').hide();
			if(data['products_search'].length==0){
				$('pselector_none').show();
			}else{
				this.objects['table_pselector'].setData(data['products_search']);
				$('pselector_table').show();
			}
		}

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_order_info');
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


	//Сохранение изменений
	orderSaveInfo: function(){
		if(typeOf(this.objects['order_info'])!='object') return;
		if(!this.objects['form_order'].validate()) return;
		var order_id = this.objects['order_info']['order_id'];
		new axRequest({
			url : '/admin/ajax/order',
			data: {
				'action':'order.info.edit',
				'order_id': order_id,
				'status': $('info_status').getValue(),
				'delivery_id': $('info_delivery_id').getValue(),
				'delivery_cost': $('info_delivery_cost').getValue(),
				'paymethod': $('info_paymethod').getValue(),
				'name': $('info_name').getValue(),
				'email': $('info_email').getValue(),
				'phone': $('info_phone').getValue(),
				'company': $('info_company').getValue(),
				'inn': $('info_inn').getValue(),
				'kpp': $('info_kpp').getValue(),
				'okpo': $('info_okpo').getValue(),
				'address': $('info_address').getValue(),
				'additional': $('info_additional').getValue(),
				'bank_name':		$('info_bank_name').getValue(),
				'bank_bik':			$('info_bank_bik').getValue(),
				'bank_account':		$('info_bank_account').getValue(),
				'bank_account_corr':$('info_bank_account_corr').getValue(),
				'legal_address': 	$('info_legal_address').getValue()
			},
			silent: false,
			waiter: true,
			display: 'none',
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function



	/*
	 * Перегрузить список товаров в заказе
	 */
	productsReload: function(){
		if(typeOf(this.objects['order_info'])!='object') return;
		var order_id = this.objects['order_info']['order_id'];
		App.message(
			'Подтвердите действие',
			'При перегрузке списка товаров заказа с сервера, все несохраненные изменения будут утеряны.<br>Продолжить?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/order',
					data:{
						'action':'order.products',
						'order_id': order_id
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


	//Применяет новый массив продукции для отображения в заказе
	setOrderProducts: function(data){
		if(data.length == 0){
			$('product_none_area').show();
			$('product_table_area').hide();
		}else{
			$('product_none_area').hide();
			$('product_table_area').show();
			this.objects['table_products'].setData(data);
			this.updateProductCost(null);
		}
	},//end function



	//Обновление цены товара
	updateProductCost: function(product_id){
		if(product_id){
			var price = parseFloat($('p'+product_id+'price').getValue());
			var count = parseFloat($('p'+product_id+'count').getValue());
			if(isNaN(price)) price = 0;
			if(isNaN(count)) count = 0;
			var sum = Math.ceil(price * count);
			$('p'+product_id+'sum').set('text',sum+'.00');
		}
		var order_sum = 0;
		for(var i=0; i<this.objects['order_products'].length;i++){
			if(this.objects['order_products'][i]['product_id'] == product_id){
				this.objects['order_products'][i]['price'] = price;
				this.objects['order_products'][i]['count'] = count;
			}
			order_sum += Math.ceil(parseFloat(this.objects['order_products'][i]['price']) * parseFloat(this.objects['order_products'][i]['count']));
		}
		$('order_sum').setValue(order_sum+'.00');
	},//end function



	//Удаление товара из списка заказов
	deleteProductFromOrder: function(product_id){
		if(!product_id) return;
		var found = false;
		for(var i=0; i<this.objects['order_products'].length;i++){
			if(this.objects['order_products'][i]['product_id'] == product_id){
				this.objects['order_products'].splice(i,1);
				found = true;
				break;
			}
		}
		if(found){
			this.setOrderProducts(this.objects['order_products']);
		}
	},//end function



	//Добавление нового товара, открытие окна выбора
	pselectorOpen: function(){
		if(typeOf(this.objects['order_info'])!='object') return;
		$('bigblock_wrapper').hide();
		$('pselector_selected_id').setValue(0);
		$('pselector_complete_button').hide();
		$('pselector').show();
	},//end function


	//Закрытие окна выбора нового товара без выполнения каких-либо действий, отмена
	pselectorCancel: function(){
		$('pselector').hide();
		$('bigblock_wrapper').show();
	},//end function


	//Товар был выбран, закрытие селектора с применением выбранного товара
	pselectorComplete: function(){
		if(typeOf(this.objects['pselector_selected'])!='object') return this.pselectorCancel();
		var p = this.objects['pselector_selected'];
		//Проверяем, есть ли уже данный товар в списке заказа
		var found = false;
		for(var i=0; i<this.objects['order_products'].length;i++){
			if(this.objects['order_products'][i]['product_id'] == p['product_id']){
				found = true;
				break;
			}
		}
		if(found){
			App.message('Внимание!','Вы пытаетесь добавить товар, который уже есть в заказе:<br><br>'+p['article']+'<br>'+p['name'],'WARNING');
		}else{
			if(typeOf(this.objects['order_info'])!='object') return;
			var order_id = this.objects['order_info']['order_id'];

			var percent = this.objects['client_discount_percent'];
			var base_price = parseFloat(p['bridge_base_price']);
			var price = parseFloat(p['bridge_price']);
			var client_price = Math.floor(price - percent * base_price / 100);
			var new_product = {
				'product_id': p['product_id'],
				'article': p['article'],
				'name': p['name'],
				'p_base_price': p['base_price'],
				'p_base_price_rub': p['base_price_rub'],
				'p_currency':  p['currency'],
				'p_price': p['price'],
				'currency': p['currency'],
				'base_price': p['base_price'],
				'base_price_rub': p['base_price_rub'],
				'exchange': p['exchange'],
				'price': client_price,
				'count': 1,
				'p_count': p['count'],
				'bridge_id': p['bridge_id'],
				'bridge_price': p['bridge_price'],
				'bridge_count': p['bridge_count'],
				'bridge_base_price': p['bridge_base_price'],
				'order_id': order_id
			}
			this.objects['order_products'].push(new_product);
			this.setOrderProducts(this.objects['order_products']);
		}
		this.pselectorCancel();
	},//end function


	//Поиск товаров
	pselectorSearch: function(){
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'products.search',
				'enabled': $('pselector_enabled').getValue(),
				'term': $('pselector_term').getValue(),
				'limit': $('pselector_limit').getValue(),
				'product_id': $('search_product_id').getValue(),
				'article': $('search_article').getValue(),
				'vendor': $('search_vendor').getValue(),
				'description': $('search_description').getValue(),
				'compatible': $('search_compatible').getValue(),
				'part_nums': $('search_part_nums').getValue(),
				'source': $('search_source').getValue()
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


	//Выбор товара из списка резаультатов поиска
	pselectorSelectProduct: function(){
		$('pselector_complete_button').hide();
		this.objects['pselector_selected'] = null;
		if(!this.objects['table_pselector'].selectedRows.length) return;
		var tr = this.objects['table_pselector'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		this.objects['pselector_selected'] = data;
		$('pselector_complete_button').show();
	},//end function


	/*
	 * Сохранение товаров в заказе
	 */
	productsSave: function(){
		if(typeOf(this.objects['order_info'])!='object') return;
		var order_id = this.objects['order_info']['order_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите сохранить заданный ассортимент товаров в заказ?',
			'CONFIRM',
			function(){
				App.pages[PAGE_NAME].productsSaveProcess();
			}
		);
	}, //end function


	/*
	 * Сохранение товаров в заказе - процесс
	 */
	productsSaveProcess: function(){
		if(typeOf(this.objects['order_info'])!='object') return;
		var order_id = this.objects['order_info']['order_id'];
		new axRequest({
			url : '/admin/ajax/order',
			data:{
				'action':'order.products.edit',
				'order_id': order_id,
				'products': this.objects['order_products']
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
	 * Открытие ссылка за товарный чек
	 */
	checkOpenLink: function(e){
		if(typeOf(this.objects['order_info'])!='object') return false;
		var link = 'http://dtbox.ru/order/documents/check?email='+encodeURIComponent(this.objects['order_info']['email'])+
		'&order='+encodeURIComponent(this.objects['order_info']['order_num'])+
		'&orderNum='+encodeURIComponent($('check_num').getValue())+
		'&orderDate='+encodeURIComponent($('check_date').getValue())+
		'&account_code='+encodeURIComponent($('check_account_code').getValue());
		$('check_link').set('href',link);
		e.stopPropagation();
		return true;
	},//end function


	/*
	 * Открытие ссылка за счет
	 */
	billOpenLink: function(e){
		if(typeOf(this.objects['order_info'])!='object') return false;
		var link = 'http://dtbox.ru/order/documents/bill?email='+encodeURIComponent(this.objects['order_info']['email'])+
		'&order='+encodeURIComponent(this.objects['order_info']['order_num'])+
		'&buyer='+encodeURIComponent($('bill_buyer').getValue())+
		'&account_code='+encodeURIComponent($('bill_account_code').getValue());
		$('bill_link').set('href',link);
		e.stopPropagation();
		return true;
	},//end function


	/*
	 * Открытие ссылка за счет-фактуру
	 */
	invoiceOpenLink: function(e){
		if(typeOf(this.objects['order_info'])!='object') return false;
		var link = 'http://dtbox.ru/order/documents/invoice?email='+encodeURIComponent(this.objects['order_info']['email'])+
		'&order='+encodeURIComponent(this.objects['order_info']['order_num'])+
		'&buyer='+encodeURIComponent($('invoice_buyer').getValue())+
		'&buyerAddress='+encodeURIComponent($('invoice_buyer_address').getValue())+
		'&buyerInn='+encodeURIComponent($('invoice_buyer_inn').getValue())+
		'&buyerKpp='+encodeURIComponent($('invoice_buyer_kpp').getValue())+
		'&orderNum='+encodeURIComponent($('invoice_num').getValue())+
		'&orderDate='+encodeURIComponent($('invoice_date').getValue())+
		'&orderDocNum='+encodeURIComponent($('invoice_doc_num').getValue())+
		'&orderDocDate='+encodeURIComponent($('invoice_doc_date').getValue())+
		'&account_code='+encodeURIComponent($('invoice_account_code').getValue());
		$('invoice_link').set('href',link);
		e.stopPropagation();
		return true;
	},//end function


	/*
	 * Открытие ссылка на товарную накладную
	 */
	torg12OpenLink: function(e){
		if(typeOf(this.objects['order_info'])!='object') return false;
		var link = 'http://dtbox.ru/order/documents/torg12?email='+encodeURIComponent(this.objects['order_info']['email'])+
		'&order='+encodeURIComponent(this.objects['order_info']['order_num'])+
		'&buyer='+encodeURIComponent($('torg12_buyer').getValue())+
		'&payer='+encodeURIComponent($('torg12_payer').getValue())+
		'&why='+encodeURIComponent($('torg12_why').getValue())+
		'&buyerOkpo='+encodeURIComponent($('torg12_buyer_okpo').getValue())+
		'&payerOkpo='+encodeURIComponent($('torg12_payer_okpo').getValue())+
		'&orderNum='+encodeURIComponent($('torg12_num').getValue())+
		'&orderDate='+encodeURIComponent($('torg12_date').getValue())+
		'&account_code='+encodeURIComponent($('torg12_account_code').getValue());
		$('torg12_link').set('href',link);
		e.stopPropagation();
		return true;
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