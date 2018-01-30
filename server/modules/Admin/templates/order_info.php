<div class="page_order_info" id="page_order_info">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal"><a class="expander" href="#" id="bigblock_expander"></a><h3 id="bigblock_title">Информация о заказе</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Основное</li>
						<li class="tab">Заказанные товары</li>
						<li class="tab">Документы</li>
					</ul>

					<div class="tab_content"><div class="wrapper">
					<!--Основное-->
						<div class="tab_content_wrapper miniform" id="order_form">

							<fieldset style="float:right;width:200px;">
								<legend>Клиент</legend>
								<div style="text-align:center;height:17px;" id="info_order_client_id"></div>
							</fieldset>

							<fieldset style="margin-right:210px;">
								<legend>Заказ ID: <b id="info_order_id"></b>, номер: <b id="info_order_num"></b> от <b id="info_timestamp"></b></legend>
								<div style="float:right;width:70px;">
									<a id="info_client_link_a" href="" target="_blank">
										<input type="button" style="height:23px;" value="Открыть..."/>
									</a>
								</div>
								<div style="margin-right:80px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_client_link"/></div>
							</fieldset>

							<div class="clear"></div>

							<fieldset style="float:right;width:140px;">
								<legend>Метод оплаты</legend>
								<div style="padding-right:5px;"><select style="width:100%;" id="info_paymethod"></select></div>
							</fieldset>
							<fieldset style="float:right;width:130px;">
								<legend>Цена доставки, руб</legend>
								<div style="padding-right:5px;"><input style="width:100%;" maxlength="32" type="text" value="" id="info_delivery_cost"/></div>
							</fieldset>
							<fieldset style="float:right;width:220px;">
								<legend>Метод доставки</legend>
								<div style="padding-right:5px;"><select style="width:100%;" id="info_delivery_id"></select></div>
							</fieldset>
							<fieldset style="margin-right:510px;">
								<legend>Статус заказа</legend>
								<div style="padding-right:5px;"><select style="width:100%;" id="info_status"></select></div>
							</fieldset>


							<div class="clear"></div>

							<fieldset style="float:right;width:200px;">
								<legend>Телефон</legend>
								<div style="padding-right:5px;"><input style="width:100%;" maxlength="32" type="text" value="" id="info_phone" placeholder="Пример: 8 (800) 123-45-67"/></div>
							</fieldset>
							<fieldset style="float:right;width:200px;">
								<legend>E-Mail</legend>
								<div style="padding-right:5px;"><input style="width:100%;" maxlength="32" type="text" value="" id="info_email" placeholder="Пример: example@example.ru"/></div>
							</fieldset>
							<fieldset style="margin-right:415px;">
								<legend>Контактное лицо</legend>
								<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_name" /></div>
							</fieldset>

							<div class="clear"></div>

							<fieldset>
								<legend>Адрес доставки</legend>
								<div style="padding-right:5px;"><textarea id="info_address" style="width:100%;height:50px;"></textarea></div>
							</fieldset>

							<div class="clear"></div>

							<fieldset>
								<legend>Дополнительные сведения к заказу</legend>
								<div style="padding-right:5px;"><textarea id="info_additional" style="width:100%;height:50px;"></textarea></div>
							</fieldset>

							<div class="clear"></div>

							<fieldset>
								<legend>Организация</legend>
								<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_company" /></div>
							</fieldset>

							<fieldset style="display:inline-block;width:150px;">
								<legend>ИНН</legend>
								<div style="padding-right:5px;"><input style="width:100%;" maxlength="32" type="text" value="" id="info_inn"/></div>
							</fieldset>
							<fieldset style="display:inline-block;width:150px;">
								<legend>КПП</legend>
								<div style="padding-right:5px;"><input style="width:100%;" maxlength="32" type="text" value="" id="info_kpp"/></div>
							</fieldset>
							<fieldset style="display:inline-block;width:150px;">
								<legend>ОКПО</legend>
								<div style="padding-right:5px;"><input style="width:100%;" maxlength="32" type="text" value="" id="info_okpo"/></div>
							</fieldset>

							<div class="clear"></div>

							<fieldset style="float:right;width:200px;">
								<legend>БИК банка</legend>
								<div style="padding-right:5px;"><input style="width:100%;" maxlength="32" type="text" value="" id="info_bank_bik" /></div>
							</fieldset>
							<fieldset style="margin-right:210px;">
								<legend>Наименование банка</legend>
								<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_bank_name" /></div>
							</fieldset>

							<div class="clear"></div>

							<fieldset style="display:inline-block;width:250px;">
								<legend>Номер счета</legend>
								<div style="padding-right:5px;"><input style="width:100%;" maxlength="32" type="text" value="" id="info_bank_account"/></div>
							</fieldset>
							<fieldset style="display:inline-block;width:250px;">
								<legend>Номер корр. счета</legend>
								<div style="padding-right:5px;"><input style="width:100%;" maxlength="32" type="text" value="" id="info_bank_account_corr"/></div>
							</fieldset>

							<div class="clear"></div>

							<fieldset>
								<legend>Юридический адрес</legend>
								<div style="padding-right:5px;"><textarea id="info_legal_address" style="width:100%;height:50px;"></textarea></div>
							</fieldset>

							<div class="clear"></div>
							<br>
							<div style="margin-left:10px;">
								<div class="ui-button" id="order_save_button" style="width:165px;margin:5px 0px;"><span>Сохранить изменения</span></div>
							</div>
						</div>
					<!--Основное-->
					</div></div>


					<div class="tab_content"><div class="wrapper">
					<!--Заказанные товары-->
						<div class="ui-button-light" id="button_new_product"><span class="ileft btn_icon_add">Добавить товар</span></div>
						<div class="ui-button-light" id="button_refresh_product"><span class="ileft btn_icon_refresh">Перегрузить</span></div>
						<div id="product_table_area">
							<div id="product_table"></div>
							<div style="text-align: right;">
								<div class="fline w200"><span>Сумма заказа (руб):</span><input style="width:150px;" type="text" readonly="true" value="" id="order_sum"/></div>
							</div>
							<div class="splitline"></div>
							<div style="text-align: right;">
								<div class="ui-button" id="product_save_button" style="width:165px;margin:5px 0px;"><span>Сохранить заказ</span></div>
								<div id="product_save_button_fail">Нельзя изменить отмененный или уже выполненный заказ</div>
							</div>
						</div>
						<div id="product_none_area" style="display:none;">
							<br><br><br>
							<h1 class="big_title">В заказе нет товаров</h1>
						</div>
					<!--Заказанные товары-->
					</div></div>



					<div class="tab_content"><div class="wrapper">
					<!--Документы-->
						<h1>Товарный чек</h1>
						<div class="fline w150"><span>Реквизиты:</span><select id="check_account_code" style="width:312px;"></select></div>
						<div class="fline w150"><span>Товарный чек №:</span><input style="width:100px;" type="text" value="" id="check_num"/> от <input type="text" value="" id="check_date" class="calendar_input" style="width:100px;"/></div>
						<div class="iline w150"><span>&nbsp;</span><a href="" id="check_link" target="_blank"><div class="ui-button-light"><span style="margin:0px;">Показать товарный чек</span></div></a></div>
						<div class="splitline"></div>
						<h1>Счет на оплату</h1>
						<div class="fline w150"><span>Реквизиты:</span><select id="bill_account_code" style="width:312px;"></select></div>
						<div class="fline w150"><span>Покупатель:</span><input style="width:400px;" type="text" value="" id="bill_buyer"/></div>
						<div class="iline w150"><span>&nbsp;</span><a href="" id="bill_link" target="_blank"><div class="ui-button-light"><span style="margin:0px;">Показать счет</span></div></a></div>
						<div class="splitline"></div>
						<h1>Счет-фактура</h1>
						<div class="fline w150"><span>Реквизиты:</span><select id="invoice_account_code" style="width:312px;"></select></div>
						<div class="fline w150"><span>Счет-фактура №:</span><input style="width:100px;" type="text" value="" id="invoice_num"/> от <input type="text" value="" id="invoice_date" class="calendar_input" style="width:100px;"/></div>
						<div class="fline w150"><span>К документу №:</span><input style="width:100px;" type="text" value="" id="invoice_doc_num"/> от <input type="text" value="" id="invoice_doc_date" class="calendar_input" style="width:100px;"/></div>
						<div class="fline w150"><span>Покупатель:</span><input style="width:400px;" type="text" value="" id="invoice_buyer"/></div>
						<div class="fline w150"><span>Адрес покупателя:</span><input style="width:400px;" type="text" value="" id="invoice_buyer_address"/></div>
						<div class="fline w150"><span>ИНН покупателя:</span><input style="width:200px;" type="text" value="" id="invoice_buyer_inn"/></div>
						<div class="fline w150"><span>КПП покупателя:</span><input style="width:200px;" type="text" value="" id="invoice_buyer_kpp"/></div>
						<div class="iline w150"><span>&nbsp;</span><a href="" id="invoice_link" target="_blank"><div class="ui-button-light"><span style="margin:0px;">Показать счет-фактуру</span></div></a></div>
						<div class="splitline"></div>
						<h1>Товарная накладная</h1>
						<div class="fline w150"><span>Реквизиты:</span><select id="torg12_account_code" style="width:312px;"></select></div>
						<div class="fline w150"><span>Накладная №:</span><input style="width:100px;" type="text" value="" id="torg12_num"/> от <input type="text" value="" id="torg12_date" class="calendar_input" style="width:100px;"/></div>
						<div class="fline w150"><span>Грузополучатель:</span><textarea id="torg12_buyer" style="width:400px;height:100px;"></textarea></div>
						<div class="fline w150"><span>ОКПО грузополучателя:</span><input style="width:200px;" type="text" value="" id="torg12_buyer_okpo"/></div>
						<div class="fline w150"><span>Плательщик:</span><textarea id="torg12_payer" style="width:400px;height:100px;"></textarea></div>
						<div class="fline w150"><span>ОКПО плательщика:</span><input style="width:200px;" type="text" value="" id="torg12_payer_okpo"/></div>
						<div class="fline w150"><span>Основание:</span><input style="width:400px;" type="text" value="" id="torg12_why"/></div>
						<div class="iline w150"><span>&nbsp;</span><a href="" id="torg12_link" target="_blank"><div class="ui-button-light"><span style="margin:0px;">Показать накладную</span></div></a></div>
					<!--Документы-->
					</div></div>

				<!--tabs_area-->
				</div>

				<div id="tabs_none" style="display:none;">
					<h1 class="errorpage_title">Заказ не найден</h1>
				</div>
			</div>
		</div>
	</div>



	<div class="bigblock" id="pselector" style="display:none;">
		<input type="hidden" id="pselector_selected_id" value="">
		<div class="titlebar"><h3 id="pselector_title">Выберите товар</h3></div>
		<div class="contentwrapper">
			<div class="contentarea">



				<div class="filter_area">

					<div class="filter_line">
						<div class="left">
							<select id="pselector_enabled">
								<option value="all">Все товары</option>
								<option value="1" selected="true">Только видимые</option>
							</select>
						</div>
						<div class="left">
							<input type="text" value="" id="pselector_term" placeholder="Поиск товаров по ID, артикулу, наименованию, описанию..." style="width:300px;"/>
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
							<div class="input_title">Совместимость:</div>
							<input type="text" value="" id="search_compatible" style="width:90px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Парт-номера:</div>
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