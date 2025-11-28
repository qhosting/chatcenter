//+------------------------------------------------------------------+
//|                                       AurumSniper_Ultimate.mq5  |
//|                                  Copyright 2025, Aurum Capital  |
//+------------------------------------------------------------------+
#property copyright "Aurum Capital"
#property version   "4.00" // Versión Final Integrada
#property strict
#include <Trade\Trade.mqh>

//--- INPUTS
input group "Conexión"
// Tu URL de producción ya configurada:
input string InpWebhookURL = "https://n8n.whatscloud.site/webhook/aurum-trading-alerts";

input group "Estrategia"
input int    InpDistanciaPuntos = 100; // Distancia D1
input int    InpMagicNumber = 8888;
input int    InpEMAPeriod   = 200;     // Tendencia H4
input int    InpRSIPeriod   = 14;      // Gatillo H1
input double InpRSIOverbought = 70.0;
input double InpRSIOversold   = 30.0;

input group "Riesgo (SL/TP)"
input int    InpATRPeriod   = 14;
input double InpSL_Multiplier = 1.5;   // SL = 1.5 veces el ATR
input double InpRiskReward    = 2.0;   // TP = 2 veces el SL

input group "Noticias (News Guard)"
input bool   InpUseNewsFilter = true;
input int    InpMinsBefore    = 60;    // Pausar 60 min antes
input int    InpMinsAfter     = 30;    // Pausar 30 min despues

//--- GLOBALES
CTrade trade;
int hMA, hRSI, hATR;
datetime last_alert_time = 0;

//+------------------------------------------------------------------+
//| Inicialización                                                   |
//+------------------------------------------------------------------+
int OnInit()
  {
   // Inicializar Indicadores
   hMA  = iMA(Symbol(), PERIOD_H4, InpEMAPeriod, 0, MODE_EMA, PRICE_CLOSE);
   hRSI = iRSI(Symbol(), PERIOD_H1, InpRSIPeriod, PRICE_CLOSE);
   hATR = iATR(Symbol(), PERIOD_H1, InpATRPeriod);

   if(hMA==INVALID_HANDLE || hRSI==INVALID_HANDLE || hATR==INVALID_HANDLE)
     {
      Print("Error fatal: No se pudieron crear los indicadores.");
      return(INIT_FAILED);
     }

   trade.SetExpertMagicNumber(InpMagicNumber);
   Print(">>> AURUM SNIPER ULTIMATE: SISTEMA ONLINE <<<");

   return(INIT_SUCCEEDED);
  }

void OnDeinit(const int reason)
  {
   IndicatorRelease(hMA);
   IndicatorRelease(hRSI);
   IndicatorRelease(hATR);
  }

//+------------------------------------------------------------------+
//| Lógica Principal (Tick)                                          |
//+------------------------------------------------------------------+
void OnTick()
  {
   // 1. Control Velas (Ejecutar solo 1 vez por vela H1)
   // Comprobamos si ya enviamos alerta en esta vela.
   // NOTA: Si no hemos enviado alerta, seguimos evaluando cada tick.
   datetime bar_time = iTime(Symbol(), PERIOD_H1, 0);
   if(last_alert_time == bar_time) return;

   // 2. Filtro Noticias (El Escudo)
   if(InpUseNewsFilter)
     {
      // Llamamos a la función autónoma que revisa el calendario
      if(HayNoticia())
        {
         static datetime last_print = 0;
         // Imprimimos aviso en el log solo cada minuto para no saturar
         if(TimeCurrent() - last_print > 60) {
            Print("⛔ NEWS GUARD: Trading pausado por noticias de alto impacto.");
            last_print = TimeCurrent();
         }
         return; // Si hay noticia, SALIMOS. No se ejecuta nada más.
        }
     }

   // 3. Datos de Mercado
   double ask = SymbolInfoDouble(Symbol(), SYMBOL_ASK);
   double bid = SymbolInfoDouble(Symbol(), SYMBOL_BID);

   double maVal[], rsiVal[], atrVal[];
   ArraySetAsSeries(maVal,true); ArraySetAsSeries(rsiVal,true); ArraySetAsSeries(atrVal,true);

   // Copiar buffers
   if(CopyBuffer(hMA,0,0,1,maVal)<1 || CopyBuffer(hRSI,0,0,1,rsiVal)<1 || CopyBuffer(hATR,0,0,1,atrVal)<1) return;

   double maH4 = maVal[0];
   double rsiH1 = rsiVal[0];
   double atrH1 = atrVal[0];

   // 4. Filtro D1 (Soportes/Resistencias)
   double d1_highs[], d1_lows[];
   if(CopyHigh(Symbol(),PERIOD_D1,1,20,d1_highs)<20 || CopyLow(Symbol(),PERIOD_D1,1,20,d1_lows)<20) return;

   double max_20d = d1_highs[ArrayMaximum(d1_highs)];
   double min_20d = d1_lows[ArrayMinimum(d1_lows)];
   double dist = InpDistanciaPuntos * _Point;

   bool zona_compra = MathAbs(ask - min_20d) <= dist;
   bool zona_venta  = MathAbs(bid - max_20d) <= dist;

   if(!zona_compra && !zona_venta) return;

   // 5. Señales (La Receta)
   string signal = "NONE";
   string reason = "";
   double sl = 0, tp = 0;

   // COMPRA
   if(zona_compra && ask > maH4 && rsiH1 < InpRSIOversold)
     {
      signal = "COMPRA";
      reason = "Zona D1 + H4 Alcista + RSI Sobreventa";
      double sl_dist = atrH1 * InpSL_Multiplier;
      sl = ask - sl_dist;
      tp = ask + (sl_dist * InpRiskReward);
     }
   // VENTA
   else if(zona_venta && bid < maH4 && rsiH1 > InpRSIOverbought)
     {
      signal = "VENTA";
      reason = "Zona D1 + H4 Bajista + RSI Sobrecompra";
      double sl_dist = atrH1 * InpSL_Multiplier;
      sl = bid + sl_dist;
      tp = bid - (sl_dist * InpRiskReward);
     }

   // 6. Enviar Alerta
   if(signal != "NONE")
     {
      int digits = (int)SymbolInfoInteger(Symbol(), SYMBOL_DIGITS);
      EnviarWebhook(signal, (signal=="COMPRA"?ask:bid), NormalizeDouble(sl,digits), NormalizeDouble(tp,digits), reason);
      last_alert_time = bar_time;
     }
  }

//+------------------------------------------------------------------+
//| FUNCIÓN FILTRO DE NOTICIAS (AUTÓNOMA BLINDADA)                   |
//+------------------------------------------------------------------+
bool HayNoticia()
  {
   // OPTIMIZACIÓN: Cachear resultado para no saturar el terminal consultando DB cada tick.
   // Se verifica cada 60 segundos.
   static bool estado_noticia = false;
   static datetime proxima_revision = 0;

   if(TimeCurrent() < proxima_revision) return estado_noticia;

   proxima_revision = TimeCurrent() + 60; // Revisar de nuevo en 1 minuto

   // Definimos las monedas DENTRO de la función para evitar errores de variables no declaradas
   string local_Base   = SymbolInfoString(Symbol(), SYMBOL_CURRENCY_BASE);
   string local_Profit = SymbolInfoString(Symbol(), SYMBOL_CURRENCY_PROFIT);

   MqlCalendarValue values[];
   datetime start = TimeCurrent() - (InpMinsAfter*60);
   datetime end   = TimeCurrent() + (InpMinsBefore*60);

   // Resetear estado antes de comprobar
   estado_noticia = false;

   if(CalendarValueHistory(values, start, end))
     {
      for(int i=0; i<ArraySize(values); i++)
        {
         MqlCalendarEvent event;
         if(CalendarEventById(values[i].event_id, event))
           {
            // Importancia Alta (>= 2)
            if(event.importance >= 2)
              {
               // Verificar si afecta a NUESTRAS monedas
               bool afecta_base   = (StringFind(event.currency, local_Base) >= 0);
               bool afecta_profit = (StringFind(event.currency, local_Profit) >= 0);

               if(afecta_base || afecta_profit)
                 {
                  Print("⚠️ ALERTA: Noticia detectada -> ", event.name);
                  estado_noticia = true;
                  return true;
                 }
              }
           }
        }
     }
   return false;
  }

//+------------------------------------------------------------------+
//| Enviar Webhook a n8n                                             |
//+------------------------------------------------------------------+
void EnviarWebhook(string tipo, double precio, double sl, double tp, string razon)
  {
   if(InpWebhookURL == "") return;

   string json = StringFormat("{\"symbol\": \"%s\", \"tipo\": \"%s\", \"precio\": %.5f, \"sl\": %.5f, \"tp\": %.5f, \"razon\": \"%s\"}",
                              Symbol(), tipo, precio, sl, tp, razon);

   char data[]; StringToCharArray(json, data, 0, StringLen(json));
   string headers = "Content-Type: application/json\r\n";
   char res[]; string res_headers;

   // Nota: Se usa 'data' como buffer de respuesta también si no importa el contenido
   int result = WebRequest("POST", InpWebhookURL, headers, 5000, data, data, res_headers);

   if(result == -1)
      Print("Error enviando Webhook: ", GetLastError());
   else
      Print("ALERTA ENVIADA: ", json);
  }
//+------------------------------------------------------------------+
