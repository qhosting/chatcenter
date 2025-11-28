//+------------------------------------------------------------------+
//|                                               AurumSniperV1.mq5 |
//|                                         Copyright 2023, Jules AI |
//|                                             https://www.mql5.com |
//+------------------------------------------------------------------+
#property copyright "Jules - AI Developer"
#property link      "https://www.mql5.com"
#property version   "1.00"
#property strict

// Inclusión de la librería estándar de comercio (requisito técnico)
#include <Trade\Trade.mqh>

//--- Parámetros de Entrada (Inputs)
input group "Configuración Webhook"
input string InpWebhookURL = "";                 // URL del Webhook (n8n)

input group "Estrategia Aurum - Filtros"
input int    InpDistanciaPuntos = 100;           // Distancia en Puntos (Filtro D1)
input int    InpMagicNumber = 123456;            // Número Mágico
input int    InpEMAPeriod   = 200;               // Periodo EMA (Filtro H4)
input int    InpRSIPeriod   = 14;                // Periodo RSI (Gatillo H1)
input double InpRSIOverbought = 70.0;            // Nivel Sobrecompra (Venta)
input double InpRSIOversold   = 30.0;            // Nivel Sobreventa (Compra)

//--- Variables Globales
CTrade         trade;                            // Objeto de trading
int            handle_ema;                       // Handle para EMA H4
int            handle_rsi;                       // Handle para RSI H1
datetime       last_alert_time = 0;              // Para controlar frecuencia de alertas

//+------------------------------------------------------------------+
//| Expert initialization function                                   |
//+------------------------------------------------------------------+
int OnInit()
  {
   // 1. Validar URL
   if(InpWebhookURL == "")
     {
      Print("ADVERTENCIA: La URL del Webhook está vacía. Las alertas no se enviarán.");
     }

   // Recordatorio de permisos
   Print("IMPORTANTE: Asegúrese de agregar la URL '", InpWebhookURL, "' en Herramientas > Opciones > Asesores Expertos > Permitir WebRequest.");

   // 2. Inicializar Indicadores

   // EMA 200 en H4 (Filtro de Tendencia)
   handle_ema = iMA(_Symbol, PERIOD_H4, InpEMAPeriod, 0, MODE_EMA, PRICE_CLOSE);
   if(handle_ema == INVALID_HANDLE)
     {
      Print("Error al crear el handle de la EMA H4. Error: ", GetLastError());
      return(INIT_FAILED);
     }

   // RSI 14 en H1 (Gatillo)
   handle_rsi = iRSI(_Symbol, PERIOD_H1, InpRSIPeriod, PRICE_CLOSE);
   if(handle_rsi == INVALID_HANDLE)
     {
      Print("Error al crear el handle del RSI H1. Error: ", GetLastError());
      return(INIT_FAILED);
     }

   trade.SetExpertMagicNumber(InpMagicNumber);

   Print("Aurum Sniper V1: Iniciado correctamente. Esperando condiciones...");
   return(INIT_SUCCEEDED);
  }

//+------------------------------------------------------------------+
//| Expert deinitialization function                                 |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
  {
   // Liberar handles de indicadores para ahorrar memoria
   IndicatorRelease(handle_ema);
   IndicatorRelease(handle_rsi);
   Print("Aurum Sniper V1: Desconectado.");
  }

//+------------------------------------------------------------------+
//| Expert tick function                                             |
//+------------------------------------------------------------------+
void OnTick()
  {
   // --- CONTROL DE FRECUENCIA (1 Alerta por Vela H1) ---
   // Obtenemos el tiempo de apertura de la vela actual en H1
   datetime current_bar_time = iTime(_Symbol, PERIOD_H1, 0);

   // Si ya enviamos una alerta en esta vela H1, no hacemos nada
   if(last_alert_time == current_bar_time) return;


   // --- OBTENCIÓN DE DATOS DE PRECIO ---
   double ask = SymbolInfoDouble(_Symbol, SYMBOL_ASK);
   double bid = SymbolInfoDouble(_Symbol, SYMBOL_BID);


   // --- PASO 1: FILTRO D1 (Soportes/Resistencias Diarios) ---
   // Calcular High y Low de los últimos 20 días (excluyendo el día actual en formación para ser estrictos con "últimos 20 días cerrados", o incluyendo si se desea dinamismo).
   // Usaremos índices 1 a 20 para tomar los 20 días anteriores cerrados.

   double d1_highs[];
   double d1_lows[];

   // Copiamos los Highs y Lows de las velas D1 (índices 1 a 20)
   if(CopyHigh(_Symbol, PERIOD_D1, 1, 20, d1_highs) < 20 || CopyLow(_Symbol, PERIOD_D1, 1, 20, d1_lows) < 20)
     {
      // Datos insuficientes todavía
      return;
     }

   // Encontrar el valor máximo y mínimo en esos arrays
   double max_20d = d1_highs[ArrayMaximum(d1_highs)];
   double min_20d = d1_lows[ArrayMinimum(d1_lows)];

   // Calcular distancia permitida en precio real
   double distancia_precio = InpDistanciaPuntos * _Point;

   // Verificar proximidad (La "Zona Aurum")
   // Para COMPRA: Precio cerca del Mínimo de 20 días (Soporte)
   bool zona_compra_d1 = MathAbs(ask - min_20d) <= distancia_precio;

   // Para VENTA: Precio cerca del Máximo de 20 días (Resistencia)
   bool zona_venta_d1  = MathAbs(bid - max_20d) <= distancia_precio;

   // Si no está en ninguna zona clave, salimos para ahorrar recursos
   if(!zona_compra_d1 && !zona_venta_d1) return;


   // --- PASO 2: FILTRO H4 (Dirección de Tendencia con EMA 200) ---
   double ema_h4[];
   ArraySetAsSeries(ema_h4, true);

   // Obtenemos el valor actual de la EMA (índice 0 o 1).
   // Usamos 0 para lectura en tiempo real.
   if(CopyBuffer(handle_ema, 0, 0, 1, ema_h4) < 1) return;

   double valor_ema_h4 = ema_h4[0];

   // Condiciones H4
   bool filtro_h4_compra = (ask > valor_ema_h4); // Precio H4 > EMA 200
   bool filtro_h4_venta  = (bid < valor_ema_h4); // Precio H4 < EMA 200


   // --- PASO 3: GATILLO H1 (RSI 14) ---
   double rsi_h1[];
   ArraySetAsSeries(rsi_h1, true);

   // Obtenemos RSI actual
   if(CopyBuffer(handle_rsi, 0, 0, 1, rsi_h1) < 1) return;

   double valor_rsi_h1 = rsi_h1[0];

   // Condiciones H1
   bool gatillo_compra = (valor_rsi_h1 < InpRSIOversold); // RSI < 30
   bool gatillo_venta  = (valor_rsi_h1 > InpRSIOverbought); // RSI > 70


   // --- EVALUACIÓN FINAL DE LA CASCADA ---

   // LÓGICA DE COMPRA
   if(zona_compra_d1 && filtro_h4_compra && gatillo_compra)
     {
      string razon = "Receta Aurum: Soporte D1 + H4 Alcista + RSI Sobreventa";
      EnviarAlerta("COMPRA", ask, razon);
      last_alert_time = current_bar_time; // Marcar vela como alertada
     }

   // LÓGICA DE VENTA
   else if(zona_venta_d1 && filtro_h4_venta && gatillo_venta)
     {
      string razon = "Receta Aurum: Resistencia D1 + H4 Bajista + RSI Sobrecompra";
      EnviarAlerta("VENTA", bid, razon);
      last_alert_time = current_bar_time; // Marcar vela como alertada
     }
  }

//+------------------------------------------------------------------+
//| Función auxiliar para enviar Webhook                             |
//+------------------------------------------------------------------+
void EnviarAlerta(string tipo, double precio, string razon)
  {
   // Si no hay URL, solo imprimimos en log
   if(InpWebhookURL == "")
     {
      Print("SEÑAL DETECTADA (Sin Webhook): ", tipo, " @ ", precio, " | ", razon);
      return;
     }

   // Preparar Payload JSON
   // Formato: {"symbol": "EURUSD", "tipo": "COMPRA", "precio": 1.0500, "razon": "..."}
   string json_payload = StringFormat("{\"symbol\": \"%s\", \"tipo\": \"%s\", \"precio\": %.5f, \"razon\": \"%s\"}",
                                      _Symbol, tipo, precio, razon);

   // Convertir cuerpo a array de caracteres
   char data[];
   StringToCharArray(json_payload, data, 0, StringLen(json_payload));

   // Preparar Headers
   string headers = "Content-Type: application/json\r\n";
   char result[];
   string result_headers;

   // Enviar WebRequest
   int timeout = 5000; // 5 segundos

   // Resetear último error
   ResetLastError();

   int res = WebRequest("POST",
                        InpWebhookURL,
                        headers,
                        timeout,
                        data,
                        data, // Usamos data como buffer temporal de respuesta si no nos importa
                        result_headers);

   if(res == 200) // HTTP OK
     {
      Print("Webhook enviado EXITOSAMENTE: ", json_payload);
     }
   else
     {
      Print("ERROR enviando Webhook. Código: ", res, " Error MQL: ", GetLastError());
      // Nota: Si el error es 4060, significa que la URL no está permitida en Opciones.
      if(GetLastError() == 4060)
         Print("Recuerde añadir la URL en Herramientas > Opciones > Asesores Expertos.");
     }
  }
//+------------------------------------------------------------------+
