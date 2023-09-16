<?php

namespace Spatie\LaravelRay\Payloads;

use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Ray\ArgumentConverter;
use Spatie\Ray\Payloads\Payload;

class ViewPayload extends Payload
{
    /** @var \Illuminate\View\View */
    protected $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function getType(): string
    {
        return 'view';
    }

    public function getContent(): array
    {
        return [
            'view_path' => $this->view->getPath(),
            'view_path_relative_to_project_root' => Str::after($this->pathRelativeToProjectRoot($this->view), '/'),
            'data' => ArgumentConverter::convertToPrimitive($this->getData($this->view)),
        ];
    }

    protected function pathRelativeToProjectRoot(View $view): string
    {
        $path = $view->getPath();

        if (Str::startsWith($path, base_path())) {
            $path = substr($path, strlen(base_path()));
        }

        return $path;
    }

    protected function getData(View $view): array
    {
        return collect($view->getData())
            ->filter(function ($value, $key) {
                return ! in_array($key, ['app', '__env', 'obLevel', 'errors']);
            })
            ->toArray();
    }
}
