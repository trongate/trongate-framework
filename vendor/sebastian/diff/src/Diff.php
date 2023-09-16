<?php declare(strict_types=1);
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Diff;

final class Diff
{
    private string $from;
    private string $to;

    /**
     * @psalm-var list<Chunk>
     */
    private array $chunks;

    /**
     * @psalm-param list<Chunk> $chunks
     */
    public function __construct(string $from, string $to, array $chunks = [])
    {
        $this->from   = $from;
        $this->to     = $to;
        $this->chunks = $chunks;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * @psalm-return list<Chunk>
     */
    public function getChunks(): array
    {
        return $this->chunks;
    }

    /**
     * @psalm-param list<Chunk> $chunks
     */
    public function setChunks(array $chunks): void
    {
        $this->chunks = $chunks;
    }
}
