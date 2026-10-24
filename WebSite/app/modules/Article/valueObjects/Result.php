<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\enums\ArticleAccess;

/**
 * Either a success value of type T, or the reason why access was denied.
 *
 * Covariant, so that a failure (Result<never>) is accepted wherever a Result<U> is expected.
 *
 * @template-covariant T
 */
final readonly class Result
{
    /**
     * @param T|null $value only meaningful when $failure is null
     */
    private function __construct(
        private ?ArticleAccess $failure,
        private mixed $value,
    ) {
    }

    /**
     * @template V
     * @param V $value
     * @return Result<V>
     */
    public static function ok(mixed $value): self
    {
        return new self(null, $value);
    }

    /**
     * @return Result<never>
     */
    public static function fail(ArticleAccess $reason): self
    {
        /** @var Result<never> $failure */
        $failure = new self($reason, null);

        return $failure;
    }

    /**
     * Monadic bind: runs $next only if the previous step succeeded.
     *
     * @template U
     * @param callable(T): Result<U> $next
     * @return Result<U>
     */
    public function andThen(callable $next): self
    {
        if ($this->failure !== null) {
            return self::fail($this->failure);
        }

        /** @var T $value */
        $value = $this->value;

        return $next($value);
    }

    /**
     * Functor map: transforms the success value, cannot fail.
     *
     * @template U
     * @param callable(T): U $transform
     * @return Result<U>
     */
    public function map(callable $transform): self
    {
        if ($this->failure !== null) {
            return self::fail($this->failure);
        }

        /** @var T $value */
        $value = $this->value;

        return self::ok($transform($value));
    }

    /**
     * Single exit point: both branches must produce the same type.
     *
     * @template R
     * @param callable(T): R $onOk
     * @param callable(ArticleAccess): R $onFail
     * @return R
     */
    public function match(callable $onOk, callable $onFail): mixed
    {
        if ($this->failure !== null) {
            return $onFail($this->failure);
        }

        /** @var T $value */
        $value = $this->value;

        return $onOk($value);
    }
}
