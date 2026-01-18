<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EastJavaShippingSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            // ===== KOTA =====
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Surabaya', 'cost' => 20000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Malang', 'cost' => 18000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kediri', 'cost' => 18000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Blitar', 'cost' => 18000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Mojokerto', 'cost' => 10000], // asal
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Madiun', 'cost' => 20000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Pasuruan', 'cost' => 15000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Probolinggo', 'cost' => 20000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Batu', 'cost' => 18000],

            // ===== KABUPATEN =====
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Malang', 'cost' => 18000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Kediri', 'cost' => 18000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Blitar', 'cost' => 18000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Mojokerto', 'cost' => 12000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Sidoarjo', 'cost' => 15000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Gresik', 'cost' => 15000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Jombang', 'cost' => 17000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Nganjuk', 'cost' => 18000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Lamongan', 'cost' => 17000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Tuban', 'cost' => 20000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Bojonegoro', 'cost' => 20000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Madiun', 'cost' => 20000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Ponorogo', 'cost' => 22000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Trenggalek', 'cost' => 22000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Tulungagung', 'cost' => 20000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Banyuwangi', 'cost' => 30000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Situbondo', 'cost' => 28000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Bondowoso', 'cost' => 26000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Jember', 'cost' => 26000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Lumajang', 'cost' => 24000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Probolinggo', 'cost' => 24000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Pasuruan', 'cost' => 17000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Bangkalan', 'cost' => 25000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Sampang', 'cost' => 28000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Pamekasan', 'cost' => 28000],
            ['origin_city' => 'Mojokerto', 'destination_city' => 'Kabupaten Sumenep', 'cost' => 30000],
        ];

        DB::table('shipping_rates')->insert($cities);
    }
}
