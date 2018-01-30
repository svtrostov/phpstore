;(function(){
var PAGE_NAME = 'admin_bridge_list';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_bridges','table_pselector','table_products'],
		'table_bridges': null,
		'table_products':null,
		'table_pselector':null,
		'bridge_products':[]
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

		$('expand_all').addEvent('click',function(e){App.pages[PAGE_NAME].objects['table_bridges'].allSectionsDisplay(true);});
		$('expand_none').addEvent('click',function(e){App.pages[PAGE_NAME].objects['table_bridges'].allSectionsDisplay(false);});

		$('products_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].objects['table_bridges'].filter($('products_filter').getValue());});

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
					name:'name',
					width:'auto',
					caption: 'Наименование',
					sortable:true,
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'name'
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
										'Вы действительно хотите удалить выбранный товар из объединения:<br><br><b>'+data['article']+'</b><br><b>'+data['name']+'</b>',
										'CONFIRM',
										function(){
											App.pages[PAGE_NAME].deleteProductFromNewBridgeList(data['product_id']);
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
					caption: '',
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
					width:'100px',
					sortable:true,
					caption: 'Остаток на складах',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'count',
					dataType: 'int',
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
						return text+"";
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

		$('button_new_bridge').addEvent('click', this.pbridgeOpen.bind(this));
		$('pbridge_cancel_button').addEvent('click', this.pbridgeCancel.bind(this));
		$('product_save_button').addEvent('click', this.createBridge.bind(this));
		
		$('button_new_product').addEvent('click', this.pselectorOpen.bind(this));
		$('pselector_cancel_button').addEvent('click', this.pselectorCancel.bind(this));
		$('pselector_complete_button').addEvent('click', this.pselectorComplete.bind(this));
		$('pselector_search').addEvent('click',this.pselectorSearch.bind(this));
		$('pselector_term').addEvent('keydown',function(e){if(e.code==13) this.pselectorSearch();}.bind(this));

		this.setData(data);
	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		if(typeOf(data['bridges'])=='array'){
			this.bridgesDataSet(data['bridges']);
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


	bridgesDataSet: function(data){
		if(!data.length){
			$('bridges_table').hide();
			$('bridges_none').show();
			return;
		}else{
			$('bridges_none').hide();
			$('bridges_table').show();
		}

		if(!this.objects['table_bridges']){
			this.objects['table_bridges'] = new jsTable('bridges_table',{
				'dataBackground1':'#efefef',
				sectionCollapsible: true,
				columns:[
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
					name:'name',
					width:'auto',
					caption: 'Наименование',
					sortable:true,
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'name'
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
										'Вы действительно хотите удалить выбранный товар из объединения:<br><br><b>'+data['article']+'</b><br><b>'+data['name']+'</b><br><br>Примечание:<br>Если после удаления товара из объединения в нем останется только один товар, объединение будет расформировано',
										'CONFIRM',
										function(){
											App.pages[PAGE_NAME].deleteProductFromBridge(data['product_id']);
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

		this.objects['table_bridges'].setData(data);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	/*
	 * Удаление товара из объединения
	 */
	deleteProductFromBridge: function(product_id){
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'products.bridge.exclude',
				'product_id': product_id
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
	 * Удаление объединения
	 */
	deleteBridge: function(bridge_id){
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'products.bridge.delete',
				'bridge_id': bridge_id
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



	//Объединение товаров: окно выбора
	pbridgeOpen: function(){
		$('bigblock_wrapper').hide();
		$('product_save_button').hide();
		$('product_table_area').hide();
		$('product_none_area').show();
		this.objects['bridge_products'] = [];
		$('pbridge').show();
	},//end function


	//Объединение товаров: закрытие окна выбора
	pbridgeCancel: function(){
		$('pbridge').hide();
		$('bigblock_wrapper').show();
	},//end function




	//Добавление нового товара, открытие окна выбора
	pselectorOpen: function(){
		$('pbridge').hide();
		$('pselector_selected_id').setValue(0);
		$('pselector_complete_button').hide();
		$('pselector').show();
	},//end function


	//Закрытие окна выбора нового товара без выполнения каких-либо действий, отмена
	pselectorCancel: function(){
		$('pselector').hide();
		$('pbridge').show();
	},//end function


	//Поиск товаров
	pselectorSearch: function(){
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'products.search',
				'enabled':'2',
				'term': $('pselector_term').getValue(),
				'limit': $('pselector_limit').getValue(),
				'product_id': $('search_product_id').getValue(),
				'article': $('search_article').getValue(),
				'vendor': $('search_vendor').getValue(),
				'description': $('search_description').getValue(),
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


	//Товар был выбран, закрытие селектора с применением выбранного товара
	pselectorComplete: function(){
		if(typeOf(this.objects['pselector_selected'])!='object') return this.pselectorCancel();
		var p = this.objects['pselector_selected'];
		if(p['bridge_id']>0){
			App.message('Ошибка','Нельзя добавить товар, который уже объединен с другими товарами','ERROR');
			return;
		}
		//Проверяем, есть ли уже данный товар в списке
		var found = false;
		for(var i=0; i<this.objects['bridge_products'].length;i++){
			if(this.objects['bridge_products'][i]['product_id'] == p['product_id']){
				found = true;
				break;
			}
		}
		if(found){
			App.message('Внимание!','Вы пытаетесь добавить товар, который уже есть:<br><br>'+p['article']+'<br>'+p['name'],'WARNING');
		}else{
			var new_product = {
				'product_id': p['product_id'],
				'article': p['article'],
				'name': p['name'],
				'pic_big': p['pic_big']
			}
			this.objects['bridge_products'].push(new_product);
			this.setBridgeProducts(this.objects['bridge_products']);
		}
		this.pselectorCancel();
	},//end function



	//Применяет новый массив продукции для отображения в списке объединения
	setBridgeProducts: function(data){
		if(data.length == 0){
			$('product_none_area').show();
			$('product_table_area').hide();
		}else{
			$('product_none_area').hide();
			$('product_table_area').show();
			this.objects['table_products'].setData(data);
		}
		if(data.length >= 2){
			$('product_save_button').show();
		}else{
			$('product_save_button').hide();
		}
	},//end function


	//Удаляет товар из списка товаров для объединения
	deleteProductFromNewBridgeList: function(product_id){
		if(!product_id) return;
		var found = false;
		for(var i=0; i<this.objects['bridge_products'].length;i++){
			if(this.objects['bridge_products'][i]['product_id'] == product_id){
				this.objects['bridge_products'].splice(i,1);
				found = true;
				break;
			}
		}
		if(found){
			this.setBridgeProducts(this.objects['bridge_products']);
		}
	},//end function


	/*
	 * Создание объединения
	 */
	createBridge: function(){
		if(typeOf(this.objects['bridge_products'])!='array') return;
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'products.bridge.create',
				'products': this.objects['bridge_products'].fromField('product_id')
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
		this.pbridgeCancel();
		$('pselector_select').show();
		$('pselector_none').hide();
		$('pselector_table').hide();
	},//end function


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();