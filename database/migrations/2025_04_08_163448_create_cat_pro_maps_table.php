<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCatProMapsTable extends Migration
{
    public function up(): void
    {
        Schema::create('cat_pro_maps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cat_id');
            $table->unsignedBigInteger('pro_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_pro_maps');
    }
}
