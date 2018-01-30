;(function(){
var PAGE_NAME = 'admin_properties';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_properties', 'table_values'],
		'validators': [],
		'table_properties': null,
		'table_values': null,
		'group_info': null,
		'property_info': null,
		'categories':[]
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
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){
		$('bigblock_expander').addEvent('click',this.fullscreen);

		this.objects['splitter'] = set_splitter_h({
			'left'		: $('properties_area'),
			'right'		: $('info_area'),
			'splitter'	: $('splitter'),
			'parent'	: $('splitter').getParent('.contentareafull')
		});

		//Вкладки
		this.objects['tabs'] = new jsTabPanel('tabs_area',{
			'onchange': null
		});

		this.objects['properties_tree'] = new jsPropertiesTree({
			'parent': 'properties_tree',
			'onselectnode': this.selectProperty.bind(this),
			'isExpanded': true
		});

		$('tree_expand_button').addEvent('click',function(){
			App.pages[PAGE_NAME].objects['properties_tree'].expandAll();
		});
		$('tree_collapse_button').addEvent('click',function(){
			App.pages[PAGE_NAME].objects['properties_tree'].collapseAll();
		});


		$('group_add_button').addEvent('click',this.propertyGroupAdd.bind(this));
		$('group_rename_button').addEvent('click',this.propertyGroupEdit.bind(this));
		$('group_delete_button').addEvent('click',this.propertyGroupDelete.bind(this));

		$('property_add_button').addEvent('click',this.propertyAdd.bind(this));
		$('property_save_button').addEvent('click',this.propertyEdit.bind(this));
		$('property_delete_button').addEvent('click',this.propertyDelete.bind(this));

		$('info_type').addEvent('change',this.propertyTypeChange.bind(this));

		$('value_add_button').addEvent('click',this.valueAdd.bind(this));
		$('value_edit_button').addEvent('click',this.valueEdit.bind(this));
		$('value_delete_button').addEvent('click',this.valueDelete.bind(this));
		$('value_up_button').addEvent('click',this.valueUp.bind(this));
		$('value_down_button').addEvent('click',this.valueDown.bind(this));
		$('values_list').addEvent('change',this.valueSelect.bind(this));

		$('value_sort_asc_button').addEvent('click',this.valueSortAsc.bind(this));
		$('value_sort_desc_button').addEvent('click',this.valueSortDesc.bind(this));

		this.objects['catalog_selector'] = new jsCatalog({
			'parent': 'category_selector_tree',
			'onselectnode': this.selectorChange.bind(this),
			'showRoot': false
		});

		$('category_selector_complete_button').addEvent('click', this.selectorComplete.bind(this));
		$('category_selector_cancel_button').addEvent('click', this.selectorClose.bind(this));
		$('category_add_button').addEvent('click', this.selectorOpen.bind(this));
		$('category_delete_button').addEvent('click', this.propertyCategoryDelete.bind(this));

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

		if(typeOf(data['properties_tree'])=='array'){
			this.objects['categories'] = data['categories'];
			this.objects['properties_tree'].build(data['properties_tree']);
			select_add({
				'list': 'info_pgroup_id',
				'key': 'pgroup_id',
				'value': 'name',
				'options': data['properties_tree'],
				'default': (typeOf(this.objects['property_info'])=='object' ? this.objects['property_info']['pgroup_id'] : '0'),
				'clear': true
			});
		}

		if(data['selected_pgroup_id']!=undefined){
			this.objects['properties_tree'].selectNodeById(true, data['selected_pgroup_id'], true);
		}

		if(data['selected_property_id']!=undefined){
			this.objects['properties_tree'].selectNodeById(false, data['selected_property_id'], true);
		}

		if(typeOf(data['property_info'])=='object'){
			this.objects['property_info'] = data['property_info'];
			for(var key in data['property_info']){
				switch(key){
					default:
						if($('info_'+key)){
							if($('info_'+key)) $('info_'+key).setValue(data['property_info'][key]);
						}
				}
			}
			this.propertyTypeChange();
			$('info_property').show();
		}


		if(typeOf(data['property_values'])=='array'){
			select_add({
				'list': 'values_list',
				'key': 'value_id',
				'value': 'name',
				'options': data['property_values'],
				'default': '0',
				'clear': true
			});
			this.valueSelect();
		}

		if(data['selected_value_id']!=undefined){
			$('values_list').setValue(data['selected_value_id']);
			this.valueSelect();
		}


		if(typeOf(data['categories'])=='array'){
			this.objects['categories'] = data['categories'];
		}


		if(typeOf(data['property_categories'])=='array'){
			select_add({
				'list': 'categories_list',
				'key': 'category_id',
				'value': 'path',
				'options': data['property_categories'],
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
		var panel = $('page_properties');
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

	/*
	 * Сокрытие всех информационных областей в info_area
	 */
	hideAllInfoAreas: function(){
		$('info_group').hide();
		$('info_property').hide();
	},//end function


	/*
	 * Выбран элемент в дереве свойств
	 */
	selectProperty: function(node){
		this.hideAllInfoAreas();
		this.objects['group_info'] = null;
		this.objects['property_info'] = null;
		if(typeOf(node)!='object') return;

		//Выбрана группа свойств
		if(node['is_group']){
			this.objects['group_info'] = node;
			$('info_group').show();
			
			
		
		}
		//Выбано свойство
		else{
			this.propertyInfo(node['property_id']);
		}

	},//end function


	/*
	 * Создание группы свойств
	 */
	propertyGroupAdd: function(){
		var name = prompt("Введите название группы", "");
		if(name != null && String(name).length > 0){
			new axRequest({
				url : '/admin/ajax/property',
				data:{
					'action':'property.group.add',
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
	 * Редактирование группы свойств
	 */
	propertyGroupEdit: function(){
		if(typeOf(this.objects['group_info'])!='object') return;
		var name = prompt("Введите название группы", this.objects['group_info']['name']);
		if(name != null && String(name).length > 0){
			new axRequest({
				url : '/admin/ajax/property',
				data:{
					'action':'property.group.edit',
					'name': name,
					'pgroup_id': this.objects['group_info']['pgroup_id']
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
	 * Удаление группы свойств
	 */
	propertyGroupDelete: function(){
		if(typeOf(this.objects['group_info'])!='object') return;
		var pgroup_id = this.objects['group_info']['pgroup_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранную группу характеристик:<br><br><b>'+this.objects['group_info']['name']+'</b><br><br>Примечание: Все характеристики данной группы будут находиться в разделе -[Нет группы]-',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/property',
					data:{
						'action':'property.group.delete',
						'pgroup_id': pgroup_id
					},
					silent: false,
					waiter: true,
					callback: function(success, status, data){
						if(success){
							App.pages[PAGE_NAME].hideAllInfoAreas();
							App.pages[PAGE_NAME].objects['group_info'] = null;
							App.pages[PAGE_NAME].setData(data);
						}
					}
				}).request();
			}
		);
		
	},//end function


	/*
	 * Создание свойства
	 */
	propertyAdd: function(){
		if(typeOf(this.objects['group_info'])!='object') return;
		var pgroup_id = this.objects['group_info']['pgroup_id'];
		var name = prompt("Введите название характеристики", "");
		if(name != null && String(name).length > 0){
			new axRequest({
				url : '/admin/ajax/property',
				data:{
					'action':'property.add',
					'name': name,
					'pgroup_id': pgroup_id
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
	 * Подучение информации о характеристике
	 */
	propertyInfo: function(property_id){
		new axRequest({
			url : '/admin/ajax/property',
			data:{
				'action':'property.info',
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
	 * Удаление свойства
	 */
	propertyDelete: function(){
		if(typeOf(this.objects['property_info'])!='object') return;
		var property_id = this.objects['property_info']['property_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранную характеристику: <b>'+this.objects['property_info']['name']+'</b><br><br><b><font color="red">!!!ВНИМАНИЕ!!!<br>ЭТА ХАРАКТЕРИСТИКА БУДЕТ УДАЛЕНА У ВСЕХ ТОВАРОВ В КАТАЛОГЕ</b></font><br><br>Продолжить?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/property',
					data:{
						'action':'property.delete',
						'property_id': property_id
					},
					silent: false,
					waiter: true,
					callback: function(success, status, data){
						if(success){
							App.pages[PAGE_NAME].hideAllInfoAreas();
							App.pages[PAGE_NAME].objects['property_info'] = null;
							App.pages[PAGE_NAME].setData(data);
						}
					}
				}).request();
			}
		);
	},//end function



	/*
	 * Изменение свойства
	 */
	propertyEdit: function(){
		if(typeOf(this.objects['property_info'])!='object') return;
		var property_id = this.objects['property_info']['property_id'];
		if( $('info_type').getValue() != this.objects['property_info']['type'] ){
			App.message(
				'Подтвердите действие',
				'Вы изменили тип данных для характеристики.<br><br><b><font color="red">!!!ВНИМАНИЕ!!!<br>ВСЕ УСТАНОВЛЕННЫЕ ЗНАЧЕНИЯ ЭТОЙ ХАРАКТЕРИСТИКИ БУДУТ УДАЛЕНЫ У ВСЕХ ТОВАРОВ В КАТАЛОГЕ</b></font><br><br>Продолжить?',
				'CONFIRM',
				function(){
					App.pages[PAGE_NAME].propertyEditProcess();
				}
			);
		}else{
			this.propertyEditProcess();
		}
	},//end function


	/*
	 * Изменение свойства - процесс
	 */
	propertyEditProcess: function(){
		if(typeOf(this.objects['property_info'])!='object') return;
		var property_id = this.objects['property_info']['property_id'];
		new axRequest({
			url : '/admin/ajax/property',
			data:{
				'action'		:'property.edit',
				'property_id'	: property_id,
				'name'			: $('info_name').getValue(),
				'type'			: $('info_type').getValue(),
				'admin_info'	: $('info_admin_info').getValue(),
				'measure'		: $('info_measure').getValue(),
				'pgroup_id'		: $('info_pgroup_id').getValue()
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].hideAllInfoAreas();
					App.pages[PAGE_NAME].objects['property_info'] = null;
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function



	/*
	 * Изменение типа данных
	 */
	propertyTypeChange: function(){
		var type = $('info_type').getValue();
		if(['list','multilist'].contains(type)) $('values_area').show(); else $('values_area').hide();
	},//end function


	/*
	 * Выбор значения
	 */
	valueSelect: function(){
		var index = $('values_list').selectedIndex;
		if(index > -1){
			$('value_edit_button').show();
			$('value_delete_button').show();
		}else{
			$('value_edit_button').hide();
			$('value_delete_button').hide();
		}
	},//end function



	/*
	 * Добавлени значения
	 */
	valueAdd: function(){
		if(typeOf(this.objects['property_info'])!='object') return;
		var property_id = this.objects['property_info']['property_id'];
		var name = prompt("Введите значение", "");
		if(name != null && String(name).length > 0){
			new axRequest({
				url : '/admin/ajax/property',
				data:{
					'action':'property.value.add',
					'value': name,
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
		}
	},//end function



	/*
	 * Изменение значения
	 */
	valueEdit: function(){
		if($('values_list').selectedIndex == -1) return;
		if(typeOf(this.objects['property_info'])!='object') return;
		var value_id = $('values_list').getValue();
		var name = select_getText($('values_list'));
		name = prompt("Введите значение", (!name ? '':name));
		if(name != null && String(name).length > 0){
			new axRequest({
				url : '/admin/ajax/property',
				data:{
					'action':'property.value.edit',
					'value_id': value_id,
					'value': name,
					'property_id': this.objects['property_info']['property_id']
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
	 * Удаление значения
	 */
	valueDelete: function(){
		if($('values_list').selectedIndex == -1) return;
		if(typeOf(this.objects['property_info'])!='object') return;
		var value_id = $('values_list').getValue();
		var property_id = this.objects['property_info']['property_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранное значение: <b>'+select_getText($('values_list'))+'</b><br><br><b>!!!ВНИМАНИЕ!!!<br>ЭТО ЗНАЧЕНИЕ БУДЕТ УДАЛЕНО У ВСЕХ ТОВАРОВ В КАТАЛОГЕ</b><br><br>Продолжить?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/property',
					data:{
						'action':'property.value.delete',
						'property_id': property_id,
						'value_id': value_id
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
	 * Переместить выше
	 */
	valueUp: function(){
		if($('values_list').selectedIndex == -1) return;
		if(typeOf(this.objects['property_info'])!='object') return;
		var value_id = $('values_list').getValue();
		var property_id = this.objects['property_info']['property_id'];
		new axRequest({
			url : '/admin/ajax/property',
			data:{
				'action':'property.value.pos',
				'value_id': value_id,
				'pos': 'up',
				'property_id': property_id
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
	 * Переместить ниже
	 */
	valueDown: function(){
		if($('values_list').selectedIndex == -1) return;
		if(typeOf(this.objects['property_info'])!='object') return;
		var value_id = $('values_list').getValue();
		var property_id = this.objects['property_info']['property_id'];
		new axRequest({
			url : '/admin/ajax/property',
			data:{
				'action':'property.value.pos',
				'value_id': value_id,
				'pos': 'down',
				'property_id': property_id
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
	 * Сортировка А...Я
	 */
	valueSortAsc: function(){
		if(typeOf(this.objects['property_info'])!='object') return;
		var property_id = this.objects['property_info']['property_id'];
		new axRequest({
			url : '/admin/ajax/property',
			data:{
				'action':'property.value.sort',
				'sort': 'asc',
				'property_id': property_id
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
	 * Сортировка Я...А
	 */
	valueSortDesc: function(){
		if(typeOf(this.objects['property_info'])!='object') return;
		var property_id = this.objects['property_info']['property_id'];
		new axRequest({
			url : '/admin/ajax/property',
			data:{
				'action':'property.value.sort',
				'sort': 'desc',
				'property_id': property_id
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
	 * Выбор каталога
	 */
	selectorOpen: function(){
		this.objects['catalog_selector'].options['showRoot'] = false;
		this.objects['catalog_selector_selected'] = null;
		this.objects['catalog_selector'].build(this.objects['categories']);
		$('category_selector_selected_name').setValue('-[Выберите каталог]-');
		$('bigblock_wrapper').hide();
		$('category_selector').show();
		$('category_selector_complete_button').hide();
	},//end function



	/*
	 * Выбран элемент
	 */
	selectorChange: function(data){
		$('category_selector_complete_button').show();
		$('category_selector_selected_name').setValue(data['name']);
		this.objects['catalog_selector_selected'] = data;
	},//end function


	/*
	 * Закрытие выбора каталога
	 */
	selectorClose: function(){
		$('bigblock_wrapper').show();
		$('category_selector').hide();
	},//end function


	/*
	 * Каталог был выбран
	 */
	selectorComplete: function(){
		if(typeOf(this.objects['catalog_selector_selected'])!='object') return;
		var category_id = this.objects['catalog_selector_selected']['category_id'];
		this.objects['catalog_selector_selected'] = null;
		this.selectorClose();
		if(typeOf(this.objects['property_info'])!='object') return;
		var property_id = this.objects['property_info']['property_id'];
		new axRequest({
			url : '/admin/ajax/property',
			data:{
				'action':'property.category.add',
				'category_id': category_id,
				'property_id': property_id
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
		if($('categories_list').selectedIndex == -1) return;
		if(typeOf(this.objects['property_info'])!='object') return;
		var category_id = $('categories_list').getValue();
		var category_name = select_getText($('categories_list'));
		var property_id = this.objects['property_info']['property_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить из фильтров каталога:<br><b>'+category_name+'</b><br>данную характеристику?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/property',
					data:{
						'action':'property.category.delete',
						'property_id': property_id,
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



	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();