<?php

namespace App\Enums;

enum Violation: string
{
    case Overlap = 'overlap';
    case Workshop = 'workshop';
    case VgpExpired = 'vgp_expired';
    case Transfer = 'transfer';
    case MissingPurchaseOrder = 'missing_purchase_order';
}
