/*----------------------------------------------------------------------
Построение маршрутов jsRoute
Stanislav V. Tretyakov (svtrostov@yandex.ru)
----------------------------------------------------------------------*/
var jsRoute = new Class({

	Implements: [Options, Events],


	options: {
		connectorLineWeight: 3,
		connectorStartWidth: 15
	},

	//Внутренние переменные
	paintbox: null, //DOM элемент, в котором происходит отрисовка
	blocks: {},	//Массив блоков DIV
	connectors: {}, //Массив коннекторов


	//Запись коннектора по-умолчанию
	defaultConnector: {
		'uid': null,
		'in' : null,
		'out': null,
		'connector': null,
		
	},


	//Инициализация
	initialize: function(element, options){
		this.setOptions(options);
		this.canvas = $(element);
	},//end function


	//Запись переменных в элемент
	storeVars: function(element, options){
		for(var i in options) element.store(i,options[i]);
	},//end function


	checkDrag: function(element){
		return this.options.dragAndDrop && !element.hasClass('nodrag');
	},//end function


	checkDrop: function(element, options){
		return this.options.dragAndDrop && !element.hasClass('nodrop');
	},//end function


	//перетаскивание
	onDrag: function(el, e){
		var uuid = el.retrieve('uuid');
		if(typeOf(this.blocks[uuid])!='object') return;
		this.blockCoord(uuid, true);

		for(var c=0; c<this.blocks[uuid]['connectors']['all'].length;c++){
			if(typeOf(this.connectors[this.blocks[uuid]['connectors']['all'][c]])!='object') continue;
			var connector = this.connectors[this.blocks[uuid]['connectors']['all'][c]];
			var outCoord = this.blockCoord(connector['out']);
			var inCoord = this.blockCoord(connector['in']);

			this.calcSegmentStartPosition(connector['start'], outCoord);
			this.calcSegmentStartPosition(connector['end'], inCoord);

			var con_left = Math.min(outCoord['left']+Math.floor(outCoord['width']/2), inCoord['left']+Math.floor(inCoord['width']/2));
			var con_top = Math.min(outCoord['top']+Math.floor(outCoord['height']/2), inCoord['top']+Math.floor(inCoord['height']/2));
			var con_width = Math.abs((outCoord['right']+Math.floor(outCoord['width']/2)) - (inCoord['right']+Math.floor(inCoord['width']/2)));
			var con_height = Math.abs((outCoord['bottom']+Math.floor(outCoord['height']/2)) - (inCoord['bottom']+Math.floor(inCoord['height']/2)));
			connector['root'].setStyles({
				'left': con_left+'px',
				'top': con_top+'px',
				'width': con_width+'px',
				'height': con_height+'px'
			});
		}
	},//end function


	//брось
	onDrop: function(el){

	},//end function




	//Добавление блока
	addBlock: function(uuid, data){
		var block = new Element('div',{
			'id': 'fcunit-'+uuid,
			'class': 'unit',
			'events':{}
		}).inject(this.canvas);
		block.store('uuid', uuid);
		this.storeVars(block, data);
		block.makeDraggable({
			limit:{x:[0,4000],y:[0,4000]}
			//droppables: this.element.getElements('a')
		}).addEvents({
			onDrag: this.onDrag.bind(this),
			onDrop: this.onDrop.bind(this)
		});
		this.blocks[uuid] = {
			'uuid': uuid,
			'block': block,
			'connectors':{
				'all':[],
				'in':{
					'left'	: [],
					'right'	: [],
					'top'	: [],
					'bottom': []
				},
				'out':{
					'left'	: [],
					'right'	: [],
					'top'	: [],
					'bottom': []
				}
			},
			'coord': null
		};
		return block;
	},//end function



	blockCoord: function(uuid, update){
		if(typeOf(this.blocks[uuid]['coord'])!='object' || update){
			this.blocks[uuid]['coord'] = this.blocks[uuid]['block'].getCoordinates(this.canvas);
		}
		return this.blocks[uuid]['coord'];
	},



	//
	connection: function(data){
		var connector = this.buildConnector(data);
		if(typeOf(connector)!='object') return false;
		App.echo(connector);
		this.blocks[connector['out']]['connectors']['all'].push(connector['uuid']);
		this.blocks[connector['out']]['connectors']['out'][connector['outsrc']].push(connector['uuid']);
		this.blocks[connector['in']]['connectors']['all'].push(connector['uuid']);
		this.blocks[connector['in']]['connectors']['in'][connector['indesc']].push(connector['uuid']);
		this.connectors[connector['uuid']]= connector;
	},//end function



	//
	buildConnector: function(data){
		if(typeOf(data)!='object'||typeOf(this.blocks[data['out']])!='object'||typeOf(this.blocks[data['in']])!='object') return false;
		var connectorUID = data['out']+'-'+data['in'];
		var root = new Element('div',{
			'class': 'connector'
		}).inject(this.canvas);
		var outCoord = this.blockCoord(data['out']);
		var inCoord = this.blockCoord(data['in']);
		var connector = {
			'uuid': connectorUID,
			'out': data['out'],
			'in': data['in'],
			'outsrc': data['outsrc'],
			'indesc': data['indesc'],
			'root': root,
			'start': this.buildConnectorSegmentStart(connectorUID+'-start', data['outsrc']),
			'end': this.buildConnectorSegmentStart(connectorUID+'-end', data['indesc'])
		};
		this.calcSegmentStartPosition(connector['start'], outCoord);
		this.calcSegmentStartPosition(connector['end'], inCoord);
		return connector;
	},//end function



	buildConnectorSegmentStart: function(uuid, orientation){
		var segment = new Element('div',{
			'id': 'fcseg-'+uuid,
			'class': 'connector_line'
		}).inject(this.canvas);
		var pos;
		switch(orientation){
			case 'left': 
				pos = {
					'top': function(coords){return coords['top']+Math.floor(coords['height']/2 - this.options.connectorLineWeight/2);}.bind(this),
					'left': function(coords){return coords['left']-this.options.connectorStartWidth;}.bind(this),
					'width': this.options.connectorStartWidth,
					'height': this.options.connectorLineWeight
				};
			break;
			case 'right':
				pos = {
					'top': function(coords){return coords['top']+Math.floor(coords['height']/2 - this.options.connectorLineWeight/2);}.bind(this),
					'left': function(coords){return coords['right'];}.bind(this),
					'width': this.options.connectorStartWidth,
					'height': this.options.connectorLineWeight
				};
			break;
			case 'top':
				pos = {
					'top': function(coords){return coords['top']-this.options.connectorStartWidth;}.bind(this),
					'left': function(coords){return coords['left']+Math.floor(coords['width']/2 - this.options.connectorLineWeight/2);}.bind(this),
					'width': this.options.connectorLineWeight,
					'height': this.options.connectorStartWidth
				};
			break;
			case 'bottom':
				pos = {
					'top': function(coords){return coords['bottom'];}.bind(this),
					'left': function(coords){return coords['left']+Math.floor(coords['width']/2 - this.options.connectorLineWeight/2);}.bind(this),
					'width': this.options.connectorLineWeight,
					'height': this.options.connectorStartWidth
				};
			break;
		}
		return {
			'segment': segment,
			'pos': pos
		};
	},



	calcSegmentStartPosition: function(segment, coord){
		segment['segment'].setStyles({
			'left': segment['pos']['left'](coord)+'px',
			'top': segment['pos']['top'](coord)+'px',
			'width': segment['pos']['width']+'px',
			'height': segment['pos']['height']+'px'
		});
	},




	empty: null
});//end class



/*----------------------------------------------------------------------
Блок маршрута jsRouteBlock
----------------------------------------------------------------------*/
var jsRouteBlock = new Class({

	//Инициализация
	initialize: function(canvas, options){
		
	}//end function

});//end class