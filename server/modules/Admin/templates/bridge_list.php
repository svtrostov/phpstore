<div class="page_admin_bridge_list" id="page_admin_bridge_list">


	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<h3 id="bigblock_title">Список объединений товаров</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">
			<!--Content-->


				<div class="tool_area">

					<div class="left">
						<div class="ui-button-light" id="expand_all" style="margin:0px;"><span class="ileft icon_select_all">Раскрыть все</span></div>
						<div class="ui-button-light" id="expand_none" style="margin:0px;"><span class="ileft icon_select_none">Свернуть все</span></div>
					</div>
					<div class="left">
						<div class="ui-button-light" id="button_new_bridge" style="margin:0px;"><span class="ileft icon_add">Выбрать товары и объединить их</span></div>
					</div>
					<div class="right">
						<input type="text" style="width:130px;" id="products_filter">
					</div>

				</div>



				<div class="bridges_area">
					<div id="bridges_table"></div>
					<div id="bridges_none" style="display:none;">
						<h1 class="errorpage_title">Объединения товаров отсутствуют</h1>
					</div>
				</div>



			<!--Content-->
			</div>
		</div>
	</div>



	<div class="bigblock" id="pbridge" style="display:none;">
		<div class="titlebar"><h3>Выберите два или более товаров для объединения</h3></div>
		<div class="contentwrapper">
			<div class="contentarea">


				<div class="ui-button-light" id="button_new_product"><span class="ileft btn_icon_add">Добавить товар</span></div>
				<div id="product_table_area">
					<div id="product_table"></div>
					<div style="text-align: right;">
						<div class="ui-button" id="product_save_button" style="width:165px;margin:5px 0px;"><span>Объединить товары</span></div>
					</div>
				</div>
				<div id="product_none_area" style="display:none;">
					<br><br><br>
					<h1 class="big_title">Выберите товары</h1>
				</div>


			</div>
			<div class="buttonarea">
				<div class="ui-button" id="pbridge_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>



	<div class="bigblock" id="pselector" style="display:none;">
		<input type="hidden" id="pselector_selected_id" value="">
		<div class="titlebar"><h3 id="pselector_title">Выберите товар для объединения</h3></div>
		<div class="contentwrapper">
			<div class="contentarea">


				<div class="filter_area">

					<div class="filter_line">
						<div class="left">
							<input type="text" value="" id="pselector_term" placeholder="Поиск товаров по ID, артикулу, наименованию, описанию..." style="width:400px;"/>
						</div>
						<div class="left">
							<select id="pselector_limit">
								<option value="50">лимит 50 записей</option>
								<option value="100" selected="selected">лимит 100 записей</option>
								<option value="500">лимит 500 записей</option>
							</select>
							<div class="ui-button-light" id="pselector_search" style="margin:0px;"><span class="ileft icon_filter">Искать</span></div>
						</div>
					</div>

					<div class="filter_line">
						<div class="left">
							<div class="input_title">ID товара:</div>
							<input type="text" value="" id="search_product_id" style="width:90px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Артикул содержит:</div>
							<input type="text" value="" id="search_article" style="width:90px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Производитель:</div>
							<input type="text" value="" id="search_vendor" style="width:90px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Описание содержит:</div>
							<input type="text" value="" id="search_description" style="width:90px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Парт-номер содержит:</div>
							<input type="text" value="" id="search_part_nums" style="width:90px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Поставщик:</div>
							<select id="search_source">
								<option value="all">-[Все]-</option>
								<option value="0">DTBOX</option>
								<option value="1">TEKO</option>
								<option value="2">VTT</option>
								<option value="3">CITILINK</option>
							</select>
						</div>
					</div>

				</div>

				<div class="pselector_area">
					<div id="pselector_table" style="display:none;"></div>
					<div id="pselector_none" style="display:none;">
						<h1 class="errorpage_title">Товары не найдены</h1>
					</div>
					<div id="pselector_select" style="display: block;">
						<h1 class="errorpage_title">Поиск по товарам</h1>
					</div>
				</div>


			</div>
			<div class="buttonarea">
				<div class="ui-button" id="pselector_complete_button"><span>Выбрать товар</span></div>
				<div class="ui-button" id="pselector_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


</div>