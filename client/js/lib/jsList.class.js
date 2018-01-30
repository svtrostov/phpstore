/*
 * Список
 * Stanislav V. Tretyakov (svtrostov@yandex.ru)
 */

/*----------------------------------------------------------------------
Построение динамических списков jsList
----------------------------------------------------------------------*/

var jsList = new Class({

	Implements: [Options, Events],

	//Настройки
	options: {
		//Путь к иконкам
		iconPath: '/lib/images/jslist/',
		//Стили таблицы - задаются в виде списка свойств
		listStyles:{
			'width':'100%',
			'backgroundColor':'#fff'
		},
		//Стили заголовков таблицы - задаются в виде списка свойств
		headerStyles:{
			'cursor': 'pointer'
		},
		//Стили ячеек данных таблицы - задаются в виде списка свойств
		dataStyles:{
			'vertical-align':'middle'
		},
		listClass: '', //CSS класс, применимый для списка
		headerClass: '', //CSS класс, применимый для заголовков списка
		dataClass: '', //CSS класс, применимый для ячеек данных списка
		sectionClass: '', //CSS класс, применимый для строк типа разделы/группы
		columns: [], //Столбцы
		onclick: null, //Пользовательская функция, вызываемая в момент клика по ячейке данных
		ondblclick: null, //Пользовательская функция, вызываемая в момент двойного клика по ячейке данных
		onmouseover: null, //Пользовательская функция, вызываемая в момент наведения курсора мыши на ячейку данных
		onmouseout: null, //Пользовательская функция, вызываемая в момент покидания курсора мыши ячейки данных
		onsortbegin:null, //Пользовательская функция, вызываемая в момент начала сортировки
		onsortend:null, //Пользовательская функция, вызываемая в момент окончания сортировки
		sortstop: false, //Признак, указывающий на остановку сортировки
		dataBackgroundFunct: null,	//Пользовательская функция вычисления цвета заливки строки, если не задано, то используются значения из dataBackground1 и dataBackground2
		dataBackground1: '#eeeeee', 	//бекграунд четных строк таблицы
		dataBackground2: '#FFFFFF', 	//бекграунд нечетных строк таблицы
		overBgType: 2, //отображение выделения: 0 - не показывать выделение ячейки, 1 - только саму ячейку, 2 - строку, 3 - столбец, 4 - строку и столбец
		overCellBg: '#c2c0cC', //цвет выделения ячейки при наведении курсора мыши
		overColBg: '#e1e1e1', //цвет выделения столбца при наведении курсора мыши
		overRowBg: '#e1e1e1', //цвет выделения строки при наведении курсора мыши
		selectedBg: '#e1e181', //цвет выделения строки при ее выделении
		selectType: 1,		//Тип выделения строк: 0 - не выделять, 1 - выделятьтолько одну строку, 2 - многострочное выделение
		sectionStyles:{},	//Стиль для строк типа разделы/группы
		height: 0,			//Высота списка
		autoHeight: true,	//Автоматический подбор высоты списка
		resizeColumns: false,		//признак возможности изменения размера столбцов
		topPanel:false,		//Признак отображения верхней панели
		headerPanel:true,	//Признак отображения строки заголовков
		footerPanel:false	//Признак отображения строки итогов
	},

	//Опции столбца списка по-умолчанию
	defaultColumn:{
		caption: '',		//Заголовок столбца
		footer: '',			//Итог по столбцу
		headerClass: '',	//CSS класс заголовка
		visible: true,		//Признак отображения столбца
		sortable: true,		//Признак сортировки по столбцу
		width: 100,			//Ширина столбца в области заголовка
		minWidth:50,		//Минимальная ширина
		maxWidth:600,		//Максимальная ширина
		resizeable:true,	//Признак изменения размера столбца
		headerStyle:{		//Стили заголовка столбца
			'cursor': 'pointer'
		},
		footerClass:'',		//CSS класс итога
		footerStyle:{		//Стили итогов столбца
			'cursor': 'default'
		},
		dataStyle:{},		//Стили данных столбца
		dataSource:-1,		//Поле массива данных, для данного столбца
		dataFunction: null,	//Функция обработки данных массива и возвращения данных для вывода в таблице
		extraData: '',		//Вспомогательный параметр, не используемый непосредственно в классе. 
		dataType: 'text',	//Тип данных в столбце: 'text', 'num','html', для данных типа 'num' рассчитывается итоговая сумма в _foot_col_sum и применяется формат dataNumFormat при выводе на экран
		dataNumFormat: {
			decimal: ".",
			group: " ",
			decimals: 2
		},
		_foot_col_sum: 0.00		//Внутреннее значение - сумма столбца для строки итогов
	},

	columns: [],			//Столбцы
	parent:null,			//Родительский элемент
	data:[],				//Массив данных
	list: null,				//Объект таблицы
	listTop: null,			//Объект верхней панели фильтра и кнопок
	filterInput:null,		//Объект поля ввода фильтрованного значения
	controlArea:null,		//Поле кнопок
	columnManager:null,		//Поле настроек отображения столбцов
	listHeader: null,		//Объект TBODY для заголовков
	listHeaderBox: null,	//Объект TBODY для заголовков
	listFooter: null,		//Объект TBODY для итогов
	listFooterBox: null,	//Объект TBODY для итогов
	listData: null,			//Объект TBODY для данных таблицы
	listDataList:null,		//Объект UL списка данных
	headerCells:[],			//Массив объектов заголовков столбцов
	footerCells:[],			//Массив объектов итогов столбцов
	headerVBox:[],			//Массив объектов checkbox для панели настроек отображения столбцов
	dataCells:[],			//Массив объектов данных таблицы
	canSortable:true,		//Признак, разрешающий сортировку по полю
	sorting: false,			//Признак нахождения в режиме сортировки
	filtering:false,		//Признак нахождения в режиме фильтрации
	removing:false,			//Признак нахождения в режиме удаления
	updating:false,			//Признак нахождения в режиме обновления
	selectedCount:0,		//Количество выделенных строк
	isBuilded:false,		//Признак, указывающий, что таблица была построена
	minListWidth: 0,		//Минимальная ширина списка
	visibleColumns: 0,		//Количество отображаемых столбцов
	selected: [],			//Массив выбранных строк
	selectedLast: false,	//Последняя выбранная строка


/*----------------------------------------------------
ФУНКЦИИ
----------------------------------------------------*/


	/*Инициализация*/
	initialize: function(parent, options){
		this.setOptions(options);
		//Родительский элемент
		this.parent = $(parent); //Родительский элемент
		this.parent = this.parent || document.body;
		this.setColumns();
		App.echo(this);
	},



	/*Функция: Задает массив данных*/
	setData: function(arr){
		this.data.empty();
		this.dataCells.empty();
		this.selected.empty();
		this.selectedCount = 0;
		this.data = arr.clone();
		if(this.isBuilded){
			this.dropData();
			this.buildData();
		}else{
			this.build();
		}
		//if(this.options.height > 0 ) 
		this.doResize(true);
		return this;
	},



	/*Функция: Задает массив данных путем добавления строк при совпадении значения с value в столбце col*/
	setDataOf: function(arr, col, value,type){
		if(!type) type = '=';
		this.data.empty();
		this.dataCells.empty();
		this.selected.empty();
		this.selectedCount = 0;
		this.data = arr.filter(function(item, index){
			switch(type){
				case '<>':
				case '!=':
					return (item[col] != value);
				case '>':
					return (item[col] > value);
				case '<':
					return (item[col] < value);
				case '=':
				default:
					return (item[col] == value);
			}
		});
		if(this.isBuilded){
			this.dropData();
			this.buildData();
		}
		//if(this.options.height > 0 ) 
		this.doResize(true);
		return this;
	},




	/*Применяет свойства столбца*/
	setColumn: function(column){
		el = {};
		for (var key in this.defaultColumn) el[key] = this.defaultColumn[key];
		for (var key in column) el[key] = column[key];
		return el;
	},



	/*Применяет свойства столбцов из опций к внутренним свойствам столбцов*/
	setColumns: function(){
		for(var i=0; i<this.options.columns.length;i++){
			this.columns[i] = this.setColumn(this.options.columns[i]);
			if(this.columns[i].visible){
				this.visibleColumns+=1;
				this.minListWidth += this.columns[i].width + 10;
			}
		}
		return this;
	},



	/*Удаление области данных*/
	dropData: function(){
		this.listDataList.getChildren().each(function(child){
			child.destroy();
		});
	},




	/*Построение списка*/
	build: function(){

		if(this.isBuilded) return this;

		//Создание области списка
		this.list = new Element('div').inject(this.parent).addClass('jslist').setStyles(this.options.listStyles);

		//Создание верхней панели
		if(this.options.topPanel){
			this.listTop = new Element('div').inject(this.list).addClass('ltop');
			this.buildTop();
		}

		//Создание области для заголовков списка
		this.listHeader 	= new Element('div').inject(this.list).addClass('lheader');
		this.listHeaderBox	= new Element('div').inject(this.listHeader).addClass('lheaderbox');
		if(this.options.headerPanel == false) this.listHeader.hide();

		//Создание области для данных списка
		this.listData = new Element('div').inject(this.list).addClass('ldata').addEvent('scroll', function(){
			this.datascroll();
		}.bind(this));

		if(this.options.height > 0 ) this.listData.setStyle('height',this.options.height+'px');
		else{
			window.addEvent('resize',
				function(){
					this.doResize(false);
				}.bind(this)
			);
		}

		//Создание списка
		this.listDataList = new Element('table').inject(this.listData).addClass('table').setProperties({
			'border':'0',
			'cellPadding':'0',
			'cellSpacing':'0'
		});

		//Создание области для итогов
		this.listFooter 	= new Element('div').inject(this.list).addClass('lfooter');
		this.listFooterBox	= new Element('div').inject(this.listFooter).addClass('lfooterbox');
		if(this.options.footerPanel == false) this.listFooter.hide();

		//Построение заголовков списка
		this.buildHeaders();

		//Построение итогов
		this.buildFooters();

		//Построение области данных
		this.buildData();

		this.isBuilded = true;

		if(this.options.height > 0 )this.doResize(true);

		return this;

	},//build function



	/*Реагирование на изменение размера*/
	doResize: function(computed){

		var maxHeight = 0;
		for(var iCol = 0; iCol < this.columns.length; iCol++){
			var th = this.headerCells[iCol+1];
			if(th){
				th.setStyle('height', 'auto');
				if(maxHeight < th.getSize().y )maxHeight = th.getSize().y;
			}
		}
		maxHeight = maxHeight - 9;
		for(var iCol = 0; iCol < this.columns.length; iCol++){
			var th = this.headerCells[iCol+1];
			if(th)th.setStyle('height', maxHeight+'px');
		}

		if(this.options.height == 0){
			var list_height = (computed) ? this.list.getComputedStyle('height').toInt() : this.list.getSize().y;
			var header_height = (!this.options.headerPanel) ? 0 : (computed ? this.listHeader.getComputedStyle('height').toInt() : this.listHeader.getSize().y);
			var footer_height = (!this.options.footerPanel) ? 0 : (computed ? this.listFooter.getComputedStyle('height').toInt() : this.listFooter.getSize().y);
			var top_height = (!this.options.topPanel) ? 0 : (computed ? this.listTop.getComputedStyle('height').toInt() : this.listTop.getSize().y);	
			var data_height = list_height - header_height - top_height - footer_height - 1;
			this.listData.setStyle('height', data_height+'px');
		}
		if(this.options.autoHeight == true){
			this.listData.setStyle('height','auto');
		}
		
	},




	/*Построение верхней панели инструментов*/
	buildTop: function(){

		//Построение поля фильтрации
		var filterDIV = new Element('div').inject(this.listTop).setStyles({
			'margin':'2px 10px',
			'line-height':'16px',
			'width':'240px',
			'float':'left'
		});
		var filterTitle = new Element('div').inject(filterDIV).setStyles({
			'float':'left',
			'width':'50px',
			'margin-top':'4px'
		}).set('text','Фильтр:');
		var filterSearch = new Element('div').inject(filterDIV).setStyles({
			'float':'left'
		});
		this.filterInput = new Element('input').inject(filterSearch).addClass('ifilter').addEvents({
			keydown: function(){
				var ev = window.event;
				var el = (ev.target)?ev.target:ev.srcElement;
				if(ev.keyCode==13)this.filter(el.value);
			}.bind(this)
		});
		var img =  new Element('div').inject(filterDIV).setStyles({
			'float':'left',
			'background':'url('+this.options.iconPath+'filter.png) no-repeat 2px 2px',
			'cursor':'pointer',
			'width':'18px',
			'height':'18px'
		}).addEvents({
			click: function(){
				this.filter(this.filterInput.value);
			}.bind(this)
		});

		new Element('div').inject(this.listTop).setStyles({
				'float': 'left',
				'height': '25px',
				'border-left': '1px solid #ccc',
				'border-right': '1px solid #fff',
				'margin': '2px;'
		});

		//Построение поля кнопок
		this.controlArea = new Element('div').inject(this.listTop).setStyles({
			'margin':'2px 2px 2px 270px',
			'line-height':'18px',
			'width':'auto',
			'height':'25px'
		});

		//Кнопка: Выбор столбцов
		var img =  new Element('div').inject(this.controlArea).setStyles({
			'float':'left',
			'background':'url('+this.options.iconPath+'show_hide.png) no-repeat 2px 2px',
			'cursor':'pointer',
			'width':'18px',
			'height':'18px'
		}).addEvents({
			click: function(){
				this.showColumnManager();
				//event.stopPropagation();
				return false;
			}.bind(this)
		});


		//Поле: выбор столбцов
		this.columnManager = new Element('div').addClass('checklistpanel').inject(this.list).hide();
		new Element('div').inject(this.columnManager).set('text','Показать столбцы:');
		this.buildColumnlist(this.columnManager);

		var documentEvent = function(event){
			var parent = event.target;
			while(typeOf(parent)=='element' && parent != document.body){
				if(parent == this) return;
				parent = parent.getParent();
			}
			this.hide();
		}.bind(this.columnManager);
		this.columnManager.getDocument().addEvent('click', documentEvent);
	},


	/*Отображение панели настроек видимости столбцов*/
	showColumnManager: function(){
		var td;
		for(var i=0; i<this.columns.length; ++i){
			this.headerVBox[i].checked = this.headerCells[i+1].jst_show;
		}
		this.columnManager.toggle();
	},



	/*Построение области заголовков*/
	buildHeaders: function(){

		var th 				= null;		//Переменная для объекта заголовка
		var iCol 			= 0;	//Индекс столбца
		var text			= '';	//Временная текстовая строка

		//Данный элемент пока не используется, декларируем как пустой объект
		this.headerCells[0]	= {};


		//Создание заголовков
		for(iCol = 0; iCol < this.columns.length; iCol++){

			//Настройки столбца
			column = this.columns[iCol];

			//Создание элемента заголовка столбца
			th = new Element('div').inject(this.listHeaderBox).addClass('th').setStyle('width',column.width+'px');

			//Дополнительная стилизация
			if(this.options.headerClass) th.addClass(this.options.headerClass);
			if(column.headerClass!='') th.addClass(column.headerClass);
			th.setStyles(column.headerStyle);

			if(column.sortable) th.addClass('sortedOn');
			if(!column.visible)th.setStyle('display','none');

			//Заголовок
			if(typeOf(column.caption)=='function'){
				text = column.caption(this, th, column);
				if(text) th.set('html',text);
			}else
				th.set('html',(column.caption!=''?column.caption:'&nbsp;'));

			if(th.jst_sortable) td.addClass('sortedOn');

			//События
			th.addEvents({
				click: function(e){this.headerclick(e);}.bind(this)
			});

			//Изменениеа размера столбца
			if(column.resizeable){
				th.makeResizable({
					modifiers: {x: 'width', y: false},
					limit: {x: [column.minWidth, column.maxWidth]},
					onComplete: this.dragcomplete.bind(this),
					onStart: this.dragstart.bind(this),
					onCancel:this.dragcancel.bind(this)
				});
			}

			//Внутренние переменные
			th.sortType = 0;			//Тип сортировки ячейки
			th.jst_col = iCol+1;		//Ячейка
			th.jst_sortable = column.sortable;
			th.jst_show = column.visible;

			//Добавление объекта ячейки в массив
			this.headerCells[iCol+1]	= th;

		}//for


		return this;

	},



	/*Построение области данных*/
	buildData: function(){

		var tr 				= null;	//Переменная для объекта TR
		var th 				= null;	//Переменная для объекта TH
		var td 				= null;	//Переменная для объекта TD
		var tddiv			= null;	//Переменная для объекта TD (текст)
		var iCol 			= 0;	//Индекс столбца
		var iRow 			= 0;	//Индекс строки
		var text			= '';	//Временная текстовая строка
		var background		= '';	//Задний фон
		var tof;

		this.listDataList.setStyle('width',this.minListWidth+'px');


		//Создание строк данных таблицы
		for(iRow = 0; iRow < this.data.length; iRow++){

			tr = new Element('tr').inject(this.listDataList).addClass('tr');
			//Внутренние переменные
			tr.jst_row 		= iRow;		//Строка
			tr.jst_indx 	= iRow;		//Строка
			tr.jst_selected = false;	//Признак выделенной строки
			tr.jst_show		= true;		//Признак отображения строки
			tr.jst_data		= this.data[iRow];
			tr.jst_is_section = false;
			tr.jst_background = null;
			this.dataCells[iRow]	= [];
			this.dataCells[iRow][0]	= tr;
			
			tof = typeOf(this.data[iRow]);
			if(tof!='array'&&tof!='object'){
				th = new Element('th',{
					'colspan':this.visibleColumns
				}).inject(tr).addClass('th').set('html',this.data[iRow]).setStyles(this.options.sectionStyles);
				if(this.options.sectionClass) th.addClass(this.options.sectionClass);
				tr.jst_is_section = true;
				this.canSortable = false;
				continue;
			}

			//Заливка строки
			if(typeOf(this.options.dataBackgroundFunct)=='function'){
				background = this.options.dataBackgroundFunct(this, this.data[iRow]);
				tr.jst_background = background;
			}else{
				background = (iRow%2) ? this.options.dataBackground1 : this.options.dataBackground2;
			}

			//Создание столбцов данных в строках таблицы
			for(iCol = 0; iCol < this.columns.length; iCol++){

				//Обнуление счетчика для итогов
				if(iRow == 0) this.columns[iCol]._foot_col_sum = 0.00;

				column = this.columns[iCol];

				//Создание ячейки таблицы для данных
				td = new Element('td').inject(tr).addClass('td').setStyles(column.dataStyle).setStyles(this.options.dataStyles).setStyle('backgroundColor',background);
				tddiv = new Element('div').inject(td).addClass('tddiv');

				//Ширина столбца
				td.setStyle('width',(column.width)+'px');
				tddiv.setStyle('width',(column.width)+'px');

				//Видимость ячейки
				if(!column.visible) td.setStyle('display','none');

				//Внутренние переменные - до вызова dataFunction
				td.jst_col = iCol+1;	//столбец
				td.jst_row = iRow;		//строка

				//Содержимое ячейки
				text = (column.dataSource!=-1) ? this.data[iRow][column.dataSource] : '';
				if(typeOf(column.dataFunction)=='function'){
					text = column.dataFunction(this, tddiv, text, this.data[iRow]);
					if(text!='') tddiv.set('html',(column.dataType=='num' ? Number(String(text).toFloat()).format(column.dataNumFormat) : text) );
				}else{
					tddiv.set('text', (column.dataType=='num' ? Number(String(text).toFloat()).format(column.dataNumFormat) : text) );
				}

				//Если тип данных в столбце - число: суммируем
				if(column.dataType=='num') 
					this.columns[iCol]._foot_col_sum += String(text).toFloat();

				//Обработка событий
				td.addEvents({
					mouseover: function(e){this.mouseover(e);}.bind(this),
					mouseout: function(e){this.mouseout(e);}.bind(this),
					click: function(e){this.mouseclick(e);}.bind(this),
					dblclick: function(e){this.mousedblclick(e);}.bind(this)
				});

				//Внутренние переменные
				td.jst_col = iCol+1;	//столбец
				td.jst_row = iRow;		//строка
				td.jst_indx= iRow;		//относителный индекс строки отображаемый на экране)
				td.jst_data= text;		//данные ячейки
				td.jst_div = tddiv;
				td.jst_background = background;
				
				//Добавление объекта ячейки в массив
				this.dataCells[iRow][iCol+1]	= td;

			}//Создание столбцов данных

		}//Создание строк данных

		//Вывод обновленных данных в строку итогов
		this.calculateFooterData();

		return this;

	},



	/*Построение области итогов*/
	buildFooters: function(){

		var th 				= null;		//Переменная для объекта заголовка
		var iCol 			= 0;	//Индекс столбца
		var text			= '';	//Временная текстовая строка

		//Данный элемент пока не используется, декларируем как пустой объект
		this.footerCells[0]	= {};


		//Создание заголовков
		for(iCol = 0; iCol < this.columns.length; iCol++){

			//Настройки столбца
			column = this.columns[iCol];

			//Создание элемента заголовка столбца
			th = new Element('div').inject(this.listFooterBox).addClass('th').setStyle('width',column.width+'px');

			//Дополнительная стилизация
			if(this.options.footerClass) th.addClass(this.options.headerClass);
			if(column.footerClass!='') th.addClass(column.footerClass);
			th.setStyles(column.footerStyle);

			//Отображение
			if(!column.visible) th.setStyle('display','none');

			//Добавление объекта ячейки в массив
			this.footerCells[iCol+1]	= th;

		}//for

		return this;

	},




	/*Вывод итогов в строку итогов*/
	calculateFooterData: function(){
		var text;

		//Создание заголовков
		for(iCol = 0; iCol < this.columns.length; iCol++){

			//Текущий столбец
			var column = this.columns[iCol];
			var th = this.footerCells[iCol+1];

			//итоговые данные
			if(typeOf(column.footer)=='function'){
				text = column.footer(this, th, column);
				if(text!='')th.set('html',text);
			}else{
				switch(column.footer){
					case '=sum': text = Number(column._foot_col_sum).format(column.dataNumFormat); break;
					default: text = column.footer; break;
				}
				th.set('html',(text!=''?text:'&nbsp;'));
			}

		}

	},//end function







	/*Выбор строк при совпадении значения из столбца COL с одним из заданных критериев */
	selectOf: function(arr, col){

		var tr, td, select;
		if(this.options.selectType==0) return this;

		//Просмотр строк
		for(var i=0; i<this.dataCells.length; ++i){
			tr = this.dataCells[i][0];
			td = this.dataCells[i][col];
			//Выделение строки
			select = ( arr.indexOf(td.jst_data) > -1) ? true : false;
			if(select){
				//Если в настройках указано выделение только одной строки
				if(this.options.selectType==1){
					this.selected.empty();
					this.clearSelected();
				}
				tr.jst_selected = true;
				this.selectedCount++;
				this.selected.push(tr);
				//Заливка строки цветом выделения
				for(var j=0; j<this.columns.length; ++j) this.dataCells[i][j+1].setStyle('backgroundColor',this.options.selectedBg);
				//Завершение работы если в настройках указано выделение одной строки
				if(this.options.selectType==1) return this;
			}
		}
		return this;
	},//end function



	/*Функция возвращает массив значений из столбца COL выбранных строк.
	Если COL - массив, то возвращается массив значений соответствующих столбцов*/
	getSelected: function(col){
		var tr, td;
		var result = [];
		var toArray = typeOf(col)=='array' ? true : false;
		for(var i=0; i<this.selected.length; ++i){
			tr = this.selected[i];
			if(!toArray){
				td = this.dataCells[tr.jst_row][col];
				result.push(td.jst_data);
			}
			else{
				var a = [];
				for(j=0;j<col.length;++j){
					td = this.dataCells[tr.jst_row][col[j]];
					a.push(td.jst_data);
				}
				result.push(a);
			}
		}
		return result;
	},//end function



	/*Прорисовка фона строк*/
	clearSelected: function (){
		var background, tr;
		this.selected.empty();
		this.selectedCount=0;
		for(var i=0; i<this.dataCells.length; ++i){
			tr = this.dataCells[i][0];
			if(tr.jst_is_section == true) continue;
			//Заливка
			if(tr.jst_background==null) background = (tr.jst_indx%2) ? this.options.dataBackground1 : this.options.dataBackground2;
			else background = tr.jst_background;
			//Ячейки
			for(var j=0; j<this.columns.length; ++j){
				this.dataCells[i][j+1].setStyle('backgroundColor', background);
			}
			tr.jst_selected = false;
			//this.selectedCount--;
		}
		return this;
	},//end function


	/*Выбор строк */
	selectAll: function(){

		var tr, td, select;
		if(this.options.selectType==0 || this.options.selectType==1) return this;

		this.selected.empty();
		this.selectedCount=0;
		
		//Просмотр строк
		for(var i=0; i<this.dataCells.length; ++i){
			tr = this.dataCells[i][0];
			if(tr.jst_is_section == true) continue;
			//Выделение строки
			tr.jst_selected = true;
			this.selectedCount++;
			this.selected.push(tr);
			//Заливка строки цветом выделения
			for(var j=0; j<this.columns.length; ++j){
				if(this.dataCells[i][j+1]) this.dataCells[i][j+1].setStyle('backgroundColor',this.options.selectedBg);
			}
		}
		return this;
	},//end function


	/*Обновление ячейки данных в зависимости от совпадения со сначением поля в указанном столбце*/
	updateOf: function(col, key, targetCol, newValue){

		if(this.updating) return this;
		this.updating = true;

		var tr, td, tddiv, update, column, iCol, iRow, iIndx;
		var indx = 0;

		//Просмотр строк
		for(var i=0; i<this.dataCells.length; ++i){
			tr = this.dataCells[i][0];
			td = this.dataCells[i][col];
			//Обновление данных
			update = ( td.jst_data == key) ? true : false;
			if(update){
				column = this.columns[targetCol-1];
				td = this.dataCells[i][targetCol];
				tddiv = td.jst_div;
				//Текущие переменные объекта
				iCol = targetCol;
				iRow = i;
				iIndx= td.jst_indx;
				//Удаление предыдущего содержимого
				tddiv.getChildren().each(function(child){
					child.destroy();
				});
				//Содержимое ячейки
				newValue = (typeOf(column.dataFunction)=='function') ? column.dataFunction(this, td, newValue, this.data[iRow]) : newValue;
				if(newValue!='') tddiv.set('html',newValue);
				//Внутренние переменные
				td.jst_col = iCol;			//столбец
				td.jst_row = iRow;			//строка
				td.jst_indx= iIndx;			//относителный индекс строки отображаемый на экране)
				td.jst_data= newValue;		//данные ячейки
				td.jst_div = tddiv;
			}
		}

		this.updating = false;
		return this;

	},//end function



	/*Удаление записи в зависимости от совпадения со сначением поля в указанном столбце*/
	removeOf: function(col, key){

		if(this.removing) return this;
		this.removing = true;

		var tr, td, background, remove;
		var indx = 0;

		//Просмотр строк таблицы
		for(var i=0; i<this.dataCells.length; ++i){
			tr = this.dataCells[i][0];
			td = this.dataCells[i][col];
			//Удаление совпадений
			remove = ( td.jst_data == key) ? true : false;
			if(remove){
				this.selected.erase(tr);
				this.dataCells.splice(i,1);
				this.data.splice(i,1);
				tr.destroy();
			}
		}

		//Перерасчет строк
		for(var i=0; i<this.dataCells.length; ++i){
			tr = this.dataCells[i][0];
			if(tr.jst_show){
				tr.jst_indx = indx;
				indx++;
			}
			tr.jst_row 	= i;		//Строка
			if(tr.jst_selected && this.options.selectType>0) background = this.options.selectedBg;
			else{
				//Заливка
				if(tr.jst_background==null) background = (tr.jst_indx%2) ? this.options.dataBackground1 : this.options.dataBackground2;
				else background = tr.jst_background;
			}

			for(var j=0; j<this.columns.length; ++j){
				this.dataCells[i][j+1].style['backgroundColor'] = background;
				this.dataCells[i][j+1].jst_row = i;
			}


		}//for

		this.removing = false;
		return this;

	},//end function




	/*Фильтр*/
	filter: function(key, col){

		var str;

		if(this.filtering) return this;
		this.filtering = true;

		var tr, td, background, showing;
		var indx = 0;

		//Если столбец не задан - поиск по всем столбцам
		if(!isset(col)) col = -1;
		key = String(key).toLowerCase();

		//Просмотр строк таблицы
		for(var i=0; i<this.dataCells.length; ++i){

			tr = this.dataCells[i][0];

			//сброс фильтра
			if(key==''){
				showing = true;
			}else{
				//Поиск значения
				if(col != -1){
					td = this.dataCells[i][col];
					showing = ( td.jst_data.toLowerCase().indexOf(key) > -1) ? true : false;
				}else{
					showing = false;
					for(var j=0; j<this.columns.length; ++j){
						td = this.dataCells[i][j+1];
						str = String(td.jst_data).toLowerCase();
						if( str.indexOf(key) > -1){
							showing = true;
							break;
						}
					}
				}
			}

			//Определение видимости или невидимости
			if(showing){
				tr.jst_show = true;
				tr.jst_indx = indx;
				tr.style['display'] = '';
				indx++;
			}else{
				tr.jst_show = false;
				tr.style['display']='none';
			}

			if(tr.jst_selected && this.options.selectType>0) background = this.options.selectedBg;
			else{
				if(tr.jst_background==null) background = (tr.jst_indx%2) ? this.options.dataBackground1 : this.options.dataBackground2;
				else background = tr.jst_background;
			}

			for(var j=0; j<this.columns.length; ++j){
				this.dataCells[i][j+1].style['backgroundColor'] = background;
			}

		}//for

		this.filtering = false;
		return this;

	},//end function



	/*Сброс фильтра*/
	filterClear: function(){
		var tr, background;
		var indx = 0;
		//Просмотр строк таблицы
		for(var i=0; i<this.dataCells.length; ++i){
			tr = this.dataCells[i][0];
			//Определение видимости или невидимости
			tr.jst_show = true;
			tr.style['display'] = '';
			tr.jst_indx = indx;
			indx++;
			if(tr.jst_selected && this.options.selectType>0) background = this.options.selectedBg;
			else{
				if(tr.jst_background==null) background = (tr.jst_indx%2) ? this.options.dataBackground1 : this.options.dataBackground2;
				else background = tr.jst_background;
			}
			for(var j=0; j<this.columns.length; ++j){
				this.dataCells[i][j+1].style['backgroundColor'] = background;
			}
		}//for
		return this;
	},//end function



	/*сортировка*/
	sort: function(el, col){
		//Если уже идет процесс сортировки - выход
		if(this.sorting || !this.canSortable) return this;

		//Если елемент не задан
		if(!el) el = this.headerCells[col];

		//Сортировка запрещена
		if(el.jst_sortable==false) return this;

		var header = this.listHeaderBox.getElements('DIV');
		header.each(function(e,i){
			if(e.jst_col != col){
				e.removeClass('sortedASC');
				e.removeClass('sortedDESC');
			}
		});

		//Определение направления сортировки
		if(el.hasClass('sortedASC')){
			el.removeClass('sortedASC');
			el.addClass('sortedDESC');
			el.sortType = 'DESC';
		}else if(el.hasClass('sortedDESC')){
			el.removeClass('sortedDESC');
			el.addClass('sortedASC');
			el.sortType = 'ASC';
		}else{
			el.addClass('sortedASC');
			el.sortType = 'ASC';
		}

		if(typeOf(this.options.onsortbegin)=='function') this.options.onsortbegin(this, col, el.sortType);
		if(this.options.sortstop) return this;

		this.sorting = true;

		//Сортировка данных
		this.sortData(col, el.sortType);

		this.sorting = false;

		if(typeOf(this.options.onsortend)=='function') this.options.onsortend(this, col, el.sortType);

		return this;
	},//end function


	/*сортировка массива данных по столбцу*/
	sortData:function(col, sortType){

		var indx = 0;
		var background, tr;


		//сортировка массива
		if(sortType=='DESC')
			this.dataCells.sort(function(a,b){
					if(a[col].jst_data > b[col].jst_data) return -1;
					if(a[col].jst_data < b[col].jst_data) return 1;
				return 0;
			});
		else
			this.dataCells.sort(function(a,b){
					if(a[col].jst_data > b[col].jst_data) return 1;
					if(a[col].jst_data < b[col].jst_data) return -1;
				return 0;
			});

		//DOM: обработка отсортированного массива
		for(var i=0; i<this.dataCells.length; ++i){
			tr = this.dataCells[i][0];

			//Непосредственная сортировка DOM элементов
			this.listDataList.appendChild(tr);

			//Внутренние переменные строки
			tr.jst_row = i;
			if(tr.jst_show){
				tr.jst_indx = indx;
				indx++;
			}

			//Заливка
			if(tr.jst_selected && this.options.selectType>0) background = this.options.selectedBg;
			else{
				if(tr.jst_background==null) background = (tr.jst_indx%2) ? this.options.dataBackground1 : this.options.dataBackground2;
				else background = tr.jst_background;
			}

			//Ячейки
			for(var j=0; j<this.columns.length; ++j){
				this.dataCells[i][j+1].jst_row = i;
				this.dataCells[i][j+1].style['backgroundColor'] = background;
			}

		}//DOM

		return this;
	},//end function




	/*клик по заголовку столбца*/
	headerclick: function(ev){
		if(!ev) var ev = window.event;
		var el = (ev.target)?ev.target:ev.srcElement;
		el = el.getParentByTag('DIV');
		var col = el.jst_col;
		//Сортировка
		this.sort(el, col);
	},//end function



	/*Событие: клик курсором по ячейке*/
	mouseclick: function(ev){
		if(!ev) var ev = window.event;
		var el = (ev.target)?ev.target:ev.srcElement;
		el = el.getParentByTag('TD');
		var col = el.jst_col;
		var row = el.jst_row;
		//Выделение строки
		if(this.options.selectType>0){
			var tr = this.dataCells[row][0];
			if(tr.jst_selected){
				this.selectedCount--;
				this.selected.erase(tr);
			}else{
				if(this.options.selectType==1){
					this.selected.empty();
					this.clearSelected();
				}
				this.selectedCount++;
				this.selected.push(tr);
			}
			tr.jst_selected = !tr.jst_selected;
		}

		//Вызов пользовательской функции 
		if(this.options.onclick) this.options.onclick(ev, this, el);
	},//end function



	/*Событие: двойной лик курсором по ячейке*/
	mousedblclick: function(ev){
		if(!ev) var ev = window.event;
		var el = (ev.target)?ev.target:ev.srcElement;
		el = el.getParentByTag('TD');
		var col = el.jst_col;
		var row = el.jst_row;
		//Вызов пользовательской функции 
		if(this.options.ondblclick) this.options.ondblclick(ev, this, el);
	},//end function



	/*Событие: наведение курсора на ячейку*/
	mouseover: function(ev){
		if(!ev) var ev = window.event;
		var el = (ev.target)?ev.target:ev.srcElement;
		el = el.getParentByTag('TD');
		var col = el.jst_col;
		var row = el.jst_row;

		/*document.title = (col+' : '+el.jst_col+' = '+el.jst_data+' = '+this.dataCells[row][col].jst_data);*/

		//выделять строку
		if(this.options.overBgType==2 || this.options.overBgType==4){
			for(var j=0; j<this.columns.length; ++j) this.dataCells[row][j+1].setStyle('backgroundColor',this.options.overRowBg);
		}
		//выделять столбец
		if(this.options.overBgType==3 || this.options.overBgType==4){
			for(var i=0; i<this.dataCells.length; ++i) this.dataCells[i][col].setStyle('backgroundColor',this.options.overColBg);
		}
		//выделять ячейку
		if(this.options.overBgType!=0) el.setStyle('backgroundColor',this.options.overCellBg);
		
		//Вызов пользовательской функции 
		if(this.options.onmouseover) this.options.onmouseover(ev, this, el);
	},//end function


	/*Событие: выход курсора из ячейки*/
	mouseout: function(ev){
		if(!ev) var ev = window.event;
		var el = (ev.target)?ev.target:ev.srcElement;
		el = el.getParentByTag('TD');
		var col = el.jst_col;
		var row = el.jst_row;
		var td, bg2;
		var tr = this.dataCells[row][0];
		var indx = tr.jst_indx;
		var background;
		if(tr.jst_selected && this.options.selectType>0) 
			background = this.options.selectedBg;
		else{
			if(tr.jst_background==null) background = (indx%2) ? this.options.dataBackground1 : this.options.dataBackground2;
			else background = tr.jst_background;
		}

		//выделять строку
		if(this.options.overBgType==2 || this.options.overBgType==4){
			for(var j=0; j<this.columns.length; ++j) this.dataCells[row][j+1].setStyle('backgroundColor',background);
		}
		//выделять столбец
		if(this.options.overBgType==3 || this.options.overBgType==4){
			for(var i=0; i<this.dataCells.length; ++i){
				if(this.dataCells[i][0].jst_row != row){
					td = this.dataCells[i][col];
					if(td.jst_background==null) background = (td.jst_indx%2) ? this.options.dataBackground1 : this.options.dataBackground2;
					else background = td.jst_background;		
					td.setStyle('backgroundColor',bg2);
				}
			}
		}
		//выделять ячейку
		if(this.options.overBgType != 0) el.setStyle('backgroundColor', background);

		//Вызов пользовательской функции 
		if(this.options.onmouseout) this.options.onmouseout(ev, this, el);
	},//end function



	/*Событие: прокрутка данных*/
	datascroll: function(){
		var xs = this.listData.getScroll().x;
		this.listHeaderBox.setStyle('left', -xs);
		this.listFooterBox.setStyle('left', -xs);
	},//end function



	/*Изменение размера столбца: старт*/
	dragstart: function(el, ev){
		//
	},//end function



	/*Изменение размера столбца: отмена*/
	dragcancel: function(){
		//
	},//end function


	/*Изменение размера столбца: завершено*/
	dragcomplete: function(el, ev){

		var td;
		var iCol = el.jst_col;
		var width = 0;

		this.minListWidth = this.calcWidth();
		this.listDataList.setStyle('width',this.minListWidth+'px');

		//Установка размера столбца для строк данных
		width = el.getStyle('width');
		for(var i=0; i<this.dataCells.length; ++i){
			td = this.dataCells[i][iCol];
			if(typeOf(td) == 'element') td.setStyle('width',width).jst_div.setStyle('width',width);
		}
		this.columns[iCol-1].width = width.toInt();
		this.footerCells[iCol].setStyle('width',width);

		window.fireEvent('resize');
	},//end function



	/*Вычисление ширины строки данных*/
	calcWidth: function(){
		var width = 0;
		//Рассчет ширины компонента
		for(var i=1; i < this.headerCells.length; ++i){
			if(this.headerCells[i].jst_show) width += this.headerCells[i].getSize().x;
		}
		return width;
	},//end function



	/*Функция построения списка столбцов*/
	buildColumnlist: function(parent){

		var li, label, checkbox, column;

		//Создание элемента Чеклиста
		var checklist = new Element('ul').inject(parent).addClass('checklist').addClass('cl1');

		//Построение списка
		for(var i=0; i<this.columns.length; ++i){

			column = this.columns[i];

			//Элемент списка
			li = new Element('li').inject(checklist);
			if(i%2==0)li.addClass('alt');

			//Подпись
			label = new Element('div').inject(li).setStyles({
				'float':'left',
				'width':'30px'
			});

			//checkbox
			checkbox = new Element('input',{
				'type': 'checkbox',
				'value': i
			}).inject(label).addEvents({
				change:function(el){
					if(typeOf(el)=='domevent'){
						el = (el.target)?el.target:el.srcElement;
					}
					var iCol = (el.value).toInt() + 1;
					this.setColumnVisible(iCol, el.checked );
				}.bind(this)
			});

			this.headerVBox[i] = checkbox;

			label = new Element('div',{
				'text': (typeOf(column.caption)=='string') ? column.caption : 'Столбец '+(i+1)
			}).inject(li).setStyles({
				'margin-left':'30px'
			}).addEvents({
				click: function(){
					this.checked = !this.checked;
					this.fireEvent('change',this);
				}.bind(checkbox)
			});


		}//for

		return checklist;
	},//end function


	/*Изменение статуса отображения столбца*/
	setColumnVisible: function(col, isVisible){
		this.headerCells[col].jst_show = isVisible;
		this.headerCells[col].style['display'] = (isVisible ? '' : 'none');
		this.footerCells[col].style['display'] = (isVisible ? '' : 'none');
		for(var i=0; i<this.dataCells.length; ++i){
			this.dataCells[i][col].style['display'] = (isVisible ? '' : 'none');
		}
		this.listDataList.setStyle('width',this.calcWidth()+'px');
	}//end function



});//end class