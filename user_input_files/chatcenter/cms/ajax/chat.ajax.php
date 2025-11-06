<?php 

define('DIR', __DIR__);

ini_set("display_errors",1);
ini_set("log_errors",1);
ini_set("error_log", DIR."/php_error_log");

date_default_timezone_set("America/Bogota");

require_once "../controllers/template.controller.php";
require_once "../controllers/curl.controller.php";

class ChatAjax{

	/*=============================================
	Revisar si hay mensajes nuevos en el chat
	=============================================*/

	public $phoneMessage;
	public $orderMessage;

	public function ajaxLastMessage(){

		/*=============================================
		Revisar si hay nuevas respuestas
		=============================================*/

		$url = "messages?linkTo=phone_message,date_created_message,initial_message&equalTo=".$this->phoneMessage.",".date("Y-m-d").",1&startAt=".$this->orderMessage."&endAt=2";
		$method = "GET";
		$fields = array();

		$getMessages = CurlController::request($url,$method,$fields);
		
		if($getMessages->status == 200){

			/*=============================================
			Cuando solo existe un registro
			=============================================*/

			if($getMessages->total == 1 && str_contains($getMessages->results[0]->template_message, '"type":"manual"') || 
			   $getMessages->total == 1 && $getMessages->results[0]->template_message == null){

				$getMessages->results[1] = $getMessages->results[0];
				$getMessages->total = 2;
			}

			if($getMessages->total > 1){

				/*=============================================
				Validar si el mensaje es del cliente
				=============================================*/

				if($getMessages->results[1]->type_message == "client"){

					/*=============================================
					Traemos la info API WS
					=============================================*/

					$url = "whatsapps?linkTo=status_whatsapp&equalTo=1";
					$method = "GET";
					$fields = array();

					$getApiWS = CurlController::request($url,$method,$fields);

					if($getApiWS->status == 200){

					  $getApiWS = $getApiWS->results[0];

					}

					/*=============================================
					preguntamos si viene una respuesta interactivas
					=============================================*/

					if(isset(json_decode($getMessages->results[1]->client_message)->id) && isset(json_decode($getMessages->results[1]->client_message)->text)){

						$getMessages->results[1]->client_message = json_decode($getMessages->results[1]->client_message)->text;

					/*=============================================
					preguntamos si viene una respuesta de imagen
					=============================================*/

					}else if(isset(json_decode($getMessages->results[1]->client_message)->type) && json_decode($getMessages->results[1]->client_message)->type == "image"){

						/*=============================================
						Traer foto con ID a través de la API de WS
						=============================================*/

						$archive = CurlController::apiWS($getApiWS,json_decode($getMessages->results[1]->client_message)->id."_ajax");
						
						$getMessages->results[1]->client_message = '<a href="'.$archive.'" target="_blank"><img src="'.$archive.'" class="img-fluid rounded"></a><br>'.urldecode(json_decode($getMessages->results[1]->client_message)->caption);

					/*=============================================
					preguntamos si viene una respuesta de video
					=============================================*/

					}else if(isset(json_decode($getMessages->results[1]->client_message)->type) && json_decode($getMessages->results[1]->client_message)->type == "video"){

						/*=============================================
						Traer foto con ID a través de la API de WS
						=============================================*/

						$archive = CurlController::apiWS($getApiWS,json_decode($getMessages->results[1]->client_message)->id."_ajax");
						
						$getMessages->results[1]->client_message = '<a href="'.$archive.'" target="_blank"><video src="'.$archive.'" controls class="img-fluid rounded"></video></a><br>'.urldecode(json_decode($getMessages->results[1]->client_message)->caption);


					/*=============================================
					preguntamos si viene una respuesta de video
					=============================================*/

					}else if(isset(json_decode($getMessages->results[1]->client_message)->type) && json_decode($getMessages->results[1]->client_message)->type == "audio"){

						/*=============================================
						Traer foto con ID a través de la API de WS
						=============================================*/

						$archive = CurlController::apiWS($getApiWS,json_decode($getMessages->results[1]->client_message)->id."_ajax");
						
						$getMessages->results[1]->client_message = '<a href="'.$archive.'" target="_blank"><video src="'.$archive.'" controls class="img-fluid rounded"></video></a>';

					/*=============================================
					preguntamos si viene una respuesta de documento
					=============================================*/

					}else if(isset(json_decode($getMessages->results[1]->client_message)->type) && json_decode($getMessages->results[1]->client_message)->type == "document"){

						/*=============================================
						Traer foto con ID a través de la API de WS
						=============================================*/

						$archive = CurlController::apiWS($getApiWS,json_decode($getMessages->results[1]->client_message)->id."_ajax");
						
						$getMessages->results[1]->client_message = '<a href="'.$archive.'" target="_blank"><img src="/views/assets/img/pdf.jpeg" class="img-fluid rounded"></a><br>'.urldecode(json_decode($getMessages->results[1]->client_message)->caption);

					}else{

						$getMessages->results[1]->client_message = $getMessages->results[1]->client_message;
					}

					$message = '<div class="msg user">
									<div class="pt-2" style="max-width:300px; overflow-wrap: break-word;">'.$getMessages->results[1]->client_message.'</div><br>
									<span class="small text-muted float-end">
										'.TemplateController::formatDate(6,$getMessages->results[1]->date_updated_message).'	
									</span>
								</div>';

					$response = array(
						"type" => "client",
						"message" => base64_encode($message),
						"lastOrder" => $getMessages->results[1]->order_message
					);

					echo json_encode($response);

				}

				/*=============================================
				Validar si el mensaje es del negocio
				=============================================*/

				if($getMessages->results[1]->type_message == "business"){

					$business_message = "";

					/*=============================================
					preguntamos si viene una respuesta de bot
					=============================================*/

					if(isset(json_decode($getMessages->results[1]->template_message)->type) && json_decode($getMessages->results[1]->template_message)->type == "bot" ||
				    isset(json_decode($getMessages->results[1]->template_message)->type) && json_decode($getMessages->results[1]->template_message)->type == "ia"){

						$url = "bots?linkTo=title_bot&equalTo=".json_decode($getMessages->results[1]->template_message)->title;
						$method = "GET";
						$fields = array();

						$getBot = CurlController::request($url,$method,$fields);

						if($getBot->status == 200){

							$bot = $getBot->results[0];

							/*=============================================
							Si hay cabecera de imagen
							=============================================*/

							if(!empty($bot->header_image_bot)){

								$business_message .= '<a href="'.urldecode($bot->header_image_bot).'" target="_blank"><img src="'.urldecode($bot->header_image_bot).'" class="img-fluid rounded"></a>';
							}

							/*=============================================
							Si hay cabecera de texto
							=============================================*/

							if(!empty($bot->header_text_bot)){

								$business_message .= '<div><strong>'.str_replace("\\n","<br>", urldecode($bot->header_text_bot)).'</strong></div>';

							}

							/*=============================================
							Si hay cabecera de video
							=============================================*/

							if(!empty($bot->header_video_bot)){

								$business_message .= '<video controls src="'.urldecode($bot->header_video_bot).'" class="img-fluid rounded"></video>';
							}

							/*=============================================
							El cuerpo del mensaje
							=============================================*/

							if(!empty($bot->body_text_bot)){

								$business_message .= str_replace("\\n","<br>", urldecode($bot->body_text_bot));

							}else{

								$getMessages->results[1]->business_message = str_replace(["\r", "\n" ], '\n', $getMessages->results[1]->business_message);

								$business_message .= str_replace("\\n","<br>", urldecode($getMessages->results[1]->business_message));
							}

							/*=============================================
							Si hay footer
							=============================================*/

							if(!empty($bot->footer_text_bot)){


								$business_message .= '<hr><div><small>'.str_replace("\\n","<br>", urldecode($bot->footer_text_bot)).'</small></div>';

							}

							/*=============================================
							Si hay botones
							=============================================*/

							if($bot->type_bot == "interactive" && $bot->interactive_bot == "button"){

								foreach (json_decode(urldecode($bot->buttons_bot)) as $index => $item) {

									$business_message .= '<div class="small mt-2 border-top p-2 w-100 text-start bg-light"><i class="bi bi-arrow-90deg-left"></i> '.$item.'</div>';
									
								}
							}

							/*=============================================
							Si hay lista
							=============================================*/

							if($bot->type_bot == "interactive" && $bot->interactive_bot == "list"){

								foreach (json_decode(urldecode($bot->list_bot)) as $index => $item) {

									$business_message .= '<div class="small mt-2 border-top p-2 w-100 text-start bg-light"><strong>'.$item->title.'</strong><br>'.$item->description.'</div>';
									
								}
							}

							/*=============================================
							Si hay lista de menú
							=============================================*/

							if($bot->type_bot == "interactive" && $bot->interactive_bot == "none"){

								$url = "messages?linkTo=order_message,phone_message&equalTo=".($getMessages->results[1]->order_message-1).",".$getMessages->results[1]->phone_message."&select=client_message";
								$method = "GET";
								$fields = array();

								$getMess = CurlController::request($url,$method,$fields);

								if($getMess->status == 200){
									
									$getMess = json_decode($getMess->results[0]->client_message)->id;

								}

								/*=============================================
								Traer Categorías y Productos
								=============================================*/

								$url = "relations?rel=products,categories&type=product,category&linkTo=id_category&equalTo=".$getMess;
								$method = "GET";
								$fields = array();

								$getMenu = CurlController::request($url,$method,$fields);

								if($getMenu->status == 200){

									$menu = $getMenu->results;
									
									foreach ($menu as $index => $item) {
										
										$business_message .= '<div class="small mt-2 border-top p-2 w-100 text-start bg-light"><strong>'.urldecode($item->title_product).'</strong><br>$'.$item->price_product.' USD</div>';
									}

								}
							}

						}else{

							$getMessages->results[1]->business_message = str_replace(["\r", "\n" ], '\n', $getMessages->results[1]->business_message);

							$business_message .= str_replace("\\n","<br>", urldecode($getMessages->results[1]->business_message));
						}

					/*=============================================
					preguntamos si viene una respuesta de imagen
					=============================================*/

					}else if(isset(json_decode($getMessages->results[1]->business_message)->type) && json_decode($getMessages->results[1]->business_message)->type == "image"){

						$business_message .= '<a href="'.urldecode(json_decode($getMessages->results[1]->business_message)->title).'" target="_blank"><img src="'.urldecode(json_decode($getMessages->results[1]->business_message)->title).'" class="img-fluid rounded"></a>';

					/*=============================================
					preguntamos si viene una respuesta de video o audio
					=============================================*/

					}else if(isset(json_decode($getMessages->results[1]->business_message)->type) && json_decode($getMessages->results[1]->business_message)->type == "video" ||
								isset(json_decode($getMessages->results[1]->business_message)->type) && json_decode($getMessages->results[1]->business_message)->type == "audio"){

						$business_message .= '<a href="'.urldecode(json_decode($getMessages->results[1]->business_message)->title).'" target="_blank"><video controls src="'.urldecode(json_decode($getMessages->results[1]->business_message)->title).'" class="img-fluid rounded"></video></a>';

					/*=============================================
					preguntamos si viene una respuesta de documento
					=============================================*/

					}else if(isset(json_decode($getMessages->results[1]->business_message)->type) && json_decode($getMessages->results[1]->business_message)->type == "document"){

						$business_message .= '<a href="'.urldecode(json_decode($getMessages->results[1]->business_message)->title).'" target="_blank"><img src="/views/assets/img/pdf.jpeg" class="img-fluid rounded"></a>';


					}else{

						/*=============================================
						preguntamos si viene enlaces en una respuesta
						=============================================*/

						if(str_contains($getMessages->results[1]->business_message, 'http')){

							$business_message = '<a href="'.$getMessages->results[1]->business_message.'" target="_blank">'.substr($getMessages->results[1]->business_message,0,35).'...</a>';
						
						}else{

							$business_message = $getMessages->results[1]->business_message;
						
						}
					}

					$message = '<div class="msg bot">
									<div class="pt-2" style="max-width:300px; overflow-wrap: break-word;">'.preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', $business_message).'</div><br>
									<span class="small text-muted float-end">
										'.TemplateController::formatDate(6,$getMessages->results[1]->date_updated_message).'	
									</span>
								</div>';

					$response = array(
						"type" => "business",
						"message" => base64_encode($message),
						"lastOrder" => $getMessages->results[1]->order_message
					);

					echo json_encode($response);


				}
			}
		}

	}

	/*=============================================
	Conversaciones manuales por parte del negocio
	=============================================*/

	public $conversation;
	public $phone;
	public $token;

	public function ajaxSendMessage(){

		$conversation = explode(".",$this->conversation);

		/*=============================================
		Cuando el negocio envía una imagen manualmente
		=============================================*/

		if(end($conversation) == "jpg" ||
		   end($conversation) == "png" ||
		   end($conversation) == "gif" ||
		   end($conversation) == "jpeg" ||
		   end($conversation) == "webp"
		){

			/*=============================================
			JSON para enviar a WS
			=============================================*/

			$json = '{
					  "messaging_product": "whatsapp",
					  "recipient_type": "individual",
					  "to": "'.$this->phone.'",
					  "type": "image",
					  "image": {
					    "link": "'.$this->conversation.'"
					  }
					}';

			$business_message = '{"type":"image","title":"'.$this->conversation.'"}';
			$template_message = '{"type":"image","title":""}';

		/*=============================================
		Cuando el negocio envía una video manualmente
		=============================================*/

		}else if(end($conversation) == "mp4" ){

			/*=============================================
			JSON para enviar a WS
			=============================================*/

			$json = '{
					  "messaging_product": "whatsapp",
					  "recipient_type": "individual",
					  "to": "'.$this->phone.'",
					  "type": "video",
					  "video": {
					    "link": "'.$this->conversation.'"
					  }
					}';

			$business_message = '{"type":"video","title":"'.$this->conversation.'"}';
			$template_message = '{"type":"video","title":""}';

		/*=============================================
		Cuando el negocio envía un audio manualmente
		=============================================*/

		}else if(end($conversation) == "mp3" ){

			/*=============================================
			JSON para enviar a WS
			=============================================*/

			$json = '{
					  "messaging_product": "whatsapp",
					  "recipient_type": "individual",
					  "to": "'.$this->phone.'",
					  "type": "audio",
					  "audio": {
					    "link": "'.$this->conversation.'"
					  }
					}';

			$business_message = '{"type":"audio","title":"'.$this->conversation.'"}';
			$template_message = '{"type":"audio","title":""}';

		/*=============================================
		Cuando el negocio envía un documento PDF manualmente
		=============================================*/

		}else if(end($conversation) == "pdf" && end($conversation) == "PDF"){

			/*=============================================
			JSON para enviar a WS
			=============================================*/

			$json = '{
					  "messaging_product": "whatsapp",
					  "recipient_type": "individual",
					  "to": "'.$this->phone.'",
					  "type": "document",
					  "document": {
					    "link": "'.$this->conversation.'"
					  }
					}';

			$business_message = '{"type":"document","title":"'.$this->conversation.'"}';
			$template_message = '{"type":"document","title":""}';

		}else{

			/*=============================================
			JSON para enviar a WS
			=============================================*/

			$json = '{
					  "messaging_product": "whatsapp",
					  "recipient_type": "individual",
					  "to": "'.$this->phone.'",
					  "type": "text",
					  "text": {
					    "preview_url": true,
					    "body": "'.$this->conversation.'"
					  }
					}';

			$business_message = $this->conversation;
			$template_message = '{"type":"manual","title":""}';

		}

		/*=============================================
		Llevar el orden de los mensajes
		=============================================*/

		$url = "messages?linkTo=phone_message&equalTo=".$this->phone."&startAt=0&endAt=1&orderBy=id_message&orderMode=DESC";
		$method = "GET";
		$fields = array(); 

		$getMessages = CurlController::request($url,$method,$fields);
		
		if($getMessages->status == 200){

			if($getMessages->results[0]->expiration_message < date("Y-m-d H:i:s")){

				$order_message = 0;
			
			}else{

				$order_message = $getMessages->results[0]->order_message + 1;
			}
		
		}

		/*=============================================
		Capturar API WS activa
		=============================================*/
		
		$url = "whatsapps?linkTo=status_whatsapp&equalTo=1&orderBy=id_whatsapp&orderMode=DESC&startAt=0&endAt=1";

		$getApiWS = CurlController::request($url,$method,$fields);

		if($getApiWS->status == 200){

			$getApiWS = $getApiWS->results[0];
		}


		/*=============================================
      	Guardamos la respuesta del negocio
      	=============================================*/

		$url = "messages?token=".$this->token."&table=admins&suffix=admin";
		$method = "POST";
		$fields = array(
			"type_message" => "business",
			"id_whatsapp_message" => $getApiWS->id_whatsapp,
			"business_message" => $business_message,
			"phone_message" => $this->phone,
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

	/*=============================================
	Traer último chat
	=============================================*/

	public $lastIdMessage;
	public $borderChat;

	public function ajaxLastChat(){

		/*=============================================
		Validamos que el último ID no esté en la vista
		=============================================*/
		$url = "messages?orderBy=id_message&orderMode=DESC&startAt=0&endAt=1&select=id_message";
		$method = "GET";
		$fields = array();

		$getMessage = CurlController::request($url,$method,$fields);

		if($getMessage->status == 200){

			if($this->lastIdMessage == $getMessage->results[0]->id_message){

				// echo "ya está en la vista";
				return;
			}else{

				$lastIdMessage = $getMessage->results[0]->id_message;
				// echo '<pre>'; print_r($lastIdMessage); echo '</pre>';
			}
		}

		/*=============================================
		Traemos los contactos
		=============================================*/

		$url = "contacts?orderBy=date_updated_contact&orderMode=DESC&startAt=0&endAt=50";
		$method = "GET";
		$fields = array();

		$getContacts = CurlController::request($url,$method,$fields);

		if($getContacts->status == 200){

		  $contacts = $getContacts->results;

		  foreach ($contacts as $key => $value) {

		  	/*=============================================
  		  	Traemos la conversación más reciente
  		  	=============================================*/

		  	$url = "messages?linkTo=phone_message&equalTo=".$value->phone_contact."&orderBy=id_message&orderMode=DESC&startAt=0&endAt=1";

		  	$getMessages = CurlController::request($url,$method,$fields);

		  	if($getMessages->status == 200){

			  $messages = $getMessages->results[0];

		  	}else{

		  	  $messages = null;
		  	}

		  	/*=============================================
		  	Todos los Mensajes
		  	=============================================*/

		  	if (!empty($messages)){

            	$date_updated_message = $messages->date_updated_message;

                if ($messages->type_message == "business"){

                	$textMessage = "...";
                	$bellNotification = "";

                }else{

                	$textMessage = mb_substr($messages->client_message,0,17)."...";
                	$bellNotification = "ok";

                	if($key == 0){

                		$textSuccess = "text-success";
                	
                	}else{

                		$textSuccess = "";
                	}
                    
                }

            /*=============================================
		  	Cuando no hay mensajes
		  	=============================================*/

            }else{

              $date_updated_message = $value->date_updated_contact;
              $textMessage = "";
              $bellNotification = "";
            
            }


		  	$chats = '<a href="/chat?phone='.$value->phone_contact.'_'.$key.'" class="text-dark">';

		  			if($this->borderChat != "" && $this->borderChat == $value->phone_contact){

		  				$chats .= '<div class="contact-item pb-0" style="border:1px solid #090">';	
		  			
		  			}else{

		  				$chats .= '<div class="contact-item pb-0">';	
		  			}

	
			        $chats .= '<div class="d-flex justify-content-between pb-0"  onclick="alertClick(1)">

			              <div>
			                <span class="small">';

			                if($bellNotification == "ok"){

			                	$chats .= '<i class="bi bi-bell-fill '.$textSuccess.'"></i>';
			                }

			                $chats .= '<strong>';

			                	if (!empty($value->name_contact)){

			                        $chats .=  substr($value->name_contact,0,15).'...';

			                   }else{ 
			             
			                      $chats .= '+'.mb_substr($value->phone_contact,0,2)." ".
			                                    mb_substr($value->phone_contact,2,3)." ".
			                                    mb_substr($value->phone_contact,5,7);  

			                   }

			                   $chats .= '</strong>
			                </span>
			              </div>

			              <div>';

			            if (date("Y-m-d") == TemplateController::formatDate(8,$date_updated_message)){
			                    
			                $chats .= '<span class="small">'.TemplateController::formatDate(6,$date_updated_message).'</span>';

			            }else{

			                $chats .= '<span class="small">'.TemplateController::formatDate(5,$date_updated_message).'</span>';
			            }
			      
			             $chats .= '</div>

			            </div>

			            <div class="d-flex justify-content-between pb-0">
			              
			              <div>
			   
			                <p class="small">'.$textMessage.'</p>
			                 
			              </div>

			              <div class="custom-control custom-checkbox">
			                <input 
			                type="checkbox" 
			                class="custom-control-input mt-2 form-check-input changeAI" 
			                id="customCheckList_'.$value->id_contact.'" 
			                idContact="'.$value->id_contact.'"
			                name="example1"';
			               
			               if ($value->ai_contact == 1){

			               	$chats .= ' checked ';

			               }

			               $chats .= 'style="width:18px !important; height:18px !important">

			                <label class="custom-control-label mb-1" for="customCheck">
			                  <i class="fas fa-robot text-success"></i>
			                </label>
			                
			              </div>

			            </div>

			          </div>

			        </a>';

		        $response = array(
					"chats" => base64_encode($chats),
					"lastIdMessage" => $lastIdMessage,
					"phone" => $contacts[0]->phone_contact
				);

				echo json_encode($response).",";
		    }

		}
		

		// echo '<pre>$lastIdMessage '; print_r($this->lastIdMessage); echo '</pre>';
		// echo '<pre>$phone '; print_r($this->phone); echo '</pre>';

	}

	/*=============================================
	Activar y desactivar la IA
	=============================================*/

	public $aiContact;
	public $idContact;

	public function ajaxUpdateAi(){

		$url = "contacts?id=".$this->idContact."&nameId=id_contact&token=".$this->token."&table=admins&suffix=admin";
		$method = "PUT";
		$fields = array(
			"ai_contact" => $this->aiContact
		);

		$fields = http_build_query($fields);

		$updateContact = CurlController::request($url,$method,$fields);

		if($updateContact->status == 200){

			echo 200;

		}
	}

	/*=============================================
	Buscar contacto
	=============================================*/

	public $searchContact;

	public function ajaxSearchContact(){


		/*=============================================
		Traemos los contactos
		=============================================*/

		$linkTo = ["phone_contact","name_contact"];

		foreach ($linkTo as $index => $item) {
			
			$url = "contacts?linkTo=".$item."&search=".$this->searchContact."&orderBy=date_updated_contact&orderMode=DESC&startAt=0&endAt=50";
			$method = "GET";
			$fields = array();

			$getContacts = CurlController::request($url,$method,$fields);

			if(isset($getContacts->status) && $getContacts->status == 200){

				$contacts = $getContacts->results;

				foreach ($contacts as $key => $value) {

					/*=============================================
		  		  	Traemos la conversación más reciente
		  		  	=============================================*/

				  	$url = "messages?linkTo=phone_message&equalTo=".$value->phone_contact."&orderBy=id_message&orderMode=DESC&startAt=0&endAt=1";

				  	$getMessages = CurlController::request($url,$method,$fields);

				  	if($getMessages->status == 200){

					  $messages = $getMessages->results[0];

				  	}else{

				  	  $messages = null;
				  	}

				  	/*=============================================
				  	Todos los Mensajes
				  	=============================================*/

				  	if (!empty($messages)){

		            	$date_updated_message = $messages->date_updated_message;

		                if ($messages->type_message == "business"){

		                	$textMessage = "...";
		                	$bellNotification = "";

		                }else{

		                	$textMessage = mb_substr($messages->client_message,0,17)."...";
		                	$bellNotification = "ok";

		                	if($key == 0){

		                		$textSuccess = "text-success";
		                	
		                	}else{

		                		$textSuccess = "";
		                	}
		                    
		                }

		            /*=============================================
				  	Cuando no hay mensajes
				  	=============================================*/

		            }else{

		              $date_updated_message = $value->date_updated_contact;
		              $textMessage = "";
		              $bellNotification = "";
		            
		            }

					
					$chats = '<a href="/chat?phone='.$value->phone_contact.'_'.$key.'" class="text-dark">';

			  			if($this->borderChat != "" && $this->borderChat == $value->phone_contact){

			  				$chats .= '<div class="contact-item pb-0" style="border:1px solid #090">';	
			  			
			  			}else{

			  				$chats .= '<div class="contact-item pb-0">';	
			  			}

		
				        $chats .= '<div class="d-flex justify-content-between pb-0"  onclick="alertClick(1)">

				              <div>
				                <span class="small">';

				                if($bellNotification == "ok"){

				                	$chats .= '<i class="bi bi-bell-fill '.$textSuccess.'"></i>';
				                }

				                $chats .= '<strong>';

				                	if (!empty($value->name_contact)){

				                        $chats .=  substr($value->name_contact,0,15).'...';

				                   }else{ 
				             
				                      $chats .= '+'.mb_substr($value->phone_contact,0,2)." ".
				                                    mb_substr($value->phone_contact,2,3)." ".
				                                    mb_substr($value->phone_contact,5,7);  

				                   }

				                   $chats .= '</strong>
				                </span>
				              </div>

				              <div>';

				            if (date("Y-m-d") == TemplateController::formatDate(8,$date_updated_message)){
				                    
				                $chats .= '<span class="small">'.TemplateController::formatDate(6,$date_updated_message).'</span>';

				            }else{

				                $chats .= '<span class="small">'.TemplateController::formatDate(5,$date_updated_message).'</span>';
				            }
				      
				             $chats .= '</div>

				            </div>

				            <div class="d-flex justify-content-between pb-0">
				              
				              <div>
				   
				                <p class="small">'.$textMessage.'</p>
				                 
				              </div>

				              <div class="custom-control custom-checkbox">
				                <input 
				                type="checkbox" 
				                class="custom-control-input mt-2 form-check-input changeAI" 
				                id="customCheckList_'.$value->id_contact.'" 
				                idContact="'.$value->id_contact.'"
				                name="example1"';
				               
				               if ($value->ai_contact == 1){

				               	$chats .= ' checked ';

				               }

				               $chats .= 'style="width:18px !important; height:18px !important">

				                <label class="custom-control-label mb-1" for="customCheck">
				                  <i class="fas fa-robot text-success"></i>
				                </label>
				                
				              </div>

				            </div>

				          </div>

				        </a>';

			        $response = array(
						"chats" => base64_encode($chats)
					);

					echo json_encode($response).",";

				}
			}
		}

		

	}



}

/*=============================================
Revisar si hay mensajes nuevos en el chat
=============================================*/

if(isset($_POST["phoneMessage"])){

	$ajax = new ChatAjax();
	$ajax -> phoneMessage = $_POST["phoneMessage"];
	$ajax -> orderMessage = $_POST["orderMessage"];
	$ajax -> ajaxLastMessage();

}

/*=============================================
Conversaciones manuales por parte del negocio
=============================================*/

if(isset($_POST["conversation"])){

	$ajax = new ChatAjax();
	$ajax -> conversation = $_POST["conversation"];
	$ajax -> phone = $_POST["phone"];
	$ajax -> token = $_POST["token"];
	$ajax -> ajaxSendMessage();
}

/*=============================================
Traer último chat
=============================================*/

if(isset($_POST["lastIdMessage"])){

	$ajax = new ChatAjax();
	$ajax -> lastIdMessage = $_POST["lastIdMessage"];
	$ajax -> phone = $_POST["phone"];
	$ajax -> borderChat = $_POST["borderChat"];
	$ajax -> ajaxLastChat();
}

/*=============================================
Activar y desactivar la IA
=============================================*/

if(isset($_POST["aiContact"])){

	$ajax = new ChatAjax();
	$ajax -> aiContact = $_POST["aiContact"];
	$ajax -> idContact = $_POST["idContact"];
	$ajax -> token = $_POST["token"];
	$ajax -> ajaxUpdateAi();
}

/*=============================================
Buscar contacto
=============================================*/

if(isset($_POST["searchContact"])){

	$ajax = new ChatAjax();
	$ajax -> searchContact = $_POST["searchContact"];
	$ajax -> ajaxSearchContact();
}

