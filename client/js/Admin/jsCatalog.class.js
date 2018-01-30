/*----------------------------------------------------------------------
Дерево
----------------------------------------------------------------------*/
var jsCatalog = new Class({

	Implements: [Options, Events],

	/*Настройки*/
	options: {
		iconPath: INTERFACE_IMAGES+'/tree/',//Путь к иконкам
		isExpanded: false, //Дерево развернуто
		parent: null,	// Родительский элемент
		treeclass: 'treedesign',
		menu_id: 2,
		nodes: [],	//Массив объектов дерева в JSON формате
		onselectnode: null,
		onselectcomplete: null,
		selected_catalog: null,
		hidden_catalog: null,
		showRoot: false		//Признак, указывающий, что надо показывать корень каталога
	},

	/*Переменные*/
	created: false,			//Признак создания области панелей
	parent: null,			//Родительский элемент области панелей
	treeRoot: null,			//Root UL компонент дерева
	selectedNode: null,		//Выбранная нода
	list: {},

	/*Елемент дерева по умолчанию*/
	defTreeNode:{
		'category_id': -1,
		'enabled': false,
		'parent_enabled': false,
		'parent_id': 0,
		'name': '',
		'childs': [],
		'selected': false,
		'collapsed':true
	},

	/*Инициализация*/
	initialize: function(options){
		this.setOptions(options);
		this.build();
	},//end function


	/*Очищает дерево*/
	clear: function(){
		this.selectedNode = null;
		if(typeOf(this.treeRoot)=='element')this.treeRoot.destroy();
		this.list = {};
	},


	/*Создание дерева*/
	build: function(nodes){

		this.clear();

		var tree_nodes = nodes || this.options.nodes;

		//Проверка массива нод
		if(typeOf(tree_nodes) != 'array') return this;
		if(tree_nodes.length == 0) return this;

		//Родитель
		this.parent = $(this.options.parent);
		if (typeOf(this.parent) != 'element') return this;
		this.parent.empty();

		var item_id, parent_id;

		if(this.options['showRoot']){
			tree_nodes = [{
				'category_id': 0,
				'records': '0',
				'enabled': true,
				'parent_enabled': true,
				'parent_id': 0,
				'name': '[Корень]',
				'childs': nodes.clone(),
				'selected': false,
				'collapsed':false
			}];
		}

		/*Построение дерева*/
		this.treeRoot = this.buildNodes(this.parent, tree_nodes, 0, []);

		return this;
	},//end function



	/*Построение элемента дерева*/
	buildNodes: function(parent, levelNodes, level, aLastNodes){

		var ul = new Element('ul').inject(parent);
		if(level == 0) ul.addClass(this.options['treeclass']);
		var isFolder, isFirst, isLast, img, src, span, child, arrLast, a, node, nodeCollapsed, node_name, color;

		//Сортировка элементов
		levelNodes.sort(function(a,b){
			var node_a = levelNodes[a];
			var node_b = levelNodes[b];
			if(typeOf(node_a)!='object'||typeOf(node_b)!='object') return 0;
			if(node_a['name']>node_b['name']) return 1;
			return -1;
		});

		//Массив признаков последних родительских нод на их уровнях
		arrLast = aLastNodes.clone();
		arrLast.push(false);

		var ef = function(e){
			var node = this.retrieve('node');
			if(this.hasClass('collapsed')){
				this.removeClass('collapsed').addClass('expanded');
				node.imgMP.setProperty('src', node.minus);
				node.imgFolder.setProperty('src', node.open);
				node.childUL.show();
			}else{
				this.removeClass('expanded').addClass('collapsed');
				node.imgMP.setProperty('src', node.plus);
				node.imgFolder.setProperty('src', node.closed);
				node.childUL.hide();
			}
			if(e)e.stop();
		}

		//Просмотр массива нод текущего уровня
		for(var i=0; i<levelNodes.length; ++i){

			node = levelNodes[i];
			if(typeOf(node)!='object') continue;

			this.list[node['category_id']]=node['name'];

			if(this.options['hidden_catalog'] && node['category_id']==this.options['hidden_catalog']) continue;

			node_name = node['name']+' ('+node['records']+')';
			if(!node['enabled']) color='#FF3333';
			else if(!node['parent_enabled']) color='#777777';
			else color='#000000';

			//Создание элемента
			li = new Element('li').inject(ul);

			//Этот элемент имеет дочерние элементы
			isFolder = (typeOf(node['childs'])=='array'&&node['childs'].length>0);
			isFirst = (i == 0) ? true : false;
			isLast = (i == levelNodes.length-1) ? true : false;
			arrLast[level] = isLast;

			for(var j=0;j<level;++j) new Element('img', {'src': this.options.iconPath + ( arrLast[j]==true ? 'empty.gif':'I.gif'),'width': 18,'height': 18}).inject(li);

			//Это обычный элемент
			if(!isFolder){
				src = (isLast) ? 'L.gif' : 'T.gif';
				new Element('img', {'src': this.options.iconPath + src,'width': 18,'height': 18}).inject(li);
				src = this.options.iconPath + '_doc.gif';
				new Element('img', {'src': src,'width': 18,'height': 18}).inject(li);
				span = new Element('span',{
					'text': node_name
				}).inject(li).setStyle('color',color);
			}
			//Это Раздел
			else{
				nodeCollapsed = (!this.options.isExpanded || node['collapsed']);
				if(isFirst && level == 0 && !isLast){
					node.minus = this.options.iconPath + 'Fminus.gif';
					node.plus =this.options.iconPath + 'Fplus.gif';
				}else{
					if(isLast){
						node.minus = this.options.iconPath + 'Lminus.gif';
						node.plus =this.options.iconPath + 'Lplus.gif';
					}else{
						node.minus = this.options.iconPath + 'Tminus.gif';
						node.plus =this.options.iconPath + 'Tplus.gif';
					}
				}
				node.open  = this.options.iconPath +'_open.gif';
				node.closed = this.options.iconPath +'_closed.gif';
				node.imgMP 		= new Element('img', {'src': (!nodeCollapsed ?  node.minus : node.plus ),'width': 18,'height': 18}).inject(li);
				node.imgFolder 	= new Element('img', {'src': (!nodeCollapsed ?  node.open : node.closed ),'width': 18,'height': 18}).inject(li);
				span = new Element('span').inject(li).set('text', node_name).setStyle('cursor','pointer').setStyle('color',color);
				node.imgMP.addEvent('click', ef.bind(span));
				node.imgFolder.addEvent('click', ef.bind(span));
				node.childUL = this.buildNodes(li, node['childs'], level+1, arrLast);
				node.span = span;
				if(nodeCollapsed){
					node.childUL.hide();
					span.addClass('collapsed');
				}else{
					span.addClass('expanded');
				}
			}//Раздел

			if(this.options['selected_catalog'] && node['category_id']==this.options['selected_catalog']){
					this.selectedNode = span;
					span.addClass('selected');
					if(this.options.onselectnode) this.options.onselectnode(node);
			}

			span.store('node', node);


			span.addEvent('click',this.nodeclick.bind(this));

		}//for

		return ul;
	},//end function



	/*Клик по ноде*/
	nodeclick: function(e){
		e.stop();
		if(this.selectedNode) this.selectedNode.removeClass('selected');
		this.selectedNode = null;
		e.target = $(e.target);
		if(!e.target) return;
		this.selectedNode = e.target;
		e.target.addClass('selected');
		var data = e.target.retrieve('node');
		if(typeOf(data)=='object'){
			if(this.options.onselectnode) this.options.onselectnode(data);
		}
	},


	/*Выбор ноды по идентификатору пункта меню*/
	selectNodeById: function(item_id, fire_event){
		if(!this.treeRoot) return;
		var data;
		var nodes = this.treeRoot.getChildren('li span');
		for(var i=0; i<nodes.length;i++){
			data = nodes[i].retrieve('node');
			if(typeOf(data)=='object'){
				if(String(data['category_id']) == String(item_id)){
					if(this.selectedNode) this.selectedNode.removeClass('selected');
					this.selectedNode = nodes[i];
					this.selectedNode.addClass('selected');
					var parent = $(nodes[i].parentNode).getParent('li span');
					while(parent){
						var node = parent.retrieve('node');
						if(parent.hasClass('collapsed')){
							parent.removeClass('collapsed').addClass('expanded');
							node.imgMP.setProperty('src', node.minus);
							node.imgFolder.setProperty('src', node.open);
							node.childUL.show();
						}
						parent = $(parent.parentNode).getParent('li span');
					}

					if(fire_event){
						if(this.options.onselectnode) this.options.onselectnode(data);
					}
					return this;
				}
			}
		}
		return this;
	}


});//end class
