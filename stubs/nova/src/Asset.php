<?php

declare(strict_types=1);

namespace Laravel\Nova;

use DateTime;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\File;
use Stringable;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method static static make(self|Stringable|string $name, string|null $path, bool|null $remote = null)
 */
abstract class Asset implements Responsable
{
    use Makeable;

    /**
     * @var string|Stringable
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $path = null;

    /**
     * @var bool
     */
    protected $remote = false;

    public function __construct(self|Stringable|string $name, ?string $path, ?bool $remote = null)
    {
        if ($name instanceof self) {
            [$this->name, $this->path, $this->remote] = [$name->name(), $name->path(), $name->isRemote()];

            return;
        }

        $this->name = $name;
        $this->path = $path;
        $this->remote = $remote ?? str_starts_with((string)$path, 'http://') || str_starts_with((string)$path, 'https://') || str_starts_with((string)$path, '://');
    }

    public static function remote(string $path): static
    {
        return new static(md5($path), $path, true);
    }

    public function name(): Stringable|string
    {
        return $this->name;
    }

    public function path(): ?string
    {
        return $this->path;
    }

    public function isRemote(): bool
    {
        return $this->remote;
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return Response
     */
    public function toResponse($request)
    {
        abort_if($this->remote || $this->path === null || !File::isFile($this->path), 404);

        $response = response(File::get($this->path), 200, $this->toResponseHeaders());
        $modified = DateTime::createFromFormat('U', (string)File::lastModified($this->path));

        return $modified === false ? $response : $response->setLastModified($modified);
    }

    abstract public function url(): string;

    /**
     * @return array<string, string>
     */
    abstract public function toResponseHeaders(): array;
}
