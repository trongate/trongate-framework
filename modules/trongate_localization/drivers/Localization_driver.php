<?php

interface Localization_driver
{
    public function languages(): array;
    public function translations(): array;

    public function read(): Localization_driver;

    public function write(string $key, array $data);
}