<?php

namespace Orchestra\Canvas\Commands;

use Orchestra\Canvas\Concerns\ResolvesPresetStubs;
use Orchestra\Canvas\Core\Commands\Command;
use Orchestra\Canvas\Core\Presets\Package;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/StubPublishCommand.php
 */
#[AsCommand(name: 'stub:publish', description: 'Publish all stubs that are available for customization')]
class StubPublish extends Command
{
    use ResolvesPresetStubs;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();
    }

    /**
     * Execute the command.
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $this->preset->filesystem();
        $stubsPath = sprintf('%s/stubs', $this->preset->basePath());

        if (! $files->isDirectory($stubsPath)) {
            $files->makeDirectory($stubsPath);
        }

        $files = [
            'cast.stub' => $stubsPath.'/cast.stub',
            'event.stub' => $stubsPath.'/event.stub',
            'job.queued.stub' => $stubsPath.'/job.queued.stub',
            'job.stub' => $stubsPath.'/job.stub',
            'markdown-notification.stub' => $stubsPath.'/markdown-notification.stub',
            'model.pivot.stub' => $stubsPath.'/model.pivot.stub',
            'model.stub' => $stubsPath.'/model.stub',
            'notification.stub' => $stubsPath.'/notification.stub',
            'observer.plain.stub' => $stubsPath.'/observer.plain.stub',
            'observer.stub' => $stubsPath.'/observer.stub',
            'request.stub' => $stubsPath.'/request.stub',
            'resource-collection.stub' => $stubsPath.'/resource-collection.stub',
            'resource.stub' => $stubsPath.'/resource.stub',
            'test.stub' => $stubsPath.'/test.stub',
            'test.unit.stub' => $stubsPath.'/test.unit.stub',
            'view-component.stub' => $stubsPath.'/view-component.stub',
            'factory.stub' => $stubsPath.'/factory.stub',
            'seeder.stub' => $stubsPath.'/seeder.stub',
            'migration.create.stub' => $stubsPath.'/migration.create.stub',
            'migration.stub' => $stubsPath.'/migration.stub',
            'migration.update.stub' => $stubsPath.'/migration.update.stub',
            'console.stub' => $stubsPath.'/console.stub',
            'policy.plain.stub' => $stubsPath.'/policy.plain.stub',
            'policy.stub' => $stubsPath.'/policy.stub',
            'rule.stub' => $stubsPath.'/rule.stub',
            'controller.api.stub' => $stubsPath.'/controller.api.stub',
            'controller.invokable.stub' => $stubsPath.'/controller.invokable.stub',
            'controller.model.api.stub' => $stubsPath.'/controller.model.api.stub',
            'controller.model.stub' => $stubsPath.'/controller.model.stub',
            'controller.nested.api.stub' => $stubsPath.'/controller.nested.api.stub',
            'controller.nested.stub' => $stubsPath.'/controller.nested.stub',
            'controller.plain.stub' => $stubsPath.'/controller.plain.stub',
            'controller.stub' => $stubsPath.'/controller.stub',
            'middleware.stub' => $stubsPath.'/middleware.stub',
        ];

        $force = $this->option('force');

        foreach ($files as $from => $to) {
            $file = $this->getStubFileFromPresetStorage($this->preset, $from);

            if ((! file_exists($to) || $force) && \is_string($file)) {
                file_put_contents($to, file_get_contents($file));
            }
        }

        $this->components->info('Stubs published successfully.');

        return 0;
    }

    /**
     * Get feature test stub file.
     *
     * @return string|bool
     */
    protected function getFeatureTestStubFile()
    {
        if ($this->preset instanceof Package) {
            return realpath(__DIR__.'/../../storage/testing/test.package.stub');
        }

        return realpath(__DIR__.'/../../storage/testing/test.stub');
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array>
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite any existing files if already exists'],
        ];
    }
}
