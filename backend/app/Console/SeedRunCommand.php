<?php

declare(strict_types=1);

namespace App\Console;

use Database\Seeds\SeederInterface;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 执行 {@see database/seeds/} 下以 `M*.php` 命名的种子类，按**文件名**排序（与 migrations 命名习惯一致）。
 */
final class SeedRunCommand extends Command
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('seed:run');
        $this->setDescription('Run database seeders (initial/demo data)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $backendRoot = dirname(__DIR__, 2);
        $pattern = $backendRoot . '/database/seeds/M*.php';
        $files = glob($pattern) ?: [];
        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $base = basename($file, '.php');
            $class = 'Database\\Seeds\\' . $base;
            if (!class_exists($class)) {
                $output->writeln("<comment>Skip {$base}: class not found</comment>");
                continue;
            }

            $ref = new ReflectionClass($class);
            if ($ref->isAbstract() || !$ref->implementsInterface(SeederInterface::class)) {
                continue;
            }

            $output->writeln("→ <info>{$base}</info>");
            $ref->newInstance()->run($this->db);
        }

        $output->writeln('<info>Seeders finished.</info>');

        return Command::SUCCESS;
    }
}
