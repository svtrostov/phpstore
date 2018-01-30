<?php
/*==================================================================================================
Title	: Tickets AJAX
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$request_action = Request::_get('action');
$db = Database::getInstance('main');

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){



	/*******************************************************************
	 * Поиск тикетов
	 ******************************************************************/
	case 'tickets.search':

		$client_id = $request->getId('client_id', 0);
		$status = $request->getEnum('status', array('0','1','2'), 1);

		$tickets = new Tickets();

		$ajax->setData(array(
			'client_tickets'	=> $tickets->getTicketsList($status, $client_id)
		));

		return Ajax::_responseSuccess(Language::_get('general','ajax/success'),Language::_get('general','ajax/action_success'),'hint');
	break;



	/*******************************************************************
	 * Сведения о тикете
	 ******************************************************************/
	case 'ticket.info':

		$client_id = $request->getId('client_id', 0);
		$ticket_id = $request->getId('ticket_id', 0);
		if(empty($ticket_id)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/incorrect_request'));
		if(empty($client_id)) return Ajax::_responseError(Language::_get('general','errors/executing'),'Publisher ID is empty');
		if(!$client->clientExists($client_id, true)) return Ajax::_responseError(Language::_get('general','errors/executing'),'Publisher ID:'.$client_id.' not found');


		$tickets = new Tickets();

		$ticket = $tickets->getTicket($ticket_id, $client_id);
		if(empty($ticket)){
			return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/tickets.ajax/not_found'));
		}

		Ajax::_setData(array(
			'ticket_info' => array(
				'info'	=> $ticket,
				'comments'	=> $tickets->getComments($ticket_id)
			)
		));
		return Ajax::_responseSuccess(Language::_get('general','ajax/success'),Language::_get('general','ajax/action_success'),'hint');
	break;




	/*******************************************************************
	 * Добавление комментария к тикету
	 ******************************************************************/
	case 'comment.add':

		if(!$user->checkAccess('can_ticket_answer')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$client_id = $request->getId('client_id', 0);
		$ticket_id = $request->getId('ticket_id', 0);
		$returntickets = $request->getBool('returntickets', true);
		$comment = trim($request->getStr('comment',''));
		if(empty($ticket_id)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/incorrect_request'));
		if(empty($comment)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/tickets.ajax/message_empty'));
		if(empty($client_id)) return Ajax::_responseError(Language::_get('general','errors/executing'),'Publisher ID is empty');
		if(!$client->clientExists($client_id, true)) return Ajax::_responseError(Language::_get('general','errors/executing'),'Publisher ID:'.$client_id.' not found');

		$tickets = new Tickets();

		$ticket = $tickets->getTicket($ticket_id, $client_id);
		if(empty($ticket)){
			return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/tickets.ajax/not_found'));
		}

		if($tickets->commentNew(array(
			'ticket_id'	=> $ticket_id,
			'is_admin'	=> 1,
			'message'	=> nl2br(htmlspecialchars($comment))
		))===false){
			return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/add'));
		}

		$tickets->ticketUpdate($ticket_id,array('status'=>2));

		$result = array(
			'ticket_info' => array(
				'info'	=> $tickets->getTicket($ticket_id, $client_id),
				'comments'	=> $tickets->getComments($ticket_id)
			)
		);
		if($returntickets){
			$result['client_tickets'] = $tickets->getTicketsList(0,$client_id);
		}
		Ajax::_setData($result);
		return Ajax::_responseSuccess(Language::_get('general','ajax/success'),Language::_get('general','ajax/action_success'),'hint');

	break; #Добавление комментария к тикету



	default:
	Ajax::_responseError('/admin/ajax/tickets',Language::_get('general','errors/handler_not_found').': '.Request::_get('action'));
}
?>