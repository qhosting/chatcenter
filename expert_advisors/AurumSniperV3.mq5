//+------------------------------------------------------------------+
//|                                            AurumSniperV3.mq5    |
//|                                  Copyright 2025, Aurum Capital  |
//+------------------------------------------------------------------+
#property copyright "Aurum Capital"
#property version   "3.70" // Versión Final (News Guard Optimizado + SL/TP)
#property strict
#include <Trade\Trade.mqh>

//--- INPUTS
input group "Conexión"
// URL YA CONFIGURADA:
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
   // Inicializar Indicadores con Symbol() para evitar errores
   hMA  = iMA(Symbol(), PERIOD_H4, InpEMAPeriod, 0, MODE_EMA, PRICE_CLOSE);
   hRSI = iRSI(Symbol(), PERIOD_H1, InpRSIPeriod, PRICE_CLOSE);
   hATR = iATR(Symbol(), PERIOD_H1, InpATRPeriod);

   if(hMA==INVALID_HANDLE || hRSI==INVALID_HANDLE || hATR==INVALID_HANDLE)
     {
      Print("Error fatal: No se pudieron crear los indicadores.");
      return(INIT_FAILED);
     }

   trade.SetExpertMagicNumber(InpMagicNumber);
   Print(">>> AURUM SNIPER V3.70: SISTEMA ONLINE <<<");

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
   // 1. Control Velas
   datetime bar_time = iTime(Symbol(), PERIOD_H1, 0);
   if(last_alert_time == bar_time) return;

   // 2. Filtro Noticias (El Escudo)
   if(InpUseNewsFilter)
     {
      // Pasamos las monedas a la función para evitar error de variables no declaradas
      string moneda1 = SymbolInfoString(Symbol(), SYMBOL_CURRENCY_BASE);
      string moneda2 = SymbolInfoString(Symbol(), SYMBOL_CURRENCY_PROFIT);

      if(HayNoticia(moneda1, moneda2))
        {
         static datetime last_print = 0;
         if(TimeCurrent() - last_print > 60) {
            Print("⛔ NEWS GUARD: Trading pausado por noticias de alto impacto.");
            last_print = TimeCurrent();
         }
         return; // Si hay noticia, ABORTAR operación.
        }
     }

   // 3. Datos de Mercado
   double ask = SymbolInfoDouble(Symbol(), SYMBOL_ASK);
   double bid = SymbolInfoDouble(Symbol(), SYMBOL_BID);

   double maVal[], rsiVal[], atrVal[];
   ArraySetAsSeries(maVal,true);
   ArraySetAsSeries(rsiVal,true);
   ArraySetAsSeries(atrVal,true);

   if(CopyBuffer(hMA,0,0,1,maVal)<1 || CopyBuffer(hRSI,0,0,1,rsiVal)<1 || CopyBuffer(hATR,0,0,1,atrVal)<1) return;

   double maH4 = maVal[0];
   double rsiH1 = rsiVal[0];
   double atrH1 = atrVal[0];

   // 4. Filtro D1
   double d1_highs[], d1_lows[];
   if(CopyHigh(Symbol(),PERIOD_D1,1,20,d1_highs)<20 || CopyLow(Symbol(),PERIOD_D1,1,20,d1_lows)<20) return;

   double max_20d = d1_highs[ArrayMaximum(d1_highs)];
   double min_20d = d1_lows[ArrayMinimum(d1_lows)];
   double dist = InpDistanciaPuntos * _Point;

   bool zona_compra = MathAbs(ask - min_20d) <= dist;
   bool zona_venta  = MathAbs(bid - max_20d) <= dist;

   if(!zona_compra && !zona_venta) return;

   // 5. Señales
   string signal = "NONE";
   string reason = "";
   double sl = 0, tp = 0;

   // COMPRA
   if(zona_compra && ask > maH4 && rsiH1 < InpRSIOversold)
     {
      signal = "COMPRA";
      reason = "Zona D1 + H4 Alcista + RSI Sobreventa";
      // Calculo SL/TP con ATR
      double sl_dist = atrH1 * InpSL_Multiplier;
      sl = ask - sl_dist;
      tp = ask + (sl_dist * InpRiskReward);
     }
   // VENTA
   else if(zona_venta && bid < maH4 && rsiH1 > InpRSIOverbought)
     {
      signal = "VENTA";
      reason = "Zona D1 + H4 Bajista + RSI Sobrecompra";
      // Calculo SL/TP con ATR
      double sl_dist = atrH1 * InpSL_Multiplier;
      sl = bid + sl_dist;
      tp = bid - (sl_dist * InpRiskReward);
     }

   // 6. Enviar
   if(signal != "NONE")
     {
      int digits = (int)SymbolInfoInteger(Symbol(), SYMBOL_DIGITS);
      EnviarWebhook(signal, (signal=="COMPRA"?ask:bid), NormalizeDouble(sl,digits), NormalizeDouble(tp,digits), reason);
      last_alert_time = bar_time;
     }
  }

//+------------------------------------------------------------------+
//| FUNCIÓN FILTRO DE NOTICIAS (PARAMETRIZADA Y OPTIMIZADA)          |
//+------------------------------------------------------------------+
bool HayNoticia(string c1, string c2)
  {
   // MEJORA: Caché estático para reducir llamadas a la base de datos (cada 60 segundos)
   static bool estado_noticia = false;
   static datetime proxima_revision = 0;

   if(TimeCurrent() < proxima_revision) return estado_noticia;

   proxima_revision = TimeCurrent() + 60; // Siguiente revisión en 1 minuto
   estado_noticia = false; // Resetear estado

   MqlCalendarValue values[];
   datetime start = TimeCurrent() - (InpMinsAfter*60);
   datetime end   = TimeCurrent() + (InpMinsBefore*60);

   if(CalendarValueHistory(values, start, end))
     {
      int total = ArraySize(values);
      for(int i=0; i<total; i++)
        {
         MqlCalendarEvent event;
         if(CalendarEventById(values[i].event_id, event))
           {
            // Verificar importancia (2=Alta)
            if(event.importance >= 2)
              {
               // Verificar monedas (Comparación directa string == string)
               if(event.currency == c1 || event.currency == c2)
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
//| Enviar Webhook                                                   |
//+------------------------------------------------------------------+
void EnviarWebhook(string tipo, double precio, double sl, double tp, string razon)
  {
   if(InpWebhookURL == "") return;

   string json = StringFormat("{\"symbol\": \"%s\", \"tipo\": \"%s\", \"precio\": %.5f, \"sl\": %.5f, \"tp\": %.5f, \"razon\": \"%s\"}",
                              Symbol(), tipo, precio, sl, tp, razon);

   char data[]; StringToCharArray(json, data, 0, StringLen(json));
   string headers = "Content-Type: application/json\r\n";
   char res[]; string res_headers;

   // MEJORA: Gestión de errores en WebRequest
   ResetLastError();
   int res_code = WebRequest("POST", InpWebhookURL, headers, 5000, data, data, res_headers);

   if(res_code != 200)
     {
      Print("Error enviando Webhook. Código: ", res_code, " Error MQL: ", GetLastError());
     }
   else
     {
      Print("ALERTA ENVIADA: ", json);
     }
  }
//+------------------------------------------------------------------+
