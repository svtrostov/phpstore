<div class="page_client_info" id="page_client_info">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<h3 id="bigblock_title">Информация о клиенте</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">


				<div id="order_add_done" style='display:none;'>
					<br><br><br>
					<div class="big_title">Заказ создан</div>
					<br><br><br>
					<div class="middle_title" id="new_order_id"></div>
				</div>

				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Основное</li>
						<li class="tab">Заказы</li>
						<li class="tab">Сообщения</li>
					</ul>

					<div class="tab_content"><div class="wrapper">
					<!--Основное-->
						<div class="tab_content_wrapper" id="client_form">
							<h1>Учетная запись</h1>
							<div class="iline w200"><span>ID:</span><input style="width:100px;" type="text" class="disabled" readonly="true" value="" id="info_client_id"/></div>
							<div class="fline w200"><span>Логин:</span><input style="width:200px;" type="text" maxlength="128" value="" id="info_username"/></div>
							<div class="fline w200"><span>Пароль:</span><input style="width:200px;" type="text" maxlength="128" value="" id="info_password"/></div>
							<div class="fline w200"><span>Учетная запись:</span><select id="info_enabled" style="width:212px;"><option value="0">Заблокирована</option><option value="1">Активна</option></select></div>
							<div class="fline w200"><span>Менеджер клиента:</span><select id="info_manager_id" style="width:312px;">
								<option value="0">-[Нет менеджера]-</option>
							</select></div>
							<div class="splitline"></div>
							<div class="fline w200"><span>Статус клиента:</span><select id="info_discount_id" style="width:312px;"></select></div>
							<div class="splitline"></div>
							<h1>Контактная информация</h1>
							<div class="fline w200"><span>Контактное лицо:</span><input style="width:300px;" type="text" maxlength="128" value="" id="info_name"/></div>
							<div class="fline w200"><span>E-Mail:</span><input style="width:300px;" placeholder="Example: example@example.ru" maxlength="64" type="text" value="" id="info_email"/></div>
							<div class="fline w200"><span>Телефон:</span><input style="width:300px" placeholder="Example: 8 (800) 123-45-67" maxlength="16" type="text" value="" id="info_phone"/></div>
							<div class="fline w200"><span>Индекс:</span><input style="width:300px;" type="text" maxlength="16" value="" id="info_zip"/></div>
							<div class="fline w200"><span>Страна:</span><input style="width:300px;" type="text" maxlength="64" value="" id="info_country"/></div>
							<div class="fline w200"><span>Город:</span><input style="width:300px;" type="text" maxlength="64" value="" id="info_city"/></div>
							<div class="fline w200"><span>Адрес:</span><input style="width:300px;" type="text" maxlength="255" value="" id="info_address"/></div>
							<h1>Юридическое лицо</h1>
							<div class="fline w200"><span>Организация:</span><input style="width:300px;" type="text" maxlength="128" value="" id="info_company"/></div>
							<div class="fline w200"><span>ИНН:</span><input style="width:300px;" type="text" maxlength="128" value="" id="info_inn"/></div>
							<div class="fline w200"><span>КПП:</span><input style="width:300px;" type="text" maxlength="128" value="" id="info_kpp"/></div>
							<div class="fline w200"><span>ОКПО:</span><input style="width:300px;" type="text" maxlength="128" value="" id="info_okpo"/></div>
							<div class="fline w200"><span>Наименование банка:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_bank_name"/></div>
							<div class="fline w200"><span>БИК банка:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_bank_bik"/></div>
							<div class="fline w200"><span>Номер счета:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_bank_account"/></div>
							<div class="fline w200"><span>Номер корр. счета:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_bank_account_corr"/></div>
							<div class="fline w200"><span>Юридический адрес:</span><textarea style="width:300px;height:100px;" type="text" id="info_legal_address"></textarea></div>
							<div class="splitline"></div>
							<div style="margin-left:210px;">
								<div class="ui-button" id="client_save_button" style="width:165px;margin:5px 0px;"><span>Сохранить изменения</span></div>
							</div>
						</div>
					<!--Основное-->
					</div></div>



					<div class="tab_content"><div class="wrapper">
					<!--Заказы-->
						<div class="ui-button" id="order_new_button" style="margin:5px 0px;"><span>Создать заказ от имени клиента</span></div>
						<div id="orders_table"style="display:none;"></div>
						<div id="orders_none" style="display:none;">
							<br><br><br>
							<h1 class="big_title">Нет заказов</h1>
						</div>
					<!--Заказы-->
					</div></div>

					<div class="tab_content"><div class="wrapper">
					<!--Сообщения-->
						<div id="tickets_list">
							<div id="tickets_table_area"></div>
							<div id="tickets_none_area" style="display:none;">
								<h1 class="errorpage_title">Нет сообщений</h1>
							</div>
						</div>
					<!--Сообщения-->
					</div></div>

				<!--tabs_area-->
				</div>

				<div id="tabs_none" style="display:none;">
					<h1 class="errorpage_title">Клиент не найден</h1>
				</div>
			</div>
		</div>
	</div>



</div>