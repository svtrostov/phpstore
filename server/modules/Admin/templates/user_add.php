<div class="page_user_info" id="page_user_info">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<h3 id="bigblock_title">Создание нового пользователя</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div class="wrapper">
					<!--Основное-->

						<div id="user_done" style='display:none;'>
							<br><br><br>
							<div class="big_title">Пользователь добавлен</div>
						</div>

						<div class="tab_content_wrapper" id="user_form">
							<h1>Учетная запись</h1>
							<div class="fline w200"><span>Логин:</span><input style="width:200px;" type="text" maxlength="128" value="" id="info_username"/></div>
							<div class="fline w200"><span>Пароль:</span><input style="width:200px;" type="text" maxlength="128" value="" id="info_password"/></div>
							<div class="fline w200"><span>Учетная запись:</span><select id="info_enabled" style="width:212px;"><option value="0">Заблокирована</option><option value="1">Активна</option></select></div>
							<div class="fline w200"><span>Имя пользователя:</span><input style="width:200px;" type="text" maxlength="128" value="" id="info_name"/></div>
							<div class="splitline"></div>

							<h1>Права доступа</h1>
							<div id="access_area"></div>

							<div class="splitline"></div>
							<div style="margin-left:210px;">
								<div class="ui-button" id="user_save_button" style="width:165px;margin:5px 0px;"><span>Добавить пользователя</span></div>
							</div>
						</div>
					<!--Основное-->
				</div>

			</div>
		</div>
	</div>



</div>