//+------------------------------------------------------------------+
//|                                            AurumSniperV4.mq5    |
//|                                  Copyright 2025, Aurum Capital  |
//+------------------------------------------------------------------+
#property copyright "Aurum Capital"
#property version   "4.00" // Versión Final de Producción
#property strict
#include <Trade\Trade.mqh>

//--- INPUTS
input group "Estrategia"
input int    InpDistanciaPuntos = 100; // Distancia D1
input int    InpEMAPeriod   = 200;     // Tendencia H4
input int    InpRSIPeriod   = 14;      // Gatillo H1
input double InpRSIOverbought = 70.0;
input double InpRSIOversold   = 30.0;

input group "Riesgo (Dynamic Risk)"
input int    InpATRPeriod   = 14;
input double InpSL_Multiplier = 1.5;   // Multiplicador ATR para SL
input double InpRiskReward    = 2.0;   // Ratio Riesgo/Beneficio (TP = SL * Ratio)

input group "News Guard"
input bool   InpUseNewsFilter = true;
input int    InpMinsBefore    = 60;    // Minutos antes de la noticia
input int    InpMinsAfter     = 30;    // Minutos después de la noticia

//--- CONSTANTES HARDCODED
const string WEBHOOK_URL = "https://n8n.whatscloud.site/webhook/aurum-trading-alerts";
const int    MAGIC_NUMBER = 8888;

//--- GLOBALES
CTrade trade;
int hMA, hRSI, hATR;
datetime last_alert_time = 0;

//+------------------------------------------------------------------+
//| Inicialización del EA                                            |
//+------------------------------------------------------------------+
int OnInit()
  {
   // Inicialización de handles de indicadores
   hMA  = iMA(_Symbol, PERIOD_H4, InpEMAPeriod, 0, MODE_EMA, PRICE_CLOSE);
   hRSI = iRSI(_Symbol, PERIOD_H1, InpRSIPeriod, PRICE_CLOSE);
   hATR = iATR(_Symbol, PERIOD_H1, InpATRPeriod);

   // Verificación de validez de handles
   if(hMA == INVALID_HANDLE || hRSI == INVALID_HANDLE || hATR == INVALID_HANDLE)
     {
      Print("ERROR FATAL: No se pudieron crear los indicadores.");
      return(INIT_FAILED);
     }

   trade.SetExpertMagicNumber(MAGIC_NUMBER);
   Print(">>> AURUM SNIPER V4: INICIADO CORRECTAMENTE <<<");
   Print("Webhook Configurado: ", WEBHOOK_URL);

   return(INIT_SUCCEEDED);
  }

//+------------------------------------------------------------------+
//| Desinicialización del EA                                         |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
  {
   IndicatorRelease(hMA);
   IndicatorRelease(hRSI);
   IndicatorRelease(hATR);
   Print("Aurum Sniper V4: Desconectado.");
  }

//+------------------------------------------------------------------+
//| Función Principal (OnTick)                                       |
//+------------------------------------------------------------------+
void OnTick()
  {
   // 1. CONTROL DE VELAS (Evitar spam, 1 alerta por vela H1)
   datetime bar_time = iTime(_Symbol, PERIOD_H1, 0);
   if(last_alert_time == bar_time) return;


   // 2. NEWS GUARD (Filtro de Noticias)
   if(InpUseNewsFilter)
     {
      if(HayNoticia())
        {
         // Para no llenar el log, imprimimos solo ocasionalmente
         static datetime last_news_print = 0;
         if(TimeCurrent() - last_news_print > 300) // Cada 5 mins
           {
            Print("⛔ NEWS GUARD ACTIVADO: Mercado volátil detectado. Trading pausado.");
            last_news_print = TimeCurrent();
           }
         return; // ABORTAR: No operar durante noticias
        }
     }


   // 3. OBTENCIÓN DE DATOS DE MERCADO
   double ask = SymbolInfoDouble(_Symbol, SYMBOL_ASK);
   double bid = SymbolInfoDouble(_Symbol, SYMBOL_BID);

   // Arrays para indicadores
   double maVal[], rsiVal[], atrVal[];
   ArraySetAsSeries(maVal, true);
   ArraySetAsSeries(rsiVal, true);
   ArraySetAsSeries(atrVal, true);

   // Copiar buffers (Índice 0 = Actual, Índice 1 = Cerrada anterior)
   // Usamos indice 0 para lectura en tiempo real
   if(CopyBuffer(hMA, 0, 0, 1, maVal) < 1 ||
      CopyBuffer(hRSI, 0, 0, 1, rsiVal) < 1 ||
      CopyBuffer(hATR, 0, 0, 1, atrVal) < 1)
     {
      return; // Esperar a tener datos
     }

   double ema_h4 = maVal[0];
   double rsi_h1 = rsiVal[0];
   double atr_h1 = atrVal[0];


   // 4. FILTRO D1 (Zonas de Soporte/Resistencia)
   double d1_highs[], d1_lows[];
   // Obtenemos últimos 20 días cerrados (índices 1 a 20)
   if(CopyHigh(_Symbol, PERIOD_D1, 1, 20, d1_highs) < 20 ||
      CopyLow(_Symbol, PERIOD_D1, 1, 20, d1_lows) < 20)
     {
      return;
     }

   double max_20d = d1_highs[ArrayMaximum(d1_highs)];
   double min_20d = d1_lows[ArrayMinimum(d1_lows)];
   double dist_puntos = InpDistanciaPuntos * _Point;

   // Lógica de Proximidad
   bool zona_compra = MathAbs(ask - min_20d) <= dist_puntos; // Cerca del mínimo (Soporte)
   bool zona_venta  = MathAbs(bid - max_20d) <= dist_puntos; // Cerca del máximo (Resistencia)

   // Si no estamos en zona clave, salimos
   if(!zona_compra && !zona_venta) return;


   // 5. EVALUACIÓN DE ESTRATEGIA (LA RECETA)
   string signal_type = "NONE";
   string razon_entrada = "";
   double entry_price = 0;
   double sl_price = 0;
   double tp_price = 0;

   // --- LÓGICA DE COMPRA ---
   if(zona_compra && (ask > ema_h4) && (rsi_h1 < InpRSIOversold))
     {
      signal_type = "COMPRA";
      razon_entrada = "Soporte D1 + H4 Alcista + RSI Sobreventa";
      entry_price = ask;

      // Cálculo Dinámico de Riesgo
      double sl_dist = atr_h1 * InpSL_Multiplier;
      sl_price = entry_price - sl_dist;
      tp_price = entry_price + (sl_dist * InpRiskReward);
     }

   // --- LÓGICA DE VENTA ---
   else if(zona_venta && (bid < ema_h4) && (rsi_h1 > InpRSIOverbought))
     {
      signal_type = "VENTA";
      razon_entrada = "Resistencia D1 + H4 Bajista + RSI Sobrecompra";
      entry_price = bid;

      // Cálculo Dinámico de Riesgo
      double sl_dist = atr_h1 * InpSL_Multiplier;
      sl_price = entry_price + sl_dist;
      tp_price = entry_price - (sl_dist * InpRiskReward);
     }


   // 6. EJECUCIÓN Y ALERTA
   if(signal_type != "NONE")
     {
      // IMPORTANTE: Marcamos la alerta como enviada ANTES de intentar el envío o INDEPENDIENTEMENTE del resultado.
      // Esto previene que un fallo de red (error 4060, timeout) cause un bucle infinito de intentos en cada tick de la misma vela.
      last_alert_time = bar_time;

      // Normalización de Precios
      int digits = (int)SymbolInfoInteger(_Symbol, SYMBOL_DIGITS);
      double norm_entry = NormalizeDouble(entry_price, digits);
      double norm_sl    = NormalizeDouble(sl_price, digits);
      double norm_tp    = NormalizeDouble(tp_price, digits);

      // Enviar Webhook
      if(EnviarWebhook(signal_type, norm_entry, norm_sl, norm_tp, razon_entrada))
        {
         Print("SEÑAL VALIDADA Y ENVIADA: ", signal_type);
        }
      else
        {
         Print("FALLO AL ENVIAR SEÑAL. Se reintentará en la próxima vela H1.");
        }
     }
  }

//+------------------------------------------------------------------+
//| NEWS GUARD: Filtro de Noticias de Alto Impacto                   |
//+------------------------------------------------------------------+
bool HayNoticia()
  {
   // --- OPTIMIZACIÓN DE RENDIMIENTO ---
   // Consultar el calendario es costoso. Lo limitamos a 1 vez por minuto.
   static bool estado_cache = false;
   static datetime proxima_consulta = 0;

   if(TimeCurrent() < proxima_consulta) return estado_cache;

   proxima_consulta = TimeCurrent() + 60; // Actualizar caché en 60 segundos
   estado_cache = false; // Resetear estado por defecto (sin noticias)

   // --- DECLARACIÓN LOCAL DE MONEDAS (Requisito Crítico) ---
   string moneda_base = SymbolInfoString(_Symbol, SYMBOL_CURRENCY_BASE);
   string moneda_profit = SymbolInfoString(_Symbol, SYMBOL_CURRENCY_PROFIT);

   // Rango de tiempo a evaluar
   datetime start = TimeCurrent() - (InpMinsAfter * 60);
   datetime end   = TimeCurrent() + (InpMinsBefore * 60);

   MqlCalendarValue values[];

   // Obtener eventos del calendario
   if(CalendarValueHistory(values, start, end))
     {
      int total_eventos = ArraySize(values);
      for(int i = 0; i < total_eventos; i++)
        {
         MqlCalendarEvent event;
         if(CalendarEventById(values[i].event_id, event))
           {
            // Filtro 1: Importancia Alta (>= 2)
            if(event.importance >= 2)
              {
               // Filtro 2: Moneda relevante para el par actual
               // Comprobamos si la moneda de la noticia coincide con Base o Profit
               if(event.currency == moneda_base || event.currency == moneda_profit)
                 {
                  Print("⚠️ NOTICIA DETECTADA: ", event.name, " [", event.currency, "]");
                  estado_cache = true; // Guardar en caché que hay noticia
                  return true;
                 }
              }
           }
        }
     }

   return false;
  }

//+------------------------------------------------------------------+
//| Enviar Webhook JSON a n8n                                        |
//+------------------------------------------------------------------+
bool EnviarWebhook(string tipo, double precio, double sl, double tp, string razon)
  {
   // Construcción del Payload JSON
   // Formato: {"symbol": "EURUSD", "tipo": "COMPRA", "precio": 1.0500, "sl": 1.0450, "tp": 1.0600, "razon": "..."}
   string json_payload = StringFormat(
      "{\"symbol\": \"%s\", \"tipo\": \"%s\", \"precio\": %.5f, \"sl\": %.5f, \"tp\": %.5f, \"razon\": \"%s\"}",
      _Symbol, tipo, precio, sl, tp, razon
   );

   char data[];
   StringToCharArray(json_payload, data, 0, StringLen(json_payload));

   string headers = "Content-Type: application/json\r\n";
   char result_data[];
   string result_headers;

   ResetLastError();

   // Envío de la solicitud
   int res_code = WebRequest("POST", WEBHOOK_URL, headers, 5000, data, data, result_headers);

   if(res_code == 200)
     {
      Print("WEBHOOK ENVIADO EXITOSAMENTE: ", json_payload);
      return true;
     }
   else
     {
      Print("ERROR AL ENVIAR WEBHOOK. Código: ", res_code, " Error MQL: ", GetLastError());
      if(GetLastError() == 4060)
         Print("RECUERDE: Añada la URL '", WEBHOOK_URL, "' en Herramientas > Opciones > Asesores Expertos.");
      return false;
     }
  }
//+------------------------------------------------------------------+
