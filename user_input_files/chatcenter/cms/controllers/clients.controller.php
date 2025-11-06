<?php 

Class ClientsController{

	static public function responseClients($getApiWS,$phone_message,$order_message,$type_conversation,$client_message){

        $idListMenu = null;
		
		/*=============================================
		Orden de la conversación
		=============================================*/

		if($order_message == 0){

			/*=============================================
            Buscamos el contacto
            =============================================*/

            $url = "contacts?linkTo=phone_contact&equalTo=".$phone_message;
            $method = "GET";
            $fields = array();

            $getContact = CurlController::request($url,$method,$fields);

            if($getContact->status != 200){

            	/*=============================================
            	Creamos el contacto
            	=============================================*/

            	$url = "contacts?token=no&except=id_contact";
            	$method = "POST";
            	$fields = array(
            		"phone_contact" => $phone_message,
            		"ai_contact" => $getApiWS->ai_whatsapp,
            		"date_created_contact" => date("Y-m-d")
            	);

            	$createContact = CurlController::request($url,$method,$fields);
            
            }else{

                /*=============================================
                Asignar o quitar asistente IA de acuerdo a la config del contacto
                =============================================*/

                $getApiWS->ai_whatsapp = $getContact->results[0]->ai_contact;

            	/*=============================================
            	Actualizamos la fecha de la última conversación con el contacto
            	=============================================*/
            	$url = "contacts?id=".$getContact->results[0]->id_contact."&nameId=id_contact&token=no&except=id_contact";
            	$method = "PUT";
            	$fields = array(
            		"date_updated_contact" => date("Y-m-d H:i:s")
            	);

            	$fields = http_build_query($fields);

            	$updateContact = CurlController::request($url,$method,$fields);

            }

            /*=============================================
            Validar si el cliente envia un archivo
            =============================================*/

            if(isset(json_decode($client_message)->type)){

              $responseBots = BotsController::responseBots("archive",$getApiWS,$phone_message,$order_message,$idListMenu);
                echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                return;
               
            }

            /*=============================================
            Respuesta con el chatbot
            =============================================*/

            if($getApiWS->ai_whatsapp == 0){

            	/*=============================================
            	Respuesta con Plantilla Bot
            	=============================================*/

            	$responseBots = BotsController::responseBots("welcome",$getApiWS,$phone_message,$order_message,$idListMenu);
            	echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';
            
            }else{

                /*=============================================
                Respuesta con Asistente IA
                =============================================*/

                $responseIA = IAController::responseIA($client_message,$getApiWS,$phone_message,$order_message);
                echo '<pre>$responseIA '; print_r($responseIA); echo '</pre>';

            }
		
        }else{

            /*=============================================
            Buscamos el contacto
            =============================================*/

            $url = "contacts?linkTo=phone_contact&equalTo=".$phone_message;
            $method = "GET";
            $fields = array();

            $getContact = CurlController::request($url,$method,$fields);

            /*=============================================
            Asignar o quitar asistente IA de acuerdo a la config del contacto
            =============================================*/

            $getApiWS->ai_whatsapp = $getContact->results[0]->ai_contact;

            /*=============================================
            Actualizamos la fecha de la última conversación con el contacto
            =============================================*/

            $url = "contacts?id=".$getContact->results[0]->id_contact."&nameId=id_contact&token=no&except=id_contact";
            $method = "PUT";
            $fields = array(
                "date_updated_contact" => date("Y-m-d H:i:s")
            );

            $fields = http_build_query($fields);

            $updateContact = CurlController::request($url,$method,$fields);

            /*=============================================
            Traer la última conversacion del cliente
            =============================================*/

            $url = "messages?linkTo=type_message,phone_message&equalTo=client,".$phone_message."&startAt=0&endAt=1&orderBy=id_message&orderMode=DESC";
            $method = "GET";
            $fields = array();

            $getMessage = CurlController::request($url,$method,$fields);

            if($getMessage->status == 200){

                $message = $getMessage->results[0];   

                /*=============================================
                Validar si el cliente envia un archivo
                =============================================*/

                if(isset(json_decode($client_message)->type)){

                  $responseBots = BotsController::responseBots("archive",$getApiWS,$phone_message,$order_message,$idListMenu);
                    echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                    return;
                   
                } 
                
                /*=============================================
                Si se envió la plantilla "welcome"
                =============================================*/

                if($message->template_message == '{"type":"bot","title":"welcome"}'){

                    /*=============================================
                    Si la respuesta es interactiva
                    =============================================*/

                    if($type_conversation == "interactive"){

                        /*=============================================
                         Si la respuesta es 1: Realizar pedido
                        =============================================*/

                        if(json_decode($message->client_message)->id == 1){

                            $responseBots = BotsController::responseBots("menu",$getApiWS,$phone_message,$order_message,$idListMenu);
                            echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';
                        
                        }


                        /*=============================================
                         Si la respuesta es 2: Reservar Mesa
                        =============================================*/

                        if(json_decode($message->client_message)->id == 2){

                            $responseBots = BotsController::responseBots("reservation",$getApiWS,$phone_message,$order_message,$idListMenu);
                            echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';
                        
                        }

                        /*=============================================
                         Si la respuesta es 3: Atención al cliente
                        =============================================*/

                        if(json_decode($message->client_message)->id == 3){

                            $responseBots = BotsController::responseBots("conversation",$getApiWS,$phone_message,$order_message,$idListMenu);
                            echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';
                        
                        }

                    }else{

                        $responseBots = BotsController::responseBots("conversation",$getApiWS,$phone_message,$order_message,$idListMenu);
                            echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';
                    }
                
                }

                /*=============================================
                Si se envió la plantilla "reservation"
                =============================================*/

                if($message->template_message == '{"type":"bot","title":"reservation"}'){

                    $responseBots = BotsController::responseBots("conversation",$getApiWS,$phone_message,$order_message,$idListMenu);
                    echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';
                }

                /*=============================================
                Si se envió la plantilla "menu"
                =============================================*/

                if($message->template_message == '{"type":"bot","title":"menu"}' ||
                   $message->template_message == '{"type":"bot","title":"reset"}'){

                    /*=============================================
                    Si la respuesta es interactiva
                    =============================================*/

                    if($type_conversation == "interactive"){

                        if(is_numeric(json_decode($message->client_message)->id)){

                            $idListMenu = json_decode($message->client_message)->id;

                            $responseBots = BotsController::responseBots("listMenu",$getApiWS,$phone_message,$order_message,$idListMenu);
                            echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                        }else{

                            if(json_decode($message->client_message)->id == "domicilio"){

                                $responseBots = BotsController::responseBots("delivery",$getApiWS,$phone_message,$order_message,$idListMenu);
                                echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                            }else{

                                $responseBots = BotsController::responseBots("conversation",$getApiWS,$phone_message,$order_message,$idListMenu);
                                echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                            }
     
                        }

                    }else{

                      $responseBots = BotsController::responseBots("conversation",$getApiWS,$phone_message,$order_message,$idListMenu);
                        echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                    }

                }

                /*=============================================
                Si se envió la plantilla "lista de menu"
                =============================================*/

                if($message->template_message == '{"type":"bot","title":"listMenu"}'){

                    /*=============================================
                    Si la respuesta es interactiva
                    =============================================*/

                    if($type_conversation == "interactive"){

                        $responseBots = BotsController::responseBots("reset",$getApiWS,$phone_message,$order_message,$idListMenu);
                        echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                    }else{

                        if($message->client_message == "Menú" || 
                           $message->client_message == "menú" ||
                           $message->client_message == "Menu" ||
                           $message->client_message == "MENÚ" ||
                           $message->client_message == "MENU" ||
                           $message->client_message == "menu"){

                            $responseBots = BotsController::responseBots("menu",$getApiWS,$phone_message,$order_message,$idListMenu);
                            echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                        }else{

                            $responseBots = BotsController::responseBots("conversation",$getApiWS,$phone_message,$order_message,$idListMenu);
                            echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';
                        }
                    }

                }

                /*=============================================
                Si se envió la plantilla "name"
                =============================================*/

                if($message->template_message == '{"type":"bot","title":"name"}'){

                    $responseBots = BotsController::responseBots("phone",$getApiWS,$phone_message,$order_message,$idListMenu);
                    echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                }

                /*=============================================
                Si se envió la plantilla "phone"
                =============================================*/

                if($message->template_message == '{"type":"bot","title":"phone"}'){

                    $responseBots = BotsController::responseBots("email",$getApiWS,$phone_message,$order_message,$idListMenu);
                    echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                }

                /*=============================================
                Si se envió la plantilla "email"
                =============================================*/

                if($message->template_message == '{"type":"bot","title":"email"}'){

                    $responseBots = BotsController::responseBots("address",$getApiWS,$phone_message,$order_message,$idListMenu);
                    echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                }

                /*=============================================
                Si se envió la plantilla "address"
                =============================================*/

                if($message->template_message == '{"type":"bot","title":"address"}'){

                    $responseBots = BotsController::responseBots("process",$getApiWS,$phone_message,$order_message,$idListMenu);
                    echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                }

                /*=============================================
                Si se envió la plantilla "confirmation"
                =============================================*/

                if($message->template_message == '{"type":"bot","title":"confirmation"}'){
                   
                    /*=============================================
                    Si la respuesta es interactiva
                    =============================================*/

                    if($type_conversation == "interactive"){

                        if(json_decode($message->client_message)->id == 1){

                            $responseBots = BotsController::responseBots("checkout",$getApiWS,$phone_message,$order_message,$message->id_conversation_message);
                            echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                        }else{

                            $responseBots = BotsController::responseBots("conversation",$getApiWS,$phone_message,$order_message,$idListMenu);
                            echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>'; 
                        }

                    }else{

                      $responseBots = BotsController::responseBots("conversation",$getApiWS,$phone_message,$order_message,$idListMenu);
                            echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                    }

                }

                /*=============================================
                Si se envió la plantilla "checkout"
                =============================================*/

                if($message->template_message == '{"type":"bot","title":"checkout"}'){

                    $responseBots = BotsController::responseBots("conversation",$getApiWS,$phone_message,$order_message,$idListMenu);
                    echo '<pre>$responseBots '; print_r($responseBots); echo '</pre>';

                }

                /*=============================================
                Si se envió la plantilla "ia"
                =============================================*/
                
                if($getApiWS->ai_whatsapp == 1){

                    /*=============================================
                    Respuesta con Asistente IA
                    =============================================*/

                    $responseIA = IAController::responseIA($client_message,$getApiWS,$phone_message,$order_message);
                    echo '<pre>$responseIA '; print_r($responseIA); echo '</pre>';
                }
            }

        }
	}

    /*=============================================
    Actualizar Contacto
    =============================================*/

    public function updateContact(){


        if(isset($_POST["id_contact"])){

            $url = "contacts?id=".$_POST["id_contact"]."&nameId=id_contact&token=".$_SESSION["admin"]->token_admin."&table=admins&suffix=admin";
            $method = "PUT";
            $fields = array(
                "name_contact" => $_POST["name_contact"]
            );

            $fields = http_build_query($fields);

            $updateContact = CurlController::request($url,$method,$fields);

            if($updateContact->status == 200){

                 echo '

                    <script>
                        fncFormatInputs();
                        fncToastr("success","El registro ha sido actualizado con éxito");
                    </script>

                ';

            }
        }
    }

    /*=============================================
    Limpiar histórico de mensajes del Contacto
    =============================================*/

    public function cleanContact(){

        if(isset($_POST["phoneContactClean"])){


            $url = "messages?linkTo=phone_message&equalTo=".$_POST["phoneContactClean"];
            $method = "GET";
            $fields = array();

            $getMessages = CurlController::request($url,$method,$fields);

            if($getMessages->status == 200){

                $countMessages = 0;

                foreach ($getMessages->results as $key => $value) {
                    
                    $url = "messages?id=".$value->id_message."&nameId=id_message&token=".$_SESSION["admin"]->token_admin."&table=admins&suffix=admin";
                    $method = "DELETE";
                    $fields = array();

                    $deleteMessages = CurlController::request($url,$method,$fields);

                    if($deleteMessages->status == 200){

                        $countMessages++;

                        if($countMessages == count($getMessages->results)){

                            echo '

                                <script>
                                    fncFormatInputs();
                                    fncSweetAlert("success","Los mensajes han sido borrados con éxito",setTimeout(()=>location.reload(),1000));
                                </script>

                            ';

                        }
                    }

                }
            }
        }

    }

    /*=============================================
    Borrar el contacto
    =============================================*/

    public function deleteContact(){

        if(isset($_POST["phoneContactDelete"])){

            $url = "messages?linkTo=phone_message&equalTo=".$_POST["phoneContactDelete"];
            $method = "GET";
            $fields = array();

            $getMessages = CurlController::request($url,$method,$fields);

            if($getMessages->status == 200){

                $countMessages = 0;

                foreach ($getMessages->results as $key => $value) {
                    
                    $url = "messages?id=".$value->id_message."&nameId=id_message&token=".$_SESSION["admin"]->token_admin."&table=admins&suffix=admin";
                    $method = "DELETE";
                    $fields = array();

                    $deleteMessages = CurlController::request($url,$method,$fields);

                    if($deleteMessages->status == 200){

                        $countMessages++;

                        if($countMessages == count($getMessages->results)){

                            $url = "contacts?id=".$_POST["phoneContactDelete"]."&nameId=phone_contact&token=".$_SESSION["admin"]->token_admin."&table=admins&suffix=admin";
                             $method = "DELETE";
                            $fields = array();

                            $deleteContact = CurlController::request($url,$method,$fields);

                             if($deleteContact->status == 200){


                                echo '

                                    <script>
                                        fncFormatInputs();
                                        fncSweetAlert("success","El contacto y sus mensajes han sido borrados con éxito",setTimeout(()=>window.location="/",1000));
                                    </script>

                                ';

                            }

                        }
                    }

                }
            }
        }

    }
    
  
    
}
