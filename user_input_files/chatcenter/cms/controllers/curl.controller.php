<?php 

class CurlController{

	/*=============================================
	Peticiones a la API
	=============================================*/	

	static public function request($url,$method,$fields){

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => ''.$url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_POSTFIELDS => $fields,
			CURLOPT_HTTPHEADER => array(
				'Authorization: sdfgsdgdsfgh4356e45rdfhdfgh5rdfhfgjrtrer'
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$response = json_decode($response);
		return $response;

	}

	/*=============================================
	Peticiones a la API de ChatGPT
	=============================================*/	

	static public function chatGPT($messages,$token,$org){
		
		// echo '<pre>$messages '; print_r($messages); echo '</pre>';

		// return;

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
		    "model": "gpt-4-0613",
		    "messages":'.$messages.'
		}',
		  CURLOPT_HTTPHEADER => array(
		    'Authorization: Bearer '.$token,
		    'OpenAI-Organization: '.$org,
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$response = json_decode($response);
		// echo '<pre>$response '; print_r($response); echo '</pre>';
		// return;
		return $response->choices[0]->message->content;

	}

	/*=============================================
	Peticiones a la API de WS
	=============================================*/	

	static public function apiWS($getApiWS,$json){

		if(str_contains($json,'{')){

			$json = $json;
			$endpoint = 'https://graph.facebook.com/v22.0/'.$getApiWS->id_number_whatsapp.'/messages';
			$method = 'POST';

		}else{

			$endpoint = 'https://graph.facebook.com/v22.0/'.explode("_",$json)[0];
			$idArchive = explode("_",$json)[0];
			
			if(count(explode("_",$json)) > 1){

				$ajax = "../";

			}else{

				$ajax = "";
			}

			$json = array();
			$method = 'GET';
		}

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => $endpoint,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_POSTFIELDS =>$json,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer '.$getApiWS->token_whatsapp,
				'Content-Type: application/json'
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);

		$response = json_decode($response);

		if($method == 'POST'){
		
			return $response;

		}else{

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => $response->url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_HTTPHEADER => array(
					'Authorization: Bearer '.$getApiWS->token_whatsapp,
					'Content-Type: application/json'
				),
			));

			$response = curl_exec($curl);
			$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

			if($httpcode == 200){

				$filename = $ajax.'views/assets/ws/'.$idArchive.'.'.explode("/",$contentType)[1];
				
				file_put_contents($filename, $response);

				return $filename;

			}

		}

	}

}
