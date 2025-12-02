//+------------------------------------------------------------------+
//|                                            AurumSniperV7.mq5    |
//|                                  Copyright 2025, Aurum Capital  |
//+------------------------------------------------------------------+
#property copyright "Aurum Capital"
#property version   "7.20" // Version Final (Auto-Trade Switch)
#property strict
#include <Trade\Trade.mqh>

//--- INPUTS
input group "Configuracion General"
input bool   InpEnableAutoTrade = true; // Activar Auto-Trading (false = Solo Alertas)

input group "Estrategia M5 Scalping"
input int    InpDistanciaPuntos = 50;  // Distancia H1 Macro (Puntos)
input int    InpEMAPeriod   = 200;     // Periodo EMA (M15)
input int    InpRSIPeriod   = 14;      // Periodo RSI (M5)
input double InpRSIOverbought = 70.0;
input double InpRSIOversold   = 30.0;

input group "Riesgo (Micro Risk)"
input double InpLotSize     = 0.01;    // Lotaje Fijo (Recomendado $100)
input int    InpATRPeriod   = 14;
input double InpSL_Multiplier = 1.5;   // SL = ATR M5 * 1.5
input double InpRiskReward    = 2.0;   // TP = SL * 2.0

input group "Gestion (Profit Locker)"
input int    InpBE_Trigger    = 100;   // Activar BreakEven a +100 puntos (10 pips)
input int    InpBE_Offset     = 10;    // Mover SL a Entrada + 10 puntos
input int    InpTrail_Dist    = 150;   // Distancia Trailing (150 puntos)
input int    InpTrail_Step    = 50;    // Paso Trailing (50 puntos)

input group "News Guard"
input bool   InpUseNewsFilter = true;
input int    InpMinsBefore    = 60;
input int    InpMinsAfter     = 30;

//--- CONSTANTES HARDCODED
const string WEBHOOK_URL = "https://n8n.whatscloud.site/webhook/aurum-trading-alerts";
const int    MAGIC_NUMBER = 9999;

//--- GLOBALES
CTrade trade;
int hMA, hRSI, hATR;
datetime last_alert_time = 0;

//+------------------------------------------------------------------+
//| Inicializacion                                                   |
//+------------------------------------------------------------------+
int OnInit()
  {
   // 1. Filtro Tendencia: EMA 200 en M15
   hMA  = iMA(_Symbol, PERIOD_M15, InpEMAPeriod, 0, MODE_EMA, PRICE_CLOSE);

   // 2. Gatillo: RSI 14 en M5 (Chart actual)
   hRSI = iRSI(_Symbol, PERIOD_M5, InpRSIPeriod, PRICE_CLOSE);

   // 3. Riesgo: ATR 14 en M5
   hATR = iATR(_Symbol, PERIOD_M5, InpATRPeriod);

   if(hMA == INVALID_HANDLE || hRSI == INVALID_HANDLE || hATR == INVALID_HANDLE)
     {
      Print("ERROR FATAL: Fallo al crear indicadores.");
      return(INIT_FAILED);
     }

   trade.SetExpertMagicNumber(MAGIC_NUMBER);
   Print(">>> AURUM SNIPER V7.20: ONLINE (Auto-Trade: ", InpEnableAutoTrade, ") <<<");

   return(INIT_SUCCEEDED);
  }

//+------------------------------------------------------------------+
//| Desinicializacion                                                |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
  {
   IndicatorRelease(hMA);
   IndicatorRelease(hRSI);
   IndicatorRelease(hATR);
  }

//+------------------------------------------------------------------+
//| Funcion Principal (OnTick)                                       |
//+------------------------------------------------------------------+
void OnTick()
  {
   // 1. GESTION DE POSICIONES (Profit Locker)
   // Siempre activa para gestionar operaciones manuales o automaticas
   GestionarPosiciones();

   // 2. CONTROL DE VELAS (1 alerta por vela M5)
   datetime bar_time = iTime(_Symbol, PERIOD_M5, 0);
   if(last_alert_time == bar_time) return;

   // 3. NEWS GUARD
   if(InpUseNewsFilter)
     {
      if(HayNoticia())
        {
         static datetime last_print = 0;
         if(TimeCurrent() - last_print > 300)
           {
            Print("NEWS GUARD: Mercado volatil. Trading pausado.");
            last_print = TimeCurrent();
           }
         return;
        }
     }

   // 4. DATOS DE MERCADO
   double ask = SymbolInfoDouble(_Symbol, SYMBOL_ASK);
   double bid = SymbolInfoDouble(_Symbol, SYMBOL_BID);

   // Buffers
   double maVal[], rsiVal[], atrVal[];
   ArraySetAsSeries(maVal, true);
   ArraySetAsSeries(rsiVal, true);
   ArraySetAsSeries(atrVal, true);

   if(CopyBuffer(hMA, 0, 0, 1, maVal) < 1 ||
      CopyBuffer(hRSI, 0, 0, 1, rsiVal) < 1 ||
      CopyBuffer(hATR, 0, 0, 1, atrVal) < 1) return;

   double ema_m15 = maVal[0];
   double rsi_m5  = rsiVal[0];
   double atr_m5  = atrVal[0];

   // 5. FILTRO MACRO (H1 - 20 Horas)
   double h1_highs[], h1_lows[];
   if(CopyHigh(_Symbol, PERIOD_H1, 1, 20, h1_highs) < 20 ||
      CopyLow(_Symbol, PERIOD_H1, 1, 20, h1_lows) < 20) return;

   double max_20h = h1_highs[ArrayMaximum(h1_highs)];
   double min_20h = h1_lows[ArrayMinimum(h1_lows)];
   double dist_puntos = InpDistanciaPuntos * _Point;

   bool zona_compra = MathAbs(ask - min_20h) <= dist_puntos;
   bool zona_venta  = MathAbs(bid - max_20h) <= dist_puntos;

   if(!zona_compra && !zona_venta) return;


   // 6. ESTRATEGIA
   string signal = "NONE";
   string razon = "";
   double entry = 0, sl = 0, tp = 0;

   // COMPRA: Zona H1 + EMA M15 Alcista + RSI M5 < 30
   if(zona_compra && (ask > ema_m15) && (rsi_m5 < InpRSIOversold))
     {
      signal = "COMPRA";
      razon = "Macro H1 + M15 Bull + M5 RSI Sobrevendido";
      entry = ask;
      double sl_dist = atr_m5 * InpSL_Multiplier;
      sl = entry - sl_dist;
      tp = entry + (sl_dist * InpRiskReward);
     }
   // VENTA: Zona H1 + EMA M15 Bajista + RSI M5 > 70
   else if(zona_venta && (bid < ema_m15) && (rsi_m5 > InpRSIOverbought))
     {
      signal = "VENTA";
      razon = "Macro H1 + M15 Bear + M5 RSI Sobrecomprado";
      entry = bid;
      double sl_dist = atr_m5 * InpSL_Multiplier;
      sl = entry + sl_dist;
      tp = entry - (sl_dist * InpRiskReward);
     }

   // 7. EJECUCION Y ALERTA
   if(signal != "NONE")
     {
      last_alert_time = bar_time; // Marcar vela usada

      int digits = (int)SymbolInfoInteger(_Symbol, SYMBOL_DIGITS);
      double n_entry = NormalizeDouble(entry, digits);
      double n_sl    = NormalizeDouble(sl, digits);
      double n_tp    = NormalizeDouble(tp, digits);

      // --- AUTO TRADE (Condicional) ---
      if(InpEnableAutoTrade)
        {
         if(signal == "COMPRA")
           {
            if(!trade.Buy(InpLotSize, _Symbol, n_entry, n_sl, n_tp, razon))
               Print("ERROR COMPRA: ", GetLastError());
           }
         else if(signal == "VENTA")
           {
            if(!trade.Sell(InpLotSize, _Symbol, n_entry, n_sl, n_tp, razon))
               Print("ERROR VENTA: ", GetLastError());
           }
        }
      else
        {
         Print("MODO ALERTA: Senal ", signal, " detectada pero Auto-Trade esta desactivado.");
        }

      // Enviar Webhook (Siempre enviar alerta)
      if(EnviarWebhook(signal, n_entry, n_sl, n_tp, razon, digits))
        {
         Print("SENAL ENVIADA: ", signal);
        }
     }
  }

//+------------------------------------------------------------------+
//| GESTION DE POSICIONES (Profit Locker)                            |
//+------------------------------------------------------------------+
void GestionarPosiciones()
  {
   for(int i = PositionsTotal() - 1; i >= 0; i--)
     {
      ulong ticket = PositionGetTicket(i);
      if(ticket <= 0) continue;

      if(PositionGetString(POSITION_SYMBOL) != _Symbol) continue;
      if(PositionGetInteger(POSITION_MAGIC) != MAGIC_NUMBER) continue;

      double open_price = PositionGetDouble(POSITION_PRICE_OPEN);
      double current_sl = PositionGetDouble(POSITION_SL);
      double current_price = PositionGetDouble(POSITION_PRICE_CURRENT);
      long type = PositionGetInteger(POSITION_TYPE);
      int digits = (int)SymbolInfoInteger(_Symbol, SYMBOL_DIGITS);
      double point = SymbolInfoDouble(_Symbol, SYMBOL_POINT);

      // --- COMPRAS ---
      if(type == POSITION_TYPE_BUY)
        {
         // Break Even
         if(current_price - open_price > InpBE_Trigger * point)
           {
            double new_sl = open_price + (InpBE_Offset * point);
            if(new_sl > current_sl)
              {
               trade.PositionModify(ticket, NormalizeDouble(new_sl, digits), PositionGetDouble(POSITION_TP));
              }
           }
         // Trailing Stop
         if(current_price - current_sl > InpTrail_Dist * point)
           {
            double new_sl = current_price - (InpTrail_Dist * point);
            if(new_sl > current_sl + (InpTrail_Step * point))
              {
               trade.PositionModify(ticket, NormalizeDouble(new_sl, digits), PositionGetDouble(POSITION_TP));
              }
           }
        }
      // --- VENTAS ---
      else if(type == POSITION_TYPE_SELL)
        {
         // Break Even
         if(open_price - current_price > InpBE_Trigger * point)
           {
            double new_sl = open_price - (InpBE_Offset * point);
            if(new_sl < current_sl || current_sl == 0)
              {
               trade.PositionModify(ticket, NormalizeDouble(new_sl, digits), PositionGetDouble(POSITION_TP));
              }
           }
         // Trailing Stop
         if(current_sl - current_price > InpTrail_Dist * point || (current_sl == 0 && open_price - current_price > InpTrail_Dist * point))
           {
             double calc_sl = (current_sl == 0) ? open_price : current_sl; // Referencia
             double new_sl = current_price + (InpTrail_Dist * point);

             if(new_sl < calc_sl - (InpTrail_Step * point) || current_sl == 0)
               {
                trade.PositionModify(ticket, NormalizeDouble(new_sl, digits), PositionGetDouble(POSITION_TP));
               }
           }
        }
     }
  }

//+------------------------------------------------------------------+
//| NEWS GUARD (Filtro Compatible)                                   |
//+------------------------------------------------------------------+
bool HayNoticia()
  {
   static bool cache = false;
   static datetime next_check = 0;

   if(TimeCurrent() < next_check) return cache;

   next_check = TimeCurrent() + 60;
   cache = false;

   // Declaracion LOCAL para evitar errores de scope
   string l_base   = SymbolInfoString(_Symbol, SYMBOL_CURRENCY_BASE);
   string l_profit = SymbolInfoString(_Symbol, SYMBOL_CURRENCY_PROFIT);

   datetime start = TimeCurrent() - (InpMinsAfter * 60);
   datetime end   = TimeCurrent() + (InpMinsBefore * 60);
   MqlCalendarValue values[];

   if(CalendarValueHistory(values, start, end))
     {
      int total = ArraySize(values);
      for(int i = 0; i < total; i++)
        {
         MqlCalendarEvent event;
         if(CalendarEventById(values[i].event_id, event))
           {
            if(event.importance >= 2)
              {
               // Usamos MqlCalendarCountry para obtener divisa segura
               MqlCalendarCountry country;
               if(CalendarCountryById(event.country_id, country))
                 {
                  string c_currency = country.currency;
                  if(StringCompare(c_currency, l_base) == 0 || StringCompare(c_currency, l_profit) == 0)
                    {
                     Print("NOTICIA ALTO IMPACTO: ", event.name);
                     cache = true;
                     return true;
                    }
                 }
              }
           }
        }
     }
   return false;
  }

//+------------------------------------------------------------------+
//| Enviar Webhook JSON                                              |
//+------------------------------------------------------------------+
bool EnviarWebhook(string tipo, double precio, double sl, double tp, string razon, int digits)
  {
   string s_p  = DoubleToString(precio, digits);
   string s_sl = DoubleToString(sl, digits);
   string s_tp = DoubleToString(tp, digits);

   string json = StringFormat(
      "{\"symbol\": \"%s\", \"tipo\": \"%s\", \"precio\": %s, \"sl\": %s, \"tp\": %s, \"razon\": \"%s\"}",
      _Symbol, tipo, s_p, s_sl, s_tp, razon
   );

   char data[];
   StringToCharArray(json, data, 0, StringLen(json));
   string headers = "Content-Type: application/json\r\n";
   char res_data[]; string res_head;

   ResetLastError();
   int res = WebRequest("POST", WEBHOOK_URL, headers, 5000, data, data, res_head);

   if(res == 200) return true;

   Print("ERROR Webhook: ", res, " Err: ", GetLastError());
   return false;
  }
//+------------------------------------------------------------------+
