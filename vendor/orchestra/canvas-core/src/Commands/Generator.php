<?php

namespace Orchestra\Canvas\Core\Commands;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Orchestra\Canvas\Core\CodeGenerator;
use Orchestra\Canvas\Core\Contracts\GeneratesCodeListener;
use Orchestra\Canvas\Core\GeneratesCode;
use Orchestra\Canvas\Core\Presets\Preset;
use Orchestra\Canvas\Core\TestGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * @property string|null  $name
 * @property string|null  $description
 */
abstract class Generator extends Command implements GeneratesCodeListener, PromptsForMissingInput
{
    use CodeGenerator, TestGenerator;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The type of class being generated.
     */
    protected string $type;

    /**
     * The type of file being generated.
     */
    protected string $fileType = 'class';

    /**
     * Generator processor.
     *
     * @var class-string<\Orchestra\Canvas\Core\GeneratesCode>
     */
    protected string $processor = GeneratesCode::class;

    /**
     * Reserved names that cannot be used for generation.
     *
     * @var array<int, string>
     */
    protected array $reservedNames = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'enum',
        'eval',
        'exit',
        'extends',
        'false',
        'final',
        'finally',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'match',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'readonly',
        'require',
        'require_once',
        'return',
        'self',
        'static',
        'switch',
        'throw',
        'trait',
        'true',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
        '__CLASS__',
        '__DIR__',
        '__FILE__',
        '__FUNCTION__',
        '__LINE__',
        '__METHOD__',
        '__NAMESPACE__',
        '__TRAIT__',
    ];

    /**
     * Construct a new generator command.
     */
    public function __construct(Preset $preset)
    {
        $this->files = $preset->filesystem();

        parent::__construct($preset);
    }

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this->setName($this->getName())
            ->setDescription($this->getDescription())
            ->addArgument('name', InputArgument::REQUIRED, "The name of the {$this->fileType}");

        if (\in_array(CreatesMatchingTest::class, class_uses_recursive($this))) {
            /** @phpstan-ignore-next-line */
            $this->addTestOptions();
        }
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
        // First we need to ensure that the given name is not a reserved word within the PHP
        // language and that the class name will actually be valid. If it is not valid we
        // can error now and prevent from polluting the filesystem using invalid files.
        if ($this->isReservedName($name = $this->generatorName())) {
            $this->components->error('The name "'.$name.'" is reserved by PHP.');

            return Command::FAILURE;
        }

        $force = $this->hasOption('force') && $this->option('force') === true;

        return $this->generateCode($force);
    }

    /**
     * Handle generating code.
     */
    public function generatingCode(string $stub, string $className): string
    {
        return $stub;
    }

    /**
     * Run after code successfully generated.
     */
    public function afterCodeHasBeenGenerated(string $className, string $path): void
    {
        if (\in_array(CreatesMatchingTest::class, class_uses_recursive($this))) {
            $this->handleTestCreationUsingCanvas($path);
        }
    }

    /**
     * Get the published stub file for the generator.
     */
    public function getPublishedStubFileName(): ?string
    {
        return null;
    }

    /**
     * Get the desired class name from the input.
     */
    public function generatorName(): string
    {
        return transform($this->argument('name'), function ($name) {
            /** @var string $name */
            return trim($name);
        });
    }

    /**
     * Checks whether the given name is reserved.
     */
    protected function isReservedName(string $name): bool
    {
        $name = strtolower($name);

        return \in_array($name, $this->reservedNames);
    }

    /**
     * Get a list of possible model names.
     *
     * @return array<int, string>
     */
    protected function possibleModels()
    {
        $sourcePath = $this->preset->sourcePath();

        $modelPath = is_dir("{$sourcePath}/Models") ? "{$sourcePath}/Models" : $sourcePath;

        return collect((new Finder)->files()->depth(0)->in($modelPath))
            ->map(fn ($file) => $file->getBasename('.php'))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Get a list of possible event names.
     *
     * @return array<int, string>
     */
    protected function possibleEvents()
    {
        $eventPath = sprintf('%s/Events', $this->preset->sourcePath());

        if (! is_dir($eventPath)) {
            return [];
        }

        return collect((new Finder)->files()->depth(0)->in($eventPath))
            ->map(fn ($file) => $file->getBasename('.php'))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'name' => [
                'What should the '.strtolower($this->type).' be named?',
                match ($this->type) {
                    'Cast' => 'E.g. Json',
                    'Channel' => 'E.g. OrderChannel',
                    'Console command' => 'E.g. SendEmails',
                    'Component' => 'E.g. Alert',
                    'Controller' => 'E.g. UserController',
                    'Event' => 'E.g. PodcastProcessed',
                    'Exception' => 'E.g. InvalidOrderException',
                    'Factory' => 'E.g. PostFactory',
                    'Job' => 'E.g. ProcessPodcast',
                    'Listener' => 'E.g. SendPodcastNotification',
                    'Mailable' => 'E.g. OrderShipped',
                    'Middleware' => 'E.g. EnsureTokenIsValid',
                    'Model' => 'E.g. Flight',
                    'Notification' => 'E.g. InvoicePaid',
                    'Observer' => 'E.g. UserObserver',
                    'Policy' => 'E.g. PostPolicy',
                    'Provider' => 'E.g. ElasticServiceProvider',
                    'Request' => 'E.g. StorePodcastRequest',
                    'Resource' => 'E.g. UserResource',
                    'Rule' => 'E.g. Uppercase',
                    'Scope' => 'E.g. TrendingScope',
                    'Seeder' => 'E.g. UserSeeder',
                    'Test' => 'E.g. UserTest',
                    default => '',
                },
            ],
        ];
    }
}
