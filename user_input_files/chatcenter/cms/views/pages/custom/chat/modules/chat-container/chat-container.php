<div class="chat-container" id="chatContainer">

  <div class="chat-header d-flex justify-content-between">

    <?php if (!empty($contacts)): ?>

      <!--======================================
      Validar Asistente IA
      ========================================-->

      <div class="bot-name bg-white rounded p-1" id="botName">
        <div class="custom-control custom-checkbox">
          
          <label id="label_<?php echo $contacts[$indexContact]->id_contact ?>" class="custom-control-label mb-1 me-1" <?php if ($contacts[$indexContact]->ai_contact == 1): ?> style="display:block" <?php else: ?> style="display:none"  <?php endif ?>>
            <i class="fas fa-robot text-success ms-1"></i>
          </label>

        </div> 

      </div> 

      <!--======================================
      Número de teléfono
      ========================================-->

      <div class="text-center">

        <?php if (isset($_GET["phone"])): $phoneContact = explode("_",$_GET["phone"])[0]; $nameContact = $contacts[$indexContact]->name_contact; ?>

          <?php else: $phoneContact = $contacts[$indexContact]->phone_contact; $nameContact = $contacts[$indexContact]->name_contact; ?>
          
        <?php endif ?>

        <?php if (!empty($nameContact)): ?>

          <?php echo $nameContact ?> ( +<?php echo mb_substr($phoneContact,0,2)." ".mb_substr($phoneContact,2,3)." ".mb_substr($phoneContact,5,7) ?>  )
          
        <?php else: ?>

          +<?php echo mb_substr($phoneContact,0,2) ?> 
           <?php echo mb_substr($phoneContact,2,3) ?>
           <?php echo mb_substr($phoneContact,5,7) ?>

        <?php endif ?>  
      </div>

      <!--======================================
      Gestión de Contacto
      ========================================-->

      <div class="d-flex justify-content-end">

        <div>
          <button type="button" class="btn btn-sm text-white rounded m-0 px-1 py-0 border-0 changeContact" infoContact='<?php echo json_encode($contacts[$indexContact]) ?>'>
            <i class="bi bi-pencil-square"></i>
          </button>
        </div>

        <div>
          <form method="POST" id="cleanMessages">
            <input type="hidden" name="phoneContactClean" value="<?php echo $contacts[$indexContact]->phone_contact ?>">
            <button type="submit" class="btn btn-sm text-white rounded m-0 px-1 py-0 border-0">
              <i class="fas fa-broom"></i>
            </button>
          </form>
        </div>

        <div>
          <form method="POST" id="deleteContact">
            <input type="hidden" name="phoneContactDelete" value="<?php echo $contacts[$indexContact]->phone_contact ?>">
            <button type="submit" class="btn btn-sm text-white rounded m-0 px-1 py-0 border-0"><i class="bi bi-trash"></i></button>
          </form>
        </div>

      </div>
      
    <?php endif ?>

  </div>

  <div class="chat-body" id="chatBody">

    <!-- <div class="position-absolute" style="top:0; right:0"><button class="btn"></button></div> -->

    <?php if (!empty($messages)): $messages = array_reverse($messages) ?>

      <input type="hidden" id="phoneMessage" value="<?php echo $phoneContact ?>">
      <input type="hidden" id="orderMessage" value="<?php echo end($messages)->order_message ?>">

      <!--======================================
      Fecha de los mensajes
      ========================================-->

      <div class="d-flex justify-content-center text-center">
        
        <span class="badge border bg-success rounded py-2 px-2">

          <?php if (date("Y-m-d") == TemplateController::formatDate(8,$messages[0]->date_updated_message)): ?>
            Hoy
          <?php else: ?>
            <?php echo TemplateController::formatDate(7,$messages[0]->date_updated_message) ?>
          <?php endif ?>

        </span>

      </div>

      <!--======================================
      Separar conversación del cliente con el negocio
      ========================================-->

      <?php foreach ($messages as $key => $value): ?>

        <!--======================================
        Agrupar por fechas las conversaciones
        ========================================-->

        <?php if ($key > 0 && TemplateController::formatDate(8,$value->date_updated_message) > TemplateController::formatDate(8,$messages[$key-1]->date_updated_message)): ?>

        <div class="d-flex justify-content-center text-center">
        
          <span class="badge border bg-success rounded py-2 px-2">

            <?php echo TemplateController::formatDate(7,$value->date_updated_message) ?>

          </span>

        </div>
          
        <?php endif ?>

        <?php if ($value->type_message == "business"): ?>

          <?php include "business/business.php"; ?>
          
        <?php endif ?>

        <?php if ($value->type_message == "client"): ?>

          <?php include "client/client.php"; ?>
          
        <?php endif ?>
        
      <?php endforeach ?>
      
    
    <?php else: ?>

      <input type="hidden" id="phoneMessage" value="<?php echo $phoneContact ?>">
      <input type="hidden" id="orderMessage" value="-1">

    <?php endif ?>

  </div>

  <!--======================================
  Formulario para enviar mensajes manuales
  ========================================-->

  <div class="chat-footer position-relative w-100">

    <button type="button" class="me-2 attach myFiles"><i class="fas fa-paperclip"></i></button>
    <input type="text" id="userInput" placeholder="Escribe tu mensaje...">
    <button type="button" class="send"><i class="fas fa-paper-plane"></i></button>

  </div>

</div>

<?php 

  include "views/modules/modals/files.php";

  require_once "controllers/clients.controller.php";
  $contact = new ClientsController();
  $contact -> updateContact();

  $cleanContact = new ClientsController();
  $cleanContact -> cleanContact();

  $deleteContact = new ClientsController();
  $deleteContact -> deleteContact();

  include "views/modules/modals/contact.php";
  
?>