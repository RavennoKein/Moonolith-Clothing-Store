<?php

namespace App\Services;

use App\Models\ShippingRate;
class ShippingService
{
  public static function calculate(string $destinationCity): int
  {
    return ShippingRate::where('origin_city', 'Mojokerto')
      ->where('destination_city', $destinationCity)
      ->value('cost') ?? 25000;
  }
}