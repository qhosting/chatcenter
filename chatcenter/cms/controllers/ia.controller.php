<?php 

Class IAController{

	static public function responseIA($client_message,$getApiWS,$phone_message,$order_message){

		/*=============================================
		Buscamos el prompt
		=============================================*/
		
		$url = "prompts?linkTo=status_prompt&equalTo=1&orderBy=id_prompt&orderMode=DESC";
		$method = "GET";
		$fields = array();

		$getPrompt = CurlController::request($url,$method,$fields);

		if($getPrompt->status == 200){

			$prompt = $getPrompt->results[0];

			if($order_message == 0){

				$messages = '[
						      {
						        "role": "system",
						        "content": "'.str_replace(["\r", "\n"], '\n', trim(strip_tags(urldecode($prompt->content_prompt)))).'"
						      },
						      {
						        "role": "user",
						        "content": "'.$client_message.'"
						      }
						    ]';

			}else{

				/*=============================================
         		Traer conversaciones anteriores
         		=============================================*/

				$url = "messages?linkTo=phone_message&equalTo=".$phone_message."&select=client_message,business_message,type_message";
				$method = "GET";
				$fields = array();

				$getMessages = CurlController::request($url,$method,$fields);

				if($getMessages->status == 200){

					$messages = '[
							      {
							        "role": "system",
							        "content": "'.str_replace(["\r", "\n"], '\n', trim(strip_tags(urldecode($prompt->content_prompt)))).'"
							      },';

					foreach ($getMessages->results as $key => $value) {
						
						if($value->type_message == "client"){

							$messages .= '{
									        "role": "user",
									        "content": "'.str_replace(["\r", "\n"], '\n', trim($value->client_message)).'"
									      },';
						}

						if($value->type_message == "business"){

							$messages .= '{
									        "role": "system",
									        "content": "'.str_replace(["\r", "\n"], '\n', trim($value->business_message)).'"
									      },';
						}
					
					}

					$messages = substr($messages,0,-1);

					$messages .= ']';
				
				}

			}

			// echo '<pre>$messages '; print_r($messages); echo '</pre>';

			// return;

			/*=============================================
         	Traer info del SuperAdministrador
         	=============================================*/

         	$url = "admins?linkTo=rol_admin&equalTo=superadmin&select=chatgpt_admin";
         	$getAdmin = CurlController::request($url,$method,$fields);

         	if($getAdmin->status == 200){

         		$admin = $getAdmin->results[0];

         		$token = json_decode($admin->chatgpt_admin)->token;
         		$org = json_decode($admin->chatgpt_admin)->org;

         		$chatGPT = CurlController::chatGPT($messages,$token,$org);
         		// echo '<pre>$chatGPT '; print_r($chatGPT); echo '</pre>';
         		// return;

         		/*=============================================
         		Plantilla de tipo texto que debemos enviar a WS
         		=============================================*/

         		$json = '{
				  "messaging_product": "whatsapp",
				  "recipient_type": "individual",
				  "to": "'.$phone_message.'",
				  "type": "text",
				  "text": {
				    "preview_url": true,
				    "body": "'.str_replace(["\r", "\n"], '\n', trim(urldecode($chatGPT))).'"
				  }
				}';

				$business_message = $chatGPT;
				$template_message = '{"type":"ia","title":""}';

				/*=============================================
				Llevar el orden de los mensajes
				=============================================*/

				$url = "messages?linkTo=phone_message&equalTo=".$phone_message."&startAt=0&endAt=1&orderBy=id_message&orderMode=DESC";
				$method = "GET";
				$fields = array();

				$getMessages = CurlController::request($url,$method,$fields);
				
				if($getMessages->status == 200){

					$order_message = $getMessages->results[0]->order_message + 1;
				
				}

				/*=============================================
		      	Guardamos la respuesta del negocio
		      	=============================================*/

				$url = "messages?token=no&except=id_message";
				$method = "POST";
				$fields = array(
					"type_message" => "business",
					"id_whatsapp_message" => $getApiWS->id_whatsapp,
					"business_message" => $business_message,
					"phone_message" => $phone_message,
					"order_message" => $order_message,
					"template_message" => $template_message,
					"date_created_message" => date("Y-m-d")
				);

				$saveMessage = CurlController::request($url,$method,$fields);

				if($saveMessage->status == 200){

					/*=============================================
		      		Enviamos datos JSON a la API de WhatsApp
		      		=============================================*/

		      		$apiWS = CurlController::apiWS($getApiWS,$json);
		      		echo '<pre>$apiWS '; print_r($apiWS); echo '</pre>';
				
				}

         	}
		}

	}

}