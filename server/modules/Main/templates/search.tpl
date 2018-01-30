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
				<div class="lightBox"><div class="title"><b>Поиск</b></div></div>
				{%FN::mc_searchForm%}
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