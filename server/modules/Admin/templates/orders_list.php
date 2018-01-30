<div class="page_orders_list" id="page_orders_list">


	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal"><a class="expander" href="#" id="bigblock_expander"></a><h3 id="bigblock_title">Заказы</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">
			<!--Content-->


				<div class="filter_area">

					<div class="filter_line">
						<div class="left">
							<select id="filter_manager_id">
								<option value="self">-[Заказы моих клиентов]-</option>
								<option value="all">-[Заказы всех менеджеров]-</option>
								<option value="0">-[Заказы без менеджера]-</option>
							</select>
						</div>
						<div class="left">
							<select id="filter_status">
								<option value="all" selected="selected">Заказы с любым статусом</option>
							</select>
						</div>
						<div class="left">
							<select id="filter_delivery" style="max-width:200px;">
								<option value="all">Любой способ доставки</option>
							</select>
						</div>
						<div class="left">
							<select id="filter_period">
								<option value="all" selected="true">За все время</option>
								<option value="1">За последние сутки</option>
								<option value="7">За последнюю неделю</option>
								<option value="30">За последний месяц</option>
								<option value="90">За последние три месяца</option>
								<option value="365">За последний год</option>
							</select>
						</div>
					</div>
					<div class="filter_line">
						<div class="left">
							<input type="text" value="" id="filter_term" placeholder="Поиск заказа по ID, имени клмента, логину, e-mail..." style="width:300px;"/>
						</div>
						<div class="left">
							<select id="filter_limit">
								<option value="50" selected="selected">лимит 50 записей</option>
								<option value="100">лимит 100 записей</option>
								<option value="500">лимит 500 записей</option>
								<option value="all">Все записи</option>
							</select>
							<div class="ui-button-light" id="filter_button" style="margin:0px;"><span class="ileft icon_filter">Искать</span></div>
						</div>
					</div>

				</div>



				<div class="orders_area">
					<div id="orders_table"></div>
					<div id="orders_none" style="display:none;">
						<h1 class="errorpage_title">Заказы не найдены</h1>
					</div>
				</div>



			<!--Content-->
			</div>
		</div>
	</div>



</div>