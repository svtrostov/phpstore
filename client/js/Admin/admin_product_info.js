;(function(){
var PAGE_NAME = 'admin_product_info';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_products', 'table_product_wh', 'table_bridge','table_pselector'],
		'validators': ['form_catalog_info','form_product_info'],
		'table_product_wh': null,
		'table_products': null,
		'table_bridge': null,
		'table_pselector':null,
		'category_info': null,
		'form_catalog_info': null,
		'form_product_info':null,
		'product_info': null,
		'product_source': null,
		'currencies': null,
		'currencies_assoc': null,
		'categories_list':{},
		'pselector_selected':null,
		'bridge_products':[],
		'product_images':[],
		'properties_tree':[],
		'product_properties':[]
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
		$('product_card').hide();
		$('product_save_button').hide();
		$('bigblock_expander').addEvent('click',this.fullscreen);

		this.objects['product_tabs'] = new jsTabPanel('product_tabs',{
			'onchange': this.productTabChange.bind(this)
		});

		this.objects['catalog_selector'] = new jsCatalog({
			'parent': 'category_selector_tree',
			'onselectnode': this.selectParentCategoryChange.bind(this),
			'showRoot': true
		});

		this.objects['table_product_wh'] = new jsTable('product_wh_table', {
			'dataBackground1':'#efefef',
			'class': 'jsTable',
			columns: [
				{
					width:'50px',
					sortable:true,
					caption: 'ID',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'warehouse_id',
					dataType: 'int'
				},
				{
					width:'250px',
					sortable:true,
					caption: 'Склад',
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'name'
				},
				{
					width:'auto',
					caption: 'Текущий остаток',
					styles:{'min-width':'18px'},
					dataStyle:{'text-align':'left'},
					dataSource:'count',
					dataFunction:function(table, cell, text, data){
						var inpt = new Element('input',{
							'type':'text',
							'value': text,
							'maxchars': 10
						}).inject(cell).setStyles({
							'width':'100px'
						}).inject(cell);
						inpt.addEvent('change', function(e){
							var count = parseFloat(inpt.getValue());
							if(isNaN(count))count = 0;
							new axRequest({
								url : '/admin/ajax/catalog',
								data:{
									'action':'product.warehouse.edit',
									'product_id': data['product_id'],
									'warehouse_id': data['warehouse_id'],
									'count': count
								},
								silent: false,
								waiter: true,
								callback: function(success, status, data){
									if(success){
										App.pages[PAGE_NAME].setData(data);
									}
								}
							}).request();
							if(e) e.stop();
						});
						var img = new Element('img',{
							'src':INTERFACE_IMAGES+'/icons/accept_16.png',
						}).setStyles({
							'cursor':'pointer',
							'vertical-align':'middle',
							'margin-left':'3px'
						}).addEvents({
							'click': function(e){
								inpt.fireEvent('change');
							}
						}).inject(cell);
						return '';
					}
				}
			],
			selectType:0
		});

		$('product_save_button').addEvent('click', this.productSaveInfo.bind(this));
		this.objects['form_product_info'] = new jsValidator('product_info_area');
		this.objects['form_product_info'].required('info_product_name').required('info_product_article').required('info_product_vendor').
		required('info_product_measure').ufloat('info_product_weight').ufloat('info_product_size_x').ufloat('info_product_size_y').ufloat('info_product_size_z').
		ufloat('info_product_base_price');
		$('info_product_category_change').addEvent('click', this.productCategoryChange.bind(this));
		$('info_product_pic_preview').addEvent('click', this.productImagePreview.bind(this));
		$('info_product_pic_delete').addEvent('click', this.productImageDelete.bind(this));
		$('product_image_upload_file').addEvent('change', this.productUploadImageFileChange.bind(this));
		$('product_image_upload_button').addEvent('click', this.productImageUpload.bind(this));

		$('product_imglist_upload_file').addEvent('change', this.productImgListUpload.bind(this));


		$('info_product_currency').addEvent('change', this.productBasePriceRubCalculate.bind(this));
		$('info_product_base_price').addEvent('change', this.productBasePriceRubCalculate.bind(this));

		$('info_product_offer').addEvent('change', this.productOfferChange.bind(this));

		$('source_update_force_button').addEvent('click', this.sourceUpdateForce.bind(this));

		$('category_selector_complete_button').addEvent('click', this.selectParentCategoryComplete.bind(this));
		$('category_selector_cancel_button').addEvent('click', this.selectParentCategoryClose.bind(this));

		this.objects['table_bridge'] = new jsTable('bridge_table', {
			'dataBackground1':'#efefef',
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
					name:'name',
					width:'auto',
					caption: 'Наименование',
					sortable:true,
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'name'
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
			],
			selectType:0
		});

		$('bridge_update_button').addEvent('click',this.changeProductBridgeId.bind(this));

		$('bridge_product_add_button').addEvent('click', this.pselectorOpen.bind(this));
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

		this.objects['property_selector'] = new jsPropertiesTree({
			'parent': 'property_selector_tree',
			'onselectnode': this.propertySelectorChange.bind(this),
			'isExpanded': true,
			'showRoot': false
		});

		$('property_selector_complete_button').addEvent('click', this.propertySelectorComplete.bind(this));
		$('property_selector_cancel_button').addEvent('click', this.propertySelectorClose.bind(this));
		$('property_add_button').addEvent('click', this.propertySelectorOpen.bind(this));
		$('property_delete_button').addEvent('click', this.propertyProductDelete.bind(this));
		$('properties_list').addEvent('change', this.propertySelect.bind(this));
		$('property_value_save').addEvent('click', this.propertyValueSave.bind(this));


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


		this.objects['__data'] = data;
		this.wto();

	},//end function


	wto: function(){
		var ready = tinyMCE.get('info_product_content') && tinyMCE.get('info_product_compatible');
		if(!ready){
			window.setTimeout(this.wto.bind(this),100);
		}else{
			this.setData(this.objects['__data']);
			this.propertySelect();
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

		if(typeOf(data['product_info'])=='object'){
			this.objects['product_info'] = data['product_info'];
			this.objects['bridge_products'] = [];
			for(var key in data['product_info']){
				switch(key){
					case 'content':  
						var ed = tinyMCE.get('info_product_content');
						if(ed)ed.setContent(data['product_info'][key]);
					break;
					case 'compatible':  
						var ed = tinyMCE.get('info_product_compatible');
						if(ed)ed.setContent(data['product_info'][key]);
					break;
					case 'bridge_id':
						var bridge_id = parseInt(data['product_info'][key]);
						$('info_product_bridge_id').setValue(bridge_id);
						if(bridge_id>0){
							this.showBridgeInfo(data['product_info']['bridge_info']);
							$('bridge_info').show();
							$('bridge_none').hide();
						}else{
							$('bridge_info').hide();
							$('bridge_none').show();
						}
					break;
					case 'category_id':
						$('label_product_category_id').setValue(data['product_info'][key]);
					default: if($('info_product_'+key)) $('info_product_'+key).setValue(data['product_info'][key]);
				}
			}
			$('bigblock_title').set('text','Карточка товара: '+data['product_info']['name']);
			$('info_product_base_price_rub').setValue(Math.ceil(this.objects['currencies_assoc'][data['product_info']['currency']] * parseFloat(data['product_info']['base_price']))+'.00');
			//this.objects['product_tabs'].showTab(0);
			this.productOfferChange();
		}

		if(typeOf(data['product_source'])=='object'){
			this.objects['product_source'] = data['product_source'];
			var source_id = parseInt(data['product_source']['source_id']);
			if(isNaN(source_id))source_id=0;
			for(var key in data['product_source']){
				if($('info_source_'+key)) $('info_source_'+key).setValue(data['product_source'][key]);
			}
			if(source_id == 1 || source_id == 2) $('source_update_force_button').show(); else $('source_update_force_button').hide();
			$('product_source_area').show();
		}

		if(typeOf(data['product_warehouses'])=='array'){
			this.objects['table_product_wh'].setData(data['product_warehouses']);
		}


		if(typeOf(data['product_images'])=='array'){
			this.objects['product_images'] = data['product_images'];
			this.buildProductImgList();
		}


		if(data['product_image_link']!=undefined){
			if(typeOf(this.objects['product_info'])=='object'){
				this.objects['product_info']['pic_big'] = data['product_image_link'];
				this.objects['product_info']['pic_small'] = data['product_image_link'];
			}
			$('info_product_pic_big').setValue(data['product_image_link']);
		}


		if(data['category_image_link']!=undefined){
			if(typeOf(this.objects['category_info'])=='object'){
				this.objects['category_info']['pic_small'] = data['category_image_link'];
			}
			$('info_category_pic_small').setValue(data['category_image_link']);
		}

		if(data['product_id']!=undefined){
			this.requestProductInfo(data['product_id']);
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

		if(typeOf(data['product_properties'])=='array'){
			this.objects['product_properties'] = data['product_properties'];
			select_add({
				'list': 'properties_list',
				'key': 'property_id',
				'value': 'path',
				'options': data['product_properties'],
				'default': '0',
				'clear': true
			});
		}


		if(typeOf(data['properties_tree'])=='array'){
			this.objects['properties_tree'] = data['properties_tree'];
		}


		if(data['selected_property_id']!=undefined){
			$('properties_list').setValue(data['selected_property_id']);
			this.propertySelect();
		}

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		return;
		var panel = $('page_product_info');
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

	requestProductInfo: function(product_id){
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'product.info',
				'product_id': product_id
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					$('product_image_upload_form').reset();
					$('product_image_upload_button').hide();
					$('product_card').show();
					$('product_start').hide();
					//$('product_save_button').show();
					App.pages[PAGE_NAME].productTabChange(App.pages[PAGE_NAME].objects['product_tabs'].index);
				}
			}
		}).request();
	},


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
		if(typeOf(this.objects['catalog_selector_selected'])!='object')return;
		if(this.objects['catalog_selector'].options.onselectcomplete) this.objects['catalog_selector'].options.onselectcomplete();
		this.objects['catalog_selector_selected'] = null;
		this.selectParentCategoryClose();
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
	 * Смена вкладки в карточке товара
	 */
	productTabChange: function(index){
		if([1,4,5,6].indexOf(index)>-1){
			$('product_save_button').hide();
		}else{
			$('product_save_button').show();
		}
		if(index == 5) this.propertySelect();
	},//end function


	/*
	 * Изменение каталога товара
	 */
	productCategoryChange: function(){
		if(typeOf(this.objects['product_info'])!='object') return;
		this.objects['catalog_selector'].options['selected_catalog']	= this.objects['product_info']['category_id'];
		this.objects['catalog_selector'].options['hidden_catalog']		= -1;
		this.objects['catalog_selector'].options['showRoot']			= false
		this.objects['catalog_selector_selected'] = null;
		this.objects['catalog_selector'].build(this.objects['categories']);
		this.objects['catalog_selector'].selectNodeById(this.objects['product_info']['category_id'], false);
		this.objects['catalog_selector'].options.onselectcomplete = this.selectParentCategoryCompleteForProductCard.bind(this);
		$('product_card').hide();
		$('category_selector').show();
		$('category_selector_complete_button').hide();
		$('category_selector_show_element').setValue('product_card');
	},//end function



	/*
	 * Родительский каталог был выбран для товара
	 */
	selectParentCategoryCompleteForProductCard: function(){
		$('info_product_category_name').setValue(this.objects['catalog_selector_selected']['name']);
		$('info_product_category_id').setValue(this.objects['catalog_selector_selected']['category_id']);
		$('label_product_category_id').setValue(this.objects['catalog_selector_selected']['category_id']);
		$('category_selector').hide();
		$('product_card').show();
	},//end function


	/*
	 * Сохранение изменений в карточке товара
	 */
	productSaveInfo: function(){
		if(typeOf(this.objects['product_info'])!='object') return;
		if(!this.objects['form_product_info'].validate()){
			var o,field='',msg='',i, errors = this.objects['form_product_info'].errorMessages;
			for(i=0; i<errors.length; i++){
				o = $(errors[i][0]).getParent('div span');
				if(o){
					msg += '<b>'+o.get('text')+'</b> '+errors[i][1]+'<br>';
				}else{
					msg += '<b>'+errors[i][0]+'</b> '+errors[i][1]+'<br>';
				}
			}
			App.message('Ошибки в заполнении формы', msg, 'ERROR');
			return;
		}
		var ed_content = tinyMCE.get('info_product_content');
		var ed_compatible = tinyMCE.get('info_product_compatible');
		var rx_data = {
				'action':'product.edit.info',
				'product_id': $('info_product_product_id').getValue(),
				'enabled': $('info_product_enabled').getValue(),
				'name': $('info_product_name').getValue(),
				'seo': $('info_product_seo').getValue(),
				'article': $('info_product_article').getValue(),
				'vendor': $('info_product_vendor').getValue(),
				'yml': $('info_product_yml_enabled').getValue(),
				'stockgallery': $('info_product_stockgallery').getValue(),
				'offer': $('info_product_offer').getValue(),
				'offer_discount': parseFloat($('info_product_offer_discount').getValue()),
				'measure': $('info_product_measure').getValue(),
				'weight': $('info_product_weight').getValue(),
				'size_x': $('info_product_size_x').getValue(),
				'size_y': $('info_product_size_y').getValue(),
				'size_z': $('info_product_size_z').getValue(),
				'currency': $('info_product_currency').getValue(),
				'base_price': $('info_product_base_price').getValue(),
				'base_price_real': $('info_product_base_price_real').getValue(),
				'base_price_factor': $('info_product_base_price_factor').getValue(),
				'pic_big': $('info_product_pic_big').getValue(),
				'category_id': $('info_product_category_id').getValue(),
				'catalog_category_id': 0,
				'description': $('info_product_description').getValue(),
				'part_nums': $('info_product_part_nums').getValue(),
				'admin_info': $('info_product_admin_info').getValue(),
				'content': ed_content.getContent(),
				'compatible': ed_compatible.getContent()
			};
		if(typeOf(this.objects['product_source'])=='object'){
			rx_data['need_update_price'] = $('info_source_need_update_price').getValue();
			rx_data['need_update_warehouse'] = $('info_source_need_update_warehouse').getValue();
			rx_data['image_checked'] = $('info_source_image_checked').getValue();
			
		}
		new axRequest({
			url : '/admin/ajax/catalog',
			data: rx_data,
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
	 * Просмотр изображения товара
	 */
	productImagePreview: function(link){
		if(typeOf(this.objects['product_info'])!='object') return;
		var path = this.objects['product_info']['pic_big'];
		if(path=='') return;
		jsSlimbox.open(path, 0, {});
	},//end function



	/*
	 * Удаление изображения товара
	 */
	productImageDelete: function(){
		if(typeOf(this.objects['product_info'])!='object') return;
		if(this.objects['product_info']['pic_big']=='') return;
		var product_id = this.objects['product_info']['product_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить файл изображения товара:<br>'+this.objects['product_info']['pic_big'],
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/catalog',
					data:{
						'action':'product.image.delete',
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
			}
		);
	},//end function




	/*
	 * Выбор файла картинки товара загружаемого на сервер
	 */
	productUploadImageFileChange: function(){
		var files = $('product_image_upload_file').files;
		if(typeOf(files)!='collection') return;
		if(files.length > 0){
			$('product_image_upload_button').show();
			$('product_image_upload_button_title').set('text','Загрузить "'+files[0]['name']+'" на сервер');
		}else{
			$('product_image_upload_button').hide();
		}
	},//end function



	/*
	 * Загрузка файла картинки на сервер
	 */
	productImageUpload: function(){
		if(typeOf(this.objects['product_info'])!='object') return;
		var pic_exists = (this.objects['product_info']['pic_big']!='' ? true : false);
		$('product_image_upload_form_product_id').setValue(this.objects['product_info']['product_id']);
		if(pic_exists){
			App.message(
				'Подтвердите действие',
				'Вы действительно хотите загрузить новое изображение товара на сервер?<br>Текущий файл изображения "'+this.objects['product_info']['pic_big']+'" будет удален и заменен на загруженный',
				'CONFIRM',
				this.productImageUploadProcess.bind(this)
			);
		}else{
			this.productImageUploadProcess();
		}
	},//end function
	productImageUploadProcess: function(){
		new axRequest({
			uploaderForm: $('product_image_upload_form'),
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					$('product_image_upload_form').reset();
					$('product_image_upload_button').hide();
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).upload();
	},//end function



	/*
	 * Пересчет базовой цены в рубли при изменении цены товара или валюты товара
	 */
	productBasePriceRubCalculate: function(){
		var currency = $('info_product_currency').getValue();
		var base_price = parseFloat($('info_product_base_price').getValue());
		if(isNaN(base_price))base_price=0;
		var price =  Math.ceil(this.objects['currencies_assoc'][currency] * base_price);
		$('info_product_base_price_rub').setValue(price+'.00');
	},//end function



	/*
	 * Отображает информацию об объединении на экране
	 */
	showBridgeInfo: function(bridge){
		if(typeOf(bridge)!='object')return;
		this.objects['bridge_products'] = bridge['products'];
		$('info_bridge_price').set('text',bridge['price']+'.00');
		$('info_bridge_count').set('text',bridge['count']);
		this.objects['table_bridge'].setData(bridge['products']);
	},//end function


	/*
	 * Удаление товара из объединения
	 */
	deleteProductFromBridge: function(exclude_id){
		if(typeOf(this.objects['product_info'])!='object') return;
		var product_id = this.objects['product_info']['product_id'];
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'product.info.bridge.exclude',
				'exclude_id': exclude_id,
				'product_id': product_id
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].requestProductInfo(product_id);
				}
			}
		}).request();
	},//end function


	/*
	 * Изменение ID объединения у товара
	 */
	changeProductBridgeId: function(){
		if(typeOf(this.objects['product_info'])!='object') return;
		var product_id = this.objects['product_info']['product_id'];
		var bridge_id = parseInt($('info_product_bridge_id').getValue());
		if(isNaN(bridge_id))bridge_id=0;
		new axRequest({
			url : '/admin/ajax/catalog',
			data:{
				'action':'product.info.bridge.change',
				'bridge_id': bridge_id,
				'product_id': product_id
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].requestProductInfo(product_id);
				}
			}
		}).request();
	},//end function



	//Добавление нового товара, открытие окна выбора
	pselectorOpen: function(){
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
		if(typeOf(this.objects['product_info'])!='object') return this.pselectorCancel();
		var product_id = this.objects['product_info']['product_id'];
		var bridge_id = this.objects['product_info']['bridge_id'];
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
			App.message('Внимание!','Вы пытаетесь добавить товар, который уже есть в объединении:<br><br>'+p['article']+'<br>'+p['name'],'WARNING');
		}else{
			new axRequest({
				url : '/admin/ajax/catalog',
				data:{
					'action':'product.info.bridge.change',
					'bridge_id': bridge_id,
					'product_id': p['product_id']
				},
				silent: false,
				waiter: true,
				callback: function(success, status, data){
					if(success){
						App.pages[PAGE_NAME].requestProductInfo(product_id);
						//App.pages[PAGE_NAME].objects['product_tabs'].setIndex(3);
					}
				}
			}).request();
		}
		this.pselectorCancel();
	},//end function


	/*
	 * Построение списка сизбражений товаров
	 */
	buildProductImgList: function(){
		if(typeOf(this.objects['product_info'])!='object') return;
		var product_id = this.objects['product_info']['product_id'];
		$('product_imglist').empty();
		var imgs = this.objects['product_images'];
		var out = '';
		var is_main = false;
		var id, img_file;
		for(var i=0; i< imgs.length; i++){
			is_main = (parseInt(imgs[i]['is_main']) > 0);
			id = 'pimg_'+imgs[i]['image_id'];
			out +='<li'+(is_main ? ' class="main"':'')+'><img src="'+imgs[i]['image_file']+'" id="'+id+'_pic"><div class="tool"><img src="/client/images/icons/'+(is_main ? 'accept_16.png':'icon_check.png')+'" id="'+id+'_main"><img src="/client/images/icons/icon_delete.png" id="'+id+'_delete"></div></li>';
		}
		$('product_imglist').set('html',out);
		for(var i=0; i< imgs.length; i++){
			id = 'pimg_'+imgs[i]['image_id'];
			$(id+'_pic').addEvent('click',function(e){jsSlimbox.open(this.retrieve('image_file'), 0, {}); e.stop(); }).store('image_file',imgs[i]['image_file']);
			$(id+'_delete').addEvent('click',function(e){ App.pages[PAGE_NAME].productImgDelete(this.retrieve('image_id')); e.stop();}).store('image_id',imgs[i]['image_id']);
			//$(id+'_main').addEvent('click',function(e){ App.pages[PAGE_NAME].productImgSetMain(this.retrieve('image_id')); e.stop();}).store('image_id',imgs[i]['image_id']);
		}
	},//end function


	/*
	 * Удалить изображение товара
	 */
	productImgDelete: function(image_id){
		if(typeOf(this.objects['product_info'])!='object') return;
		var product_id = this.objects['product_info']['product_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранный файл изображения товара?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/catalog',
					data:{
						'action':'product.imglist.delete',
						'image_id': image_id,
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
			}
		);
	},//end function


	/*
	 * Сделать изображение товара основным
	 */
	productImgSetMain: function(image_id){
		if(typeOf(this.objects['product_info'])!='object') return;
		var product_id = this.objects['product_info']['product_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите сделать выбранное изображение основным для данного товара?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/catalog',
					data:{
						'action':'product.imglist.main',
						'image_id': image_id,
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
			}
		);
	},//end function


	/*
	 * Выбор файла картинки товара загружаемого на сервер
	 */
	productImgListUpload: function(){
		if(typeOf(this.objects['product_info'])!='object') return;
		var product_id = this.objects['product_info']['product_id'];
		var files = $('product_imglist_upload_file').files;
		if(typeOf(files)!='collection') return;
		if(files.length > 0){
			$('product_imglist_upload_form_product_id').setValue(product_id);
			new axRequest({
				uploaderForm: $('product_imglist_upload_form'),
				silent: false,
				waiter: true,
				callback: function(success, status, data){
					if(success){
						$('product_imglist_upload_form').reset();
						App.pages[PAGE_NAME].setData(data);
					}
				}
			}).upload();
		}
	},//end function







	/*
	 * Выбор каталога
	 */
	propertySelectorOpen: function(){
		if(typeOf(this.objects['product_info'])!='object') return;
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
		if(typeOf(this.objects['product_info'])!='object') return;
		var product_id = this.objects['product_info']['product_id'];
		new axRequest({
			url : '/admin/ajax/property',
			data:{
				'action':'property.product.add',
				'product_id': product_id,
				'property_id': property_id
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
	 * Удаление характеристики из товара
	 */
	propertyProductDelete: function(){
		if($('properties_list').selectedIndex == -1) return;
		if(typeOf(this.objects['product_info'])!='object') return;
		var property_id = $('properties_list').getValue();
		var property_name = select_getText($('properties_list'));
		var product_id = this.objects['product_info']['product_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить характеристику товара:<br><b>'+property_name+'</b>?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/property',
					data:{
						'action':'property.product.delete',
						'property_id': property_id,
						'product_id': product_id
					},
					silent: false,
					waiter: true,
					callback: function(success, status, data){
						if(success){
							App.pages[PAGE_NAME].setData(data);
							App.pages[PAGE_NAME].propertySelect();
						}
					}
				}).request();
			}
		);
	},//end function


	/*
	 * Сокрытие всех областей в values_area
	 */
	hideAllValuesAreas: function(){
		$('property_value_list').hide();
		$('property_value_multilist').hide();
		$('property_value_num').hide();
		$('property_value_bool').hide();
		$('property_value_button').hide();
	},//end function


	/*
	 * Выбор свойства товара из списка свойств
	 */
	propertySelect: function(){
		this.hideAllValuesAreas();
		this.objects['selected_property'] = null;
		if(typeOf(this.objects['product_info'])!='object') return;
		if($('properties_list').selectedIndex == -1) return;
		var property_id = $('properties_list').getValue();
		var property = this.objects['product_properties'].filterRow('property_id',property_id,1);
		if(typeOf(property)!='object') return;
		this.objects['selected_property'] = property;
		switch(property['type']){
			case 'list':
				select_add({
					'list': 'value_list',
					'key': 'value_id',
					'value': 'name',
					'options': [{'value_id':0,'name':'-[Выберите значение]-'}],
					'default': '0',
					'clear': true
				});
				select_add({
					'list': 'value_list',
					'key': 'value_id',
					'value': 'name',
					'options': property['values'],
					'default': property['applied'],
					'clear': false
				});
				$('property_value_list').show();
				$('property_value_button').show();
			break;

			case 'multilist':
				buildChecklist({
					'parent': 'property_value_multilist',
					'options': property['values'],
					'key': 'value_id',
					'value': 'name',
					'selected': property['applied'],
					'clear': true
				});
				$('property_value_multilist').show();
				$('property_value_button').show();
			break;

			case 'num':
				$('value_num').setValue(property['applied']);
				$('property_value_num').show();
				$('property_value_button').show();
			break;

			case 'bool':
				$('value_bool').setValue(property['applied']);
				$('property_value_bool').show();
				$('property_value_button').show();
			break;
		}

	},//end function


	/*
	 * Сохранение значений характеристики товара
	 */
	propertyValueSave: function(){
		if(typeOf(this.objects['product_info'])!='object') return;
		if(typeOf(this.objects['selected_property'])!='object') return;
		var property = this.objects['selected_property'];
		var applied;
		switch(property['type']){
			case 'list':
				applied = $('value_list').getValue();
			break;

			case 'multilist':
				applied = [];
				$('property_value_multilist').getElements('input[type=checkbox]').each(function(el){if(el.checked==true){applied.push(el.value);}});
			break;

			case 'num':
				applied = $('value_num').getValue();
			break;

			case 'bool':
				applied = $('value_bool').getValue();
			break;

			default: return;
		}

		new axRequest({
			url : '/admin/ajax/property',
			data:{
				'action':'property.product.applied',
				'property_id': property['property_id'],
				'product_id': this.objects['product_info']['product_id'],
				'type': property['type'],
				'applied': applied
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].propertySelect();
				}
			}
		}).request();

	},//end function


	/*
	 * Обновление цены товара и остатков от поставщика прямо сейчас
	 */
	sourceUpdateForce: function(){
		if(typeOf(this.objects['product_info'])!='object') return;
		new axRequest({
			url : '/admin/ajax/source',
			data:{
				'action':'source.update.force',
				'product_id': this.objects['product_info']['product_id']
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.Location.setLocation('reload');
				}
			}
		}).request();
		
	},//end function


	/*
	 * Изменение специального предложения для товара
	 */
	productOfferChange: function(){
		var is_offer = parseInt($('info_product_offer').getValue());
		if(is_offer > 0)
			$('info_product_offer_discount_area').show();
		else
			$('info_product_offer_discount_area').hide();
	},//end function


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();