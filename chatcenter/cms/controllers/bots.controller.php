<?php 

Class BotsController{

	static public function responseBots($bot,$getApiWS,$phone_message,$order_message,$idListMenu){

		/*=============================================
      	Traemos la plantilla Bot
      	=============================================*/

      	$url = "bots?linkTo=title_bot&equalTo=".$bot;
      	$method = "GET";
      	$fields = array();

      	$getBot = CurlController::request($url,$method,$fields);

      	if($getBot->status == 200){

      		$getBot = $getBot->results[0];

      		/*=============================================
			Plantilla de tipo texto
			=============================================*/

      		if($getBot->type_bot == "text"){

      			/*=============================================
				Enviamos el checkout
				=============================================*/

				if($bot == "checkout"){

					$url = "orders?linkTo=conversation_order&equalTo=".$idListMenu."&select=total_order";
					$method = "GET";
					$fields = array();

					$getOrder = CurlController::request($url,$method,$fields);

					if($getOrder->status == 200){
						
						$getBot->body_text_bot = $getBot->body_text_bot;
						$getBot->body_text_bot .= $getOrder->results[0]->total_order;


					}

				}

      			$json = '{
				  "messaging_product": "whatsapp",
				  "recipient_type": "individual",
				  "to": "'.$phone_message.'",
				  "type": "text",
				  "text": {
				    "preview_url": true,
				    "body": "'.str_replace(["\r", "\n"], '\n', trim(urldecode($getBot->body_text_bot))).'"
				  }
				}';

				$business_message = $getBot->body_text_bot;
				$template_message = '{"type":"bot","title":"'.$bot.'"}';
      		
      		}

      		/*=============================================
			Plantilla de tipo interactivo
			=============================================*/

			if($getBot->type_bot == "interactive"){

				$interactive_bot = $getBot->interactive_bot;

				/*=============================================
				Cuando construimos la lista del menú
				=============================================*/

				if($getBot->interactive_bot == "none"){

					$interactive_bot = "list";

					/*=============================================
					Traer Categorías y Productos
					=============================================*/

					$url = "relations?rel=products,categories&type=product,category&linkTo=id_category&equalTo=".$idListMenu;
					$method = "GET";
					$fields = array();

					$getMenu = CurlController::request($url,$method,$fields);

					if($getMenu->status == 200){

						$menu = $getMenu->results;
						// echo '<pre>'; print_r($menu); echo '</pre>';
						// return;
						
						$getBot->header_text_bot = $menu[0]->title_category;

						$action = '"action": {
						      "button":"Ver opciones",
						      "sections": [
						        {
						          "title": "'.urldecode($menu[0]->title_category).'",
						          "rows": [';

								foreach ($menu as $key => $value) {
									
									$action .= '{
							              "id": "'.$value->code_product.'",
							              "title": "'.urldecode($value->title_product).'",
							              "description": "$'.$value->price_product.' USD"
							            },';
								}

								$action = mb_substr($action,0,-1);		
	     
								$action .= ']
						            
						        }
						      ]
						    }
						  }
						}';

					}
					
				
				}

				/*=============================================
				Cuando construimos la órden
				=============================================*/

				if($bot == "confirmation"){

					$getBot->body_text_bot = "";
					$totalOrder = 0;
					$totalMessages = 0;
					$order = new stdClass();
					$order->conversation_order = $idListMenu;
					$order->products_order = "";
					$order->contact_order = $phone_message;
					$order->status_order = "Pendiente";
					$order->date_created_order = date("Y-m-d");

					$url = "messages?linkTo=id_conversation_message,type_message&equalTo=".$idListMenu.",client";
					$method = "GET";
					$fields = array();

					$getMessages = CurlController::request($url,$method,$fields);

					if($getMessages->status == 200){
						
						$messages = $getMessages->results;

						foreach ($messages as $key => $value) {

							$totalMessages++;

							/*=============================================
                  			Encontrar coincidencia de Código de productos
                  			=============================================*/
							
							if(str_contains($value->client_message, 'sku')){
								
								/*=============================================
								Buscamos productos con sus respectivos precios
								=============================================*/

								$url = "products?linkTo=code_product&equalTo=".json_decode($value->client_message)->id;

								$getProduct = CurlController::request($url,$method,$fields);
								
								if($getProduct->status == 200){

									$totalOrder += $getProduct->results[0]->price_product;

									$getBot->body_text_bot .= urldecode($getProduct->results[0]->title_product)." - \$".$getProduct->results[0]->price_product." USD\n";

									$order->products_order .= urldecode($getProduct->results[0]->title_product)." - \$".$getProduct->results[0]->price_product." USD\n";
								}	

							}

							/*=============================================
                  			Datos del nombre
                  			=============================================*/

                  			if($value->template_message == '{"type":"bot","title":"name"}'){

                  				$getBot->body_text_bot .= "\n*Nombre:* ".$value->client_message."\n";
                  				$order->name_order = $value->client_message;

                  			
                  			}

                  			/*=============================================
                  			Datos del celular
                  			=============================================*/

                  			if($value->template_message == '{"type":"bot","title":"phone"}'){

                  				$getBot->body_text_bot .= "*Celular:* ".$value->client_message."\n";
                  				$order->phone_order = $value->client_message;
                  			
                  			}

                  			/*=============================================
                  			Datos del correo electrónico
                  			=============================================*/

                  			if($value->template_message == '{"type":"bot","title":"email"}'){

                  				$getBot->body_text_bot .= "*Email:* ".$value->client_message."\n";
                  				$order->email_order = $value->client_message;
                  			
                  			}

                  			/*=============================================
                  			Datos de dirección
                  			=============================================*/

                  			if($value->template_message == '{"type":"bot","title":"address"}'){

                  				$getBot->body_text_bot .= "*Dirección:* ".$value->client_message."\n";
                  				$order->address_order = $value->client_message;
                  			
                  			}

                  			if($totalMessages == count($messages)){

                  				$getBot->body_text_bot .= "\n*Total Pedido: \$".$totalOrder." USD*\n";
                  				$order->total_order = $totalOrder;
                  			}
						}

					}

					/*=============================================
					Guardar orden en base de datos
					=============================================*/

					$url = "orders?token=no&except=id_order";
					$method = "POST";

					$createOrder = CurlController::request($url,$method,(array)$order);
					// echo '<pre>$createOrder '; print_r($createOrder); echo '</pre>';
		
				}

				/*=============================================
				Creamos el JSON para WhatsApp
				=============================================*/

				$json = '{
						  "messaging_product": "whatsapp",
						  "recipient_type": "individual",
						  "to": "'.$phone_message.'",
						  "type": "interactive",
						  "interactive": {
						    "type": "'.$interactive_bot.'",';

				$header = '';

				if(!empty($getBot->header_text_bot)){

					$header = '"header": {
								  "type": "text",
								  "text": "'.mb_substr(trim(urldecode($getBot->header_text_bot)),0,60).'"
								},';
				}

				if(!empty($getBot->header_image_bot)){

					$header = '"header": {
								  "type": "image",
								  "image": {
								    "link": "'.urldecode($getBot->header_image_bot).'"
								  }
								},';
				}

				if(!empty($getBot->header_video_bot)){

					$header = '"header": {
								  "type": "video",
								  "video": {
								    "link": "'.urldecode($getBot->header_video_bot).'"
								  }
								},';
				}


				$json .= $header.'
						    "body": {
						      "text": "'.str_replace(["\r", "\n"], '\n', trim(urldecode($getBot->body_text_bot))).'"
						    },
						    "footer": {
						      "text": "'.mb_substr(trim(urldecode($getBot->footer_text_bot)),0,60).'"
						    },';

				if($getBot->interactive_bot == "button"){

					$json .= '"action": {
						      "buttons": [';

					// echo '<pre>$getBot->buttons_bot '; print_r(json_decode(urldecode($getBot->buttons_bot))); echo '</pre>';

					if(!empty($getBot->buttons_bot)){

						foreach (json_decode(urldecode($getBot->buttons_bot)) as $key => $value) {
							
							$json .= '{
						          "type": "reply",
						          "reply": {
						            "id": "'.$key.'",
						            "title": "'.mb_substr($value,0,24).'"
						          }
						        },';
						}

					}

					$json = mb_substr($json,0,-1);		
	     
					$json .= ']
						    }
						  }
						}';

				}

				if($getBot->interactive_bot == "list"){
						    
					$json .= '"action": {
						      "button":"Ver opciones",
						      "sections": [
						        {
						          "title": "'.$getBot->title_list_bot.'",
						          "rows": [';

					          if(!empty($getBot->list_bot)){

									foreach (json_decode(urldecode($getBot->list_bot)) as $key => $value) {
										
										$json .= '{
								              "id": "'.$value->id.'",
								              "title": "'.$value->title.'",
								              "description": "'.$value->description.'"
								            },';
									}

								}

								$json = mb_substr($json,0,-1);		
	     
								$json .= ']
						            
						        }
						      ]
						    }
						  }
						}';

				}

				if($getBot->interactive_bot == "none"){

					$json .= $action;

				}

				$business_message = urldecode($getBot->body_text_bot);
				$template_message = '{"type":"bot","title":"'.$bot.'"}';


			}


			// echo '<pre>$json '; print_r($json); echo '</pre>';

			// return;
      	
      	}

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
			"initial_message" => 1,
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

?>