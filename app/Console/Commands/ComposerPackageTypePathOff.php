<?php

namespace Totocsa01\ComposerPackageDevelopment\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class ComposerPackageTypePathOff extends Command
{
    protected $signature = 'dev:composer-package-type-path-off'
        . ' {package : vendor/repository}';

    protected $description = 'Closing development of a compose package';

    protected string $vendor;
    protected string $packageName;
    protected string $tag = '';

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

        $basePath = base_path();
        $packageArray = explode('/', $this->argument('package'));

        $this->vendor = $packageArray[0] ?? '';

        if (strpos(':', ($packageArray[1] ?? ''))) {
            [$this->packageName, $this->tag] = explode(':', $packageArray[1]);
            $this->tag = ":{$this->tag}";
        } else {
            $this->packageName = $packageArray[1] ?? '';
        }

        $output = [];
        $result_code = Command::SUCCESS;

        if ($result_code === Command::SUCCESS) {
            $cmd = implode(' ', [
                "composer -d $basePath config --unset",
                escapeshellarg("repositories.{$this->vendor}-{$this->packageName}"),
            ]);

            $this->commandExec($cmd, $output, $result_code);
        }

        if ($result_code === Command::SUCCESS) {
            $cmd = implode(' ', [
                "composer -d $basePath remove",
                escapeshellarg("{$this->vendor}/{$this->packageName}"),
            ]);

            $this->commandExec($cmd, $output, $result_code);
        }

        if ($result_code === Command::SUCCESS) {
            $cmd = implode(' ', [
                "composer -d $basePath require",
                escapeshellarg("{$this->vendor}/{$this->packageName}{$this->tag}"),
            ]);

            $this->commandExec($cmd, $output, $result_code);
        }

        if ($result_code === Command::SUCCESS) {
            $this->components->info("The {$this->vendor}/{$this->packageName}{$this->tag} package has been installed successfully.");
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
            ],
            [
                'package' => [
                    'required',
                    'string',
                    //'regex:/^[a-zA-Z0-9](?:[a-zA-Z0-9._-]*[a-zA-Z0-9])?\/[a-zA-Z0-9](?:[a-zA-Z0-9._-]*[a-zA-Z0-9])?$/',
                ],
            ]
        );
    }
}
