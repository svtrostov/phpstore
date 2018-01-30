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
					<form method="post" name="forma_search" action="/search/" onSubmit="return SearchChek()">
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

			{%FN::mc_getUserStockgallery%}


			<div class="greeting">
				<strong>Добро <span class="greetUser">пожаловать!</span></strong>
			</div>
			<div class="welcome">
			Наш магазин — место, где каждый может найти полезную информацию об интересующих товарах, сравнить цены, получить консультацию специалиста.
			</div>

			<div class="centralArea">
				{%FN::mc_getNews,3%}

				<div class="lightBox"><div class="title">Популярные товары</div></div>
				<table cellspacing="1 cellpadding="1" border="1" class="lightGrid"><tbody>
				<tr>
					<td class="bg_printer" style="width:195px;">
						<div class="box">
							<div class="title">Оргтехника</div>
							<ul>
								<li><a href="/shop/CID_369.html">МФУ</a></li>
								<li><a href="/shop/CID_368.html">Принтеры</a></li>
								<li><a href="/shop/CID_884.html">Факсы</a></li>
							</ul>
						</div>
					</td>
					<td class="bg_cartridge" style="width:195px;">
						<div class="box">
							<div class="title">Картриджи</div>
							<ul>
								<li><a href="/shop/CID_926.html">Лазерные</a></li>
								<li><a href="/shop/CID_929.html">Матричные</a></li>
								<li><a href="/shop/CID_927.html">Струйные</a></li>
							</ul>
						</div>
					</td>
					<td class="bg_spareparts" style="width:195px;">
						<div class="box">
							<div class="title">Запчасти</div>
							<ul>
								<li><a href="/shop/CID_813.html">Крышки/корпуса</a></li>
								<li><a href="/shop/CID_806.html">Ремонтные комплекты</a></li>
							</ul>
						</div>
					</td>
					<td class="bg_toner" style="width:195px;">
						<div class="box">
							<div class="title">Тонеры</div>
							<ul>
								<li><a href="/shop/CID_872.html">Тонеры</a></li>
								<li><a href="/shop/CID_869.html">Краски</a></li>
								
							</ul>
						</div>
					</td>
				</tr>
				</tbody></table>
			</div>

			<div class="rightArea">
				<div class="darkBox">
					<div class="title">Кабинет клиента</div>
					{%FN::mc_getUserAreaMenu%}
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