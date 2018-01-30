/*----------------------------------------------------------------------
Построение динамических таблиц
Stanislav V. Tretyakov (svtrostov@yandex.ru)
----------------------------------------------------------------------*/

var jsTable = new Class({

	Implements: [Options, Events],

	//Настройки
	options: {
		class: 'jsTable',			//CSS класс таблицы
		attributes: { 				//Свойства таблицы - задаются в виде списка свойств
			'border':'0',
			'cellPadding':'0',
			'cellSpacing':'0'
		},
		name: 'jst_',
		contextmenu: false,			//Признак использования контекстного меню для выбора столбцов
		columns: [], 				//Массив столбцов
		rowFunction: null,			//Функция обработки строки данных, возвращает объект с настройками для строки
		sectionFunction: null,		//Функция обработки строк секций
		sectionCollapsible: false,	//Признак, разрешающий сворачивать/разворачивать разделы
		dataBackground1: 'transparent',	//бекграунд четных строк таблицы
		dataBackground2: 'transparent',	//бекграунд нечетных строк таблицы
		stopSorting: false,			//Признак остановки процесса сортировки сразу же после генерации события sortbegin
		selectType: 1,				//Тип выделения строк: 0 - не выделять, 1 - выделятьтолько одну строку, 2 - многострочное выделение
		selectColor: '#e1e181',		//цвет выделения строки при ее выделении
		rowHoverType: 0, 			//отображение выделения: 0 - не показывать выделение ячейки, 1 - выделять строку
		rowHoverColor: '#e1e1e1'	//цвет выделения строки при наведении курсора мыши
	},

	//Опции столбца по-умолчанию
	defaultColumn: {
		name: '',			//Имя столбца
		caption: '',		//Заголовок столбца
		class: '',			//CSS класс заголовка
		visible: true,		//Признак отображения столбца
		sortable: false,	//Признак сортировки по столбцу
		resizeable: false,	//Признак изменения размера столбца
		width: null,		//Ширина столбца
		styles:{			//Стили заголовка столбца
			'cursor': 'pointer'
		},
		dataClass:null,		//CSS класс данных столбца
		dataStyle:null,		//Стили данных столбца
		dataSource:-1,		//Поле массива данных, для данного столбца
		dataFunction: null,	//Функция обработки данных массива и возвращения данных для вывода в таблице
		dataType: 'text',	//Тип данных в столбце: 'text', 'num','html','int'
		dataNumFormat: {
			decimal: ".",
			group: " ",
			decimals: 2
		},
	},

	//Внутренние переменные
	columns: [],			//Массив столбцов таблицы
	parent:null,			//Родительский элемент
	data:[],				//Массив данных
	visibleColumns: 0,		//Количество отображаемых столбцов
	isBuilded: false,		//Признак, указывающий что таблица была построена
	enableSortable: true,	//Общий статус сортировки столбцов
	sorting: false,			//Признак, что уже идет процесс сортировки
	filtering: false,		//Признак, что уже идет процесс фильтрации
	selectedCount: 0,		//Количество выбранных строк
	selectedRows: [],		//Массив выбранных строк
	lastSelectedRow: null,	//Последняя выбранная строка

	//Объекты
	oContextMenu: null,	//Объект DIV - выпадающее контекстное меню для заголовков таблицы
	oContextMenuList: null, //Объект DIV - Область, в которой непосредственно отрисовывается список контекстного меню
	oWrapper: null,		//Объект DIV (обертка таблицы), CSS class: .jsTable
	oTable: null,		//Объект TABLE (таблица), CSS class: .jsTable > table
	oTHead: null,		//Объект THEAD (область заголовков столбцов), CSS class: .jsTable > table > thead
	oTHeadTR: null,		//Объект THEAD TR (область заголовков столбцов), CSS class: .jsTable > table > thead > tr
	oTBody: null,		//Объект TBODY (область данных) , CSS class: .jsTable > table > tbody
	oTBodyCount:0,		//Количество тегов TBODY
	oTBodyRows: 0,		//Количество строк в TBODY

	//Массивы объектов
	headerCells:[], //Массив объектов заголовков столбцов, CSS class: .jsTable > table > thead > th
	dataCells:[],	//Массив объектов данных таблицы, CSS class: .jsTable > table > tbody > tr > td или th




	/*------------------------------------------------------------------
	ФУНКЦИИ ИНИЦИАЛИЗАЦИИ И ПОСТРОЕНИЯ ТАБЛИЦЫ
	------------------------------------------------------------------*/

	//Объединяет 2 объекта в один
	mergeObjects: function(slave, master){
		return Object.merge({}, slave, master);
	},




	//Запись переменных в элемент
	storeVars: function(element, options){
		for(var i in options) element.store(i,options[i]);
	},//end function





	//Инициализация
	initialize: function(parent, options){

		//Применение опций
		this.setOptions(options);

		//Родительский элемент
		this.parent = $(parent);
		this.parent = this.parent || document.body;

		//Применение столбцов
		this.setColumns(this.options.columns);

		document.addEvent('click', this.eventDocumentClick.bind(this));

		return this;
	},//end function


	//Уничтожение
	terminate: function(){
		document.removeEvent('click', this.eventDocumentClick.bind(this));
		this.data.empty();
		this.columns.empty();
		this.dataCells.empty();
		this.headerCells.empty();
		this.selectedRows.empty();
		this.lastSelectedRow = null;
		if(this.oWrapper) this.oWrapper.destroy();
		this.oContextMenu = null;
		for(var key in this) this[key] = null;
	},



	//Применение настроек для столбцов таблицы
	setColumns: function(columns){

		var obj;

		if(typeOf(columns)!='array') return this;
		this.columns.empty();
		this.visibleColumns = 0;

		for(var i=0; i<columns.length; i++){
			this.columns[i] = this.mergeObjects(this.defaultColumn, columns[i]);
			if(this.columns[i]['name'] == '') this.columns[i]['name'] = 'column_'+i;
			//App.echo('Local storage read ['+this.options.name+this.columns[i]['name']+'] = ['+App.localStorage.read(this.options.name+this.columns[i]['name'], this.columns[i]['visible'], false)+']');
			var v = App.localStorage.read(this.options.name+this.columns[i]['name'], this.columns[i]['visible'], true);
			if(typeOf(v)=='string'){
				this.columns[i]['visible'] = (v.toLowerCase()=='true' ? true : false);
			}else{
				this.columns[i]['visible'] = v;
			}
			if(this.columns[i]['visible'] == true) this.visibleColumns++;
			if(typeOf(this.columns[i]['class']) == 'array') this.columns[i]['class'] = this.columns[i]['class'].join(' ');
			if(typeOf(this.columns[i]['dataClass']) == 'array') this.columns[i]['dataClass'] = this.columns[i]['dataClass'].join(' ');
		}

		return this;
	},//end function




	//Применение массива данных
	setData: function(data){

		var tof = typeOf(data);

		if(tof!='array'&&tof!='object') return this;
		this.data.empty();
		this.dataCells.empty();
		this.selectedCount = 0;
		this.selectedRows.empty();
		this.lastSelectedRow = null;
		if(tof == 'array'){
			this.data = data.clone();
		}else{
			this.data = [];
			for(var i in data) this.data.push(data[i]);
		}

		//Если таблица уже построена, перестраиваем
		if(this.isBuilded){
			this.dropData();
			this.buildData();
		}else{
			this.build();
		}

		return this;
	},//end function




	//Удаление области данных
	dropData: function(){
		if(!this.isBuilded) return;
		this.oTable.getChildren().each(function(child){
			if(child.get('tag') == 'tbody'){
				child.destroy();
			}
		});
		this.oTBody = null;
		this.oTBodyCount = 0;
		this.oTBodyRows = 0;
		this.enableSortable = true;
	},//end function




	//Построение таблицы
	build: function(){

		//Если тавлица уже построена - выход
		if(this.isBuilded) return this;

		//Создание обертки таблицы
		this.oWrapper = new Element('div',{
			'class': this.options.class
		}).inject(this.parent);

		//Создание элемента таблицы
		this.oTable = new Element('table').inject(this.oWrapper).setProperties(this.options.attributes);

		//Создание элемента THead для заголовков таблицы
		this.oTHead = new Element('thead').inject(this.oTable);

		//Создание элемента THead TR для заголовков таблицы
		this.oTHeadTR = new Element('tr').inject(this.oTHead);

		//Построение области заголовков таблицы
		this.buildHeaders();

		//Построение области данных
		this.buildData();

		this.isBuilded = true;

		return this;
	},//end function




	//Построение области заголовков таблицы
	buildHeaders: function(){

		var th			= null;	//Переменная для объекта TH
		var column		= null;	//Настройки столбца
		var text		= '';	//Временная текстовая строка

		//Данный элемент пока не используется, декларируем как пустой объект
		this.headerCells[0] = null;

		//Создание заголовков
		for(var columnIndex=0; columnIndex<this.columns.length; columnIndex++){

			column = this.columns[columnIndex];
			th = new Element('th').inject(this.oTHeadTR);

			if(column['class']) th.addClass(column['class']);
			if(column['width']) th.setStyle('width',column['width']);

			if(column['sortable']) th.addClass('sortedOn');
			if(!column['visible']) th.hide();
			
			
			text = (typeOf(column['caption'])=='function') ? column['caption'](this, th) : column.caption;
			if(text) th.set('html',text);

			//События
			th.addEvents({
				'click': this.eventHeaderClick.bind(this),
				'contextmenu': this.eventHeaderContextMenu.bind(this)
			});

			//Изменениеа размера столбца/*
			if(column['resizeable']){
				th.makeResizable({
					modifiers	: {x: 'width', y: false},
					limit		: {x: [50, 650]},
					onComplete	: this.eventDragComplete.bind(this),
					onStart		: this.eventDragStart.bind(this),
					onCancel	: this.eventDragCancel.bind(this)
				});
			}

			//Применение стилей
			th.setStyles(column['styles']);

			//Внутренние переменные
			th.store('col', columnIndex+1); //Номер столбца
			th.store('sortType', 0); //Тип сортировки ячейки
			th.store('sortable', column['sortable']); //Признак сортировки
			th.store('visible', column['visible']); //Признак видимости

			//Добавление объекта ячейки в массив
			this.headerCells[columnIndex+1]	= th;

		}//Создание заголовков

		return this;
	},//end function



	//Простроение тега TBody
	buildTBody: function(){
		if(this.oTBody && !this.oTBodyRows) return this.oTBody;

		//Создание элемента TBody для данных таблицы
		this.oTBody = new Element('tbody').inject(this.oTable);
		this.oTBodyRows = 0;
		this.oTBodyCount++;

		//Если несколько TBody, запрет сортировки
		if(this.oTBodyCount > 1){
			this.enableSortable = false;
		}

		return this.oTBody;
	},//end function



	//Вставка в текущий TBody элемента
	injectTBody: function(el){
		if(el)el.inject(this.oTBody);
		this.oTBodyRows++;
		return this.oTBody;
	},//end function



	//Построение области данных таблицы
	buildData: function(){

		var tr 			= null;	//Переменная для объекта TR
		var a			= null;	//Переменная для объекта A
		var th 			= null;	//Переменная для объекта TH
		var td 			= null;	//Переменная для объекта TD
		var indexCol	= 0;	//Индекс столбца
		var indexRow 	= 0;	//Индекс строки
		var text		= '';	//Временная текстовая строка
		var background	= '';	//Задний фон
		var typeOfData	= null;	//Тип переданных данных
		var rowDefaults	= null; //Переменная для временного хранения настроек строки
		var cellDefaults= null; //Переменная для временного хранения настроек ячейки
		var column		= null;	//Настройки столбца

		this.buildTBody();

		//Создание строк данных таблицы
		for(indexRow = 0; indexRow < this.data.length; indexRow++){

			//Элемент
			tr = new Element('tr');
			this.dataCells[indexRow]	= [];
			this.dataCells[indexRow][0]	= tr;

			rowDefaults = {
				'row'		: indexRow, //Строка
				'index'		: indexRow, //Индекс строки
				'selected'	: false,	//Признак выделенной строки
				'visible'	: true,		//Признак отображения строки
				'collapse'	: false,	//Признак закрытой секции
				'filtered'	: false,	//Признак фильтрации данных
				'data'		: this.data[indexRow], //Данные строки
				'is_section': false, //Признак что строка является секцией
				'background': (indexRow%2 ? this.options.dataBackground1 : this.options.dataBackground2), //Заливка
				'bg_recalc'	: true //Разрешить пересчет заливки при сортировки и фильтрации
			};

			//Тип переданных данных
			typeOfData = typeOf(this.data[indexRow]);

			//Если передан не массив или объект, считаем, что это раздел
			if(typeOfData!='array' && typeOfData!='object'){

				th = new Element('th',{
					'colspan': this.visibleColumns
				}).inject(tr);

				//Создание нового TBODY для раздела
				this.buildTBody();

				//Обработка заголовка секции через функцию
				if(typeOf(this.options.sectionFunction)=='function'){
					text = this.options.sectionFunction(this, th, this.oTBody, this.data[indexRow]);
					if(typeOf(text)=='object'){
						rowDefaults = this.mergeObjects(rowDefaults, text);
					}
				}

				th.set('html', String(this.data[indexRow]));
				rowDefaults['is_section'] = true;
				rowDefaults['background'] = null;
				this.storeVars(tr, rowDefaults);
				tr.store('th_section',th);
				this.enableSortable = false;

				if(this.options.sectionCollapsible){
					a = new Element('a',{'href':'#'}).inject(th).addEvents({
						'click': this.eventSectionMouseDoubleClick.bind(this)
					});
					//Обработка событий
					tr.addEvents({
						'dblclick': this.eventSectionMouseDoubleClick.bind(this)
					}).addClass('opened');
				}

				//Вставка строки
				this.injectTBody(tr);

				continue;
			}//Если передан не массив или объект, считаем, что это раздел


			//Вставка строки
			this.injectTBody(tr);

			//Обработка строки через функцию
			if(typeOf(this.options.rowFunction)=='function'){
				text = this.options.rowFunction(this, tr, this.data[indexRow]);
				if(typeOf(text)=='object'){
					rowDefaults = this.mergeObjects(rowDefaults, text);
				}
			}

			//Сохранение внутренних переменных в элементе, включая строку данных
			this.storeVars(tr, rowDefaults);

			//Создание столбцов данных в строках таблицы
			for(indexCol = 0; indexCol < this.columns.length; indexCol++){

				column = this.columns[indexCol];

				//Создание ячейки таблицы для данных
				td = new Element('td',{'class':column.dataClass}).inject(tr);

				//Применение стилей
				if(typeOf(column['dataStyle'])=='object') td.setStyles(column['dataStyle']);

				//Видимость ячейки
				if(!column['visible']) td.hide();

				cellDefaults = {
					'row'		: indexRow, //Строка
					'index'		: indexRow, //Индекс строки
					'col'		: indexCol+1, //Столбец
					'rawdata'	: (column['dataSource'] != -1 ? rowDefaults['data'][column['dataSource']] : ''),
					'data'		: (column['dataSource'] != -1 ? rowDefaults['data'][column['dataSource']] : ''),
					'type'		: column['dataType'],
					'background': rowDefaults['background'], //Заливка
					'bg_recalc'	: true //Разрешить пересчет заливки при сортировки и фильтрации
				}


				//Содержимое ячейки
				if(typeOf(column.dataFunction)=='function'){
					text = column.dataFunction(this, td, cellDefaults['data'], rowDefaults['data']);
					if(typeOf(text)=='object'){
						cellDefaults = this.mergeObjects(cellDefaults, text);
					}else{
						cellDefaults['data'] = text;
					}
				}
				if(cellDefaults['data']){
					switch(column['dataType']){
						case 'html': td.set('html', cellDefaults['data']); break;
						case 'int':
							cellDefaults['data'] = parseInt(cellDefaults['data']);
							td.set('text', cellDefaults['data']);
						break;
						case 'num':
							cellDefaults['data'] = parseFloat(cellDefaults['data']);
							td.set('text', cellDefaults['data'].format(column['dataNumFormat']));
						break;
						default: td.set('text', cellDefaults['data']); break;
					}
				}

				//Обработка событий
				td.addEvents({
					'mouseover'	: this.eventMouseOver.bind(this),
					'mouseout'	: this.eventMouseOut.bind(this),
					'click'		: this.eventMouseClick.bind(this),
					'dblclick'	: this.eventMouseDoubleClick.bind(this)
				});

				//Заливка
				td.setStyle('backgroundColor', cellDefaults['background']);

				//Сохранение внутренних переменных в элементе, включая строку данных
				this.storeVars(td, cellDefaults);

				//Добавление объекта ячейки в массив
				this.dataCells[indexRow][indexCol+1] = td;

			}//Создание столбцов данных

		}//Создание строк данных таблицы

		return this;
	},//end function




	//Пересчет заливки у строки
	updateRowBackground: function(tr){

		var background = null;
		var td, row_selected = false;

		if(tr.retrieve('is_section') == true) return;

		//Если строка выбрана и требуется цвет выделения
		if(tr.retrieve('selected') && this.options.selectType>0){
			row_selected = true;
			background = this.options.selectColor;
		}else{
			background = tr.retrieve('bg_recalc') ? ((tr.retrieve('index')%2) ? this.options.dataBackground1 : this.options.dataBackground2) : tr.retrieve('background');
		}

		//Если заливка не задана - выход
		if(!background) return;
		var row = tr.retrieve('row');

		//Заливка ячеек
		for(var j=0; j<this.columns.length; ++j){
			td = this.dataCells[row][j+1];
			if(typeOf(td) != 'element') continue;
			td.store('row', row);
			if(td.retrieve('bg_recalc') || row_selected) td.setStyle('backgroundColor', background);
		}
	},//end function





	//Сокрытие/отображение секции
	sectionDisplay: function(element, visible){
		visible = (!visible) ? false : true;
		var tbody = element.getParent('tbody');
		if(!tbody) return;
		element.store('visible', visible);
		if(visible){
			element.removeClass('closed').addClass('opened');
		}else{
			element.removeClass('opened').addClass('closed');
		}
		tbody.getChildren('>tr').each(function(child){
			if(child.retrieve('is_section'))return;
			child.store('visible',visible);
			child.store('collapse',!visible);
			if(visible){
				if(!child.retrieve('filtered')) child.show(); 
			}else{
				child.hide();
			}
			//child.setStyle('display',(visible ? 'table-row':'none'));
		});
	},//end function




	//Сокрытие / отображение всех секций
	allSectionsDisplay: function(visible){

		if(!this.isBuilded) return;
		var element;
		var tbodys = this.oTable.getChildren();
		for(var i=0; i<tbodys.length; i++){
			if(tbodys[i].get('tag') == 'tbody'){
				element = tbodys[i].getFirst('>tr');
				if(element && element.retrieve('is_section')) this.sectionDisplay(element, visible);
			}
		}

	},//end function




	//Построение контекстного меню
	buildContextMenu: function(){
		if(!this.oContextMenu){
			this.oContextMenu = new Element('div',{}).setStyles({
				'position':'absolute',
				'left':'5px',
				'top':'5px',
				'width':'auto',
				'height':'auto',
				'padding':'5px',
				'background-color':'#fff',
				'border':'1px solid #ccc',
			}).inject(this.oWrapper).hide();
			this.oContextMenuList = new Element('div',{}).inject(this.oContextMenu);
			var btn = new Element('input',{
				'type':'button',
				'value':'Закрыть меню'
			}).setStyles({
				'width':'100%'
			}).addEvents({
				'click': this.contextMenuHide.bind(this)
			}).inject(this.oContextMenu);
		}

		var selected =[];
		for(var i=0;i<this.columns.length;i++){
			if(this.columns[i]['visible']) selected.push(this.columns[i]['name']);
		}

		var list = buildChecklist({
			'parent': this.oContextMenuList,
			'options': this.columns,
			'key': 'name',
			'value': 'caption',
			'sections': false,
			'selected': selected,
			'clear': true,
			'onclick':function(ch){
				this.columnVisibility(ch.value, ch.checked);
			}.bind(this)
		});

		this.oContextMenu.show();

	},//end function

	/*
	 * Сокрытие контекстного меню
	 */
	contextMenuHide: function(){
		if(this.oContextMenu) this.oContextMenu.hide();
	},//end function



	/*------------------------------------------------------------------
	ФУНКЦИИ ОБРАБОТКИ СОБЫТИЙ
	------------------------------------------------------------------*/

	//Клик по документу
	eventDocumentClick: function(event){
		if(this.oContextMenu) this.contextMenuHide();
	},//end function


	//Клик по заголовку столбца
	eventHeaderClick: function(event){

		if(this.oContextMenu) this.oContextMenu.hide();

		if(!event || (event && typeOf(event.target) != 'element')) return;

		var element = event.target.get('tag') == 'th'
			? event.target
			: event.target.getParent('th');

		if(!element) return;

		Function.stopEvent(event);

		//Сортировка
		this.sortColumn(element.retrieve('col'));

	},//end function



	//Вызов контекстного меню на заголовке столбца
	eventHeaderContextMenu: function(event){
		if(!this.options.contextmenu) return;
		var element = event.target.get('tag') == 'th'
			? event.target
			: event.target.getParent('th');
		if(!element) return;

		//Построение меню 
		this.buildContextMenu();

		event.stop();
	},//end function



	//Клик курсором по ячейке
	eventMouseClick: function(event){

		if(this.oContextMenu) this.oContextMenu.hide();

		if(!event || (event && typeOf(event.target) != 'element')) return;
		if(event.target.get('tag') == 'a' && (event.target.get('target')=='_blank' || event.target.hasClass('mailto'))) return;
		var element = event.target.get('tag') == 'td'
			? event.target
			: event.target.getParent('td');

		if(!element) return;

		var col = element.retrieve('col');
		var row = element.retrieve('row');
		var tr = this.dataCells[row][0];

		//Выделение строки
		if(this.options.selectType > 0 && !tr.retrieve('is_section')){

			var selected = tr.retrieve('selected');
			tr.store('selected',!selected);

			if(selected){
				this.selectedCount--;
				this.selectedRows.erase(tr);
				if(this.options.selectType == 1) this.lastSelectedRow = null;
			}else{
				if(this.options.selectType == 1 && this.lastSelectedRow != null){
					this.lastSelectedRow.store('selected',false);
					this.updateRowBackground(this.lastSelectedRow);
					this.selectedCount--;
					this.selectedRows.erase(this.lastSelectedRow);
				}
				this.selectedCount++;
				this.selectedRows.push(tr);
				this.lastSelectedRow = tr;
			}
			this.updateRowBackground(tr);

		}

		//Вызов пользовательской функции 
		this.fireEvent('click',[this, element]);

		return false;
	},//end function





	//двойной лик курсором по ячейке
	eventMouseDoubleClick: function(event){

		if(!event || (event && typeOf(event.target) != 'element')) return;
		var element = event.target.get('tag') == 'td'
			? event.target
			: event.target.getParent('td');

		if(!element) return;

		//Вызов пользовательской функции
		this.fireEvent('dblclick',[this, element]);
	},//end function




	//двойной лик курсором по ячейке
	eventSectionMouseDoubleClick: function(event){

		if(!event || (event && typeOf(event.target) != 'element')) return;
		var element = event.target.get('tag') == 'tr'
			? event.target
			: event.target.getParent('tr');

		if(!element) return;
		if(!element.retrieve('is_section')) return;
		var visible = !element.retrieve('visible');
		this.sectionDisplay(element, visible);

	},//end function




	//наведение курсора на ячейку
	eventMouseOver: function(event){

		if(this.options.rowHoverType!=1) return;

		if(!event || (event && typeOf(event.target) != 'element')) return;
		var element = event.target.get('tag') == 'td'
			? event.target
			: event.target.getParent('td');

		if(!element) return;

		var col = element.retrieve('col');
		var row = element.retrieve('row');
		var tr = this.dataCells[row][0];

		//выделять строку
		for(var j=0; j<this.columns.length; ++j) this.dataCells[row][j+1].setStyle('backgroundColor',this.options.rowHoverColor);

	},





	//выход курсора из ячейки
	eventMouseOut: function(event){

		if(this.options.rowHoverType!=1) return;

		if(!event || (event && typeOf(event.target) != 'element')) return;
		var element = event.target.get('tag') == 'td'
			? event.target
			: event.target.getParent('td');

		if(!element) return;

		var col = element.retrieve('col');
		var row = element.retrieve('row');
		var tr = this.dataCells[row][0];

		this.updateRowBackground(tr);

	},




	//Изменение размера столбца: старт
	eventDragStart: function(element, event){
		//
	},//end function




	//Изменение размера столбца: отмена
	eventDragCancel: function(){
		//
	},//end function




	//Изменение размера столбца: завершено
	eventDragComplete: function(element, event){
		//
	},//end function





	/*------------------------------------------------------------------
	ФУНКЦИИ РАБОТЫ С ВЫДЕЛЕННЫМИ СТРОКАМИ
	------------------------------------------------------------------*/


	//Снятие выделения со всех строк
	clearSelected: function (){

		var tr;
		for(var i=0; i<this.selectedRows.length; ++i){
			tr = this.selectedRows[i];
			tr.store('selected', false);
			this.updateRowBackground(tr);
		}

		this.selectedRows.empty();
		this.selectedCount = 0;
		this.lastSelectedRow = null;

		return this;
	},//end function




	/*Выбор строк при совпадении значения из столбца COL с одним из заданных критериев */
	selectOf: function(arr, col){

		this.clearSelected();

		var tr, td, select;
		if(this.options.selectType==0) return this;

		//Просмотр строк
		for(var i=0; i<this.dataCells.length; ++i){
			tr = this.dataCells[i][0];
			if(tr.retrieve('is_section')) continue;
			td = this.dataCells[i][col];
			if(typeOf(td)!='element') continue;
			//Выделение строки
			if(arr.contains(String(td.retrieve('data')))){
				tr.store('selected', true);
				this.selectedCount++;
				this.selectedRows.push(tr);
				this.updateRowBackground(tr);
				this.lastSelectedRow = tr;
				//Завершение работы если в настройках указано выделение одной строки
				if(this.options.selectType==1) return this;
			}
		}
		return this;
	},//end function


	/*Выбор всех строк */
	selectAll: function(){

		this.clearSelected();
		var tr, td, select;
		if(this.options.selectType==0) return this;

		//Просмотр строк
		for(var i=0; i<this.dataCells.length; ++i){
			tr = this.dataCells[i][0];
			if(tr.retrieve('is_section')) continue;
			if(!tr.retrieve('visible')) continue;
			tr.store('selected', true);
			this.selectedCount++;
			this.selectedRows.push(tr);
			this.updateRowBackground(tr);
			this.lastSelectedRow = tr;
			//Завершение работы если в настройках указано выделение одной строки
			if(this.options.selectType==1) return this;
		}
		return this;
	},//end function







	/*------------------------------------------------------------------
	ФУНКЦИИ СОРТИРОВКИ ДАННЫХ
	------------------------------------------------------------------*/


	//Сортировка
	sortColumn: function(columnNumber){

		//Если уже идет процесс сортировки или сортировка запрещена - выход
		if(this.sorting || !this.enableSortable) return this;

		var header = this.headerCells[columnNumber];
		if(typeOf(header) != 'element') return this;

		//Сортировка запрещена
		if(!header.retrieve('sortable')) return this;

		for(var i=1; i<this.headerCells.length; i++){
			if(this.headerCells[i].retrieve('col') != columnNumber){
				this.headerCells[i].removeClass('sortedASC');
				this.headerCells[i].removeClass('sortedDESC');
			}
		};

		//Определение направления сортировки
		if(header.hasClass('sortedASC')){
			header.removeClass('sortedASC').addClass('sortedDESC').store('sortType','DESC');
		}else 
		if(header.hasClass('sortedDESC')){
			header.removeClass('sortedDESC').addClass('sortedASC').store('sortType','ASC');
		}else{
			header.addClass('sortedASC').store('sortType','ASC');
		}

		//Генерация события о начале сортировки
		this.fireEvent('sortbegin', [this, columnNumber, header.retrieve('sortType')]);

		if(this.options.stopSorting) return this;
		this.sorting = true;

		//Сортировка данных
		this.sortData(columnNumber, header.retrieve('sortType'));

		this.sorting = false;

		//Генерация события об окончании сортировки
		this.fireEvent('sortend', [this, columnNumber, header.retrieve('sortType')]);

		return this;
	},//end function





	//Сортировка массива данных по столбцу
	sortData:function(columnNumber, sortType){

		var indx = 0;
		var tr;
		var column = this.columns[columnNumber-1];
		var column_type = column['type'];

		//сортировка массива
		if(sortType=='DESC')
			this.dataCells.sort(function(a,b){
				var data_a = a[columnNumber].retrieve('data');
				var data_b = b[columnNumber].retrieve('data');
				switch(column_type){
					case 'int':
					case 'num':
						data_a = parseFloat(data_a);
						data_b = parseFloat(data_b);
					break;
					case 'date':
						data_a = data_a.replace(/(\d+).(\d+).(\d+)/, '$3-$2-$1');
						data_b = data_b.replace(/(\d+).(\d+).(\d+)/, '$3-$2-$1');
					break;
				}
				if(data_a > data_b) return -1;
				if(data_a < data_b) return 1;
				return 0;
			});
		else
			this.dataCells.sort(function(a,b){
				var data_a = a[columnNumber].retrieve('data');
				var data_b = b[columnNumber].retrieve('data');
				switch(column_type){
					case 'int':
					case 'num':
						data_a = parseFloat(data_a);
						data_b = parseFloat(data_b);
					break;
					case 'date':
						data_a = data_a.replace(/(\d+).(\d+).(\d+)/, '$3-$2-$1');
						data_b = data_b.replace(/(\d+).(\d+).(\d+)/, '$3-$2-$1');
					break;
				}
				if(data_a > data_b) return 1;
				if(data_a < data_b) return -1;
				return 0;
			});

		//DOM: обработка отсортированного массива
		for(var i=0; i<this.dataCells.length; ++i){

			tr = this.dataCells[i][0];

			//Непосредственная сортировка DOM элементов
			this.oTBody.appendChild(tr);

			//Внутренние переменные строки
			tr.store('row', i);
			if(tr.retrieve('visible')){
				tr.store('index', indx);
				indx++;
			}

			//Обновление заливки
			this.updateRowBackground(tr);

		}//DOM

		return this;
	},//end function







	/*Фильтр таблицы*/
	filter: function(key, col){

		var str;

		if(this.filtering) return this;
		this.filtering = true;

		var tr, td, background, showing;
		var indx = 0;
		var is_section;

		//Если столбец не задан - поиск по всем столбцам
		if(!col) col = -1;
		key = String(key).toLowerCase();

		//Просмотр строк таблицы
		for(var i=0; i<this.dataCells.length; ++i){

			tr = this.dataCells[i][0];
			is_section = tr.retrieve('is_section');

			if(is_section) continue;

			//сброс фильтра
			if(key==''){
				showing = true;
			}else{
				//Поиск значения
				if(col != -1){
					td = this.dataCells[i][col];
					showing = ( String(td.retrieve('data')).toLowerCase().indexOf(key) > -1) ? true : false;
				}else{
					showing = false;
					for(var j=0; j<this.columns.length; ++j){
						td = this.dataCells[i][j+1];
						str = String(td.retrieve('data')).toLowerCase();
						if( str.indexOf(key) > -1){
							showing = true;
							break;
						}
					}
				}
			}

			//Определение видимости или невидимости
			if(showing){
				if(!tr.retrieve('collapse')){
					tr.store('visible',true);
					tr.show();
				}
				tr.store('filtered', false);
				tr.store('index', indx);
				indx++;
			}else{
				tr.store('filtered',true);
				tr.store('visible',false);
				tr.hide();
			}

			//Обновление заливки
			this.updateRowBackground(tr);

		}//for

		this.filtering = false;
		return this;

	},//end function



	/*Фильтр таблицы по нескольким полям*/
	multiFilter: function(conditions){

		var str;

		if(this.filtering || typeOf(conditions)!='object') return this;
		this.filtering = true;

		var tr, td, background, showing;
		var indx = 0, condcnt=0, value, field;
		var is_section, is_array, fnd, in_rawdata, who_search;

		for(field in conditions) condcnt++;

		//Просмотр строк таблицы
		for(var i=0; i<this.dataCells.length; ++i){

			tr = this.dataCells[i][0];
			is_section = tr.retrieve('is_section');

			if(is_section) continue;

			if(condcnt > 0){

				showing = false;

				//conditions
				for(field in conditions){
					value = conditions[field];
					if(typeOf(value) == 'object'){
						in_rawdata = value['raw'] || false;
						value = value['value'] || '';
					}
					is_array = (typeOf(value) == 'array');
					if(!in_rawdata) who_search = 'data'; else who_search = 'rawdata';

					for(var j=0; j<this.columns.length; ++j){
						if(this.columns[i]['visible'] != true) continue;
						if(field != this.columns[j]['name']) continue;
						td = this.dataCells[i][j+1];
						if(is_array){
							str = String(td.retrieve(who_search)).toLowerCase();
							fnd = false;
							for(var x=0; x<value.length;x++){
								str = String(td.retrieve(who_search)).toLowerCase();
								fnd = (str.indexOf(value[x]) == -1);
								break;
							}
							if(fnd) showing = true;
						}else{
							str = String(td.retrieve(who_search)).toLowerCase();
							if( str.indexOf(value) > -1) showing = true;
						}

						if(showing) break;
					}//for

					if(showing) break;

				}//conditions

			}//condcnt
			else{
				showing = true;
			}


			//Определение видимости или невидимости
			if(showing){
				if(!tr.retrieve('collapse')){
					tr.store('visible',true);
					tr.show();
				}
				tr.store('filtered', false);
				tr.store('index', indx);
				indx++;
			}else{
				tr.store('filtered',true);
				tr.store('visible',false);
				tr.hide();
			}

			//Обновление заливки
			this.updateRowBackground(tr);

		}//for

		this.filtering = false;
		return this;

	},//end function




	/*Возвращает ID столбца в массиве, в зависимости от имени столбца*/
	columnName2Index: function(name){
		if(!name) return -1;
		for(var i=0; i< this.columns.length; i++){
			if(name == this.columns[i]['name']) return i;
		}
		return -1;
	},


	/*Показ или сокрытие столбца*/
	columnVisibility: function(column_id, is_visible){
		if(this.visibleColumns==1 && !is_visible) return;
		if(typeOf(column_id)=='string') column_id = this.columnName2Index(column_id);
		if(column_id == -1) return;
		is_visible = (is_visible ? true : false);
		this.columns[column_id]['visible'] = is_visible;
		this.visibleColumns = 0;
		var th, tr, td, i;
		for(i=0; i< this.columns.length; i++){
			th = this.headerCells[i+1];
			if(this.columns[i]['visible']){
				this.visibleColumns++;
				th.show();
			}else{
				th.hide();
			}
			App.localStorage.write(this.options.name+this.columns[i]['name'], this.columns[i]['visible'], true);
		}

		//Просмотр строк
		for(i=0; i<this.dataCells.length; ++i){
			tr = this.dataCells[i][0];
			if(tr.retrieve('is_section')){
				th = tr.retrieve('th_section');
				th.set('colspan', this.visibleColumns);
				continue;
			}
			td = this.dataCells[i][column_id+1];
			if(is_visible) td.show(); else td.hide();
		}
		return this;
		return this;
	},//end function


	empty: null

});//end class
