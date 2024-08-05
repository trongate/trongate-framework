<?php

interface Localization_driver
{
    public function languages(): iterable;
    public function translations(): iterable;

    public function read(): Localization_driver;

    public function write(string $key, array $data);

    public function search(string $query): iterable;
}