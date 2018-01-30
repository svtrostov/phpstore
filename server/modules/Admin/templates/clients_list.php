<div class="page_admin_clients_list" id="page_admin_clients_list">


	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<h3 id="bigblock_title">Клиенты</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">
			<!--Content-->


				<div class="filter_area">

					<div class="filter_line">
						<div class="left">
							<select id="filter_manager_id">
								<option value="all">-[Любой менеджер]-</option>
								<option value="0">-[Нет менеджера]-</option>
							</select>
						</div>
						<div class="left">
							<input type="text" value="" id="filter_search_name" placeholder="Поиск клиента по ID, имени, логину, e-mail..."/>
						</div>
						<div class="left">
							<select id="filter_limit">
								<option value="50">лимит 50 записей</option>
								<option value="100" selected="selected">лимит 100 записей</option>
								<option value="500">лимит 500 записей</option>
								<option value="all">Все записи</option>
							</select>
							<div class="ui-button-light" id="filter_button" style="margin:0px;"><span class="ileft icon_filter">Искать</span></div>
						</div>
					</div>

				</div>



				<div class="clients_area">
					<div id="clients_table"></div>
					<div id="clients_none" style="display:none;">
						<h1 class="errorpage_title">Клиенты не найдены</h1>
					</div>
				</div>



			<!--Content-->
			</div>
		</div>
	</div>



</div>