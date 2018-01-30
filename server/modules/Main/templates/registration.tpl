<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
	<head>
		<title>DTBOX: интернет-магазин</title>
		<meta http-equiv="X-UA-Compatible" content="IE=9"/>
		<meta charset="UTF-8">
		<meta name="description" content="DTBOX:: интернет-магазин">
		<meta name="keywords" content="картридж, тонер, чернила, ролик, бумага, купить картридж, продажа, расходные материалы, принтер, факс, комплектующие,">
		<meta name="copyright" content="Dolgov Alexander">
		<meta name="engine-copyright" content="AlexSoft">
		<meta http-equiv="Content-Type" content="text; charset=utf-8" />
		<LINK rel="shortcut icon" href="/favicon.ico" type="images/x-icon">
		<LINK rel="icon" href="/favicon.ico" type="images/x-icon">
		<link rel="stylesheet" type="text/css" href="/client/css/ui-reset.css"/>
		<link rel="stylesheet" type="text/css" href="/client/css/ui-shop.css"/>
		<meta name="robots" content="all">
		<script type="text/javascript">
			var COOKIE_PREFIX = "{%SESSION_COOKIE%}";
			var INTERFACE_IMAGES = "/client/images";
			var INTERFACE_CSS = "/client/css";
			var INTERFACE_MODULE = "{%REQUEST_MODULE%}";
			var REQUEST_INFO = {%REQUEST_INFO%};
		</script>
		<script type="text/javascript" src="/client/js/lib/__core.js"></script>
		<script type="text/javascript" src="/client/js/lib/__more.js"></script>
		<script type="text/javascript" src="/client/js/lib/__utils.js"></script>
		<script type="text/javascript" src="/client/js/lib/__app.js"></script>
		<script type="text/javascript" src="/client/js/lib/axRequest.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsMessage.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsValidator.class.js"></script>
	</head>
	<body>

	<div id="mainWrapper">
		<div id="wrapper">


			<div id="header">

				<div class="boxes_above_header">
					<div class="menu">
						<a class="first" href="/">Главная</a>
						<a href="/price/all">Прайс-лист</a>
						<a href="/page/delivery.html">Доставка</a>
						<a href="/news">Новости</a>
						<a href="/map">Карта сайта</a>
						<a href="/page/contacts.html">Контакты</a>
					</div>
				</div>

				<div id="box_header_logo">
					<div id="box_header_logo_box"></div>
					<div id="box_header_logo_text">Интернет магазин</div>
				</div>

				<a href="/order/cart">
				<div id="box_header_cart">
					<div class="cart_header_wrapper">
						<div class="cart_header_inner">
							{%FN::mc_getUserCart%}
						</div>
					</div>
				</div>
				</a>

				<div class="box_header_phone">
					<span>Позвоните нам: </span><b>+7 (863) 309-00-89</b>
				</div>

				<div class="search">
					<form method="post" action="/search/">
						<input type="hidden" value="2" name="set">
						<div class="input-width">
							<div class="width-setter">
								<input type="text" name="words" placeholder="Введите название искомого товара..." size="10" maxlength="300" class="go fl_left">
							</div>
						</div>
						<div class="header_search_button">
							<button id="tdb1" type="submit" class="ui-button" role="button" aria-disabled="false"><span>Найти</span></button>
						</div>
					</form>

				</div>

			</div>

			<div class="catalog_navbar">
				<div class="catalog_navbar_bg">
					<ul id="nav">{%FN::mc_getUserMainMenu%}</ul>
				</div>
			</div>



			<div class="centralArea">

			<form method="post" id="regform">
			<input type="hidden" name="action" value="registration">

				<br><div id="bg_catalog_1">Регистрация клиента</div>

				<div class="lightBox">
					<div class="title"><b>Данные для входа</b></div>
				</div><br>
				<div class="orderClientInfo">
					<table class="utable">
						<tr>
							<td width="150" class="param">Логин*:</td>
							<td class="value"><input type="text" class="input" name="client_login" id="client_login" value="" style="width:250px;"></td>
						</tr>
						<tr>
							<td class="param">Пароль*:</td>
							<td class="value"><input type="password" class="input" name="client_password" id="client_password" style="width:250px;" value="">
								<br><input type="password" class="input" name="client_password2" id="client_password2" style="width:250px;" value=""> (Повторите пароль)
							</td>
						</tr>
					</table>

				<div class="lightBox">
					<div class="title"><b>Личные данные</b></div>
				</div>
				<br>
					<table class="utable">
						<tr>
							<td width="150" class="param">Контактное лицо*:</td>
							<td class="value"><input type="text" class="input" name="client_name" id="client_name" value="" style="width:300px" placeholder="Фамилия Имя Отчество"></td>
						</tr>
						<tr>
							<td class="param">E-mail*:</td>
							<td class="value"><input type="text" class="input" name="client_email" id="client_email" value="" style="width:300px" placeholder="Адрес электронной почты"></td>
						</tr>
						<tr>
							<td class="param">Компания:</td>
							<td class="value"><input type="text" class="input" name="client_company" id="client_company" style="width:300px;" value=""></td>
						</tr>
						<tr>
							<td class="param">ИНН:</td>
							<td class="value"><input type="text" class="input" name="client_inn" id="client_inn" style="width:300px;" value=""></td>
						</tr>
						<tr>
							<td class="param">КПП:</td>
							<td class="value"><input type="text" class="input" name="client_kpp" id="client_kpp" style="width:300px;" value=""></td>
						</tr>
						<tr>
							<td class="param">Телефон*:</td>
							<td class="value"><input type="text" class="input" name="client_phone" id="client_phone" style="width:300px;" value="" placeholder="Контактный телефон"></td>
						</tr>
						<tr>
							<td class="param">Страна:</td>
							<td class="value"><input type="text" class="input" name="client_country" id="client_country" style="width:300px;" value=""></td>
						</tr>
						<tr>
							<td class="param">Почтовый индекс:</td>
							<td class="value"><input type="text" class="input" name="client_zip" id="client_zip" style="width:300px;" value=""></td>
						</tr>
						<tr>
							<td class="param">Город*:</td>
							<td class="value"><input type="text" class="input" name="client_city" id="client_city" style="width:300px;" value=""></td>
						</tr>
						<tr>
							<td class="param">Адрес*:</td>
							<td class="value"><textarea style="width:300px; height:100px;" name="client_address" id="client_address"></textarea></td>
						</tr>
						<tr>
							<td></td>
							<td>
								Поля, отмеченные * обязательны для заполнения.<br>
								</div>
							<br></td>
					</tr>
					<tr>
						<td></td>
						<td>
							<button type="button" class="ui-button-light" role="button" aria-disabled="false" onclick="registerUser()"><span>Зарегистрироваться</span></button>
							<br>
						</td>
						</tr>
					</table>
				</div>
				</form>
				<script>
					var regformvalidator = new jsValidator("regform");
					regformvalidator.required("client_email").email("client_email").required("client_city").
					required("client_address").required("client_name").required("client_phone").phone("client_phone")
					.required("client_login").username("client_login").range("client_login",5,20).range("client_password",5,20).required("client_password").matches("client_password","client_password2");
					var fields=['client_login','client_name','client_email','client_company','client_inn','client_kpp','client_phone','client_country','client_zip','client_city','client_address'];
					function registerUser(){
						if(!regformvalidator.validate())return false;
						if(App.localStorage.isLocalStorageAvailable()){
							for(var i=0;i<fields.length;i++){
								App.localStorage.write('reg_'+fields[i],$(fields[i]).getValue(), true);
							}
						}
						$("regform").submit();
					}
					function infoReset(){
						regformvalidator.empty();
						$("regform").reset();
					}
					document.addEvent("appbegin", function() {
						if(App.localStorage.isLocalStorageAvailable()){
							for(var i=0;i<fields.length;i++){
								$(fields[i]).setValue(App.localStorage.read('reg_'+fields[i],'', true));
							}
						}
						if(typeOf(REQUEST_INFO["get"]["e"])=="string"){
							switch(REQUEST_INFO["get"]["e"]){
								case "email": App.message("Регистрация","Не задан адрес электронной почты","WARNING"); break;
								case "emailexists": App.message("Регистрация","Указанный адрес электронной почты уже используется одним из клиентов магазина. Если вы забыли свой логин/пароль для входа, воспользуйтесь восстановлением пароля для получения контроля над Вашей учетной записью.","WARNING"); break;
								case "name": App.message("Регистрация","Не задано контактное имя","WARNING"); break;
								case "address": App.message("Регистрация","Не задан почтовый адрес","WARNING"); break;
								case "city": App.message("Регистрация","Не задан город","WARNING"); break;
								case "phone": App.message("Регистрация","Не задан контактный номер телефона","WARNING"); break;
								case "login": App.message("Регистрация","Не задано имя пользователя или задано некорреткно","WARNING"); break;
								case "password": App.message("Регистрация","Не задан пароль  или задан некорреткно","WARNING"); break;
								case "exists": App.message("Регистрация","Выбранное имя пользователя уже занято","WARNING"); break;
								case "internal": App.message("Регистрация","При реигстрации произошла ошибка, попробуйте повторить чуть позже","ERROR"); break;
							}
						}
					});
				</script>

			</div>



			<div class="rightArea">
				<div class="darkBox">
					<div class="title">Кабинет клиента</div>
					{%FN::mc_getUserAreaMenu%}
				</div>

				<div class="darkBox">
					<div class="title">Как сделать заказ</div>
					<ul class="howtoorder">
						<li class="step1">Найдите интересуемые Вас товары и добавьте их в &laquo;Корзину&raquo;;</li>
						<li class="step2">Перейдите в <a class="link" href="/order/cart">&laquo;Корзину&raquo;</a> для оформления заказа;</li>
						<li class="step3">Проверте выбранный ассортимент и количество товара;</li>
						<li class="step4">Заполните форму заказа и нажмите кнопку &laquo;Оформить покупку&raquo;;</li>
						<li class="step5">Наши менеджеры свяжутся с Вами для уточнения деталей заказа.</li>
						<li class="comment">Товары с пометкой &laquo;Под заказ&raquo; будут доставлены в течении десяти рабочих дней после подтверждения заказа.</li>
					</ul>
				</div>

			</div>



			<div class="clear"></div>

			<div id="footer"><p>Copyright &copy; Alexander Dolgov<br>Все права защищены.</p></div>
		</div>
	</div>

	</body>
</html>