;(function(){
var PAGE_NAME = 'admin_catalog';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_products', 'table_product_wh'],
		'validators': ['form_catalog_info'],
		'table_product_wh': null,
		'table_products': null,
		'category_info': null,
		'form_catalog_info': null,
		'currencies': null,
		'currencies_assoc': null,
		'categories_list':{},
		'product_source': null,
		'properties_tree': []
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
		['info_product_content','info_product_compatible'].each(function(id){
			var ed = tinyMCE.get(id);
			if(ed) ed.destroy(false);
		});
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

		//Организации
		this.objects['splitter'] = set_splitter_h({
			'left'		: $('categories_area'),
			'right'		: $('product_area'),
			'splitter'	: $('splitter'),
			'parent'	: $('splitter').getParent('.contentareafull')
		});

		this.objects['catalog'] = new jsCatalog({
			'parent': 'categories_tree',
			'onselectnode': this.selectCatalog.bind(this),
		});

		this.objects['catalog_selector'] = new jsCatalog({
			'parent': 'category_selector_tree',
			'onselectnode': this.selectParentCategoryChange.bind(this),
			'showRoot': true
		});


		this.objects['table_products'] = new jsTable('products_table', {
			'dataBackground1':'#efefef',
			'name': PAGE_NAME+'_products_table',
			'contextmenu':true,
			'class': 'jsTable',
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
					name:'id',
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
					name:'pic',
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
					name:'desc',
					width:'auto',
					caption: 'Описание',
					sortable:true,
					visible: false,
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'desc'
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
					dataSource:'base_price',
					dataType: 'num',
					dataFunction:function(table, cell, text, data){
						return Math.ceil(App.pages[PAGE_NAME].objects['currencies_assoc'][data['currency']] * parseFloat(data['base_price']));
					}
				},
				{
					name:'price',
					width:'100px',
					sortable:true,
					caption: 'Цена для клиента, руб (без скидки)',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'right'},
					dataSource:'price',
					dataType: 'num'
				},
				{
					name:'count',
					width:'80px',
					sortable:true,
					visible: false,
					caption: 'Остаток на складах',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'count'
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
					name:'update_time',
					width:'120px',
					sortable:true,
					visible: false,
					caption: 'Время изменения',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'update_time'
				}
			],
			selectType:2
		});
		this.objects['table_products'].addEvent('click', this.selectProduct.bind(this));

		$('category_path_change_button').addEvent('click', this.selectParentCategory.bind(this));
		$('category_selector_complete_button').addEvent('click', this.selectParentCategoryComplete.bind(this));
		$('category_selector_cancel_button').addEvent('click', this.selectParentCategoryClose.bind(this));

		this.objects['form_catalog_info'] = new jsValidator('category_info_area');
		this.objects['form_catalog_info'].required('info_category_id').required('info_category_name');
		$('category_info_save_button').addEvent('click', this.categoryInfoSave.bind(this));
		$('category_delete_button').addEvent('click', this.categoryDelete.bind(this));
		$('category_add_button').addEvent('click', this.categoryAdd.bind(this));

		$('info_category_pic_preview').addEvent('click', this.categoryImagePreview.bind(this));
		$('info_category_pic_delete').addEvent('click', this.categoryImageDelete.bind(this));
		$('category_image_upload_file').addEvent('change', this.categoryUploadImageFileChange.bind(this));
		$('category_image_upload_button').addEvent('click', this.categoryImageUpload.bind(this));

		$('catalog_reload').addEvent('click',this.reloadCatalogProducts.bind(this));

		$('products_select_all').addEvent('click', function(){
			App.pages[PAGE_NAME].objects['table_products'].selectAll();
		});
		$('products_select_none').addEvent('click', function(){
			App.pages[PAGE_NAME].objects['table_products'].clearSelected();
		});
		$('products_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].objects['table_products'].filter($('products_filter').getValue());});
		$('products_action_go').addEvent('click', this.productsDoAction.bind(this));


		$('navigator_page_prev').addEvent('click', this.navigatorPagePrev.bind(this));
		$('navigator_page_next').addEvent('click', this.navigatorPageNext.bind(this));
		$('navigator_page_no').addEvent('change', this.navigatorPageSet.bind(this));


		this.objects['property_selector'] = new jsPropertiesTree({
			'parent': 'property_selector_tree',
			'onselectnode': this.propertySelectorChange.bind(this),
			'isExpanded': true,
			'showRoot': false
		});

		$('property_selector_complete_button').addEvent('click', this.propertySelectorComplete.bind(this));
		$('property_selector_cancel_button').addEvent('click', this.propertySelectorClose.bind(this));
		$('property_add_button').addEvent('click', this.propertySelectorOpen.bind(this));
		$('property_delete_button').addEvent('click', this.propertyCategoryDelete.bind(this));


		//Данные
		this.setData(data);

	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		if(typeOf(data['currencies'])=='array'){
			this.objects['currencies'] = data['currencies'];
			select_add({
				'list': 'info_product_currency',
				'key': 'code',
				'value': 'name',
				'options': data['currencies'],
				'default': 'rub',
				'clear': true
			});
			this.objects['currencies_assoc'] = {};
			for(var i=0; i<data['currencies'].length; i++){
				this.objects['currencies_assoc'][data['currencies'][i]['code']]= parseFloat(data['currencies'][i]['exchange']);
			}
		}

		if(typeOf(data['categories'])=='array'){
			this.objects['categories'] = data['categories'];
			this.objects['catalog'].build(data['categories']);
			this.objects['categories_list'] = this.objects['catalog'].list;
		}

		if(typeOf(data['category_products'])=='array'){
			if(data['category_products'].length>0){
				this.objects['table_products'].setData(data['category_products']);
				$('products_selected_action').setValue('none');
				$('products_table_none').hide();
				$('products_table_wrapper').show();
			}else{
				$('products_table_wrapper').hide();
				$('products_table_none').show();
			}
		}

		if(typeOf(data['category_info'])=='object'){
			this.objects['category_info'] = data['category_info'];
			$('info_category_id').setValue(data['category_info']['category_id']);
			$('info_category_name').setValue(data['category_info']['name']);
			$('info_category_seo').setValue(data['category_info']['seo']);
			$('info_category_desc').setValue(data['category_info']['desc']);
			$('info_category_enabled').setValue(data['category_info']['enabled']);
			$('info_category_hide_filters').setValue(data['category_info']['hide_filters']);
			$('info_category_parent_id').setValue(data['category_info']['parent_id']);
			$('info_category_pic_small').setValue(data['category_info']['pic_small']);
			var path = this.catalogPathCalculate(data['category_info']['parents']);
			$('info_category_path').set('text',path);
			$('category_title_area').set('text',path+' > '+data['category_info']['name']);
			$('category_image_upload_form').reset();
			$('category_image_upload_button').hide();
		}

		if(data['selected_category_id']!=undefined){
			this.objects['catalog'].selectNodeById(data['selected_category_id'], false);
		}

		if(data['category_image_link']!=undefined){
			if(typeOf(this.objects['category_info'])=='object'){
				this.objects['category_info']['pic_small'] = data['category_image_link'];
			}
			$('info_category_pic_small').setValue(data['category_image_link']);
		}

		if(typeOf(data['navigator'])=='object'){
			$('navigator_page_no').setValue(data['navigator']['page_no']);
			$('navigator_per_page').setValue(data['navigator']['per_page']);
			$('navigator_count').set('text',data['navigator']['count']);
		}

		if(typeOf(data['properties_tree'])=='array'){
			this.objects['properties_tree'] = data['properties_tree'];
		}


		if(typeOf(data['category_properties'])=='array'){
			select_add({
				'list': 'properties_list',
				'key': 'property_id',
				'value': 'path',
				'options': data['category_properties'],
				'default': '0',
				'clear': true
			});
		}

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_catalog');
		var title = $('bigblock_title').getParent('.titlebar');
		if(title.hasClass('expanded')||normal_screen==true){
			title.removeClass('expanded').addClass('normal');
			panel.inject($('adminarea'));
		}else{
			title.removeClass('normal').addClass('expanded');
			panel.inject(document.body);
		}
	},//end function


	catalogPathCalculate: function(data){
		var path='[Корень]';
		data.each(function(item, index){
			path+=' > '+item['name'];
		});
		return path;
	},




	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	/*
	 * Выбран элемент каталога
	 */
	selectCatalog: function(node){
		if(typeOf(node)!='object'){
			return;
		}
		$('category_area').show();
		$('category_none').hide();
		var category_id = node['category_id']

		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'category.info',
				'category_id': category_id,
				'info': 1,
				'products': 1,
				'navigator':1,
				'page_no': 1,
				'per_page':$('navigator_per_page').getValue()
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
	 * Перегрузка товаров каталога
	 */
	reloadCatalogProducts: function(){
		if(typeOf(this.objects['category_info'])!='object') return;
		var category_id = this.objects['category_info']['category_id'];
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'category.info',
				'category_id': category_id,
				'info': 0,
				'products': 1,
				'navigator':1,
				'page_no':$('navigator_page_no').getValue(),
				'per_page':$('navigator_per_page').getValue()
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	}, //end function


	/*
	 * Следующая страница
	 */
	navigatorPageNext: function(){
		var page_no = parseInt($('navigator_page_no').getValue());
		if(isNaN(page_no)) page_no = 0;
		$('navigator_page_no').setValue(page_no + 1);
		this.reloadCatalogProducts();
	},

	/*
	 * Предыдущая страница
	 */
	navigatorPagePrev: function(){
		var page_no = parseInt($('navigator_page_no').getValue());
		if(isNaN(page_no)){
			page_no = 1;
		}else{
			page_no = (page_no > 1 ? page_no-1 : 1);
		}
		$('navigator_page_no').setValue(page_no);
		this.reloadCatalogProducts();
	},


	/*
	 * Задана страница
	 */
	navigatorPageSet: function(){
		var page_no = parseInt($('navigator_page_no').getValue());
		if(isNaN(page_no)) page_no = 1;
		$('navigator_page_no').setValue(page_no);
		this.reloadCatalogProducts();
	},


	/*
	 * Выбран товар
	 */
	selectProduct: function(){
		if(!this.objects['table_products'].selectedRows.length) return;
		var tr = this.objects['table_products'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
	},//end function


	/*
	 * Выбор родительского каталога
	 */
	selectParentCategory: function(){
		if(typeOf(this.objects['category_info'])!='object') return;
		this.objects['catalog_selector'].options['selected_catalog']	= this.objects['category_info']['parent_id'];
		this.objects['catalog_selector'].options['hidden_catalog']		= this.objects['category_info']['category_id'];
		this.objects['catalog_selector'].options['showRoot']			= true;
		this.objects['catalog_selector_selected'] = null;
		this.objects['catalog_selector'].build(this.objects['categories']);
		this.objects['catalog_selector'].selectNodeById(this.objects['category_info']['parent_id'], false);
		this.objects['catalog_selector'].options.onselectcomplete = this.selectParentCategoryCompleteForCategory.bind(this);
		$('category_selector_show_element').setValue('bigblock_wrapper');
		$('bigblock_wrapper').hide();
		$('category_selector').show();
		$('category_selector_complete_button').hide();
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
		if(typeOf(this.objects['category_info'])!='object' || typeOf(this.objects['catalog_selector_selected'])!='object')return;
		if(this.objects['catalog_selector'].options.onselectcomplete) this.objects['catalog_selector'].options.onselectcomplete();
		this.objects['catalog_selector_selected'] = null;
		this.selectParentCategoryClose();
	},//end function


	/*
	 * Родительский каталог был выбран для другого каталога
	 */
	selectParentCategoryCompleteForCategory: function(){
		$('info_category_parent_id').setValue(this.objects['catalog_selector_selected']['category_id']);
		$('info_category_path').setValue($('category_selector_selected_name').getValue());
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
	 * Сохранение изменений в настройках каталога
	 */
	categoryInfoSave: function(){
		if(typeOf(this.objects['category_info'])!='object') return;
		if(!this.objects['form_catalog_info'].validate()) return;
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'category.change.info',
				'category_id': $('info_category_id').getValue(),
				'parent_id': $('info_category_parent_id').getValue(),
				'name': $('info_category_name').getValue(),
				'seo': $('info_category_seo').getValue(),
				'desc': $('info_category_desc').getValue(),
				'enabled': $('info_category_enabled').getValue(),
				'hide_filters': $('info_category_hide_filters').getValue()
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
	 * Удаление выбранного каталога
	 */
	categoryDelete: function(){
		if(typeOf(this.objects['category_info'])!='object') return;
		var category_id = this.objects['category_info']['category_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранный каталог:<br><br><b>'+this.objects['category_info']['name']+'</b><br><br>Путь к каталогу: <br><b>'+this.catalogPathCalculate(this.objects['category_info']['parents'])+'</b>',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/catalog',
					data:{
						'action':'category.delete',
						'category_id': category_id
					},
					silent: false,
					waiter: true,
					callback: function(success, status, data){
						if(success){
							$('category_area').hide();
							$('category_none').show();
							App.pages[PAGE_NAME].objects['category_info'] = null;
							App.pages[PAGE_NAME].setData(data);
						}
					}
				}).request();
			}
		);
	},//end function



	/*
	 * Создание каталога
	 */
	categoryAdd: function(){
		var name = prompt("Введите имя нового каталога", "");
		if(name != null && String(name).length > 0){
			new axRequest({
				url : '/admin/ajax/catalog',
				data:{
					'action':'category.add',
					'name': name
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
	},//end function




	/*
	 * Просмотр изображения товара
	 */
	categoryImagePreview: function(link){
		if(typeOf(this.objects['category_info'])!='object') return;
		var path = this.objects['category_info']['pic_small'];
		if(path=='') return;
		jsSlimbox.open(path, 0, {});
	},//end function



	/*
	 * Удаление изображения товара
	 */
	categoryImageDelete: function(){
		if(typeOf(this.objects['category_info'])!='object') return;
		if(this.objects['category_info']['pic_small']=='') return;
		var category_id = this.objects['category_info']['category_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить файл изображения каталога:<br>'+this.objects['category_info']['pic_small'],
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/catalog',
					data:{
						'action':'category.image.delete',
						'category_id': category_id
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
	 * Выбор файла картинки товара загружаемого на сервер
	 */
	categoryUploadImageFileChange: function(){
		var files = $('category_image_upload_file').files;
		App.echo(files);
		App.echo(typeOf(files));
		if(typeOf(files)!='collection') return;
		if(files.length > 0){
			$('category_image_upload_button').show();
			$('category_image_upload_button_title').set('text','Загрузить "'+files[0]['name']+'" на сервер');
		}else{
			$('category_image_upload_button').hide();
		}
	},//end function



	/*
	 * Загрузка файла картинки на сервер
	 */
	categoryImageUpload: function(){
		if(typeOf(this.objects['category_info'])!='object') return;
		var pic_exists = (this.objects['category_info']['pic_small']!='' ? true : false);
		$('category_image_upload_form_category_id').setValue(this.objects['category_info']['category_id']);
		if(pic_exists){
			App.message(
				'Подтвердите действие',
				'Вы действительно хотите загрузить новое изображение каталога на сервер?<br>Текущий файл изображения "'+this.objects['category_info']['pic_small']+'" будет удален и заменен на загруженный',
				'CONFIRM',
				this.categoryImageUploadProcess.bind(this)
			);
		}else{
			this.categoryImageUploadProcess();
		}
	},//end function
	categoryImageUploadProcess: function(){
		new axRequest({
			uploaderForm: $('category_image_upload_form'),
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					$('category_image_upload_form').reset();
					$('category_image_upload_button').hide();
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).upload();
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
		if(typeOf(this.objects['category_info'])!='object') return;
		var action = $('products_selected_action').getValue();
		if(action=='none') return;
		var products = this.getSelectedProducts();
		if(!products.length) return;
		var category_id = this.objects['category_info']['category_id'];
		switch(action){
			//Включить/выключить
			case 'enable':
			case 'disable':
				new axRequest({
					url : '/admin/ajax/catalog',
					data:{
						'action':'products.status',
						'products': products,
						'status': (action=='enable'?1:0),
						'category_id': category_id,
						'navigator':1,
						'page_no':$('navigator_page_no').getValue(),
						'per_page':$('navigator_per_page').getValue()
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
						'action':'products.yml',
						'products': products,
						'yml': (action=='ymlon'?1:0),
						'category_id': category_id,
						'navigator':1,
						'page_no':$('navigator_page_no').getValue(),
						'per_page':$('navigator_per_page').getValue()
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
				if(typeOf(this.objects['category_info'])!='object') return;
				this.objects['catalog_selector'].options['selected_catalog']	= -1;
				this.objects['catalog_selector'].options['hidden_catalog']		= -1;
				this.objects['catalog_selector'].options['showRoot']			= false;
				this.objects['catalog_selector_selected'] = null;
				this.objects['catalog_selector'].build(this.objects['categories']);
				this.objects['catalog_selector'].selectNodeById(this.objects['category_info']['category_id'], false);
				this.objects['catalog_selector'].options.onselectcomplete = this.selectParentCategoryCompleteForProducts.bind(this);
				$('category_selector_show_element').setValue('bigblock_wrapper');
				this.objects['products_moving'] = products;
				$('bigblock_wrapper').hide();
				$('category_selector').show();
				$('category_selector_complete_button').hide();
			break;
		}
		
	},//end function




	/*
	 * Родительский каталог был выбран для товаров
	 */
	selectParentCategoryCompleteForProducts: function(){
		var move_to = this.objects['catalog_selector_selected']['category_id'];
		var category_id = this.objects['category_info']['category_id'];
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'products.move',
				'products': this.objects['products_moving'],
				'move_to': move_to,
				'category_id': category_id,
				'navigator':1,
				'page_no':1,
				'per_page':$('navigator_per_page').getValue()
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
	 * Выбор каталога
	 */
	propertySelectorOpen: function(){
		if(typeOf(this.objects['category_info'])!='object') return;
		this.objects['property_selector'].options['showRoot'] = false;
		this.objects['property_selector_selected'] = null;
		this.objects['property_selector'].build(this.objects['properties_tree']);
		$('property_selector_selected_name').setValue('-[Выберите характеристику]-');
		$('bigblock_wrapper').hide();
		$('property_selector').show();
		$('property_selector_complete_button').hide();
	},//end function



	/*
	 * Выбран элемент
	 */
	propertySelectorChange: function(data){
		if(data['is_group']){
			$('property_selector_complete_button').hide();
			this.objects['property_selector_selected'] = null;
			$('property_selector_selected_name').setValue('-[Выберите характеристику]-');
			return;
		}
		$('property_selector_selected_name').setValue(data['path']);
		$('property_selector_complete_button').show();
		this.objects['property_selector_selected'] = data;
	},//end function


	/*
	 * Закрытие выбора каталога
	 */
	propertySelectorClose: function(){
		$('bigblock_wrapper').show();
		$('property_selector').hide();
	},//end function


	/*
	 * Каталог был выбран
	 */
	propertySelectorComplete: function(){
		if(typeOf(this.objects['property_selector_selected'])!='object') return;
		var property_id = this.objects['property_selector_selected']['property_id'];
		this.objects['property_selector_selected'] = null;
		this.propertySelectorClose();
		if(typeOf(this.objects['category_info'])!='object') return;
		var category_id = this.objects['category_info']['category_id'];
		new axRequest({
			url : '/admin/ajax/property',
			data:{
				'action':'property.category.add',
				'category_id': category_id,
				'property_id': property_id,
				'from_catalog': true
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


	/*
	 * Удаление характеристики из категории
	 */
	propertyCategoryDelete: function(){
		if($('properties_list').selectedIndex == -1) return;
		if(typeOf(this.objects['category_info'])!='object') return;
		var property_id = $('properties_list').getValue();
		var property_name = select_getText($('properties_list'));
		var category_id = this.objects['category_info']['category_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить из фильтров каталога характеристику:<br><b>'+property_name+'</b>?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/property',
					data:{
						'action':'property.category.delete',
						'property_id': property_id,
						'category_id': category_id,
						'from_catalog': true
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




	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();