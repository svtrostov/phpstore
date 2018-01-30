<div class="page_user_info" id="page_user_info">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<h3 id="bigblock_title">Информация о пользователе</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Основное</li>
						<li class="tab">История входов</li>
						<li class="tab">Протокол действий</li>
					</ul>

					<div class="tab_content"><div class="wrapper">
					<!--Основное-->
						<div class="tab_content_wrapper" id="user_form">
							<h1>Учетная запись</h1>
							<div class="iline w200"><span>ID:</span><input style="width:100px;" type="text" class="disabled" readonly="true" value="" id="info_user_id"/></div>
							<div class="fline w200"><span>Логин:</span><input style="width:200px;" type="text" maxlength="128" value="" id="info_username"/></div>
							<div class="fline w200"><span>Пароль:</span><input style="width:200px;" type="text" maxlength="128" value="" id="info_password"/></div>
							<div class="fline w200"><span>Учетная запись:</span><select id="info_enabled" style="width:212px;"><option value="0">Заблокирована</option><option value="1">Активна</option></select></div>
							<div class="fline w200"><span>Имя пользователя:</span><input style="width:200px;" type="text" maxlength="128" value="" id="info_name"/></div>
							<div class="splitline"></div>

							<h1>Права доступа</h1>
							<div id="access_area"></div>

							<div class="splitline"></div>
							<div style="margin-left:210px;">
								<div class="ui-button" id="user_save_button" style="width:165px;margin:5px 0px;"><span>Сохранить изменения</span></div>
							</div>
						</div>
					<!--Основное-->
					</div></div>

					<div class="tab_content"><div class="wrapper">
					<!--История входов-->
						<div id="login_table"style="display:none;"></div>
						<div id="login_none">
							<br><br><br>
							<h1 class="big_title">История входов пуста</h1>
						</div>
					<!--История входов-->
					</div></div>

					<div class="tab_content"><div class="wrapper">
					<!--Протокол действий-->
						<div id="action_table"style="display:none;"></div>
						<div id="action_none" style="display:none;">
							<br><br><br>
							<h1 class="big_title">Протокол действий пуст</h1>
						</div>
					<!--Протокол действий-->
					</div></div>

				<!--tabs_area-->
				</div>

				<div id="tabs_none" style="display:none;">
					<h1 class="errorpage_title">Пользователь не найден</h1>
				</div>

			</div>
		</div>
	</div>



</div>