<?php

declare(strict_types=1);

namespace Plugin\Youbuwei\SystemConfig\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Plugin\Youbuwei\SystemConfig\Service\ConfigModuleRegistry;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * 配置同步命令.
 *
 * @example
 * php bin/hyperf.php config:sync
 * php bin/hyperf.php config:sync --verbose
 */
#[Command]
class ConfigSyncCommand extends HyperfCommand
{
    protected ConfigModuleRegistry $registry;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('config:sync');
        $this->registry = $container->get(ConfigModuleRegistry::class);
    }

    public function configure()
    {
        $this->setDescription('同步配置模块定义到数据库')
            ->addOption('dry-run', 'd', null, '只扫描不实际写入数据库');
    }

    public function handle()
    {
        $dryRun = $this->input->getOption('dry-run');

        $this->output->title('配置模块同步工具');
        $this->output->newLine();

        $files = $this->registry->getModuleFiles();

        if (empty($files)) {
            $this->output->warning('未找到任何配置模块定义文件');
            $this->output->note('请在 config/modules/ 目录下创建配置定义文件');
            return;
        }

        $this->output->section('发现配置文件');
        $fileTable = new Table($this->output);
        $fileTable->setHeaders(['序号', '文件名', '路径']);
        foreach ($files as $index => $file) {
            $fileName = basename($file);
            $relativePath = str_replace(BASE_PATH . '/', '', $file);
            $fileTable->addRow([$index + 1, $fileName, $relativePath]);
        }
        $fileTable->render();
        $this->output->newLine();

        if ($dryRun) {
            $this->output->note('Dry-run 模式：只扫描不实际写入数据库');
            $this->output->success('扫描完成');
            return;
        }

        $this->output->section('开始同步');
        $this->output->writeln('<info>正在扫描并注册配置模块...</info>');

        try {
            $stats = $this->registry->scanAndRegister();

            $this->output->newLine();
            $this->output->section('同步结果');
            $statsTable = new Table($this->output);
            $statsTable->setHeaders(['类型', '数量']);
            $statsTable->addRow(['配置模块', $stats['modules']]);
            $statsTable->addRow(['配置分组', $stats['groups']]);
            $statsTable->addRow(['配置项', $stats['items']]);
            $statsTable->render();

            $this->output->newLine();
            $this->output->success('配置模块同步成功！');
        } catch (\Throwable $e) {
            $this->output->error('配置模块同步失败');
            $this->output->error($e->getMessage());
        }
    }
}
