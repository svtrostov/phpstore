<div class="page_search_log" id="page_search_log">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" id="bigblock_expander"></a>
			<h3 id="bigblock_title">Журнал поисковых запросов клиентов</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div class="tool_area">
					<div class="left" style="height:31px;">
						<img src="/client/images/dtbox/prev.png" id="navigator_page_prev">
						<input type="text" style="width:50px" value="1" id="navigator_page_no">
						<img src="/client/images/dtbox/next.png" id="navigator_page_next">
					</div>
					<div class="left">
						Всего записей <span id="navigator_count" style="font-weight:bold;">0</span>, на странице: <select id="navigator_per_page">
							<option value="10">10</option>
							<option value="20">20</option>
							<option value="50" selected="true">50</option>
							<option value="100">100</option>
							<option value="200">200</option>
							<option value="500">500</option>
							<option value="1000">1000</option>
						</select>
					</div>
					<div class="left">
						<input type="text" value="" id="filter_date_from" class="calendar_input"/>
						<input type="text" value="" id="filter_date_to" class="calendar_input"/>
					</div>
					<div class="left">
						<div class="ui-button-light" id="filter_button" style="margin:0px;"><span class="ileft icon_reload">Обновить</span></div>
					</div>
				</div>

				<div id="data_area">
					<div id="protocol_table_wrapper"><div id="protocol_table"></div></div>
					<div id="protocol_none" style="display:none;">
						<h1 class="errorpage_title">Записи не найдены</h1>
					</div>
				</div>

			</div>
		</div>
	</div>


</div>