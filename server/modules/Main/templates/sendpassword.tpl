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
			<input type="hidden" name="action" value="restore">


				<div class="lightBox">
					<div class="title"><b>Восстановление пароля</b></div>
				</div><br>
				<div class="orderClientInfo">
					<table class="utable">
						<tr>
							<td class="param">&nbsp;</td>
							<td class="value">Для восстановления пароля введите адрес электронной почты, заданный Вами при регистрации</td>
						</tr>
						<tr>
							<td class="param">E-mail:</td>
							<td class="value"><input type="text" class="input" name="client_email" id="client_email" value="" style="width:300px" placeholder="Адрес электронной почты"></td>
						</tr>
						<td></td>
						<td>
							<button type="button" class="ui-button-light" role="button" aria-disabled="false" onclick="restorePassword()"><span>Восстановить пароль</span></button>
							<br>
						</td>
						</tr>
					</table>
				</div>
				</form>
				<script>
					var regformvalidator = new jsValidator("regform");
					regformvalidator.required("client_email").email("client_email");
					function restorePassword(){
						if(!regformvalidator.validate())return false;
						$("regform").submit();
					}
					function infoReset(){
						regformvalidator.empty();
						$("regform").reset();
					}
					document.addEvent("appbegin", function() {
						if(typeOf(REQUEST_INFO["get"]["e"])=="string"){
							switch(REQUEST_INFO["get"]["e"]){
								case "email": App.message("Восстановление пароля","Не задан адрес электронной почты","WARNING"); break;
								case "exists": App.message("Восстановление пароля","Указанный адрес электронной почты не найден","WARNING"); break;
								case "internal": App.message("Восстановление пароля","При реигстрации произошла ошибка, попробуйте повторить чуть позже","ERROR"); break;
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