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
	 * Добавление нового тикета
	 ******************************************************************/
	case 'ticket.new':
		$subject = trim($request->getStr('subject',''));
		$message = trim($request->getStr('message',''));
		if(empty($subject)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/tickets.ajax/subject_empty'));
		if(empty($message)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/tickets.ajax/message_empty'));

		$tickets = new Tickets();
		if(($ticket_id = $tickets->ticketNew(array(
			'subject'	=> nl2br(htmlspecialchars($subject)),
			'message'	=> nl2br(htmlspecialchars($message)),
			'status'	=> 1
		)))===false){
			return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/add'));
		}

		Ajax::_setData(array(
			'tickets' => $tickets->getTicketsList(0,$user->getClientID()),
			'ticket' => array(
				'info'	=> $tickets->getTicket($ticket_id, $user->getClientID()),
				'comments'	=> $tickets->getComments($ticket_id)
			)
		));
		return Ajax::_responseSuccess(Language::_get('general','ajax/success'),Language::_get('general','ajax/action_success'),'hint');

	break; #Добавление нового тикета



	/*******************************************************************
	 * Сведения о тикете
	 ******************************************************************/
	case 'ticket.info':

		$ticket_id = $request->getId('ticket_id', 0);
		if(empty($ticket_id)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/incorrect_request'));

		$tickets = new Tickets();

		$ticket = $tickets->getTicket($ticket_id, $user->getClientID());
		if(empty($ticket)){
			return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/tickets.ajax/not_found'));
		}

		Ajax::_setData(array(
			'ticket' => array(
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

		$ticket_id = $request->getId('ticket_id', 0);
		$comment = trim($request->getStr('comment',''));
		if(empty($ticket_id)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/incorrect_request'));
		if(empty($comment)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/tickets.ajax/message_empty'));

		$tickets = new Tickets();

		$ticket = $tickets->getTicket($ticket_id, $user->getClientID());
		if(empty($ticket)){
			return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/tickets.ajax/not_found'));
		}

		if($tickets->commentNew(array(
			'ticket_id'	=> $ticket_id,
			'message'	=> nl2br(htmlspecialchars($comment))
		))===false){
			return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/add'));
		}

		$tickets->ticketUpdate($ticket_id,array('status'=>1));

		Ajax::_setData(array(
			'tickets' => $tickets->getTicketsList(0,$user->getClientID()),
			'ticket' => array(
				'info'	=> $tickets->getTicket($ticket_id, $user->getClientID()),
				'comments'	=> $tickets->getComments($ticket_id)
			)
		));
		return Ajax::_responseSuccess(Language::_get('general','ajax/success'),Language::_get('general','ajax/action_success'),'hint');

	break; #Добавление комментария к тикету



	default:
	Ajax::_responseError('/main/ajax/tickets',Language::_get('general','errors/handler_not_found').': '.Request::_get('action'));
}
?>