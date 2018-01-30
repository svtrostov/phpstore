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
		<script type="text/javascript" src="/client/js/lib/jsTabPanel.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsMessage.class.js"></script>
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

				<div class="siteMiddleDivContent">
					<br>
					<div id="bg_catalog_1">Информация о доставке</div>


					<table cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td >
								
							</td>
						</tr>
					</table>

					<div style="padding:5px">
						Интернет-магазин выполняет доставку любого товара своей собственной Службой доставки.</br>
						<br>
						<h2>Стоимость доставки курьером</h2></br>
						Стоимость доставки товара из нашего магазина по г. Ростову-на-Дону составляет 200 руб.</br>
						Уточнить стоимость доставки в иные населенные пункты, Вы можете <a class="link" href="/page/contacts.html">связавшись с менеджерами DTBox</a></br>
						</br>
						<h2>Время доставки</h2></br>
						Время доставки согласовывается с менеджером Службы доставки, который обязательно свяжется с вами сразу после того, как вы разместите свой заказ.</br>
						Неправильно указанный номер телефона, неточный или неполный адрес могут привести к дополнительной задержке!</br>
						Пожалуйста, внимательно проверяйте ваши персональные данные при регистрации и оформлении заказа. Конфиденциальность ваших регистрационных данных гарантируется.</br>
						</br>
						Доставка выполняется в будние дни с 10:00 до 18:00 часов.</br>
						Товары, заказанные в выходные и праздничные дни, доставляются в первый рабочий день.</br>
						Время осуществления доставки зависит от времени размещения заказа и наличия товара на складе.</br>
						Если заказ подтвержден менеджером Службы доставки то товар может быть доставлен на следующий рабочий день между 10:00 и 18:00.</br>
						</br>
						Вы также можете указать любое другое удобное время доставки, и заказ будет доставлен в удобное Вам время.</br>
						Иное время доставки, а также место доставки в населенные пункты области определяется по договоренности с клиентом.</br>
						</br>
						<h2>Место доставки</h2></br>
						Доставка осуществляется по адресу, указанному при оформлении заказа. Если необходимо доставить заказ по иному адресу, необходимо сообщить адрес менеджеру Службы доставки, который свяжется с вами непосредственно после оформления заказа на сайте.</br>
						</br>
						<h2>Прочее</h2></br>
						При доставке Вам будут переданы все необходимые документы на покупку: товарный, кассовый чеки, а также гарантийный талон.</br>
						При оформлении покупки на организацию, Вам будут переданы счет-фактура, а также накладная, в которой необходимо поставить печать вашей организации.</br>
						Цена, указанная в переданных вам курьером документах, является окончательной, курьер не обладает правом корректировки цены.</br>
						Стоимость доставки выделяется в документах на покупку отдельной графой.</br>
						</br>
						Просим Вас помнить, что все технические параметры и потребительские свойства приобретаемого товара вам следует уточнять у нашего менеджера до момента покупки товара.</br>
						В обязанности работников Службы доставки не входит осуществление консультаций и комментариев относительно потребительских свойств товара.</br>
						При необходимости инсталляции приобретаемого в нашем магазине товара Вам необходимо сообщить об этом нашему менеджеру.</br>
						При доставке заказа проверяйте его комплектацию, работоспособность и наличие механических повреждений.</br>
						После принятия заказа от курьера, претензии по поводу механических повреждений не рассматриваются.</br>
						</br>
						<h2>Контактная информация</h2></br>
						При возникновении вопросов просим Вас обращаться по следующим координатам:</br>
						Служба доставки: 8 (863) 309 00 89 (многоканальный).</br>
						Электронная почта: <a class="link" href="mailto:info@dtbox.ru">info@dtbox.ru</a></br>
						</br>

					</div>
				</div>

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
						<li class="step3">Проверьте выбранный ассортимент и количество товара;</li>
						<li class="step4">Заполните форму заказа и нажмите кнопку &laquo;Оформить покупку&raquo;;</li>
						<li class="step5">Наши менеджеры свяжутся с Вами для уточнения деталей заказа.</li>
						<li class="comment">Товары с пометкой &laquo;Под заказ&raquo; будут доставлены в течении десяти рабочих дней после оплаты заказа.</li>
					</ul>
				</div>

			</div>



			<div class="clear"></div>

			<div id="footer"><p>Copyright &copy; Alexander Dolgov<br>Все права защищены.</p></div>
		</div>
	</div>

	<script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	  ga('create', 'UA-64303813-1', 'auto');
	  ga('send', 'pageview');

	</script>


	</body>
</html>