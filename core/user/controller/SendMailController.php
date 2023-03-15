<?php

namespace core\user\controller;

use core\user\model\Model;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Выпуск №152 | Пользовательская часть | отправка уведомлений на email

class SendMailController extends BaseUser
{

	private $_body = '';
	private $_ErrorInfo = '';


	protected function inputData()
	{

		parent::inputData();
	}


	public function setMailBody($body)
	{

		if (is_array($body)) {

			$body = implode("\n", $body);
		}

		$this->_body .= $body;

		return $this;
	}

	public function send($email = null, $subject = null)
	{
		!$this->model && $this->model = Model::instance();

		$to = [];

		if (!$this->set) {

			$this->set = $this->model->get('settings', [
				'order' => ['id'], // сортируем по полю: id
				'limit' => 1
			]);

			$this->set && $to[] = $this->set[0]['email'];
		}

		if ($email) {

			$to[] = $email;
		}


		$mail = new PHPMailer(true);

		try {
			//Server settings
			//$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
			$mail->isSMTP();                                            //Send using SMTP
			$mail->Host       = 'smtp.mail.ru';                     //Set the SMTP server to send through
			$mail->SMTPAuth   = true;                                   //Enable SMTP authentication
			$mail->Username   = 'sereja-suvorov-1979@mail.ru';                     //SMTP username
			$mail->Password   = 'zu9aDdrrty7xiWfaY3JA';                               //SMTP password
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
			$mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

			//Recipients
			$mail->setFrom('sereja-suvorov-1979@mail.ru', 'Заявка с интернет-магазина ' . $_SERVER['HTTP_HOST']);
			//$mail->addAddress('joe@example.net', 'Joe User');     //Add a recipient

			foreach ($to as $adress) {

				$mail->addAddress($adress);               //Name is optional
			}


			$mail->addReplyTo('sereja-suvorov-1979@mail.ru'/* , 'Information' */);
			//$mail->addCC('cc@example.com');
			//$mail->addBCC('bcc@example.com');

			//Attachments
			//$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
			//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name



			//Content
			$mail->isHTML(true);
			$mail->CharSet = 'UTF-8';                                  //Set email format to HTML
			$mail->Subject = $subject ?: 'Заявка с интернет-магазина ' . $_SERVER['HTTP_HOST'];
			$mail->Body    = $this->_body;
			//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

			$mail->send();

			return true;
		} catch (Exception $e) {

			$this->_ErrorInfo = $mail->ErrorInfo;
		}

		return false;
	}

	public function getMailError()
	{

		return $this->_ErrorInfo;
	}
}
