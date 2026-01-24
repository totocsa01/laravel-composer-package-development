<?php

namespace Totocsa01\ComposerPackageDevelopment\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class ComposerPackageTypePathOn extends Command
{
    protected $signature = 'dev:composer-package-type-path-on'
        . ' {package : vendor/repository}'
        . ' {--branch=main : Git branch to use}'
        . ' {--git-clone : Run git clone}';

    protected $description = 'Development a package. type: path';

    protected string $branch;
    protected string $vendor;
    protected string $repository;
    protected string $repositoryDir;

    public function handle(): int
    {
        $validator = $this->validatorInputs();
        if ($validator->fails()) {
            $this->error('Error');

            $errors = $validator->errors()->toArray();
            foreach ($errors as $fi => $errs) {
                $this->line("[$fi]", 'fg=blue');
                foreach ($errs as $msg) {
                    $this->line($msg);
                }
            }

            return Command::INVALID;
        }

        $ds = DIRECTORY_SEPARATOR;
        $packageArray = explode('/', $this->argument('package'));

        $this->branch = $this->option('branch');
        $this->vendor = $packageArray[0] ?? '';
        $this->repository = $packageArray[1] ?? '';
        $this->repositoryDir = base_path("packages{$ds}{$this->vendor}{$ds}{$this->repository}");

        if (!is_dir($this->repositoryDir)) {
            if (!@mkdir($this->repositoryDir, 0775, true)) {
                $error = error_get_last();
                $this->components->error($error['message'] ?? "Cannot create {$this->repositoryDir} directory.");
                return Command::FAILURE;
            }
        }

        $output = [];
        $result_code = Command::SUCCESS;

        if ($this->option('git-clone')) {
            $cmd = implode(' ', [
                'git clone -b',
                escapeshellarg($this->branch),
                escapeshellarg("git@github.com:{$this->vendor}/{$this->repository}.git"),
                escapeshellarg($this->repositoryDir),
            ]);

            $this->commandExec($cmd, $output, $result_code);
        }

        if ($result_code === Command::SUCCESS) {
            $basePath = base_path();

            $repo = json_encode([
                'name' => "{$this->vendor}-{$this->repository}",
                'type' => 'path',
                'url' => "packages/{$this->vendor}/{$this->repository}",
                'options' => [
                    'symlink' => true,
                ],
            ], JSON_UNESCAPED_SLASHES);

            $cmd = implode(' ', [
                "composer -d $basePath config",
                escapeshellarg("repositories.{$this->vendor}-{$this->repository}"),
                escapeshellarg($repo),
            ]);

            $this->commandExec($cmd, $output, $result_code);
        }

        if ($result_code === Command::SUCCESS) {
            $cmd = implode(' ', [
                "composer -d $basePath require",
                escapeshellarg("{$this->vendor}/{$this->repository}:dev-{$this->branch}"),
                '--no-interaction --prefer-source',
            ]);

            $this->commandExec($cmd, $output, $result_code);
        }

        if ($result_code === Command::SUCCESS) {
            $this->components->info("Package [{$this->repositoryDir}] created successfully.");
        } else {
            $this->line("<error>Error</error> Last exit code: $result_code");
        }

        return $result_code;
    }

    protected function commandExec(string $command, &$output, &$result_code): string|false
    {
        $this->line("› $command", 'fg=green');
        return exec($command, $output, $result_code);
    }

    protected function validatorInputs(): \Illuminate\Validation\Validator
    {
        return Validator::make(
            [
                'package' => $this->argument('package'),
                'branch'  => $this->option('branch'),
            ],
            [
                'package' => [
                    'required',
                    'string',
                    'regex:/^[a-zA-Z0-9](?:[a-zA-Z0-9._-]*[a-zA-Z0-9])?\/[a-zA-Z0-9](?:[a-zA-Z0-9._-]*[a-zA-Z0-9])?$/',
                ],
                'branch' => [
                    'required',
                    'string',
                    'max:100',
                    'regex:/^[a-zA-Z0-9.\-_]{1,100}$/',
                ],
            ]
        );
    }
}
