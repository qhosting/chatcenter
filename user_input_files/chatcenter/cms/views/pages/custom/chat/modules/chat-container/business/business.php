<?php 

$business_message = "";

/*=============================================
preguntamos si viene una respuesta de bot
=============================================*/

if(isset(json_decode($value->template_message)->type) && json_decode($value->template_message)->type == "bot" ||
   isset(json_decode($value->template_message)->type) && json_decode($value->template_message)->type == "ia"){

	$url = "bots?linkTo=title_bot&equalTo=".json_decode($value->template_message)->title;
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

			$value->business_message = str_replace(["\r", "\n" ], '\n', $value->business_message);

			$business_message .= str_replace("\\n","<br>", urldecode($value->business_message));
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

			$url = "messages?linkTo=order_message,phone_message&equalTo=".($value->order_message-1).",".$value->phone_message."&select=client_message";
			$method = "GET";
			$fields = array();

			$getMessage = CurlController::request($url,$method,$fields);

			if($getMessage->status == 200){
				
				$getMessage = json_decode($getMessage->results[0]->client_message)->id;

			}

			/*=============================================
			Traer Categorías y Productos
			=============================================*/

			$url = "relations?rel=products,categories&type=product,category&linkTo=id_category&equalTo=".$getMessage;
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

		$value->business_message = str_replace(["\r", "\n" ], '\n', $value->business_message);

		$business_message .= str_replace("\\n","<br>", urldecode($value->business_message));
	}


/*=============================================
preguntamos si viene una respuesta de imagen
=============================================*/

}else if(isset(json_decode($value->business_message)->type) && json_decode($value->business_message)->type == "image"){

	$business_message .= '<a href="'.urldecode(json_decode($value->business_message)->title).'" target="_blank"><img src="'.urldecode(json_decode($value->business_message)->title).'" class="img-fluid rounded"></a>';

/*=============================================
preguntamos si viene una respuesta de video o audio
=============================================*/

}else if(isset(json_decode($value->business_message)->type) && json_decode($value->business_message)->type == "video" ||
			isset(json_decode($value->business_message)->type) && json_decode($value->business_message)->type == "audio"){

	$business_message .= '<a href="'.urldecode(json_decode($value->business_message)->title).'" target="_blank"><video controls src="'.urldecode(json_decode($value->business_message)->title).'" class="img-fluid rounded"></video></a>';

/*=============================================
preguntamos si viene una respuesta de documento
=============================================*/

}else if(isset(json_decode($value->business_message)->type) && json_decode($value->business_message)->type == "document"){

	$business_message .= '<a href="'.urldecode(json_decode($value->business_message)->title).'" target="_blank"><img src="/views/assets/img/pdf.jpeg" class="img-fluid rounded"></a>';

}else{

	/*=============================================
	preguntamos si viene enlaces en una respuesta
	=============================================*/

	if(str_contains($value->business_message, 'http')){

		$business_message = '<a href="'.$value->business_message.'" target="_blank">'.substr($value->business_message,0,35).'...</a>';
	
	}else{

		$business_message = $value->business_message;
	
	}
	
}


?>

<div class="msg bot">
	<div class="pt-2" style="max-width:300px; overflow-wrap: break-word;">
		<?php echo preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', $business_message) ?>		
	</div><br>
	<span class="small text-muted float-end">
		<?php echo TemplateController::formatDate(6,$value->date_updated_message) ?>		
	</span>
</div>