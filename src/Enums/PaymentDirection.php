<?php

namespace Whilesmart\Payments\Enums;

enum PaymentDirection: string
{
    case Inbound = 'inbound';   // money in (collections)
    case Outbound = 'outbound'; // money out (payouts, refunds)
}
