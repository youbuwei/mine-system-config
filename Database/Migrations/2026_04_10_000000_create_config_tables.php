<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateConfigTables extends Migration
{
    /**
     * Run the migrations.
     * 合并后的最终3级配置结构（模块 > 分组 > 配置项）.
     */
    public function up(): void
    {
        // 配置模块表
        Schema::create('config_module', static function (Blueprint $table) {
            $table->comment('配置模块表');
            $table->bigIncrements('id')->comment('主键ID');
            $table->string('key', 100)->unique()->comment('模块标识 (如: order, payment)');
            $table->string('name', 100)->comment('模块名称');
            $table->string('description', 500)->default('')->comment('模块描述');
            $table->string('icon', 100)->default('')->comment('图标');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->unsignedTinyInteger('is_enabled')->default(1)->comment('是否启用: 1=是, 0=否');
            $table->string('definition_path', 255)->default('')->comment('定义文件路径');
            $table->datetimes();
            $table->softDeletes();

            $table->index('sort');
            $table->index('is_enabled');
        });

        // 配置分组表
        Schema::create('config_group', static function (Blueprint $table) {
            $table->comment('配置分组表');
            $table->bigIncrements('id')->comment('主键ID');
            $table->unsignedBigInteger('module_id')->nullable()->comment('所属模块ID');
            $table->string('key', 100)->comment('分组标识');
            $table->string('name', 100)->comment('分组名称');
            $table->string('description', 500)->default('')->comment('分组描述');
            $table->string('icon', 100)->default('')->comment('图标');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态: 1=启用, 0=禁用');
            $table->string('definition_path', 255)->default('')->comment('定义文件路径');
            $table->datetimes();
            $table->softDeletes();

            $table->index('module_id');
            $table->index('sort');
            $table->foreign('module_id', 'fk_group_module')
                ->references('id')
                ->on('config_module')
                ->onDelete('cascade');
        });

        // 配置项定义表
        Schema::create('config_item', static function (Blueprint $table) {
            $table->comment('配置项定义表');
            $table->bigIncrements('id')->comment('主键ID');
            $table->unsignedBigInteger('module_id')->nullable()->comment('所属模块ID（冗余字段）');
            $table->unsignedBigInteger('group_id')->comment('所属分组ID');
            $table->string('key', 100)->comment('配置项标识');
            $table->string('name', 100)->comment('配置项名称');
            $table->string('description', 500)->default('')->comment('配置项说明');
            $table->string('type', 50)->comment('字段类型: text/textarea/switch/select/multiSelect/number等');
            $table->json('options')->nullable()->comment('选项配置');
            $table->json('validation')->nullable()->comment('验证规则');
            $table->text('default_value')->nullable()->comment('默认值');
            $table->string('placeholder', 200)->default('')->comment('占位提示');
            $table->string('tooltip', 500)->default('')->comment('帮助提示');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->unsignedTinyInteger('is_encrypted')->default(0)->comment('是否加密存储');
            $table->unsignedTinyInteger('is_system')->default(0)->comment('是否系统字段');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态: 1=启用, 0=禁用');
            $table->string('definition_path', 255)->default('')->comment('定义文件路径');
            $table->datetimes();
            $table->softDeletes();

            $table->unique(['group_id', 'key'], 'uk_group_key');
            $table->index('module_id');
            $table->index('group_id');
            $table->index('type');
            $table->index('sort');

            $table->foreign('module_id', 'fk_item_module')
                ->references('id')
                ->on('config_module')
                ->onDelete('cascade');

            $table->foreign('group_id', 'fk_item_group')
                ->references('id')
                ->on('config_group')
                ->onDelete('cascade');
        });

        // 配置值表
        Schema::create('config_value', static function (Blueprint $table) {
            $table->comment('配置值表');
            $table->bigIncrements('id')->comment('主键ID');
            $table->unsignedBigInteger('field_id')->comment('字段ID');
            $table->string('scope', 20)->default('default')->comment('作用域: default/tenant/{id}');
            $table->text('value')->nullable()->comment('配置值');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('最后更新人');
            $table->datetimes();

            $table->unique(['field_id', 'scope'], 'uk_field_scope');
            $table->index('scope');
            $table->index('field_id');

            $table->foreign('field_id', 'fk_value_item')
                ->references('id')
                ->on('config_item')
                ->onDelete('cascade');
        });

        // 配置变更日志表
        Schema::create('config_log', static function (Blueprint $table) {
            $table->comment('配置变更日志表');
            $table->bigIncrements('id')->comment('主键ID');
            $table->unsignedBigInteger('field_id')->comment('字段ID');
            $table->string('scope', 20)->default('default')->comment('作用域');
            $table->text('old_value')->nullable()->comment('旧值');
            $table->text('new_value')->nullable()->comment('新值');
            $table->unsignedBigInteger('changed_by')->nullable()->comment('操作人');
            $table->timestamp('changed_at')->useCurrent()->comment('操作时间');
            $table->string('ip', 45)->default('')->comment('操作IP');

            $table->index(['field_id', 'scope'], 'idx_field_scope');
            $table->index('changed_at');
            $table->index('changed_by');

            $table->foreign('field_id', 'fk_log_item')
                ->references('id')
                ->on('config_item')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_log');
        Schema::dropIfExists('config_value');
        Schema::dropIfExists('config_item');
        Schema::dropIfExists('config_group');
        Schema::dropIfExists('config_module');
    }
}
