<div class="page_accounts">

	<div class="bigblock" id="wrapper">
		<div class="titlebar"><h3>Реквизиты платежных документов</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="area">
					<div id="table_area_wrapper"><div id="table_area" class="table_area"></div></div>
				</div>
				<div id="splitter"></div>
				<div id="info">
					<div class="ui-button-light" id="button_new_record"><span>Добавить реквизиты</span></div>
					<div class="ui-button-light" id="button_delete_record"><span>Удалить</span></div>
					<div id="info_area"></div>
				</div>


			</div>
		</div>
	</div>


	<div id="tmpl_info" style="padding:10px;display:none;width:100%;">
		<div class="fline w200"><span>Идентификатор:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="info_account_id"/></div>

		<div class="fline w200"><span>Код (для настроек магазина):</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_code"/></div>
		<div class="fline w200"><span>Наименование:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_name"/></div>
		<div class="splitline"></div>
		<h1>Банковские реквизиты</h1>
		<div class="fline w200"><span>Наименование банка:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_bank_name"/></div>
		<div class="fline w200"><span>БИК банка:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_bank_bik"/></div>
		<div class="fline w200"><span>Номер счета:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_bank_account"/></div>
		<div class="fline w200"><span>Номер корр. счета:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_bank_account_corr"/></div>
		<div class="splitline"></div>
		<h1>Реквизиты организации</h1>
		<div class="fline w200"><span>Наименование организации:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_company"/></div>
		<div class="fline w200"><span>Юридический адрес:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_address"/></div>
		<div class="fline w200"><span>Фактический адрес:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_address_real"/></div>
		<div class="fline w200"><span>Номер телефона:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_phone"/></div>
		<div class="fline w200"><span>Свидетельство ИП:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_certificate"/></div>
		<div class="fline w200"><span>ОГРН:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_ogrn"/></div>
		<div class="fline w200"><span>ИНН:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_inn"/></div>
		<div class="fline w200"><span>КПП:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_kpp"/></div>
		<div class="fline w200"><span>ОКПО:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_okpo"/></div>
		<div class="fline w200"><span>Фамилия И.О. подписанта:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_sign_name"/></div>
		<div class="fline w200"><span>Должность подписанта:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_sign_post"/></div>

		<div class="ui-button-light" style="margin-left:160px;" id="button_change_record"><span>Сохранить</span></div>
		<div class="ui-button-light" style="margin-left:10px;" id="button_change_cancel"><span>Отмена</span></div>
	</div>

	<div id="tmpl_new" style="padding:10px;display:none;width:100%;">
		<div class="fline w200"><span>Код (для настроек магазина):</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_code"/></div>
		<div class="fline w200"><span>Наименование:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_name"/></div>
		<div class="splitline"></div>
		<h1>Банковские реквизиты</h1>
		<div class="fline w200"><span>Наименование банка:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_bank_name"/></div>
		<div class="fline w200"><span>БИК банка:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_bank_bik"/></div>
		<div class="fline w200"><span>Номер счета:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_bank_account"/></div>
		<div class="fline w200"><span>Номер корр. счета:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_bank_account_corr"/></div>
		<div class="splitline"></div>
		<h1>Реквизиты организации</h1>
		<div class="fline w200"><span>Наименование организации:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_company"/></div>
		<div class="fline w200"><span>Юридический адрес:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_address"/></div>
		<div class="fline w200"><span>Фактический адрес:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_address_real"/></div>
		<div class="fline w200"><span>Номер телефона:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_phone"/></div>
		<div class="fline w200"><span>Свидетельство ИП:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_certificate"/></div>
		<div class="fline w200"><span>ОГРН:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_ogrn"/></div>
		<div class="fline w200"><span>ИНН:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_inn"/></div>
		<div class="fline w200"><span>КПП:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_kpp"/></div>
		<div class="fline w200"><span>ОКПО:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_okpo"/></div>
		<div class="fline w200"><span> Фамилия И.О. подписанта:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_sign_name"/></div>
		<div class="fline w200"><span>Должность подписанта:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_sign_post"/></div>

		<div class="ui-button-light" style="margin-left:210px;"id="button_add_record" style="width:165px;margin:5px 0px;"><span>Добавить</span></div>
	</div>


</div>