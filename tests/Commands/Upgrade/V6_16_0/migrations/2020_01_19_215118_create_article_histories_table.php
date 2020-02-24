<?php

namespace Tests\Commands\Upgrade\V6_16_0\Migrations;

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateArticleHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('article_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('article_id');
            $table->mediumText('markdown');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \Schema::dropIfExists('article_histories');
    }
}
