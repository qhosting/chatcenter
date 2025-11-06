/*=============================================
Mover el scroll hasta la última conversación
=============================================*/

function scrollMoveToEnd(){

	$(document).ready(function() {

		var messages = $(".msg:last");

		if(messages.length > 0){

			$("html, body, #chatBody").animate({

				scrollTop: $("#chatBody")[0].scrollHeight
			
			},500);

		}

	})

}

scrollMoveToEnd();



/*=============================================
Revisar si hay mensajes nuevos en el chat
=============================================*/

var interval = setInterval(function(){

	var phoneMessage = $("#phoneMessage").val();
	// console.log("phoneMessage", phoneMessage);
	var orderMessage = $("#orderMessage").val();
	// console.log("orderMessage", orderMessage);

	if(phoneMessage != undefined && orderMessage != undefined){

		/*=============================================
		Cuando el chat está vacío
		=============================================*/
		if(orderMessage == -1){

			orderMessage = 0;
		
		}

		intervalMessage(phoneMessage,orderMessage);
	}

	/*=============================================
	Función para indentificar nuevos chats
	=============================================*/

	intervalChat();


},2000); //cada 2 segundos

/*=============================================
Función si hay mensajes nuevos en el chat
=============================================*/

function intervalMessage(phoneMessage,orderMessage){

	var data = new FormData();
	data.append("phoneMessage", phoneMessage);
	data.append("orderMessage", orderMessage);

	$.ajax({
		url:"/ajax/chat.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
        processData: false,
        success: function (response){
        	
        	// console.log("response", response);
        	
        	if(response != ""){
        		
        		// console.log("response", response);

        		var response = JSON.parse("["+response+"]");

        		/*=============================================
				Evitar repetir pintar el chat
				=============================================*/

				if(response[0].lastOrder != $("#orderMessage").val()){

	        		$("#chatBody").append(decodeURIComponent(escape(atob(response[0].message))));
	        		$("#orderMessage").val(response[0].lastOrder);

	        		/*=============================================
					Sonido cuando el cliente escribe un nuevo mensaje en el chat actual
					=============================================*/

	        		if(response[0].type == "client"){

		        		$("#messageSound")[0].play();

		        	}

		        	scrollMoveToEnd();

		        }
	        

        	}

        }

	})

}

/*=============================================
Respondiendo Chat Manualmente desde el botón
=============================================*/

$(document).on("click",".send",function(){

	var conversation = $("#userInput").val();

	sendMessage(conversation);

})

/*=============================================
Respondiendo Chat Manualmente con Enter
=============================================*/

$("#userInput").keyup(function(event){

	event.preventDefault();

	if(event.keyCode == 13 && $("#userInput").val() != ""){

		var conversation = $("#userInput").val();

		sendMessage(conversation);
	}

})

/*=============================================
Función para enviar la conversación
=============================================*/

function sendMessage(conversation){

	$("#userInput").val("");

	var data = new FormData();
	data.append("conversation", conversation);
	data.append("phone", $("#phoneMessage").val());
	data.append("token",localStorage.getItem("tokenAdmin"));

	$.ajax({
		url:"/ajax/chat.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
        processData: false,
        success: function (response){
        	
        	// console.log("response", response);
        }

    })

}

/*=============================================
Función para indentificar nuevos chats
=============================================*/

function intervalChat(){

	var data = new FormData();
	data.append("lastIdMessage", $("#lastIdMessage").attr("lastIdMessage"));
	data.append("phone", $("#phoneMessage").val());
	data.append("borderChat", $("#borderChat").val());

	$.ajax({
		url:"/ajax/chat.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
        processData: false,
        success: function (response){
        	
        	if(response != ""){

        		$("#lastIdMessage").html('');
        		
        		// console.log("response", response);

        		var response = JSON.parse("["+response.slice(0,-1)+"]");
        		// console.log("response", response);

        		$("#lastIdMessage").attr("lastIdMessage",response[0].lastIdMessage);

        		/*=============================================
				Sonido cuando el cliente tiene una conversación nueva
				=============================================*/

        		if(response[0].phone != $("#phoneMessage").val() && response[0].lastIdMessage != null){

        			$("#chatSound")[0].play();
        		}

        		response.forEach((e,i)=>{

        			$("#lastIdMessage").append(decodeURIComponent(escape(atob(e.chats))));

        		})
        		
        	}
        }

    })

}

/*=============================================
Función para activar y desactivar ia
=============================================*/

$(document).on("change",".changeAI", function(){

	if($(this).prop("checked")){

		var aiContact = 1;

	}else{

		var aiContact = 0;
	}

	var idContact = $(this).attr("idContact");

	var data = new FormData();
	data.append("aiContact", aiContact);
	data.append("idContact", idContact);
	data.append("token", localStorage.getItem("tokenAdmin"));

	$.ajax({
		url:"/ajax/chat.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
        processData: false,
        success: function (response){
        	
        	if(response == 200){

        		if($("#label_"+idContact).length > 0){

        			if(aiContact == 1){

        				$("#label_"+idContact).show();
        			
        			}else{

        				$("#label_"+idContact).hide();
        			}

        		}

        		fncToastr("success", "Contacto Actualizado Correctamente");
        	
        	}else{

        		fncToastr("error", "Error al actualizar el Contacto");
        	}

        }

    })
})


/*=============================================
Cambiando info del contacto
=============================================*/

$(document).on("click",".changeContact",function(){

	var dataContact = JSON.parse($(this).attr("infoContact"));
	
	$("#myContact").modal("show");

	/*=============================================
	Cuando se abre la ventana modal
	=============================================*/

	$("#myContact").on("shown.bs.modal", function(){

		$("#id_contact").val(dataContact.id_contact)
		$("#phone_contact").val(dataContact.phone_contact)
		$("#name_contact").val(dataContact.name_contact)
	
	})
})

/*=============================================
Validar borrado de histórico de mensajes
=============================================*/

$(document).on("submit","#cleanMessages", function(e){

	e.preventDefault();

	fncSweetAlert("confirm","¿Está seguro de borrar este histórico de mensajes?","").then(resp=>{

		if(resp){

			e.target.submit();
		
		}

	})

})

/*=============================================
Validar eliminar el contacto
=============================================*/

$(document).on("submit","#deleteContact", function(e){

	e.preventDefault();

	fncSweetAlert("confirm","¿Está seguro de borrar este contacto?","").then(resp=>{

		if(resp){

			e.target.submit();
		
		}

	})

})

/*=============================================
Buscar contacto
=============================================*/

$(document).on("keyup",".searchContact",function(e){
 
	var data = new FormData();
	data.append("searchContact", $(this).val());

	$.ajax({
		url:"/ajax/chat.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
        processData: false,
        success: function (response){
        	
        	if(response != ""){

        		$("#lastIdMessage").html('');
        		
        		// console.log("response", response);

        		var response = JSON.parse("["+response.slice(0,-1)+"]");
        		// console.log("response", response);

        		// $("#lastIdMessage").attr("lastIdMessage",response[0].lastIdMessage);


        		/*=============================================
				Sonido cuando el cliente tiene una conversación nueva
				=============================================*/

        		response.forEach((e,i)=>{

        			$("#lastIdMessage").append(decodeURIComponent(escape(atob(e.chats))));

        		})
        		
        	}else{

        		$("#lastIdMessage").html('');
        	}
        	
        }

    })

})

/*=============================================
Visualizar chat en pantallas pequeñas
=============================================*/

if(window.matchMedia("(max-width:991px)").matches){
	
	$("#chatContainer").addClass("offcanvas");
	$("#chatContainer").addClass("offcanvas-end");

	$(document).ready(function(){
		var miOffcanvas = new bootstrap.Offcanvas($('#chatContainer')[0]);
	    miOffcanvas.show();
	});

}else{

	$("#chatContainer").removeClass("offcanvas");
	$("#chatContainer").removeClass("offcanvas-end");

}


